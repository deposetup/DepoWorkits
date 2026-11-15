# webits → its taşıma raporu

- Tarih: 2026-11-15
- Uygulama `webits.depowork.com.tr` adresinden `its.depowork.com.tr` adresine taşındı (Plesk alt alan adı, klasörler, belge kökü, SSL, `APP_URL`).
- Sunucu klasörleri: `its.depowork.com.tr/its-app` (belge kökü `.../its-app/public`).
- Bu repo'ya `main` üzerindeki boş duruma karşı `migrate/webits` dalıyla aktarıldı. Geri dönüş noktası: `pre-webits-migration` etiketi.
- `.env`, şifre ve API anahtarı repo'ya alınmadı; yalnızca `.env.example` var.
- Bilinen kalıntı: veritabanı adı `work_webits`, kullanıcı `webitsadmin` (sunucuda, repo dışında).
- Force-push yapılmadı; repo ayarları, webhook ve deploy anahtarlarına dokunulmadı.

## Dağıtım
- Plesk Git: `main` dalına yapılan her push, GitHub webhook'u (yalnızca `push` olayı) ile `its.depowork.com.tr/its-app` klasörüne otomatik dağıtılır.
- Repoda olmayan `.env` ve `vendor` sunucuda korunur. Bağımlılık veya migration gerektiren değişikliklerden sonra sunucuda `composer install` / `php artisan migrate` ayrıca çalıştırılmalıdır.
