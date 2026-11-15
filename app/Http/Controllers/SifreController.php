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
        $request->user()->update(['password' => $v['password']]);
        AuditLog::kaydet('sifre_degistir');

        return back()->with('ok', 'Şifreniz güncellendi.');
    }
}
