<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** KVKK aydınlatma metni onaylanmadan müşteri kullanıcısı hiçbir ekranı kullanamaz. */
class EnsureKvkk
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && ! $user->superAdminMi() && $user->tenant && ! $user->tenant->kvkk_onay_tarihi
            && ! $request->routeIs('kvkk.form', 'kvkk.onay', 'logout', 'sifre', 'sifre.guncelle')) {
            return redirect()->route('kvkk.form');
        }

        return $next($request);
    }
}
