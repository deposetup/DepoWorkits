<?php

namespace App\Http\Controllers;

use App\Models\IlacAyari;
use App\Models\StokKalemi;
use App\Services\Its\ItsClient;
use App\Services\StokServisi;
use Illuminate\Http\Request;

class StokController extends Controller
{
    /** gtin => ilaç adı (İTS ilaç listesi) */
    private function adlar(ItsClient $its): array
    {
        try {
            // yalnız gtin=>ad haritası önbelleğe alınır (tam liste büyük)
            return \Illuminate\Support\Facades\Cache::remember('ilac_adlari_'.auth()->user()->tenant_id, now()->addHours(6),
                fn () => collect($its->ilacListesi(auth()->user()->tenant))->pluck('drugName', 'gtin')->all());
        } catch (\Throwable $e) {
            report($e); // ilaç adı alınamazsa liste yine de açılır (adlar boş görünür)

            return [];
        }
    }

    public function index(Request $request, ItsClient $its)
    {
        $tid = $request->user()->tenant_id;
        $q = StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta')
            ->selectRaw('gtin, count(*) as adet, min(skt) as en_yakin_skt')->groupBy('gtin')->orderBy('gtin');
        if ($ara = $request->query('ara')) {
            $q->where('gtin', 'like', '%'.preg_replace('/\D/', '', $ara).'%');
        }

        return view('stok.index', ['satirlar' => $q->get(), 'adlar' => $this->adlar($its), 'ara' => $ara]);
    }

    /** GTIN (barkod) bazlı stok: ürün başına adet ve parti/SKT kırılımı; Excel çıktısı. */
    public function barkod(Request $request, ItsClient $its)
    {
        $tid = $request->user()->tenant_id;
        $ara = trim((string) $request->query('ara', ''));
        $adlar = $this->adlar($its);

        $temel = StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta');
        if ($ara !== '') {
            $gtinler = collect($adlar)->filter(fn ($ad) => mb_stripos($ad, $ara) !== false)->keys()->all();
            $temel->where(fn ($w) => $w->where('gtin', 'like', '%'.preg_replace('/\D/', '', $ara).'%')
                ->when(preg_replace('/\D/', '', $ara) === '', fn ($x) => $x->whereRaw('1=0'))
                ->orWhereIn('gtin', $gtinler));
        }

        $partiler = (clone $temel)->selectRaw('gtin, bn, skt, count(*) as adet')
            ->groupBy('gtin', 'bn', 'skt')->orderBy('gtin')->orderBy('skt')->get()->groupBy('gtin');

        if ($request->boolean('excel')) {
            $satirlar = [];
            foreach ($partiler as $gtin => $liste) {
                foreach ($liste as $p) {
                    $satirlar[] = [$adlar[$gtin] ?? '', $gtin, $p->bn, $p->skt ? \Illuminate\Support\Carbon::parse($p->skt)->format('d.m.Y') : '', (int) $p->adet];
                }
            }

            return \App\Support\XlsxYazici::indir('barkod-bazli-stok-'.now()->format('Ymd-His').'.xlsx',
                ['İlaç', 'GTIN', 'Parti', 'SKT', 'Adet'], $satirlar, 'Barkod Bazlı Stok');
        }

        $urunler = $partiler->map(fn ($liste, $gtin) => (object) [
            'gtin' => $gtin,
            'ad' => $adlar[$gtin] ?? null,
            'adet' => $liste->sum('adet'),
            'en_yakin_skt' => $liste->pluck('skt')->filter()->map(fn ($d) => \Illuminate\Support\Carbon::parse($d))->min(),
            'partiler' => $liste,
        ])->values();

        return view('stok.barkod', ['urunler' => $urunler, 'ara' => $ara, 'toplam' => $urunler->sum('adet')]);
    }

    /** Karekod metni (okunabilir GS1 biçimi). */
    public static function karekod(StokKalemi $k): string
    {
        return '(01)'.$k->gtin.'(21)'.$k->sn
            .($k->bn ? '(10)'.$k->bn : '')
            .($k->skt ? '(17)'.$k->skt->format('ymd') : '');
    }

    /** Ürün bazlı (karekodlu) stok listesi; seçilenlere toplu işlem yapılabilir. */
    public function liste(Request $request, ItsClient $its)
    {
        $tid = $request->user()->tenant_id;
        $durum = $request->query('durum', 'stokta');
        abort_unless(in_array($durum, ['stokta', 'satis', 'sarf', 'devir', 'deaktive'], true), 404);
        $ara = trim((string) $request->query('ara', ''));
        $adlar = $this->adlar($its);

        $q = StokKalemi::where('tenant_id', $tid)->where('durum', $durum);
        if ($ara !== '') {
            $gtinler = collect($adlar)->filter(fn ($ad) => mb_stripos($ad, $ara) !== false)->keys()->all();
            $q->where(fn ($w) => $w->where('gtin', 'like', "%$ara%")->orWhere('sn', 'like', "%$ara%")
                ->orWhere('bn', 'like', "%$ara%")->orWhereIn('gtin', $gtinler));
        }
        $q->orderBy('gtin')->orderBy('skt')->orderBy('sn');
        if ($request->boolean('excel')) {
            $ad = ['stokta' => 'stok', 'satis' => 'satildi', 'sarf' => 'sarf', 'devir' => 'devredildi', 'deaktive' => 'deaktive'][$durum];

            return \App\Support\XlsxYazici::indir($ad.'-listesi-'.now()->format('Ymd-His').'.xlsx',
                ['İlaç', 'GTIN', 'Seri No', 'Parti', 'SKT', 'Karekod'],
                $q->lazy(1000)->map(fn ($k) => [$adlar[$k->gtin] ?? '', $k->gtin, $k->sn, $k->bn, $k->skt?->format('d.m.Y'), self::karekod($k)]),
                'Stok');
        }
        $kalemler = $q->paginate(200)->withQueryString();

        return view('stok.liste', [
            'kalemler' => $kalemler, 'adlar' => $adlar, 'ara' => $ara, 'durum' => $durum,
            'sebepler' => StokServisi::DEAKTIVE_SEBEPLER,
        ]);
    }

    public function toplu(Request $request, StokServisi $servis)
    {
        abort_unless($request->user()->tenant_id, 403);
        $v = $request->validate([
            'tur' => 'required|in:satis,sarf,iade,deaktive,devir,satis_iptal,sarf_iptal,devir_iptal,deaktive_iptal',
            'secili' => 'required|array|min:1|max:1000',
            'secili.*' => 'integer',
            'karsi_gln' => 'nullable|digits:13',
            'sebep' => 'nullable|in:'.implode(',', array_keys(StokServisi::DEAKTIVE_SEBEPLER)),
            'aciklama' => 'nullable|string|max:255',
        ], ['secili.required' => 'Önce listeden ürün seçin.']);
        if (in_array($v['tur'], ['satis', 'iade', 'devir'], true)) {
            $request->validate(['karsi_gln' => 'required|digits:13']);
        }
        if (in_array($v['tur'], ['deaktive', 'deaktive_iptal'], true)) {
            $request->validate(['sebep' => 'required']);
        }

        $kalemler = StokKalemi::where('tenant_id', $request->user()->tenant_id)->whereIn('id', $v['secili'])->get()
            ->map(fn ($k) => ['kod' => self::karekod($k), 'gtin' => $k->gtin, 'sn' => $k->sn, 'bn' => $k->bn, 'xd' => $k->skt?->toDateString()])
            ->all();
        $sonuc = $servis->kalemleriIsle($request->user(), $v['tur'], $kalemler, $v['sebep'] ?? null, $v['aciklama'] ?? null, $v['karsi_gln'] ?? null);

        return view('islem.form', [
            'tur' => $v['tur'], 'baslik' => StokServisi::TURLER[$v['tur']][2], 'sebepler' => StokServisi::DEAKTIVE_SEBEPLER, 'sonuc' => $sonuc,
            'geri' => route('stok.liste'),
        ]);
    }

    public function kritikMiad(Request $request, ItsClient $its)
    {
        $tid = $request->user()->tenant_id;
        $ayar = IlacAyari::where('tenant_id', $tid)->get()->keyBy('gtin');
        $stok = StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta')
            ->selectRaw('gtin, count(*) as adet')->groupBy('gtin')->pluck('adet', 'gtin');
        $adlar = $this->adlar($its);

        $kritik = [];
        foreach ($adlar as $gtin => $ad) {
            $esik = $ayar[$gtin]->kritik_stok ?? 0;
            $adet = $stok[$gtin] ?? 0;
            if ($esik > 0 && $adet <= $esik) {
                $kritik[] = ['gtin' => $gtin, 'ad' => $ad, 'adet' => $adet, 'esik' => $esik];
            }
        }

        $miad = [];
        foreach (StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta')->whereNotNull('skt')->orderBy('skt')->get() as $k) {
            $gun = $ayar[$k->gtin]->miad_gun ?? 90;
            if ($k->skt->lte(now()->addDays($gun))) {
                $miad[] = ['kalem' => $k, 'ad' => $adlar[$k->gtin] ?? $k->gtin, 'kalan' => (int) now()->startOfDay()->diffInDays($k->skt, false)];
            }
        }

        return view('stok.kritik-miad', ['adlar' => $adlar, 'ayar' => $ayar, 'kritik' => $kritik, 'miad' => $miad]);
    }

    public function ayarKaydet(Request $request)
    {
        $v = $request->validate(['gtin' => 'required|digits:14', 'kritik_stok' => 'required|integer|min:0|max:100000', 'miad_gun' => 'required|integer|min:1|max:1000']);
        IlacAyari::updateOrCreate(['tenant_id' => $request->user()->tenant_id, 'gtin' => $v['gtin']], ['kritik_stok' => $v['kritik_stok'], 'miad_gun' => $v['miad_gun']]);

        return back()->with('ok', 'Ayar kaydedildi.');
    }
}
