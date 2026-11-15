<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ItsCredential;
use App\Models\Tenant;
use Illuminate\Http\Request;

/**
 * Müşterinin İTS kullanıcı adı ve şifresini yalnızca DepoWork ekibi (süper yönetici) girer/değiştirir.
 * Değerler hiçbir ekranda gösterilmez, loga yazılmaz; yalnızca yazılabilir.
 */
class ItsKimlikController extends Controller
{
    public function form(Tenant $tenant)
    {
        return view('admin.its-kimlik', ['tenant' => $tenant, 'kayit' => $tenant->itsCredential]);
    }

    public function kaydet(Request $request, Tenant $tenant)
    {
        $v = $request->validate(['kullanici_adi' => 'required|string|max:100', 'sifre' => 'required|string|max:200']);
        ItsCredential::updateOrCreate(['tenant_id' => $tenant->id], $v);
        AuditLog::kaydet('its_kimlik_kaydet', ['hedef_tenant_id' => $tenant->id]); // değerler loga yazılmaz

        return back()->with('ok', 'İTS kullanıcı bilgileri şifreli olarak kaydedildi.');
    }
}
