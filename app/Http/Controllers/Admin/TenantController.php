<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        return view('admin.tenants', ['tenants' => Tenant::orderBy('unvan')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'unvan' => 'required|string|max:255',
            'gln' => 'required|digits:13|unique:tenants,gln',
            'tur' => 'required|in:eczane,hastane,depo,uretici,ihracatci',
            'eposta' => 'nullable|email',
            'lisans_baslangic' => 'required|date',
            'lisans_bitis' => 'required|date|after:lisans_baslangic',
        ]);
        $tenant = Tenant::create($data);
        AuditLog::kaydet('tenant_olustur', ['tenant' => $tenant->id]);

        return back()->with('ok', 'Müşteri eklendi.');
    }

    public function uzat(Request $request, Tenant $tenant)
    {
        $gun = $request->validate(['gun' => 'required|integer|min:1|max:3650'])['gun'];
        $baz = $tenant->lisans_bitis->isFuture() ? $tenant->lisans_bitis : now();
        $tenant->update(['lisans_bitis' => $baz->copy()->addDays($gun)]);
        AuditLog::kaydet('lisans_uzat', ['tenant' => $tenant->id, 'gun' => $gun]);

        return back()->with('ok', 'Lisans uzatıldı.');
    }

    public function turDegistir(Request $request, Tenant $tenant)
    {
        $tur = $request->validate(['tur' => 'required|in:eczane,hastane,depo,uretici,ihracatci'])['tur'];
        $eski = $tenant->tur;
        $tenant->update(['tur' => $tur]);
        AuditLog::kaydet('tenant_tur_degistir', ['tenant' => $tenant->id, 'eski' => $eski, 'yeni' => $tur]);

        return back()->with('ok', 'Müşteri türü güncellendi.');
    }

    public function aktifToggle(Tenant $tenant)
    {
        $tenant->update(['aktif' => ! $tenant->aktif]);
        AuditLog::kaydet('tenant_aktif_degistir', ['tenant' => $tenant->id, 'aktif' => $tenant->aktif]);

        return back();
    }
}
