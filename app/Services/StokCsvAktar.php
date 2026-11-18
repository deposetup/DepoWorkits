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
    public const ADIM = 5000;

    /** @return array{okunan:int,eklenen:int,guncellenen:int,atlanan_durum:int,gecersiz:int,ornek_hatalar:array<int,string>,dosya_gln:?string} */
    public function ice(Tenant $tenant, UploadedFile $dosya): array
    {
        @set_time_limit(600);
        $durum = $this->hazirla($tenant, $dosya);
        try {
            while (! $durum['bitti']) {
                $durum = $this->adim($tenant, $durum, 100000);
            }

            return $this->sonuc($tenant, $durum);
        } finally {
            $this->temizle($durum);
        }
    }

    /**
     * Dosyayı (csv ya da zip içindeki csv) geçici bir CSV'ye çıkarır, başlığı çözer, satırları sayar.
     * Dönen dizi adım adım işlemek için kalıcı durumdur (cache'e konabilir).
     */
    public function hazirla(Tenant $tenant, UploadedFile $dosya): array
    {
        @set_time_limit(600);
        $ad = $dosya->getClientOriginalName();
        $dosyaGln = preg_match('/(?<!\d)(\d{13})(?!\d)/', $ad, $m) ? $m[1] : null;
        if ($dosyaGln && $dosyaGln !== $tenant->gln) {
            throw new \InvalidArgumentException('Dosya adındaki GLN ('.$dosyaGln.') bu müşterinin GLN\'i ('.$tenant->gln.') ile uyuşmuyor. Yanlış müşteriye yükleme engellendi.');
        }

        $klasor = storage_path('app/stok-aktar');
        if (! is_dir($klasor)) { @mkdir($klasor, 0775, true); }
        $yol = tempnam($klasor, 'stok');
        if (strtolower($dosya->getClientOriginalExtension()) === 'zip') {
            $zip = new \ZipArchive;
            if ($zip->open($dosya->getRealPath()) !== true) { @unlink($yol); throw new \InvalidArgumentException('ZIP dosyası açılamadı.'); }
            $csv = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (preg_match('/\.csv$/i', $zip->getNameIndex($i))) { $csv = $zip->getNameIndex($i); break; }
            }
            if (! $csv) { $zip->close(); @unlink($yol); throw new \InvalidArgumentException('ZIP içinde .csv dosyası yok.'); }
            file_put_contents($yol, $zip->getFromName($csv));
            $zip->close();
        } else {
            copy($dosya->getRealPath(), $yol);
        }

        $f = fopen($yol, 'r');
        $baslik = fgets($f);
        if ($baslik === false) { fclose($f); @unlink($yol); throw new \InvalidArgumentException('Dosya boş.'); }
        $konum = ftell($f);
        $toplam = 0;
        while (($satir = fgets($f)) !== false) {
            if (trim($satir, "\r\n") !== '') { $toplam++; }
        }
        fclose($f);
        $baslik = preg_replace('/^\xEF\xBB\xBF/', '', $baslik);
        $ayrac = collect([';', ',', "\t"])->sortByDesc(fn ($a) => substr_count($baslik, $a))->first();
        $kolonlar = array_map(fn ($k) => strtoupper(trim($k, " \t\r\n\"")), explode($ayrac, $baslik));
        $g = array_search('GTIN', $kolonlar); $s = array_search('SN', $kolonlar); $b = array_search('BN', $kolonlar); $x = array_search('EXP_DATE', $kolonlar);
        if ($g === false || $s === false) { @unlink($yol); throw new \InvalidArgumentException('Başlık satırında GTIN ve SN kolonları bulunamadı (beklenen: GTIN;SN;BN;EXP_DATE).'); }

        return [
            'yol' => $yol, 'ayrac' => $ayrac, 'g' => $g, 's' => $s, 'b' => $b, 'x' => $x, 'konum' => $konum, 'toplam' => $toplam, 'bitti' => $toplam === 0,
            'basla' => now()->format('Y-m-d H:i:s'), 'var' => StokKalemi::where('tenant_id', $tenant->id)->where('durum', 'stokta')->count(), 'gecerli' => 0, 'dosya_gln' => $dosyaGln,
            'r' => ['okunan' => 0, 'eklenen' => 0, 'guncellenen' => 0, 'atlanan_durum' => 0, 'gecersiz' => 0, 'silinen' => 0, 'ornek_hatalar' => []],
        ];
    }

    /** En fazla $limit satır işler; durumu günceller ve döndürür. */
    public function adim(Tenant $tenant, array $d, int $limit = self::ADIM): array
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');
        $f = fopen($d['yol'], 'r');
        fseek($f, $d['konum']);
        $r = $d['r'];
        $satirlar = [];
        $n = 0;
        while ($n < $limit && ($satir = fgets($f)) !== false) {
            $satir = trim($satir, "\r\n");
            if ($satir === '') { continue; }
            $n++;
            $r['okunan']++;
            $h = str_getcsv($satir, $d['ayrac']);
            $gtin = preg_replace('/\D/', '', (string) ($h[$d['g']] ?? ''));
            $sn = trim((string) ($h[$d['s']] ?? ''));
            $bn = $d['b'] !== false ? trim((string) ($h[$d['b']] ?? '')) : '';
            $skt = $d['x'] !== false ? trim((string) ($h[$d['x']] ?? '')) : '';
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
            $satirlar[$gtin.'/'.$sn] = [$gtin, $sn, $bn, $skt];
        }
        $d['konum'] = ftell($f);
        $bitti = feof($f) || fgets($f) === false;
        fclose($f);

        if ($satirlar) {
            // Satış/devir/sarf gibi başka durumdaki yerel kayıtlar dosya ile ezilmez (yalnızca bu adımdaki ürünler için sorgulanır).
            $korunan = [];
            foreach (array_chunk(array_unique(array_column($satirlar, 0)), 500) as $gtinler) {
                foreach (StokKalemi::where('tenant_id', $tenant->id)->where('durum', '!=', 'stokta')->whereIn('gtin', $gtinler)->get(['gtin', 'sn']) as $k) {
                    $korunan[$k->gtin.'/'.$k->sn] = true;
                }
            }
            $simdi = isset($d['basla']) ? \Illuminate\Support\Carbon::parse($d['basla']) : now();
            $parca = [];
            foreach ($satirlar as $anahtar => [$gtin, $sn, $bn, $skt]) {
                if (isset($korunan[$anahtar])) { $r['atlanan_durum']++; continue; }
                $d['gecerli']++;
                $parca[] = ['tenant_id' => $tenant->id, 'gtin' => $gtin, 'sn' => $sn, 'bn' => $bn ?: null, 'skt' => $skt ?: null, 'durum' => 'stokta', 'son_islem_at' => $simdi, 'created_at' => $simdi, 'updated_at' => $simdi];
            }
            foreach (array_chunk($parca, 1000) as $p) {
                DB::table('stok_kalemleri')->upsert($p, ['tenant_id', 'gtin', 'sn'], ['bn', 'skt', 'durum', 'son_islem_at', 'updated_at']);
            }
        }
        $d['r'] = $r;
        $d['bitti'] = $bitti;

        return $d;
    }

    /** Tüm adımlar bitince özet sonucu üretir, kırmızı oda ve denetim kaydını işler. */
    public function sonuc(Tenant $tenant, array $d): array
    {
        $r = $d['r'];
        // Dosya İTS'in tam stok kaydıdır: bu aktarımda dosyada görülmeyen eski 'stokta' kayıtlar silinir (güncel stok = dosya).
        // Boş/hatalı dosya stoğu silmesin diye yalnızca geçerli satır varsa ve geçersiz satır yoksa çalışır.
        $r['silinen'] = 0;
        $r['silme_atlandi'] = false;
        if (isset($d['basla'])) {
            if (($d['gecerli'] ?? 0) > 0 && ($r['gecersiz'] ?? 0) === 0) {
                $r['silinen'] = StokKalemi::where('tenant_id', $tenant->id)->where('durum', 'stokta')
                    ->where(fn ($q) => $q->whereNull('son_islem_at')->orWhere('son_islem_at', '<', $d['basla']))->delete();
            } else {
                $r['silme_atlandi'] = true;
            }
        }
        $son = StokKalemi::where('tenant_id', $tenant->id)->where('durum', 'stokta')->count();
        $r['eklenen'] = max(0, $son - ($d['var'] - $r['silinen']));
        $r['guncellenen'] = max(0, $d['gecerli'] - $r['eklenen']);
        StokKalemi::redOdasinaAl($tenant->id);
        AuditLog::kaydet('stok.csv-ice-aktar', ['tenant' => $tenant->id, 'okunan' => $r['okunan'], 'eklenen' => $r['eklenen'], 'silinen' => $r['silinen'], 'gecersiz' => $r['gecersiz']]);

        return $r + ['dosya_gln' => $d['dosya_gln']];
    }

    public function temizle(array $d): void
    {
        if (! empty($d['yol'])) { @unlink($d['yol']); }
    }
}
