<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class KullaniciController extends Controller
{
    public function index(Tenant $tenant)
    {
        return view('admin.kullanicilar', ['tenant' => $tenant, 'kullanicilar' => User::where('tenant_id', $tenant->id)->orderBy('name')->get()]);
    }

    public function store(Request $request, Tenant $tenant)
    {
        $v = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'rol' => 'required|in:tenant_admin,kullanici',
            'password' => ['required', Password::min(10)->letters()->numbers()],
        ]);
        $u = User::create(['name' => $v['name'], 'email' => $v['email'], 'password' => $v['password'], 'tenant_id' => $tenant->id, 'rol' => $v['rol'], 'aktif' => true]);
        AuditLog::kaydet('kullanici_olustur', ['tenant' => $tenant->id, 'user' => $u->id]);

        return back()->with('ok', 'Kullanıcı eklendi.');
    }

    public function aktifToggle(Tenant $tenant, User $user)
    {
        abort_unless($user->tenant_id === $tenant->id, 404);
        $user->update(['aktif' => ! $user->aktif]);
        AuditLog::kaydet('kullanici_aktif_degistir', ['user' => $user->id, 'aktif' => $user->aktif]);

        return back();
    }
}
