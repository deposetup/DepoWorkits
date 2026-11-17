<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PtsPaketi;
use App\Models\StokKalemi;
use App\Services\Its\ItsClient;
use App\Support\Gs1Parser;
use App\Support\ItsHata;
use App\Support\PtsXmlParser;
use Illuminate\Http\Request;

/**
 * İTS stok listesi servisi sunmaz (REST kılavuzu). Bu yüzden aday ürünler (karekod listesi ya da alınmış PTS
 * paketleri) İTS Durum Sorgu ile doğrulanır; son sahibi (Gln1) bu paydaşsa ve ürün arada değilse (Gln2 boş) stoğa alınır.
 */
class StokAktarController extends Controller
{
    private const LIMIT = 2000;

    public function form(Request $request)
    {
        return view('stok.ice-aktar', ['paketSayisi' => PtsPaketi::where('tenant_id', $request->user()->tenant_id)->count(), 'sonuc' => null]);
    }

    /** İTS portalından indirilen stok CSV/ZIP dosyasını müşterinin stoğuna alır. */
    public function csv(Request $request, \App\Services\StokCsvAktar $aktar)
    {
        abort_unless($request->user()->tenant_id, 403);
        $request->validate(['dosya' => 'required|file|max:51200|mimes:csv,txt,zip']);
        try {
            $sonuc = $aktar->ice($request->user()->tenant, $request->file('dosya'));
        } catch (\InvalidArgumentException $e) {
            return back()->with('hata', $e->getMessage());
        }

        return view('stok.ice-aktar', ['paketSayisi' => PtsPaketi::where('tenant_id', $request->user()->tenant_id)->count(), 'sonuc' => null, 'csv' => $sonuc]);
    }

    /** 1. aşama (XHR, yükleme yüzdesi tarayıcıda): dosyayı alır, çözer, satırları sayar. */
    public function csvHazirla(Request $request, \App\Services\StokCsvAktar $aktar)
    {
        abort_unless($request->user()->tenant_id, 403);
        $request->validate(['dosya' => 'required|file|max:51200|mimes:csv,txt,zip']);
        try {
            $durum = $aktar->hazirla($request->user()->tenant, $request->file('dosya'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $token = bin2hex(random_bytes(16));
        \Illuminate\Support\Facades\Cache::put($this->csvAnahtar($request, $token), $durum, now()->addHours(2));

        return response()->json(['token' => $token, 'toplam' => $durum['toplam'], 'dosya' => $request->file('dosya')->getClientOriginalName()]);
    }

    /** 2. aşama: her çağrıda en fazla 5.000 satır işler; bitince özeti döndürür. */
    public function csvAdim(Request $request, \App\Services\StokCsvAktar $aktar)
    {
        abort_unless($request->user()->tenant_id, 403);
        $v = $request->validate(['token' => ['required', 'regex:/^[a-f0-9]{32}$/']]);
        $anahtar = $this->csvAnahtar($request, $v['token']);
        $kilit = \Illuminate\Support\Facades\Cache::lock($anahtar.':kilit', 300);
        if (! $kilit->get()) {
            return response()->json(['message' => 'Önceki adım hâlâ sürüyor, lütfen bekleyin.'], 409);
        }
        try {
            $durum = \Illuminate\Support\Facades\Cache::get($anahtar);
            if (! $durum || ! is_file($durum['yol'])) {
                return response()->json(['message' => 'İçe aktarma oturumu bulunamadı veya süresi doldu. Dosyayı yeniden yükleyin.'], 404);
            }
            $tenant = $request->user()->tenant;
            $durum = $aktar->adim($tenant, $durum);
            $cevap = ['okunan' => $durum['r']['okunan'], 'toplam' => $durum['toplam'], 'bitti' => $durum['bitti'], 'gecersiz' => $durum['r']['gecersiz']];
            if ($durum['bitti']) {
                $cevap['sonuc'] = $aktar->sonuc($tenant, $durum);
                $aktar->temizle($durum);
                \Illuminate\Support\Facades\Cache::forget($anahtar);
            } else {
                \Illuminate\Support\Facades\Cache::put($anahtar, $durum, now()->addHours(2));
            }

            return response()->json($cevap);
        } finally {
            $kilit->release();
        }
    }

    /** Doğrulamalı içe aktarma (PTS paketleri / karekod listesi): işi başlatır. */
    public function dogrulaBaslat(Request $request, \App\Services\StokDogrulaAktar $servis)
    {
        abort_unless($request->user()->tenant_id, 403);
        $v = $request->validate(['kaynak' => 'required|in:kodlar,pts', 'kodlar' => 'nullable|required_if:kaynak,kodlar|string|max:2000000']);
        $d = $servis->baslat($request->user()->tenant, $v['kaynak'], $v['kodlar'] ?? null);
        $token = bin2hex(random_bytes(16));
        \Illuminate\Support\Facades\Cache::put('stokdogrula:'.$request->user()->tenant_id.':'.$token, $d, now()->addHours(3));

        return response()->json(['token' => $token] + $this->dogrulaDurum($servis, $d));
    }

    /** Doğrulamalı içe aktarma: bir adım ilerletir. */
    public function dogrulaAdim(Request $request, \App\Services\StokDogrulaAktar $servis)
    {
        abort_unless($request->user()->tenant_id, 403);
        $v = $request->validate(['token' => ['required', 'regex:/^[a-f0-9]{32}$/']]);
        $anahtar = 'stokdogrula:'.$request->user()->tenant_id.':'.$v['token'];
        $kilit = \Illuminate\Support\Facades\Cache::lock($anahtar.':kilit', 300);
        if (! $kilit->get()) {
            return response()->json(['message' => 'Önceki adım hâlâ sürüyor, lütfen bekleyin.'], 409);
        }
        try {
            $d = \Illuminate\Support\Facades\Cache::get($anahtar);
            if (! $d) {
                return response()->json(['message' => 'İçe aktarma oturumu bulunamadı veya süresi doldu. İşlemi yeniden başlatın.'], 404);
            }
            try {
                $d = $servis->adim($request->user()->tenant, $d);
            } catch (\Throwable $e) {
                report($e);

                return response()->json(['message' => 'İTS sorgusu başarısız: '.$e->getMessage().' (aynı adım tekrar denenebilir; ilerleme korunur)'], 502);
            }
            $cevap = $this->dogrulaDurum($servis, $d);
            if ($d['bitti']) {
                $cevap['sonuc'] = $servis->sonuc($d);
                $servis->temizle($d);
                \Illuminate\Support\Facades\Cache::forget($anahtar);
            } else {
                \Illuminate\Support\Facades\Cache::put($anahtar, $d, now()->addHours(3));
            }

            return response()->json($cevap);
        } finally {
            $kilit->release();
        }
    }

    private function dogrulaDurum(\App\Services\StokDogrulaAktar $servis, array $d): array
    {
        return [
            'asama' => $d['asama'], 'bitti' => $d['bitti'],
            'a' => ['islenen' => $d['asama'] === 'paket' ? $d['paketSira'] : count($d['paketler']), 'toplam' => count($d['paketler'])],
            'b' => ['islenen' => $d['islenen'], 'toplam' => $d['asama'] === 'sorgu' ? $d['yazilan'] : 0],
            'alinan' => $d['alinan'], 'zatenVar' => $d['zatenVar'], 'digerSay' => $d['digerSay'], 'aday' => $d['yazilan'],
        ];
    }

    private function csvAnahtar(Request $request, string $token): string
    {
        return 'stokcsv:'.$request->user()->tenant_id.':'.$token;
    }

    public function calistir(Request $request, ItsClient $its)
    {
        abort_unless($request->user()->tenant_id, 403);
        $v = $request->validate(['kaynak' => 'required|in:kodlar,pts', 'kodlar' => 'nullable|required_if:kaynak,kodlar|string|max:200000']);
        $tenant = $request->user()->tenant;
        $adaylar = [];
        $hatalar = [];

        if ($v['kaynak'] === 'kodlar') {
            foreach (preg_split('/\r\n|\r|\n/', $v['kodlar']) as $kod) {
                $kod = trim($kod);
                if ($kod === '') { continue; }
                try {
                    $k = Gs1Parser::parse($kod);
                    if (! $k['seri']) { throw new \InvalidArgumentException('Seri no (21) yok.'); }
                    $adaylar[$k['gtin'].'/'.$k['seri']] = ['gtin' => $k['gtin'], 'sn' => $k['seri'], 'bn' => $k['lot'], 'xd' => $k['skt']];
                } catch (\InvalidArgumentException $e) {
                    $hatalar[] = mb_substr($kod, 0, 60).' → '.$e->getMessage();
                }
            }
        } else {
            foreach (PtsPaketi::where('tenant_id', $tenant->id)->get() as $paket) {
                foreach (json_decode($paket->xml, true) ?: [] as $xml) {
                    try {
                        foreach (PtsXmlParser::parse((string) $xml)['urunler'] as $u) {
                            $adaylar[$u['gtin'].'/'.$u['sn']] = $u;
                        }
                    } catch (\InvalidArgumentException) {}
                }
            }
        }

        // zaten stokta olanlar yeniden sorgulanmaz
        $mevcut = StokKalemi::where('tenant_id', $tenant->id)->where('durum', 'stokta')->get(['gtin', 'sn'])->map(fn ($k) => $k->gtin.'/'.$k->sn)->flip();
        $zatenVar = count(array_intersect_key($adaylar, $mevcut->all()));
        $adaylar = array_diff_key($adaylar, $mevcut->all());
        if (count($adaylar) > self::LIMIT) {
            $hatalar[] = count($adaylar).' aday ürün var; bu seferde ilk '.self::LIMIT.' tanesi işlendi. Kalanlar için işlemi tekrarlayın.';
            $adaylar = array_slice($adaylar, 0, self::LIMIT, true);
        }

        $alinan = 0;
        $digerleri = [];
        if ($adaylar) {
            try {
                $durumlar = $its->durumSorgula($tenant, array_values($adaylar));
            } catch (\Throwable $e) {
                return back()->withInput()->with('hata', $e->getMessage());
            }
            foreach ($durumlar as $d) {
                $a = $adaylar[$d['gtin'].'/'.$d['sn']] ?? null;
                if (! $a) { continue; }
                $bizde = ($d['gln1'] ?? null) === $tenant->gln && empty($d['gln2']) && ItsHata::basarili($d['uc']);
                if ($bizde) {
                    StokKalemi::updateOrCreate(
                        ['tenant_id' => $tenant->id, 'gtin' => $a['gtin'], 'sn' => $a['sn']],
                        ['bn' => ($a['bn'] ?? null) ?: null, 'skt' => $a['xd'] ?? null, 'durum' => 'stokta', 'son_islem_at' => now()]
                    );
                    $alinan++;
                } else {
                    $digerleri[] = $a + ['gln1' => $d['gln1'], 'gln2' => $d['gln2'], 'neden' => ItsHata::basarili($d['uc'])
                        ? ($d['gln1'] === $tenant->gln ? 'Ürün arada (alıcı henüz Mal Alım yapmamış)' : 'Son sahibi başka paydaş')
                        : (ItsHata::aciklama($d['uc']) ?? 'Bilinmeyen kod ('.$d['uc'].')')];
                }
            }
            AuditLog::kaydet('stok.ice-aktar', ['alinan' => $alinan, 'atlanan' => count($digerleri)]);
        }

        return view('stok.ice-aktar', [
            'paketSayisi' => PtsPaketi::where('tenant_id', $tenant->id)->count(),
            'sonuc' => ['alinan' => $alinan, 'zatenVar' => $zatenVar, 'digerleri' => $digerleri, 'hatalar' => $hatalar, 'sorgulanan' => count($adaylar)],
        ]);
    }
}
