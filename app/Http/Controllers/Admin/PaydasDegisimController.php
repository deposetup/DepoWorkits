<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaydasDegisimController extends Controller
{
    public const OLAYLAR = ['yeni' => ['Yeni eklendi', 'success'], 'pasif' => ['Pasife alındı', 'danger'], 'aktif' => ['Yeniden aktif oldu', 'primary'], 'kaldirildi' => ['Listeden kalktı', 'dark']];

    public function index(Request $request)
    {
        $v = $request->validate(['olay' => 'nullable|in:'.implode(',', array_keys(self::OLAYLAR)), 'ara' => 'nullable|string|max:100']);
        $hazir = Schema::hasTable('paydas_degisimleri');
        $kayitlar = $hazir
            ? DB::table('paydas_degisimleri')->when($v['olay'] ?? null, fn ($q, $o) => $q->where('olay', $o))
                ->when(trim($v['ara'] ?? ''), fn ($q, $a) => $q->where(fn ($w) => $w->where('unvan', 'like', "%$a%")->orWhere('gln', 'like', "%$a%")))
                ->orderByDesc('id')->paginate(50)->withQueryString()
            : null;
        $izlenen = $hazir ? DB::table('paydas_anliklari')->where('tip', 'depo')->count() : 0;
        $sonKontrol = $hazir ? DB::table('paydas_anliklari')->where('tip', 'depo')->max('updated_at') : null;

        return view('admin.paydas-degisimleri', ['kayitlar' => $kayitlar, 'olaylar' => self::OLAYLAR, 'f' => $v, 'izlenen' => $izlenen, 'sonKontrol' => $sonKontrol, 'alici' => config('its.bildirim_eposta'), 'mailAyarli' => ! in_array(config('mail.default'), ['log', 'array'], true)]);
    }
}
