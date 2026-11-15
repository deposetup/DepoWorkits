<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GirisKaydi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|string|max:255', 'password' => 'required|string']);
        $kimlik = trim($data['email']);

        // 13 haneli rakam = müşteri GLN'i; aksi hâlde e-posta.
        if (preg_match('/^\d{13}$/', $kimlik)) {
            return $this->glnIleGiris($request, $kimlik, $data['password']);
        }
        if (! filter_var($kimlik, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors(['email' => 'Geçerli bir e-posta adresi ya da 13 haneli GLN numarası yazın.'])->onlyInput('email');
        }

        $user = User::where('email', $kimlik)->first();

        if ($user?->kilit_bitis?->isFuture()) {
            GirisKaydi::kaydet($user, $kimlik, 'eposta', false);

            return back()->withErrors(['email' => 'Hesap geçici olarak kilitlendi. Daha sonra tekrar deneyin.']);
        }

        if (! $user || ! $user->aktif || ! Auth::attempt(['email' => $kimlik, 'password' => $data['password']])) {
            if ($user) {
                $user->hatali_giris++;
                if ($user->hatali_giris >= config('its.max_hatali_giris')) {
                    $user->kilit_bitis = now()->addMinutes(config('its.kilit_dakika'));
                    $user->hatali_giris = 0;
                }
                $user->save();
            }
            GirisKaydi::kaydet($user, $kimlik, 'eposta', false);

            return back()->withErrors(['email' => 'E-posta veya şifre hatalı.'])->onlyInput('email');
        }

        return $this->girisTamamla($request, $user, $kimlik, 'eposta');
    }

    /** GLN + şifre ile giriş: GLN'in müşterisindeki aktif kullanıcılardan şifresi eşleşen oturum açar. */
    private function glnIleGiris(Request $request, string $gln, string $sifre)
    {
        $anahtar = 'gln-giris:'.$gln.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($anahtar, (int) config('its.max_hatali_giris'))) {
            GirisKaydi::kaydet(null, $gln, 'gln', false);

            return back()->withErrors(['email' => 'Çok fazla hatalı deneme. Lütfen biraz bekleyip tekrar deneyin.'])->onlyInput('email');
        }

        $tenant = Tenant::where('gln', $gln)->first();
        $adaylar = $tenant ? User::where('tenant_id', $tenant->id)->where('aktif', true)->get() : collect();
        $user = $adaylar->first(fn (User $u) => ! $u->kilit_bitis?->isFuture() && Hash::check($sifre, $u->password));
        if ($adaylar->isEmpty()) {
            Hash::check($sifre, '$2y$12$abcdefghijklmnopqrstuuJ0u0Yl3pQp8o3x3q9m0z1l1rW6kQ8Gy'); // süre eşitleme
        }

        if (! $user) {
            RateLimiter::hit($anahtar, (int) config('its.kilit_dakika') * 60);
            GirisKaydi::kaydet($adaylar->first(), $gln, 'gln', false);

            return back()->withErrors(['email' => 'GLN veya şifre hatalı.'])->onlyInput('email');
        }

        RateLimiter::clear($anahtar);
        Auth::login($user);

        return $this->girisTamamla($request, $user, $gln, 'gln');
    }

    private function girisTamamla(Request $request, User $user, string $kimlik, string $yontem)
    {
        $request->session()->regenerate();
        $user->forceFill(['hatali_giris' => 0, 'kilit_bitis' => null, 'son_giris' => now(), 'son_giris_ip' => $request->ip()])->save();
        AuditLog::kaydet('giris');
        GirisKaydi::kaydet($user, $kimlik, $yontem, true);

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
