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
        StokKalemi::redOdasinaAl($tid);
        $q = StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta')
            ->selectRaw('gtin, count(*) as adet, min(skt) as en_yakin_skt')->groupBy('gtin')->orderBy('gtin');
        if ($ara = $request->query('ara')) {
            $q->where('gtin', 'like', '%'.preg_replace('/\D/', '', $ara).'%');
        }

        $adlar = $this->adlar($its);
        $tum = $q->get();
        $receteler = \App\Models\IlacRecete::tipler($tum->pluck('gtin'));
        $sirala = in_array($request->query('sirala'), ['ad', 'adet', 'gtin', 'skt', 'recete'], true) ? $request->query('sirala') : 'gtin';
        $yon = $request->query('yon') === 'desc' ? 'desc' : 'asc';
        $turkce = fn (string $a): string => mb_strtolower(strtr($a, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');
        $anahtar = match ($sirala) {
            'ad' => fn ($r) => $turkce((string) ($adlar[$r->gtin] ?? '')),
            'adet' => fn ($r) => (int) $r->adet,
            'skt' => fn ($r) => (string) ($r->en_yakin_skt ?? '9999-12-31'),
            'recete' => fn ($r) => $turkce((string) ($receteler[$r->gtin] ?? '~')),
            default => fn ($r) => (string) $r->gtin,
        };
        $satirlar = $tum->sortBy($anahtar, SORT_NATURAL, $yon === 'desc')->values();

        if ($request->boolean('excel')) {
            $veri = $satirlar->map(fn ($r) => [
                $adlar[$r->gtin] ?? '', $r->gtin, $receteler[$r->gtin] ?? '', (int) $r->adet,
                $r->en_yakin_skt ? \Illuminate\Support\Carbon::parse($r->en_yakin_skt)->format('d.m.Y') : '',
            ])->all();
            $veri[] = ['TOPLAM ('.$satirlar->count().' kalem)', '', '', (int) $satirlar->sum('adet'), ''];

            return \App\Support\XlsxYazici::indir('stok-'.now()->format('Ymd-His').'.xlsx',
                ['İlaç', 'GTIN', 'Reçete', 'Adet', 'En Yakın SKT'], $veri, 'Stok');
        }

        return view('stok.index', ['kalem' => $satirlar->count(), 'kutu' => (int) $satirlar->sum('adet'), 'satirlar' => $satirlar, 'adlar' => $adlar, 'ara' => $ara, 'sirala' => $sirala, 'yon' => $yon, 'receteler' => $receteler]);
    }

    /** GTIN (barkod) bazlı stok: ürün başına adet ve parti/SKT kırılımı; Excel çıktısı. */
    public function barkod(Request $request, ItsClient $its)
    {
        $tid = $request->user()->tenant_id;
        StokKalemi::redOdasinaAl($tid);
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
            // Barkod başına tek satır: toplam adet + partiler tek hücrede
            $satirlar = [];
            foreach ($partiler as $gtin => $liste) {
                $partiMetni = $liste->map(fn ($p) => ($p->bn ?: '—').' / '.($p->skt ? \Illuminate\Support\Carbon::parse($p->skt)->format('d.m.Y') : '—').' ('.(int) $p->adet.')')->implode('; ');
                $satirlar[] = [$adlar[$gtin] ?? '', $gtin, (int) $liste->sum('adet'), $partiMetni];
            }

            return \App\Support\XlsxYazici::indir('barkod-bazli-stok-'.now()->format('Ymd-His').'.xlsx',
                ['İlaç', 'GTIN', 'Adet', 'Parti / SKT (adet)'], $satirlar, 'Barkod Bazlı Stok');
        }

        $urunler = $partiler->map(fn ($liste, $gtin) => (object) [
            'gtin' => $gtin,
            'ad' => $adlar[$gtin] ?? null,
            'adet' => $liste->sum('adet'),
            'en_yakin_skt' => $liste->pluck('skt')->filter()->map(fn ($d) => \Illuminate\Support\Carbon::parse($d))->min(),
            'partiler' => $liste,
        ])->values();

        $sirala = in_array($request->query('sirala'), ['ad', 'adet', 'skt', 'gtin'], true) ? $request->query('sirala') : 'gtin';
        $yon = $request->query('yon') === 'desc' ? 'desc' : 'asc';
        $turkce = fn (string $a): string => mb_strtolower(strtr($a, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');
        $anahtar = match ($sirala) {
            'ad' => fn ($u) => $turkce((string) ($u->ad ?? '')),
            'adet' => fn ($u) => (int) $u->adet,
            'skt' => fn ($u) => $u->en_yakin_skt?->format('Y-m-d') ?? '9999-12-31',
            default => fn ($u) => (string) $u->gtin,
        };
        $urunler = $urunler->sortBy($anahtar, SORT_NATURAL, $yon === 'desc')->values();

        $receteler = \App\Models\IlacRecete::tipler($urunler->pluck('gtin'));

        return view('stok.barkod', ['receteler' => $receteler, 'urunler' => $urunler, 'ara' => $ara, 'toplam' => $urunler->sum('adet'), 'sirala' => $sirala, 'yon' => $yon]);
    }

    /** Karekod metni (okunabilir GS1 biçimi). */
    public static function karekod(StokKalemi $k): string
    {
        return '(01)'.$k->gtin.'(21)'.$k->sn
            .($k->bn ? '(10)'.$k->bn : '')
            .($k->skt ? '(17)'.$k->skt->format('ymd') : '');
    }

    /** Parantezsiz, taranabilir ham GS1 metni: 01GTIN 17SKT 10PARTI<GS>21SERI */
    public static function karekodHam(StokKalemi $k): string
    {
        return '01'.$k->gtin
            .($k->skt ? '17'.$k->skt->format('ymd') : '')
            .($k->bn ? '10'.$k->bn."\x1d" : '')
            .'21'.$k->sn;
    }

    /** Ürün bazlı (karekodlu) stok listesi; seçilenlere toplu işlem yapılabilir. */
    public function liste(Request $request, ItsClient $its)
    {
        $tid = $request->user()->tenant_id;
        $durum = $request->query('durum', 'stokta');
        abort_unless(in_array($durum, ['stokta', 'satis', 'sarf', 'devir', 'ihracat', 'deaktive'], true), 404);
        abort_if($durum === 'ihracat' && ! $request->user()->tenant?->ihracatYapabilir(), 404);
        abort_if($durum === 'satis' && ! $request->user()->tenant?->satisYapabilir(), 404);
        $ara = trim((string) $request->query('ara', ''));
        $adlar = $this->adlar($its);
        StokKalemi::redOdasinaAl($tid);
        $lokasyon = (string) $request->query('lokasyon', '');

        $q = StokKalemi::where('tenant_id', $tid)->where('durum', $durum);
        if ($lokasyon !== '' && StokKalemi::lokasyonVar()) {
            $lokasyon === '-' ? $q->whereNull('lokasyon') : $q->where('lokasyon', $lokasyon);
        }
        if ($ara !== '') {
            $gtinler = collect($adlar)->filter(fn ($ad) => mb_stripos($ad, $ara) !== false)->keys()->all();
            $q->where(fn ($w) => $w->where('gtin', 'like', "%$ara%")->orWhere('sn', 'like', "%$ara%")
                ->orWhere('bn', 'like', "%$ara%")->orWhereIn('gtin', $gtinler));
        }
        $sirala = (string) $request->query('sirala', '');
        $yon = $request->query('yon') === 'desc' ? 'desc' : 'asc';
        if ($sirala === 'ilac') {
            $gtinler = (clone $q)->reorder()->distinct()->pluck('gtin')->all();
            usort($gtinler, fn ($a, $b) => strcoll(mb_strtolower($adlar[$a] ?? '~'), mb_strtolower($adlar[$b] ?? '~')) ?: strcmp($a, $b));
            if ($yon === 'desc') {
                $gtinler = array_reverse($gtinler);
            }
            if ($gtinler) {
                $vaka = 'case gtin';
                foreach ($gtinler as $i => $g) {
                    $vaka .= ' when ? then '.$i;
                }
                $q->orderByRaw($vaka.' end', $gtinler);
            }
            $q->orderBy('skt')->orderBy('sn');
        } elseif ($sirala === 'skt') {
            $q->orderByRaw('skt is null')->orderBy('skt', $yon)->orderBy('gtin')->orderBy('sn');
        } elseif ($sirala === 'lokasyon' && StokKalemi::lokasyonVar()) {
            $q->orderByRaw('lokasyon is null')->orderBy('lokasyon', $yon)->orderBy('gtin')->orderBy('skt')->orderBy('sn');
        } else {
            $sirala = '';
            $q->orderBy('gtin')->orderBy('skt')->orderBy('sn');
        }
        $receteHarita = \App\Models\IlacRecete::tipler((clone $q)->reorder()->distinct()->pluck('gtin'));
        $receteHarita = collect($receteHarita)->mapWithKeys(fn ($tip, $g) => [\App\Models\IlacRecete::anahtar($g) => $tip])->all();
        if ($request->boolean('excel')) {
            $ad = ['stokta' => 'stok', 'satis' => 'satildi', 'sarf' => 'sarf', 'devir' => 'devredildi', 'ihracat' => 'ihrac', 'deaktive' => 'deaktive'][$durum];

            return \App\Support\XlsxYazici::indir($ad.'-listesi-'.now()->format('Ymd-His').'.xlsx',
                ['İlaç', 'Reçete', 'GTIN', 'Seri No', 'Parti', 'SKT', 'Lokasyon', 'Karekod'],
                $q->lazy(1000)->map(fn ($k) => [$adlar[$k->gtin] ?? '', $receteHarita[\App\Models\IlacRecete::anahtar($k->gtin) ?? ''] ?? '', $k->gtin, $k->sn, $k->bn, $k->skt?->format('d.m.Y'), $k->lokasyon ?? '', self::karekodHam($k)]),
                'Stok');
        }
        $sayfa = in_array((int) $request->query('sayfa'), [100, 300, 600, 1000], true) ? (int) $request->query('sayfa') : 100;
        $kalemler = $q->paginate($sayfa)->withQueryString();

        return view('stok.liste', [
            'sayfa' => $sayfa, 'kalemler' => $kalemler, 'adlar' => $adlar, 'ara' => $ara, 'durum' => $durum, 'receteHarita' => $receteHarita,
            'sayilar' => StokKalemi::where('tenant_id', $tid)->selectRaw('durum, count(*) as adet')->groupBy('durum')->pluck('adet', 'durum')->all(),
            'sirala' => $sirala, 'yon' => $yon, 'lokasyon' => $lokasyon, 'lokasyonlar' => StokKalemi::lokasyonlar($tid), 'redOdasi' => StokKalemi::RED_ODASI,
            'sebepler' => StokServisi::DEAKTIVE_SEBEPLER,
        ]);
    }

    /** Red Odası: SKT'si geçmiş, stoktaki ürünlerin ilaç ve parti bazlı dökümü. */
    public function redOdasi(Request $request, ItsClient $its)
    {
        return $this->oda($request, $its, StokKalemi::RED_ODASI, 'red-odasi');
    }

    /** Karantina Odası: kullanıcının elle karantinaya aldığı stoktaki ürünler. */
    public function karantinaOdasi(Request $request, ItsClient $its)
    {
        return $this->oda($request, $its, StokKalemi::KARANTINA_ODASI, 'karantina-odasi');
    }

    private function oda(Request $request, ItsClient $its, string $oda, string $dosya)
    {
        $tid = $request->user()->tenant_id;
        abort_unless($tid, 403);
        StokKalemi::redOdasinaAl($tid);
        $adlar = $this->adlar($its);
        $partiler = collect();
        if (StokKalemi::lokasyonVar()) {
            $partiler = StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta')->where('lokasyon', $oda)
                ->selectRaw('gtin, bn, skt, count(*) as adet')->groupBy('gtin', 'bn', 'skt')->orderBy('gtin')->orderBy('skt')->get();
        }

        if ($request->boolean('excel')) {
            return \App\Support\XlsxYazici::indir($dosya.'-'.now()->format('Ymd-His').'.xlsx', ['İlaç', 'GTIN', 'Parti', 'SKT', 'Adet'],
                $partiler->map(fn ($p) => [$adlar[$p->gtin] ?? '', $p->gtin, $p->bn, $p->skt ? \Illuminate\Support\Carbon::parse($p->skt)->format('d.m.Y') : '', (int) $p->adet])->all(), $oda);
        }

        $receteler = \App\Models\IlacRecete::tipler($partiler->pluck('gtin')->unique());
        $urunler = $partiler->groupBy('gtin')->map(fn ($l, $gtin) => (object) ['gtin' => $gtin, 'ad' => $adlar[$gtin] ?? null, 'recete' => $receteler[$gtin] ?? null, 'adet' => $l->sum('adet'), 'partiler' => $l])->values();

        // Red Odası'nda SKT'si geçmiş ürünler: tek tıkla "Deaktive (Miat)" bildirimi için otomatik seçilir.
        $miatIds = ($oda === StokKalemi::RED_ODASI && StokKalemi::lokasyonVar())
            ? StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta')->where('lokasyon', $oda)
                ->whereNotNull('skt')->where('skt', '<', now()->toDateString())->orderBy('skt')->limit(5000)->pluck('id')->all()
            : [];

        return view('stok.red-odasi', ['urunler' => $urunler, 'toplam' => $urunler->sum('adet'), 'hazir' => StokKalemi::lokasyonVar(),
            'oda' => $oda, 'karantina' => $oda === StokKalemi::KARANTINA_ODASI, 'miatIds' => $miatIds]);
    }

    /** Reyon - Raf: tüm lokasyonların stoktaki adet özeti. */
    public function reyonRaf(Request $request)
    {
        $tid = $request->user()->tenant_id;
        abort_unless($tid, 403);
        StokKalemi::redOdasinaAl($tid);
        $hazir = StokKalemi::lokasyonVar();
        $satirlar = $hazir ? StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta')
            ->selectRaw('lokasyon, count(*) as adet, count(distinct gtin) as cesit')->groupBy('lokasyon')->orderBy('lokasyon')->get() : collect();

        return view('stok.reyon-raf', ['satirlar' => $satirlar, 'hazir' => $hazir]);
    }

    /** Seçili stok kalemlerine lokasyon (reyon/raf) atar; yalnızca yerel kayıttır, İTS'e bildirilmez. */
    public function lokasyonAta(Request $request)
    {
        $tid = $request->user()->tenant_id;
        abort_unless($tid && StokKalemi::lokasyonVar(), 403);
        $v = $request->validate(['secili' => 'required|array|min:1|max:1000', 'secili.*' => 'integer', 'lokasyon' => 'nullable|string|max:80'],
            ['secili.required' => 'Önce listeden ürün seçin.']);
        $lok = \App\Support\Metin::baslik(trim((string) ($v['lokasyon'] ?? '')));
        $lok = $lok === '' ? null : $lok;

        $q = StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta')->whereIn('id', $v['secili']);
        $suresiGecen = (clone $q)->whereNotNull('skt')->where('skt', '<', now()->toDateString())->count();
        // SKT'si geçenler her zaman Red Odası'nda kalır.
        $adet = $q->where(fn ($w) => $w->whereNull('skt')->orWhere('skt', '>=', now()->toDateString()))->update(['lokasyon' => $lok]);
        \App\Models\AuditLog::kaydet('stok_lokasyon', ['adet' => $adet, 'lokasyon' => $lok]);

        return back()->with('ok', $adet.' ürünün lokasyonu '.($lok ? '"'.$lok.'"' : 'temizlendi').'.'
            .($suresiGecen ? ' SKT\'si geçen '.$suresiGecen.' ürün Red Odası\'nda kaldı.' : ''));
    }

    public function toplu(Request $request, StokServisi $servis)
    {
        abort_unless($request->user()->tenant_id, 403);
        $v = $request->validate([
            'tur' => 'required|in:satis,sarf,iade,deaktive,devir,ihracat,satis_iptal,sarf_iptal,devir_iptal,ihracat_iptal,deaktive_iptal',
            'secili' => 'required|array|min:1|max:1000',
            'secili.*' => 'integer',
            'karsi_gln' => 'nullable|digits:13',
            'sebep' => 'nullable|in:'.implode(',', array_keys(StokServisi::DEAKTIVE_SEBEPLER)),
            'aciklama' => 'nullable|string|max:255',
        ], ['secili.required' => 'Önce listeden ürün seçin.']);
        $sarfMi = in_array($v['tur'], ['sarf', 'sarf_iptal'], true);
        $azami = $sarfMi ? StokServisi::SARF_AZAMI : 100;
        if (count($v['secili']) > $azami) {
            $mesaj = 'Tek seferde en fazla '.$azami.' ürün için bildirim yapılabilir; seçiminizi '.$azami.' ve altına indirin.';

            return $request->expectsJson() ? response()->json(['message' => $mesaj], 422) : back()->withErrors(['secili' => $mesaj]);
        }
        abort_if(in_array($v['tur'], ['sarf', 'sarf_iptal'], true) && ! $request->user()->tenant->sarfYapabilir(), 403, 'Ecza depoları sarf bildirimi yapamaz.');
        abort_if(in_array($v['tur'], ['satis', 'satis_iptal'], true) && ! $request->user()->tenant->satisYapabilir(), 403, 'Hastaneler satış yapamaz; yalnızca sarf ve iade bildirir.');
        abort_if(in_array($v['tur'], ['ihracat', 'ihracat_iptal'], true) && ! $request->user()->tenant->ihracatYapabilir(), 403, 'İhracat bildirimi yalnızca ihracat yetkisi olan paydaşlar (depo, üretici, ihracatçı, ihracata yetkili firma) içindir.');
        abort_if(in_array($v['tur'], ['devir', 'devir_iptal'], true) && ! $request->user()->tenant->devirYapabilir(), 403, 'Ecza depoları ve hastaneler Mal Devir kullanmaz.');
        if (in_array($v['tur'], ['satis', 'iade', 'devir'], true)) {
            $request->validate(['karsi_gln' => 'required|digits:13']);
        }
        if (in_array($v['tur'], ['deaktive', 'deaktive_iptal'], true)) {
            $request->validate(['sebep' => 'required']);
        }
        if ($v['tur'] === 'ihracat') {
            $request->validate(['aciklama' => 'required|string|max:100'], ['aciklama.required' => 'İhracat için alıcı bilgisi zorunludur.']);
        }

        $kalemler = StokKalemi::where('tenant_id', $request->user()->tenant_id)->whereIn('id', $v['secili'])->get()
            ->map(fn ($k) => ['kod' => self::karekod($k), 'gtin' => $k->gtin, 'sn' => $k->sn, 'bn' => $k->bn, 'xd' => $k->skt?->toDateString()])
            ->all();
        $sonuc = $servis->kalemleriIsle($request->user(), $v['tur'], $kalemler, $v['sebep'] ?? null, $v['aciklama'] ?? null, $v['karsi_gln'] ?? null);

        if ($request->expectsJson()) {
            \App\Support\TopluSonuc::ekle($request, $v['tur'], $sonuc);

            return response()->json(['satirlar' => $sonuc['satirlar'], 'bildirim' => $sonuc['bildirim']]);
        }

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

        // Binlerce ürün olabileceği için tek tek değil, ilaç/parti/SKT bazında gruplanarak okunur.
        StokKalemi::redOdasinaAl($tid);
        $enGeniş = max(90, (int) $ayar->max('miad_gun'));
        $gruplar = StokKalemi::where('tenant_id', $tid)->where('durum', 'stokta')->whereNotNull('skt')
            ->where('skt', '<=', now()->addDays($enGeniş)->toDateString())
            ->selectRaw('gtin, bn, skt, count(*) as adet')->groupBy('gtin', 'bn', 'skt')->orderBy('skt')->orderBy('gtin')->get();
        $miad = [];
        $gecen = [];
        $gecenAdet = 0;
        $yaklasanAdet = 0;
        foreach ($gruplar as $g) {
            $skt = \Illuminate\Support\Carbon::parse($g->skt);
            $kalan = (int) now()->startOfDay()->diffInDays($skt->startOfDay(), false);
            $satir = ['gtin' => $g->gtin, 'ad' => $adlar[$g->gtin] ?? null, 'bn' => $g->bn, 'skt' => $skt, 'adet' => (int) $g->adet, 'kalan' => $kalan];
            if ($kalan < 0) {
                $gecenAdet += $satir['adet'];
                $gecen[] = $satir;
            } elseif ($kalan <= ($ayar[$g->gtin]->miad_gun ?? 90)) {
                $yaklasanAdet += $satir['adet'];
                $miad[] = $satir;
            }
        }

        $receteler = \App\Models\IlacRecete::tipler(collect($gecen)->merge($miad)->pluck('gtin')->unique());

        return view('stok.kritik-miad', ['receteler' => $receteler, 'adlar' => $adlar, 'ayar' => $ayar, 'kritik' => $kritik, 'miad' => $miad, 'gecen' => $gecen, 'gecenAdet' => $gecenAdet, 'yaklasanAdet' => $yaklasanAdet]);
    }

    public function ayarKaydet(Request $request, ItsClient $its)
    {
        // İlaç yazılarak aranır: "Ad (GTIN)", yalnızca GTIN/barkod ya da ilaç adının bir kısmı kabul edilir.
        if ($request->filled('ilac')) {
            $giris = trim((string) $request->input('ilac'));
            $gtin = null;
            if (preg_match('/(\d{13,14})\)?\s*$/', $giris, $m)) {
                $gtin = strlen($m[1]) === 13 ? '0'.$m[1] : $m[1];
            } else {
                $adlar = $this->adlar($its);
                $eslesen = array_keys(array_filter($adlar, fn ($ad) => mb_stripos($ad, $giris) !== false));
                if (count($eslesen) === 1) {
                    $gtin = $eslesen[0];
                } elseif (count($eslesen) > 1) {
                    return back()->withInput()->withErrors(['ilac' => count($eslesen).' ilaç eşleşti; listeden seçin ya da adı/barkodu daha açık yazın.']);
                }
            }
            if (! $gtin) {
                return back()->withInput()->withErrors(['ilac' => '"'.mb_substr($giris, 0, 60).'" ile eşleşen ilaç bulunamadı.']);
            }
            $request->merge(['gtin' => $gtin]);
        }
        $v = $request->validate(['gtin' => 'required|digits:14', 'kritik_stok' => 'required|integer|min:0|max:100000', 'miad_gun' => 'required|integer|min:1|max:1000']);
        IlacAyari::updateOrCreate(['tenant_id' => $request->user()->tenant_id, 'gtin' => $v['gtin']], ['kritik_stok' => $v['kritik_stok'], 'miad_gun' => $v['miad_gun']]);

        return back()->with('ok', 'Ayar kaydedildi.');
    }
}
