<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bakanlık test kullanıcısı gelince .env içinde ITS_DRIVER=rest yapılır.
        $this->app->bind(\App\Services\Its\ItsClient::class, fn () => match (config('its.driver')) {
            'rest' => new \App\Services\Its\ItsRestClient(),
            default => new \App\Services\Its\MockItsClient(),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFive();
        \App\Support\MailAyari::uygula(); // Yönetim > Mail Ayarları (varsa .env değerlerinin yerine geçer)
    }
}
