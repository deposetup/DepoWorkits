<?php

namespace App\Http\Controllers;

use App\Models\Hareket;
use App\Services\StokServisi;
use Illuminate\Http\Request;

class AnalizController extends Controller
{
    public function index(Request $request)
    {
        $tid = $request->user()->tenant_id;
        $ozet = Hareket::where('tenant_id', $tid)->where('basarili', true)->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('tur, count(*) as adet')->groupBy('tur')->pluck('adet', 'tur');
        $hareketler = Hareket::where('tenant_id', $tid)
            ->when($request->query('tur'), fn ($q, $t) => $q->where('tur', $t))
            ->latest()->paginate(50)->withQueryString();

        // Ecza depolarında kullanılmayan işlemler (sarf, devir) özet kartlarında ve filtrede gösterilmez.
        $tenant = $request->user()->tenant;
        $gorunen = collect(StokServisi::TURLER)->reject(fn ($t, $k) => ($tenant && ! $tenant->sarfYapabilir() && str_starts_with($k, 'sarf'))
            || ($tenant && ! $tenant->satisYapabilir() && str_starts_with($k, 'satis'))
            || ($tenant && ! $tenant->devirYapabilir() && str_starts_with($k, 'devir')))->all();

        return view('analiz.index', ['ozet' => $ozet, 'hareketler' => $hareketler, 'turler' => StokServisi::TURLER, 'gorunen' => $gorunen]);
    }
}
