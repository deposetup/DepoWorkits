<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * PTS verisi olmayan ama İTS'te "arada" kalan ürünler için: CSV (GTIN;SN;BN;SKT) okunur, her ürün için karekod üretilir
 * ve onaylanırsa mevcut Mal Kabul akışıyla İTS'e Mal Alım bildirimi yapılır.
 */
class ManuelEkleController extends Controller
{
    private const LIMIT = 1000;

    public function form()
    {
        return view('manuel.index', ['onizleme' => null]);
    }

    public function onizle(Request $request)
    {
        abort_unless($request->user()->tenant_id, 403);
        $request->validate(['dosya' => 'nullable|file|max:10240|mimes:csv,txt', 'metin' => 'nullable|string|max:300000']);
        $icerik = $request->hasFile('dosya') ? (string) file_get_contents($request->file('dosya')->getRealPath()) : (string) $request->input('metin', '');
        if (trim($icerik) === '') {
            return back()->withErrors(['dosya' => 'CSV dosyası seçin ya da satırları yapıştırın.']);
        }

        return view('manuel.index', ['onizleme' => $this->coz($icerik)]);
    }

    /** @return array{satirlar:array,gecersiz:array,fazla:int,gecmis:int} */
    private function coz(string $icerik): array
    {
        $icerik = preg_replace('/^\xEF\xBB\xBF/', '', $icerik);
        if (! mb_check_encoding($icerik, 'UTF-8')) {
            $icerik = mb_convert_encoding($icerik, 'UTF-8', 'Windows-1254');
        }
        $satirlar = preg_split('/\r\n|\r|\n/', trim($icerik));
        $ayrac = collect([';', ',', "\t"])->sortByDesc(fn ($a) => substr_count($satirlar[0], $a))->first();
        $ilk = array_map(fn ($k) => strtoupper(trim($k, " \t\"")), explode($ayrac, $satirlar[0]));
        $baslikVar = in_array('GTIN', $ilk, true) || in_array('SN', $ilk, true);
        $g = 0; $s = 1; $b = 2; $x = 3;
        if ($baslikVar) {
            $g = array_search('GTIN', $ilk); $s = array_search('SN', $ilk); $b = array_search('BN', $ilk);
            $x = array_search('SKT', $ilk); $x = $x === false ? array_search('EXP_DATE', $ilk) : $x; $x = $x === false ? array_search('XD', $ilk) : $x;
            if ($g === false || $s === false) {
                return ['satirlar' => [], 'gecersiz' => ['Başlıkta GTIN ve SN kolonları bulunamadı (beklenen: GTIN;SN;BN;SKT).'], 'fazla' => 0, 'gecmis' => 0];
            }
            array_shift($satirlar);
        }

        $out = ['satirlar' => [], 'gecersiz' => [], 'fazla' => 0, 'gecmis' => 0];
        $goruldu = [];
        foreach ($satirlar as $n => $satir) {
            $satir = trim($satir);
            if ($satir === '') { continue; }
            $no = $n + ($baslikVar ? 2 : 1);
            $h = str_getcsv($satir, $ayrac);
            $gtin = preg_replace('/\D/', '', (string) ($h[$g] ?? ''));
            $gtin = strlen($gtin) === 13 ? '0'.$gtin : $gtin;
            $sn = trim((string) ($h[$s] ?? ''));
            $bn = $b === false ? '' : trim((string) ($h[$b] ?? ''));
            $skt = $x === false ? '' : trim((string) ($h[$x] ?? ''));
            if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $skt, $m)) { $skt = $m[3].'-'.$m[2].'-'.$m[1]; }
            $hata = null;
            if (strlen($gtin) !== 14) { $hata = 'GTIN 14 hane değil'; }
            elseif ($sn === '' || strlen($sn) > 20 || preg_match('/[()\s]/', $sn)) { $hata = 'Seri no boş, 20 karakterden uzun ya da geçersiz karakter içeriyor'; }
            elseif ($bn === '' || strlen($bn) > 20 || preg_match('/[()\s]/', $bn)) { $hata = 'Parti no boş, 20 karakterden uzun ya da geçersiz karakter içeriyor'; }
            elseif (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $skt) || ! checkdate((int) substr($skt, 5, 2), (int) substr($skt, 8, 2), (int) substr($skt, 0, 4))) { $hata = 'SKT YYYY-AA-GG biçiminde değil'; }
            if ($hata) {
                if (count($out['gecersiz']) < 20) { $out['gecersiz'][] = 'Satır '.$no.': '.$hata; }
                continue;
            }
            if (isset($goruldu[$gtin.'/'.$sn])) { continue; }
            $goruldu[$gtin.'/'.$sn] = true;
            if (count($out['satirlar']) >= self::LIMIT) { $out['fazla']++; continue; }
            $gecmis = $skt < now()->toDateString();
            $out['gecmis'] += $gecmis ? 1 : 0;
            $out['satirlar'][] = ['gtin' => $gtin, 'sn' => $sn, 'bn' => $bn, 'skt' => $skt, 'gecmis' => $gecmis,
                'kod' => '(01)'.$gtin.'(17)'.date('ymd', strtotime($skt)).'(10)'.$bn.'(21)'.$sn];
        }

        return $out;
    }
}
