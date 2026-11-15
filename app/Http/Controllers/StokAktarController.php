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
