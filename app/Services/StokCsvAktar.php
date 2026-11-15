<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\StokKalemi;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * İTS portalından müşterinin GLN'i ile indirilen stok dosyasını (GTIN;SN;BN;EXP_DATE, .csv ya da .zip) müşterinin stoğuna alır.
 * Dosya İTS'in kendi stok kaydıdır; bu yüzden ürünler yeniden sorgulanmaz. Yalnızca yerel kayıt yapılır, İTS'e bildirim gitmez.
 */
class StokCsvAktar
{
    /** @return array{okunan:int,eklenen:int,guncellenen:int,atlanan_durum:int,gecersiz:int,ornek_hatalar:array<int,string>,dosya_gln:?string} */
    public function ice(Tenant $tenant, UploadedFile $dosya): array
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');
        $ad = $dosya->getClientOriginalName();
        $dosyaGln = preg_match('/(?<!\d)(\d{13})(?!\d)/', $ad, $m) ? $m[1] : null;
        if ($dosyaGln && $dosyaGln !== $tenant->gln) {
            throw new \InvalidArgumentException('Dosya adındaki GLN ('.$dosyaGln.') bu müşterinin GLN\'i ('.$tenant->gln.') ile uyuşmuyor. Yanlış müşteriye yükleme engellendi.');
        }

        $yol = $dosya->getRealPath();
        $gecici = null;
        if (strtolower($dosya->getClientOriginalExtension()) === 'zip') {
            $zip = new \ZipArchive;
            if ($zip->open($yol) !== true) { throw new \InvalidArgumentException('ZIP dosyası açılamadı.'); }
            $csv = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (preg_match('/\.csv$/i', $zip->getNameIndex($i))) { $csv = $zip->getNameIndex($i); break; }
            }
            if (! $csv) { throw new \InvalidArgumentException('ZIP içinde .csv dosyası yok.'); }
            $gecici = tempnam(sys_get_temp_dir(), 'stok');
            file_put_contents($gecici, $zip->getFromName($csv));
            $zip->close();
            $yol = $gecici;
        }

        try {
            return $this->isle($tenant, $yol) + ['dosya_gln' => $dosyaGln];
        } finally {
            if ($gecici) { @unlink($gecici); }
        }
    }

    private function isle(Tenant $tenant, string $yol): array
    {
        $f = fopen($yol, 'r');
        $baslik = fgets($f);
        if ($baslik === false) { throw new \InvalidArgumentException('Dosya boş.'); }
        $baslik = preg_replace('/^\xEF\xBB\xBF/', '', $baslik);
        $ayrac = collect([';', ',', "\t"])->sortByDesc(fn ($a) => substr_count($baslik, $a))->first();
        $kolonlar = array_map(fn ($k) => strtoupper(trim($k, " \t\r\n\"")), explode($ayrac, $baslik));
        $g = array_search('GTIN', $kolonlar); $s = array_search('SN', $kolonlar); $b = array_search('BN', $kolonlar); $x = array_search('EXP_DATE', $kolonlar);
        if ($g === false || $s === false) { throw new \InvalidArgumentException('Başlık satırında GTIN ve SN kolonları bulunamadı (beklenen: GTIN;SN;BN;EXP_DATE).'); }

        // Satış/devir/sarf gibi başka durumdaki yerel kayıtlar dosya ile ezilmez.
        $korunan = StokKalemi::where('tenant_id', $tenant->id)->where('durum', '!=', 'stokta')->get(['gtin', 'sn'])->mapWithKeys(fn ($k) => [$k->gtin.'/'.$k->sn => true])->all();
        $var = StokKalemi::where('tenant_id', $tenant->id)->where('durum', 'stokta')->count();

        $r = ['okunan' => 0, 'eklenen' => 0, 'guncellenen' => 0, 'atlanan_durum' => 0, 'gecersiz' => 0, 'ornek_hatalar' => []];
        $parca = [];
        $goruldu = [];
        $simdi = now();
        $yaz = function () use (&$parca, $tenant) {
            if (! $parca) { return; }
            DB::table('stok_kalemleri')->upsert(array_values($parca), ['tenant_id', 'gtin', 'sn'], ['bn', 'skt', 'durum', 'son_islem_at', 'updated_at']);
            $parca = [];
        };

        while (($satir = fgets($f)) !== false) {
            $satir = trim($satir, "\r\n");
            if ($satir === '') { continue; }
            $r['okunan']++;
            $h = str_getcsv($satir, $ayrac);
            $gtin = preg_replace('/\D/', '', (string) ($h[$g] ?? ''));
            $sn = trim((string) ($h[$s] ?? ''));
            $bn = $b !== false ? trim((string) ($h[$b] ?? '')) : '';
            $skt = $x !== false ? trim((string) ($h[$x] ?? '')) : '';
            $gtin = strlen($gtin) === 13 ? '0'.$gtin : $gtin;
            $hata = null;
            if (strlen($gtin) !== 14) { $hata = 'GTIN 14 hane değil'; }
            elseif ($sn === '' || strlen($sn) > 20) { $hata = 'Seri no boş ya da 20 karakterden uzun'; }
            elseif (strlen($bn) > 20) { $hata = 'Parti no 20 karakterden uzun'; }
            elseif ($skt !== '' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $skt)) { $hata = 'SKT YYYY-AA-GG biçiminde değil'; }
            if ($hata) {
                $r['gecersiz']++;
                if (count($r['ornek_hatalar']) < 10) { $r['ornek_hatalar'][] = 'Satır '.($r['okunan'] + 1).': '.$hata; }
                continue;
            }
            $anahtar = $gtin.'/'.$sn;
            if (isset($korunan[$anahtar])) { $r['atlanan_durum']++; continue; }
            if (isset($goruldu[$anahtar])) { continue; } // dosyada tekrarlanan satır
            $goruldu[$anahtar] = true;
            $parca[$anahtar] = ['tenant_id' => $tenant->id, 'gtin' => $gtin, 'sn' => $sn, 'bn' => $bn ?: null, 'skt' => $skt ?: null, 'durum' => 'stokta', 'son_islem_at' => $simdi, 'created_at' => $simdi, 'updated_at' => $simdi];
            if (count($parca) >= 1000) { $yaz(); }
        }
        $yaz();
        fclose($f);

        $son = StokKalemi::where('tenant_id', $tenant->id)->where('durum', 'stokta')->count();
        $r['eklenen'] = max(0, $son - $var);
        $r['guncellenen'] = count($goruldu) - $r['eklenen'];
        StokKalemi::redOdasinaAl($tenant->id);
        AuditLog::kaydet('stok.csv-ice-aktar', ['tenant' => $tenant->id, 'okunan' => $r['okunan'], 'eklenen' => $r['eklenen'], 'gecersiz' => $r['gecersiz']]);

        return $r;
    }
}
