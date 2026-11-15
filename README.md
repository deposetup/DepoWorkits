# DepoWork ITS

Çok müşterili, süre lisanslı İTS web portalı. Laravel (PHP 8.4), Plesk üzerinde çalışır.

## Durum (Aşama 2: iskelet)
- Tenant + lisans modeli, lisans middleware'i (süre dolunca salt-okunur / engel)
- Giriş, hesap kilitleme, KVKK onayı, audit log
- SuperAdmin müşteri paneli (`/yonetim/musteriler`)
- `ItsClient` arayüzü + `MockItsClient`; İTS şifreleri `encrypted` cast ile saklanır
- GS1 karekod ayrıştırıcı (`App\Support\Gs1Parser`) birim testli

## Yerel çalıştırma
    composer install && cp .env.example .env && php artisan key:generate
    php artisan migrate --seed --seeder=DemoSeeder   # yalnızca geliştirme
    php artisan serve
    php artisan test

İTS API bilgileri: `docs/its-ozet.md`, açık sorular: `docs/acik-sorular.md`.
Gizli bilgiler yalnızca `.env` dosyasındadır, repoya girmez.
