<?php

namespace App\Http\Controllers;

use App\Services\Its\ItsClient;
use Illuminate\Http\Request;
use App\Models\PtsPaketi;
use App\Services\StokServisi;
use App\Support\PtsXmlParser;
use Illuminate\Support\Carbon;

class PtsController extends Controller
{
    public function index(Request $request, ItsClient $its)
    {
        $v = $request->validate([
            'baslangic' => 'nullable|date',
            'bitis' => 'nullable|date|after_or_equal:baslangic',
            'kaynak_gln' => 'nullable|digits:13',
            'sadece_alinmayan' => 'nullable|boolean',
        ]);

        $sonuc = null;
        $hata = null;
        if ($request->hasAny(['baslangic', 'bitis'])) {
            if (empty($v['baslangic']) || empty($v['bitis'])) {
                $hata = 'Başlangıç ve bitiş tarihini birlikte girin.';
            } elseif (Carbon::parse($v['baslangic'])->diffInDays(Carbon::parse($v['bitis'])) > 31) {
                $hata = 'Tarih aralığı en fazla 31 gün olabilir (İTS kuralı).';
            } else {
                try {
                    $sonuc = $its->ptsSorgula($request->user()->tenant, $v['baslangic'], $v['bitis'], (bool) ($v['sadece_alinmayan'] ?? false), $v['kaynak_gln'] ?? null);
                } catch (\Throwable $e) {
                    $hata = $e->getMessage();
                }
            }
        }

        if (is_array($sonuc) && $request->boolean('excel')) {
            return \App\Support\XlsxYazici::indir('pts-sorgu-'.now()->format('Ymd-His').'.xlsx',
                ['Transfer No', 'Gönderen GLN', 'Alıcı GLN', 'Tarih', 'Durum'],
                array_map(fn ($r) => [$r['transferId'], $r['gonderen'], $r['alici'], $r['tarih'] ? Carbon::parse($r['tarih'])->format('d.m.Y H:i') : '', $r['alindi'] === null ? '' : ($r['alindi'] ? 'Alındı' : 'Alınmadı')], $sonuc),
                'PTS');
        }

        // Gönderen GLN cari değilse, kimin olduğu İTS paydaş listesinden bulunur (kırmızı gösterilir).
        $paydasAdlari = [];
        if (is_array($sonuc) && $sonuc) {
            $cariler = \App\Models\Cari::adlar($request->user()->tenant_id);
            $cariDegil = array_values(array_diff(array_unique(array_column($sonuc, 'gonderen')), array_keys($cariler)));
            $paydasAdlari = \App\Support\PaydasAdi::coz($cariDegil, $request->user()->tenant, $its);
        }

        return view('pts.index', ['sonuc' => $sonuc, 'hata' => $hata, 'paydasAdlari' => $paydasAdlari]);
    }

    /** Paketi İTS'ten indirir, saklar ve önizlemeye yönlendirir (kılavuz §43; mal kabul sayılmaz). */
    public function al(Request $request, ItsClient $its)
    {
        $v = $request->validate(['transfer_id' => 'required|digits_between:1,20']);
        $tenant = $request->user()->tenant;
        try {
            $r = $its->ptsPaketAl($tenant, $v['transfer_id']);
        } catch (\Throwable $e) {
            return redirect()->route('pts')->withErrors(['pts' => $e->getMessage()]);
        }

        $adet = 0; $kaynak = $belge = null;
        foreach ($r['xmller'] as $xml) {
            try {
                $p = PtsXmlParser::parse($xml);
                $adet += count($p['urunler']);
                $kaynak ??= $p['kaynak'];
                $belge ??= $p['belge'];
            } catch (\InvalidArgumentException) {
                // önizlemede hata olarak gösterilir
            }
        }
        $paket = PtsPaketi::updateOrCreate(
            ['tenant_id' => $tenant->id, 'transfer_id' => $v['transfer_id']],
            ['kaynak_gln' => $kaynak && preg_match('/^\d{13}$/', $kaynak) ? $kaynak : null, 'belge_no' => $belge ? mb_substr($belge, 0, 40) : null,
             'urun_sayisi' => $adet, 'md5_uyumlu' => $r['md5_uyumlu'], 'xml' => json_encode($r['xmller'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)]
        );

        return redirect()->route('pts.paket', $paket);
    }

    /** Elle yüklenen XML/ZIP dosyasını PTS paketi gibi içeri alır (İTS'e hiçbir şey göndermez). */
    public function yukle(Request $request)
    {
        $request->validate(['dosya' => ['required', 'file', 'max:20480', 'extensions:xml,zip']], [
            'dosya.required' => 'Bir XML veya ZIP dosyası seçin.', 'dosya.max' => 'Dosya en fazla 20 MB olabilir.', 'dosya.extensions' => 'Yalnızca .xml veya .zip dosyası yüklenebilir.',
        ]);
        $tenant = $request->user()->tenant;
        $bayt = (string) file_get_contents($request->file('dosya')->getRealPath());

        try {
            $xmller = \App\Support\PtsDosya::xmllerCikar($bayt);
        } catch (\RuntimeException $e) {
            return redirect()->route('pts')->withErrors(['pts' => $e->getMessage()]);
        }

        $adet = 0; $kaynak = $belge = $alici = null; $hatalar = []; $yapi = [];
        foreach ($xmller as $ad => $xml) {
            try {
                $p = PtsXmlParser::parse($xml);
                $adet += count($p['urunler']);
                $kaynak ??= $p['kaynak']; $belge ??= $p['belge']; $alici ??= $p['alici'];
                $yapi = $yapi + $p['yapi'];
            } catch (\InvalidArgumentException $e) {
                $hatalar[] = $ad.': '.$e->getMessage();
            }
        }
        if ($adet === 0) {
            $ipucu = $yapi ? ' Dosyada bulunan etiketler: '.implode(', ', array_slice(array_keys($yapi), 0, 15)).'.' : '';

            return redirect()->route('pts')->withErrors(['pts' => 'Dosyada GTIN ve seri numarası içeren ürün bulunamadı.'.($hatalar ? ' '.implode(' | ', $hatalar) : '').$ipucu]);
        }

        $paket = PtsPaketi::updateOrCreate(
            ['tenant_id' => $tenant->id, 'transfer_id' => 'DOSYA-'.substr(sha1($bayt), 0, 12)],
            ['kaynak_gln' => $kaynak && preg_match('/^\d{13}$/', $kaynak) ? $kaynak : null, 'belge_no' => $belge ? mb_substr($belge, 0, 40) : null,
             'urun_sayisi' => $adet, 'md5_uyumlu' => null, 'xml' => json_encode($xmller, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)]
        );

        $uyari = 'Dosyadan '.$adet.' ürün okundu. Bu dosya İTS PTS üzerinden alınmadı; ürünlerin size ait olduğunu Mal Alım sırasında İTS doğrular.';
        if ($alici && preg_match('/^\d{13}$/', $alici) && $alici !== $tenant->gln) {
            $uyari .= ' DİKKAT: dosyadaki alıcı GLN ('.$alici.') sizin GLN\'inizden farklı.';
        }

        return redirect()->route('pts.paket', $paket)->with('ok', $uyari);
    }

    public function paket(Request $request, PtsPaketi $paket)
    {
        abort_unless($paket->tenant_id === $request->user()->tenant_id, 404);
        [$urunler, $yapi, $hatalar] = $this->oku($paket);

        return view('pts.paket', ['paket' => $paket, 'urunler' => $urunler, 'yapi' => $yapi, 'hatalar' => $hatalar, 'sonuc' => null]);
    }

    /** Paketteki her ürünü (karekod bilgisiyle) Excel'e aktarır. */
    public function excel(Request $request, PtsPaketi $paket)
    {
        abort_unless($paket->tenant_id === $request->user()->tenant_id, 404);
        [$urunler] = $this->oku($paket);

        return \App\Support\XlsxYazici::indir('pts-paket-'.$paket->transfer_id.'.xlsx',
            ['GTIN', 'Seri No', 'Parti', 'SKT', 'Karekod'],
            array_map(fn ($u) => [$u['gtin'], $u['sn'], $u['bn'], $u['xd'], '(01)'.$u['gtin'].'(21)'.$u['sn'].($u['bn'] ? '(10)'.$u['bn'] : '').($u['xd'] ? '(17)'.date('ymd', strtotime($u['xd'])) : '')], $urunler),
            'PTS Paketi');
    }

    public function malAlim(Request $request, PtsPaketi $paket, StokServisi $servis)
    {
        abort_unless($paket->tenant_id === $request->user()->tenant_id, 404);
        [$urunler, $yapi, $hatalar] = $this->oku($paket);
        $kalemler = array_map(fn ($u) => $u + ['kod' => $u['gtin'].' / '.$u['sn']], $urunler);

        $sonuc = $servis->kalemleriIsle($request->user(), 'kabul', $kalemler);
        $hepsiTamam = $sonuc['satirlar'] && collect($sonuc['satirlar'])->every(fn ($s) => $s['durum'] === 'tamam');
        if ($hepsiTamam) {
            $paket->update(['durum' => 'mal_alindi']);
        }

        return view('pts.paket', ['paket' => $paket, 'urunler' => $urunler, 'yapi' => $yapi, 'hatalar' => $hatalar, 'sonuc' => $sonuc]);
    }

    /** @return array{0:array,1:array,2:array} ürünler, yapı özeti, okuma hataları */
    private function oku(PtsPaketi $paket): array
    {
        $urunler = []; $yapi = []; $hatalar = [];
        foreach (json_decode($paket->xml, true) ?: [] as $ad => $xml) {
            try {
                $p = PtsXmlParser::parse((string) $xml);
                $urunler = array_merge($urunler, $p['urunler']);
                foreach ($p['yapi'] as $k => $n) { $yapi[$k] = ($yapi[$k] ?? 0) + $n; }
            } catch (\InvalidArgumentException $e) {
                $hatalar[] = $ad.': '.$e->getMessage();
            }
        }

        return [$urunler, $yapi, $hatalar];
    }
}
