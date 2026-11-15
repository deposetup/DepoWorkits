<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Süper yöneticinin bir müşteriyi, o müşterinin kullanıcısı gibi (salt okunur) görmesi.
 * Oturumda `musteri_gozu_yonetici_id` tutulur; yazma istekleri ImpersonationGuard ile engellenir.
 */
class MusteriGozuController extends Controller
{
    public function basla(Request $request, Tenant $tenant)
    {
        $yonetici = $request->user();
        abort_if($request->session()->has('musteri_gozu_yonetici_id'), 403);

        $hedef = $tenant->users()->where('aktif', true)->orderBy('id')->first();
        if (! $hedef) {
            return back()->with('hata', 'Bu müşterinin aktif kullanıcısı yok; önce kullanıcı ekleyin.');
        }

        AuditLog::kaydet('musteri_gozu_basla', ['tenant_id' => $tenant->id, 'hedef_kullanici_id' => $hedef->id]);

        Auth::login($hedef);
        $request->session()->regenerate();
        $request->session()->put('musteri_gozu_yonetici_id', $yonetici->id);

        return redirect()->route('home');
    }

    public function bitir(Request $request)
    {
        $yoneticiId = $request->session()->get('musteri_gozu_yonetici_id');
        abort_unless($yoneticiId, 404);

        $yonetici = User::where('id', $yoneticiId)->where('rol', 'super_admin')->where('aktif', true)->first();
        $musteriKullanici = $request->user();

        if (! $yonetici) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect('/giris');
        }

        AuditLog::kaydet('musteri_gozu_bitir', ['yonetici_id' => $yonetici->id]);

        Auth::login($yonetici);
        $request->session()->regenerate();
        $request->session()->forget('musteri_gozu_yonetici_id');

        return redirect()->route('admin.tenants.index');
    }
}
