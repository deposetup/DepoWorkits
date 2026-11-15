<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class SifreController extends Controller
{
    public function form()
    {
        return view('sifre');
    }

    public function guncelle(Request $request)
    {
        $v = $request->validate([
            'mevcut' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);
        $user = $request->user();
        $cakisma = $user->tenant_id && \App\Models\User::where('tenant_id', $user->tenant_id)->where('id', '!=', $user->id)->get()
            ->contains(fn ($x) => \Illuminate\Support\Facades\Hash::check($v['password'], $x->password));
        if ($cakisma) {
            return back()->withErrors(['password' => 'Bu şifre sizin müşterinizdeki başka bir kullanıcıda da var. Farklı bir şifre seçin.']);
        }
        $user->update(['password' => $v['password']]);
        AuditLog::kaydet('sifre_degistir');

        return back()->with('ok', 'Şifreniz güncellendi.');
    }
}
