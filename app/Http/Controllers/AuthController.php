<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $data['email'])->first();

        if ($user?->kilit_bitis?->isFuture()) {
            return back()->withErrors(['email' => 'Hesap geçici olarak kilitlendi. Daha sonra tekrar deneyin.']);
        }

        if (! $user || ! $user->aktif || ! Auth::attempt($data)) {
            if ($user) {
                $user->hatali_giris++;
                if ($user->hatali_giris >= config('its.max_hatali_giris')) {
                    $user->kilit_bitis = now()->addMinutes(config('its.kilit_dakika'));
                    $user->hatali_giris = 0;
                }
                $user->save();
            }

            return back()->withErrors(['email' => 'E-posta veya şifre hatalı.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user->forceFill(['hatali_giris' => 0, 'kilit_bitis' => null, 'son_giris' => now(), 'son_giris_ip' => $request->ip()])->save();
        AuditLog::kaydet('giris');

        return redirect()->intended('/');
    }

    public function logout(Request $request)
    {
        AuditLog::kaydet('cikis');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/giris');
    }

    public function kvkkOnayla(Request $request)
    {
        $tenant = $request->user()->tenant;
        abort_unless($tenant, 404);
        $tenant->update(['kvkk_onay_tarihi' => now()]);
        AuditLog::kaydet('kvkk_onay');

        return back();
    }
}
