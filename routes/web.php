<?php

use App\Http\Controllers\Admin\KullaniciController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\SifreController;
use App\Http\Controllers\AnalizController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DurumController;
use App\Http\Controllers\AyarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IslemController;
use App\Http\Controllers\PtsController;
use App\Http\Controllers\StokController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/giris', [AuthController::class, 'form'])->name('login');
    Route::post('/giris', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/sifremi-unuttum', [\App\Http\Controllers\SifreTalepController::class, 'form'])->name('sifre.unuttum');
    Route::post('/sifremi-unuttum', [\App\Http\Controllers\SifreTalepController::class, 'gonder'])->middleware('throttle:5,10')->name('sifre.unuttum.gonder');
});

Route::middleware(['auth', 'musteri-gozu', 'kvkk'])->group(function () {
    Route::post('/musteri-gozu/bitir', [\App\Http\Controllers\Admin\MusteriGozuController::class, 'bitir'])->name('musteri-gozu.bitir');
    Route::post('/cikis', [AuthController::class, 'logout'])->name('logout');
    Route::get('/sifre', [SifreController::class, 'form'])->name('sifre');
    Route::post('/sifre', [SifreController::class, 'guncelle'])->name('sifre.guncelle');
    Route::view('/kilavuz', 'kilavuz')->name('kilavuz');
    Route::get('/kvkk-onay', fn () => view('kvkk'))->name('kvkk.form');
    Route::post('/kvkk-onay', [AuthController::class, 'kvkkOnayla'])->name('kvkk.onay');

    // Raporlar: lisans bitse bile salt-okunur açık (tenant ayarına bağlı)
    Route::middleware('lisans:salt_okunur')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('home');
        Route::get('/stok', [StokController::class, 'index'])->name('stok');
        Route::get('/ilac-fiyat', [\App\Http\Controllers\IlacFiyatController::class, 'index'])->name('ilac-fiyat');
        Route::get('/reyon-raf', [StokController::class, 'reyonRaf'])->name('stok.reyon-raf');
        Route::get('/reyon-raf/red-odasi', [StokController::class, 'redOdasi'])->name('stok.red-odasi');
        Route::get('/reyon-raf/karantina-odasi', [StokController::class, 'karantinaOdasi'])->name('stok.karantina');
        Route::redirect('/stok/red-odasi', '/reyon-raf/red-odasi');
        Route::get('/stok/barkod', [StokController::class, 'barkod'])->name('stok.barkod');
        Route::view('/karekod-olustur', 'stok.karekod-olustur')->name('karekod.olustur');
        Route::get('/kritik-miad', [StokController::class, 'kritikMiad'])->name('kritik-miad');
        Route::get('/analiz', [AnalizController::class, 'index'])->name('analiz');
    });

    // İTS işlemleri: lisans zorunlu (ekranlar Aşama 3'te eklenecek)
    Route::middleware('lisans')->group(function () {
        Route::get('/ayarlar/its', [AyarController::class, 'its'])->name('ayarlar.its');
        Route::post('/ayarlar/its/talep', [AyarController::class, 'talep'])->middleware('throttle:5,1')->name('ayarlar.its.talep');
        Route::post('/ayarlar/its/test', [AyarController::class, 'itsTest'])->name('ayarlar.its.test');
        Route::get('/durum', [DurumController::class, 'form'])->name('durum');
        Route::post('/durum', [DurumController::class, 'sorgula'])->name('durum.sorgula');
        Route::post('/pts/paket/{paket}/dogrula', [DurumController::class, 'paket'])->name('pts.dogrula')->middleware('bildirim');
        Route::get('/pts', [PtsController::class, 'index'])->name('pts')->middleware('bildirim');
        Route::post('/pts/yukle', [PtsController::class, 'yukle'])->name('pts.yukle')->middleware('bildirim');
        Route::post('/pts/al', [PtsController::class, 'al'])->name('pts.al')->middleware('bildirim');
        Route::post('/pts/say', [PtsController::class, 'say'])->name('pts.say')->middleware('bildirim');
        Route::get('/pts/paket/{paket}', [PtsController::class, 'paket'])->name('pts.paket')->middleware('bildirim');
        Route::get('/pts/paket/{paket}/excel', [PtsController::class, 'excel'])->name('pts.excel')->middleware('bildirim');
        Route::post('/pts/paket/{paket}/mal-alim', [PtsController::class, 'malAlim'])->name('pts.mal-alim')->middleware('bildirim');
        Route::get('/pts/gonderimler', [\App\Http\Controllers\PtsGonderimController::class, 'index'])->name('pts.gonderimler')->middleware('bildirim');
        Route::post('/pts/gonderimler/{gonderim}/gonder', [\App\Http\Controllers\PtsGonderimController::class, 'gonder'])->name('pts.gonder')->middleware('bildirim');
        Route::post('/pts/gonderimler/{gonderim}/tekrar', [\App\Http\Controllers\PtsGonderimController::class, 'tekrar'])->name('pts.tekrar')->middleware('bildirim');
        Route::post('/pts/gonderimler/{gonderim}/vazgec', [\App\Http\Controllers\PtsGonderimController::class, 'vazgec'])->name('pts.vazgec')->middleware('bildirim');
        Route::post('/kritik-miad', [StokController::class, 'ayarKaydet'])->name('kritik-miad.kaydet');
        Route::get('/manuel-ekle', [\App\Http\Controllers\ManuelEkleController::class, 'form'])->name('manuel.ekle')->middleware('bildirim');
        Route::post('/manuel-ekle', [\App\Http\Controllers\ManuelEkleController::class, 'onizle'])->name('manuel.onizle')->middleware('bildirim');
        Route::get('/stok/liste', [StokController::class, 'liste'])->name('stok.liste');
        Route::post('/stok/lokasyon', [StokController::class, 'lokasyonAta'])->name('stok.lokasyon')->middleware('bildirim');
        Route::post('/stok/toplu', [StokController::class, 'toplu'])->name('stok.toplu')->middleware('bildirim');
        Route::get('/cariler', [\App\Http\Controllers\CariController::class, 'index'])->name('cari.index');
        Route::get('/cariler/ara', [\App\Http\Controllers\CariController::class, 'ara'])->name('cari.ara');
        Route::get('/cariler/yeni', [\App\Http\Controllers\CariController::class, 'yeni'])->name('cari.yeni');
        Route::post('/cariler/yeni', [\App\Http\Controllers\CariController::class, 'olustur'])->name('cari.olustur');
        Route::get('/cariler/{cari}/duzelt', [\App\Http\Controllers\CariController::class, 'duzelt'])->name('cari.duzelt');
        Route::post('/cariler/{cari}/duzelt', [\App\Http\Controllers\CariController::class, 'guncelle'])->name('cari.guncelle');
        Route::post('/cariler', [\App\Http\Controllers\CariController::class, 'ekle'])->name('cari.ekle');
        Route::post('/cariler/{cari}/sil', [\App\Http\Controllers\CariController::class, 'sil'])->name('cari.sil');
        Route::get('/paydaslar', [\App\Http\Controllers\PaydasController::class, 'index'])->name('paydaslar');
        Route::get('/stok/ice-aktar', [\App\Http\Controllers\StokAktarController::class, 'form'])->name('stok.ice-aktar')->middleware('bildirim');
        Route::post('/stok/ice-aktar/csv', [\App\Http\Controllers\StokAktarController::class, 'csv'])->name('stok.ice-aktar.csv')->middleware('bildirim');
        Route::post('/stok/ice-aktar/csv/hazirla', [\App\Http\Controllers\StokAktarController::class, 'csvHazirla'])->name('stok.ice-aktar.csv.hazirla')->middleware('bildirim');
        Route::post('/stok/ice-aktar/dogrula/baslat', [\App\Http\Controllers\StokAktarController::class, 'dogrulaBaslat'])->name('stok.ice-aktar.dogrula.baslat')->middleware('bildirim');
        Route::post('/stok/ice-aktar/dogrula/adim', [\App\Http\Controllers\StokAktarController::class, 'dogrulaAdim'])->name('stok.ice-aktar.dogrula.adim')->middleware('bildirim');
        Route::post('/stok/ice-aktar/csv/adim', [\App\Http\Controllers\StokAktarController::class, 'csvAdim'])->name('stok.ice-aktar.csv.adim')->middleware('bildirim');
        Route::post('/stok/ice-aktar', [\App\Http\Controllers\StokAktarController::class, 'calistir'])->name('stok.ice-aktar.calistir')->middleware('bildirim');
        Route::post('/islem-sonuc/excel', [IslemController::class, 'sonucExcel'])->name('islem.sonuc-excel');
        Route::get('/islem/{tur}', [IslemController::class, 'form'])->name('islem')->middleware('bildirim');
        Route::post('/islem/{tur}', [IslemController::class, 'calistir'])->name('islem.calistir')->middleware('bildirim');
        Route::post('/islem/{tur}/sonuc', [IslemController::class, 'sonuc'])->name('islem.sonuc')->middleware('bildirim');
    });

    Route::middleware('superadmin')->prefix('yonetim')->name('admin.')->group(function () {
        Route::get('/musteriler', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/musteriler', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('/talepler', [\App\Http\Controllers\Admin\TalepController::class, 'index'])->name('talepler');
        Route::get('/ilac-recete', [\App\Http\Controllers\Admin\IlacReceteController::class, 'index'])->name('ilac-recete');
        Route::get('/ilac-fiyat', [\App\Http\Controllers\Admin\IlacFiyatController::class, 'index'])->name('ilac-fiyat');
        Route::post('/ilac-fiyat', [\App\Http\Controllers\Admin\IlacFiyatController::class, 'yukle'])->name('ilac-fiyat.yukle');
        Route::post('/ilac-recete', [\App\Http\Controllers\Admin\IlacReceteController::class, 'yukle'])->name('ilac-recete.yukle');
        Route::get('/kullanicilar', [\App\Http\Controllers\Admin\YoneticiController::class, 'index'])->name('yoneticiler');
        Route::post('/kullanicilar', [\App\Http\Controllers\Admin\YoneticiController::class, 'store'])->name('yoneticiler.store');
        Route::post('/kullanicilar/{user}/sifre', [\App\Http\Controllers\Admin\YoneticiController::class, 'sifreDegistir'])->name('yoneticiler.sifre');
        Route::post('/kullanicilar/{user}/aktif', [\App\Http\Controllers\Admin\YoneticiController::class, 'aktifToggle'])->name('yoneticiler.aktif');
        Route::post('/talepler/{talep}/durum', [\App\Http\Controllers\Admin\TalepController::class, 'durum'])->name('talepler.durum');
        Route::get('/mail-ayarlari', [\App\Http\Controllers\Admin\MailAyarController::class, 'form'])->name('mail-ayarlari');
        Route::post('/mail-ayarlari', [\App\Http\Controllers\Admin\MailAyarController::class, 'kaydet'])->name('mail-ayarlari.kaydet');
        Route::get('/paydas-degisimleri', [\App\Http\Controllers\Admin\PaydasDegisimController::class, 'index'])->name('paydas-degisimleri');
        Route::post('/paydas-degisimleri/test-mail', [\App\Http\Controllers\Admin\PaydasDegisimController::class, 'testMail'])->middleware('throttle:3,1')->name('paydas-degisimleri.test-mail');
        Route::get('/musteriler/{tenant}/stok-csv', [\App\Http\Controllers\Admin\StokCsvController::class, 'form'])->name('stok-csv');
        Route::post('/musteriler/{tenant}/stok-csv', [\App\Http\Controllers\Admin\StokCsvController::class, 'yukle'])->name('stok-csv.yukle');
        Route::get('/giris-kayitlari', [\App\Http\Controllers\Admin\GirisKayitController::class, 'index'])->name('giris-kayitlari');
        Route::get('/musteriler/{tenant}/duzelt', [TenantController::class, 'duzelt'])->name('tenants.duzelt');
        Route::post('/musteriler/{tenant}/duzelt', [TenantController::class, 'guncelle'])->name('tenants.guncelle');
        Route::post('/musteriler/{tenant}/sil', [TenantController::class, 'sil'])->name('tenants.sil');
        Route::post('/gln-sorgu', \App\Http\Controllers\Admin\GlnSorguController::class)->middleware('throttle:20,1')->name('gln-sorgu');
        Route::post('/musteriler/{tenant}/tur', [TenantController::class, 'turDegistir'])->name('tenants.tur');
        Route::post('/musteriler/{tenant}/kalan-gun', [TenantController::class, 'kalanAyarla'])->name('tenants.kalan');
        Route::post('/musteriler/{tenant}/uzat', [TenantController::class, 'uzat'])->name('tenants.uzat');
        Route::get('/musteriler/{tenant}/kullanicilar', [KullaniciController::class, 'index'])->name('kullanici.index');
        Route::post('/musteriler/{tenant}/kullanicilar', [KullaniciController::class, 'store'])->name('kullanici.store');
        Route::post('/musteriler/{tenant}/kullanicilar/{user}/sifre', [KullaniciController::class, 'sifreDegistir'])->name('kullanici.sifre');
        Route::post('/musteriler/{tenant}/kullanicilar/{user}/aktif', [KullaniciController::class, 'aktifToggle'])->name('kullanici.aktif');
        Route::post('/musteriler/{tenant}/musteri-gozu', [\App\Http\Controllers\Admin\MusteriGozuController::class, 'basla'])->name('tenants.musteri-gozu');
        Route::get('/musteriler/{tenant}/its', [\App\Http\Controllers\Admin\ItsKimlikController::class, 'form'])->name('tenants.its');
        Route::post('/musteriler/{tenant}/its', [\App\Http\Controllers\Admin\ItsKimlikController::class, 'kaydet'])->name('tenants.its.kaydet');
        Route::post('/musteriler/{tenant}/pts-havuzu-sifirla', [TenantController::class, 'ptsHavuzuSifirla'])->name('tenants.pts-sifirla');
        Route::post('/musteriler/{tenant}/aktif', [TenantController::class, 'aktifToggle'])->name('tenants.aktif');
    });
});
