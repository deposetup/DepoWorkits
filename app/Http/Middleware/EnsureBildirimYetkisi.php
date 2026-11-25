<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Ürün hareketi bildirimi yapamayan kurum türlerini (örn. Geri Ödeme Kurumu) işlem rotalarından uzak tutar. */
class EnsureBildirimYetkisi
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $request->user()?->tenant;
        abort_if($tenant && ! $tenant->bildirimYapabilir(), 403, 'Kurum türünüz ürün hareketi bildirimi yapamaz; yalnızca sorgu ve raporları kullanabilirsiniz.');

        return $next($request);
    }
}
