<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Cari;
use Illuminate\Http\Request;

class CariController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->tenant_id, 403);
        $ara = trim((string) $request->query('ara', ''));
        $cariler = Cari::where('tenant_id', $request->user()->tenant_id)
            ->when($ara !== '', fn ($q) => $q->where(fn ($w) => $w->where('unvan', 'like', '%'.$ara.'%')->orWhere('gln', 'like', '%'.$ara.'%')->orWhere('il', 'like', '%'.$ara.'%')))
            ->orderBy('unvan')->paginate(50)->withQueryString();

        return view('cari.index', ['cariler' => $cariler, 'ara' => $ara, 'tipler' => PaydasController::TIPLER]);
    }

    /** Paydaş listesinden cari ekler (varsa bilgilerini günceller). */
    public function ekle(Request $request)
    {
        abort_unless($request->user()->tenant_id, 403);
        $v = $request->validate([
            'gln' => 'required|digits:13',
            'unvan' => 'required|string|max:255',
            'tip' => 'nullable|in:'.implode(',', array_keys(PaydasController::TIPLER)),
            'il' => 'nullable|string|max:60', 'ilce' => 'nullable|string|max:60',
            'eposta' => 'nullable|email|max:255',
            'telefon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\s-]{7,30}$/'],
            'aktif' => 'nullable|boolean',
        ]);
        $tid = $request->user()->tenant_id;
        $cari = Cari::updateOrCreate(['tenant_id' => $tid, 'gln' => $v['gln']], [
            'user_id' => $request->user()->id, 'unvan' => $v['unvan'], 'tip' => $v['tip'] ?? null,
            'il' => $v['il'] ?? null, 'ilce' => $v['ilce'] ?? null, 'eposta' => $v['eposta'] ?? null,
            'telefon' => $v['telefon'] ?? null, 'aktif' => (bool) ($v['aktif'] ?? true),
        ]);
        AuditLog::kaydet('cari_ekle', ['cari' => $cari->id, 'gln' => $cari->gln]);

        return back()->with('ok', $cari->unvan.' cariye eklendi.');
    }

    public function sil(Request $request, Cari $cari)
    {
        abort_unless($cari->tenant_id === $request->user()->tenant_id, 404);
        $cari->delete();
        AuditLog::kaydet('cari_sil', ['gln' => $cari->gln]);

        return back()->with('ok', $cari->unvan.' cariden çıkarıldı.');
    }

    /** Satış/devir/iade ekranlarındaki cari seçici için ad ya da GLN ile arama. */
    public function ara(Request $request)
    {
        abort_unless($request->user()->tenant_id, 403);
        $q = trim((string) $request->query('q', ''));

        return response()->json(Cari::where('tenant_id', $request->user()->tenant_id)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('unvan', 'like', '%'.$q.'%')->orWhere('gln', 'like', '%'.$q.'%')))
            ->orderBy('unvan')->limit(15)->get(['gln', 'unvan', 'tip', 'il', 'ilce', 'aktif']));
    }
}
