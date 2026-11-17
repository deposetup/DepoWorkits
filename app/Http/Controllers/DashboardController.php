<?php

namespace App\Http\Controllers;

use App\Models\Hareket;
use App\Models\PtsPaketi;
use App\Models\StokKalemi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->superAdminMi()) {
            return redirect()->route('admin.tenants.index');
        }

        $tenant = $user->tenant;

        return view('dashboard', ['tenant' => $tenant] + ($tenant ? [
            'sarf' => $tenant->sarfYapabilir() ? $this->aylikSarf($tenant->id) : null,
            'pts' => $this->ptsHavuzu($tenant->id),
            'bugun' => $this->bugunGirenler($tenant->id),
        ] : []));
    }

    /** Son 6 ayın sarf özeti; müşteri hiç sarf bildirimi yapmadıysa null (kart gizlenir). */
    private function aylikSarf(int $tid): ?array
    {
        $temel = Hareket::where('tenant_id', $tid)->where('basarili', true);
        if (! (clone $temel)->where('tur', 'sarf')->exists()) {
            return null;
        }

        $aylar = [];
        for ($i = 0; $i < 6; $i++) {
            $bas = now()->startOfMonth()->subMonths($i);
            $r = (clone $temel)->whereBetween('created_at', [$bas, $bas->copy()->endOfMonth()])
                ->selectRaw("sum(case when tur = 'sarf' then 1 else 0 end) as sarf, sum(case when tur = 'sarf_iptal' then 1 else 0 end) as iptal, count(distinct case when tur = 'sarf' then gtin end) as cesit")
                ->first();
            $sarf = (int) $r->sarf;
            $iptal = (int) $r->iptal;
            $aylar[] = ['ay' => $bas, 'sarf' => $sarf, 'iptal' => $iptal, 'net' => max(0, $sarf - $iptal), 'cesit' => (int) $r->cesit];
        }

        // Bu ayın en çok sarf edilen ürünleri (ilaç adı önbellekte varsa gösterilir; İTS çağrısı yapılmaz)
        $adlar = Cache::get('ilac_adlari_'.$tid, []);
        $enCok = (clone $temel)->where('tur', 'sarf')->where('created_at', '>=', now()->startOfMonth())
            ->selectRaw('gtin, count(*) as adet')->groupBy('gtin')->orderByDesc('adet')->limit(5)->get()
            ->map(fn ($x) => ['gtin' => $x->gtin, 'ad' => $adlar[$x->gtin] ?? null, 'adet' => (int) $x->adet]);

        return ['aylar' => $aylar, 'en_cok' => $enCok];
    }

    /** PTS havuzu: alınmış (indirilmiş) ama Mal Alım bildirimi yapılmamış paketler = arada kalanlar. */
    private function ptsHavuzu(int $tid): array
    {
        $bekleyen = PtsPaketi::where('tenant_id', $tid)->where('durum', 'indirildi');
        $liste = (clone $bekleyen)->orderBy('created_at')->limit(5)->get(['id', 'transfer_id', 'kaynak_gln', 'belge_no', 'urun_sayisi', 'created_at']);

        return [
            'bekleyen_paket' => (clone $bekleyen)->count(),
            'bekleyen_urun' => (int) (clone $bekleyen)->sum('urun_sayisi'),
            'en_eski_gun' => ($ilk = $liste->first()) ? (int) $ilk->created_at->startOfDay()->diffInDays(now()->startOfDay()) : null,
            'son30_alinan' => PtsPaketi::where('tenant_id', $tid)->where('durum', 'mal_alindi')->where('updated_at', '>=', now()->subDays(30))->count(),
            'liste' => $liste,
        ];
    }

    /** Bugün stoğa giren ürünler (GTIN bazında); ilaç adı önbellekte varsa gösterilir, İTS çağrısı yapılmaz. */
    private function bugunGirenler(int $tid): array
    {
        $satirlar = StokKalemi::where('tenant_id', $tid)->where('created_at', '>=', now()->startOfDay())
            ->selectRaw('gtin, count(*) as adet, min(skt) as en_yakin_skt')->groupBy('gtin')->orderByDesc('adet')->get();
        $adlar = Cache::get('ilac_adlari_'.$tid, []);

        return [
            'kalem' => $satirlar->count(),
            'kutu' => (int) $satirlar->sum('adet'),
            'liste' => $satirlar->take(10)->map(fn ($x) => ['gtin' => $x->gtin, 'ad' => $adlar[$x->gtin] ?? null, 'adet' => (int) $x->adet]),
        ];
    }
}
