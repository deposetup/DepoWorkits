<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/** Yönetim paneline giren (müşteriye bağlı olmayan) yönetici kullanıcılar. */
class YoneticiController extends Controller
{
    public function index()
    {
        return view('admin.yoneticiler', ['yoneticiler' => User::where('rol', 'super_admin')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', Password::min(10)->letters()->numbers()],
        ]);
        $u = User::create(['name' => $v['name'], 'email' => $v['email'], 'password' => $v['password'], 'tenant_id' => null, 'rol' => 'super_admin', 'aktif' => true]);
        AuditLog::kaydet('yonetici_olustur', ['user' => $u->id, 'email' => $u->email]);

        return back()->with('ok', $u->name.' yönetici olarak eklendi. Giriş: e-posta + belirlediğiniz şifre.');
    }

    public function sifreDegistir(Request $request, User $user)
    {
        abort_unless($user->superAdminMi(), 404);
        $v = $request->validate(['password' => ['required', Password::min(10)->letters()->numbers()]]);
        $user->forceFill(['password' => $v['password'], 'remember_token' => null])->save();
        AuditLog::kaydet('yonetici_sifre_degistir', ['user' => $user->id]);

        return back()->with('ok', $user->name.' için şifre değiştirildi. Yeni şifreyi kendisine iletin.');
    }

    public function aktifToggle(Request $request, User $user)
    {
        abort_unless($user->superAdminMi(), 404);
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['yonetici' => 'Kendi hesabınızı pasifleştiremezsiniz.']);
        }
        $user->update(['aktif' => ! $user->aktif]);
        AuditLog::kaydet('yonetici_aktif_degistir', ['user' => $user->id, 'aktif' => $user->aktif]);

        return back()->with('ok', $user->name.($user->aktif ? ' aktifleştirildi.' : ' pasifleştirildi; artık giriş yapamaz.'));
    }
}
