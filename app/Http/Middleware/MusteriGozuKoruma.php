<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** "Müşteri gözüyle" modunda yalnızca okuma yapılır; bildirim, kayıt ve ayar değişiklikleri engellenir. */
class MusteriGozuKoruma
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has('musteri_gozu_yonetici_id')
            && ! $request->isMethodSafe()
            && ! $request->routeIs('musteri-gozu.bitir')) {
            return redirect()->back()->with('hata', 'Müşteri gözü modu salt okunurdur; bu işlem yapılmadı.');
        }

        return $next($request);
    }
}
