<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lisansı bitmiş müşteri yalnızca "Lisans süreniz doldu" sayfasını görür.
 * `salt_okunur` ile işaretli rotalar (raporlar), tenant ayarı izin veriyorsa açık kalır.
 */
class EnsureLicenseActive
{
    public function handle(Request $request, Closure $next, string $mod = 'tam'): Response
    {
        $user = $request->user();
        if (! $user || $user->superAdminMi()) {
            return $next($request);
        }

        $tenant = $user->tenant;
        if (! $tenant || ! $tenant->aktif) {
            abort(403, 'Hesabınız pasif.');
        }

        if ($tenant->lisansAktifMi()) {
            return $next($request);
        }

        if ($mod === 'salt_okunur' && $tenant->bitince_salt_okunur && $request->isMethodSafe()) {
            return $next($request);
        }

        return response()->view('lisans-doldu', ['tenant' => $tenant], 402);
    }
}
