# DepoWork İTS

Ecza depoları için Laravel tabanlı **İlaç Takip Sistemi (İTS/İEGM) entegrasyon ve lisans yönetim platformu**.

- **Domain:** its.depowork.com.tr
- **Sağlayıcı:** DEPO SETUP YAZILIM VE DANIŞMANLIK LTD. ŞTİ.

## Kapsam

- **Depo (Müşteri) Yönetimi** — her ecza deposu için GLN numarası ve İTS şifresi. Bu bilgiler
  sadece **admin** tarafından yönetilir; müşteri panelde salt-okunur görür, değişiklik için
  mutlaka talep açması gerekir (`credential_change_requests` onay akışı).
- **İTS/İEGM Entegrasyonu** — `App\Services\ItsClient` üzerinden gerçek zamanlı bildirim
  gönderimi (Alım, Satış, Devir, Eczane Satış, İhracat, Üretim, Deaktivasyon ve bunların
  iptalleri). Servis adresleri ve auth (Access Token) akışı, İTS'nin resmi
  ["RESTAPI Servisleri Kullanım Kılavuzu"](https://its.gov.tr/Content/pdf/28-06-2022_%C4%B0LA%C3%87%20TAK%C4%B0P%20S%C4%B0STEM%C4%B0%20RESTAPI%20SERV%C4%B0S%20KILAVUZU.pdf)
  baz alınarak uygulanmıştır; her bildirim türünün tam alan şeması için
  `docs/its-api-referans.md`'ye bakın.
- **Yıllık Lisanslama** — her depo için lisans dönemi tanımlanır; süre dolmadan önce
  (varsayılan: 30/14/7/1 gün kala) depo yetkilisine otomatik hatırlatma maili gönderilir
  (`licenses:check-expirations` zamanlanmış görevi).
- **Roller** — `admin` ve `customer`.

## Gereksinimler

- PHP ^8.3, Laravel ^13
- Node/npm **gerekmez** — arayüz düz Blade + inline CSS ile yapılıyor, ayrı bir asset
  derleme adımı yok.
- **Plesk'te document root, `public/` klasörüne ayarlanmalıdır** (Laravel'in front
  controller'ı `public/index.php`'dedir; proje kökü değil).

## Kurulum (sunucu tarafı)

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan storage:link
```

Zamanlanmış görevler için sunucu cron'una tek satır eklenir:

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Dağıtım

Bu depo, Plesk üzerinde **Git entegrasyonu** ile `its.depowork.com.tr` alanına bağlıdır.
`main` şubesine yapılan her push, otomatik olarak sunucuya dağıtılır.
