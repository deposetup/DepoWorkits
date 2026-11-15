<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DestekTalebi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TalepController extends Controller
{
    public function index(Request $request)
    {
        $durum = in_array($request->query('durum'), ['yeni', 'islemde', 'kapandi'], true) ? $request->query('durum') : null;
        $talepler = Schema::hasTable('destek_talepleri')
            ? DestekTalebi::query()->when($durum, fn ($q) => $q->where('durum', $durum))->latest()->limit(200)->get()
            : collect();
        $tenantlar = \App\Models\Tenant::whereIn('id', $talepler->pluck('tenant_id')->unique())->pluck('unvan', 'id');

        return view('admin.talepler', [
            'talepler' => $talepler, 'tenantlar' => $tenantlar, 'durum' => $durum,
            'konular' => DestekTalebi::KONULAR + ['sifre_unuttum' => 'Şifremi unuttum (girişten)'],
        ]);
    }

    public function durum(Request $request, DestekTalebi $talep)
    {
        $d = $request->validate(['durum' => 'required|in:yeni,islemde,kapandi'])['durum'];
        $talep->update(['durum' => $d]);
        AuditLog::kaydet('talep_durum', ['talep' => $talep->id, 'durum' => $d]);

        return back()->with('ok', 'Talep durumu güncellendi.');
    }
}
