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
    Route::get('/kvkk-onay', fn () => view('kvkk'))->name('kvkk.form');
    Route::post('/kvkk-onay', [AuthController::class, 'kvkkOnayla'])->name('kvkk.onay');

    // Raporlar: lisans bitse bile salt-okunur açık (tenant ayarına bağlı)
    Route::middleware('lisans:salt_okunur')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('home');
        Route::get('/stok', [StokController::class, 'index'])->name('stok');
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
        Route::post('/pts/paket/{paket}/dogrula', [DurumController::class, 'paket'])->name('pts.dogrula');
        Route::get('/pts', [PtsController::class, 'index'])->name('pts');
        Route::post('/pts/yukle', [PtsController::class, 'yukle'])->name('pts.yukle');
        Route::post('/pts/al', [PtsController::class, 'al'])->name('pts.al');
        Route::get('/pts/paket/{paket}', [PtsController::class, 'paket'])->name('pts.paket');
        Route::get('/pts/paket/{paket}/excel', [PtsController::class, 'excel'])->name('pts.excel');
        Route::post('/pts/paket/{paket}/mal-alim', [PtsController::class, 'malAlim'])->name('pts.mal-alim');
        Route::get('/pts/gonderimler', [\App\Http\Controllers\PtsGonderimController::class, 'index'])->name('pts.gonderimler');
        Route::post('/pts/gonderimler/{gonderim}/gonder', [\App\Http\Controllers\PtsGonderimController::class, 'gonder'])->name('pts.gonder');
        Route::post('/pts/gonderimler/{gonderim}/vazgec', [\App\Http\Controllers\PtsGonderimController::class, 'vazgec'])->name('pts.vazgec');
        Route::post('/kritik-miad', [StokController::class, 'ayarKaydet'])->name('kritik-miad.kaydet');
        Route::get('/stok/liste', [StokController::class, 'liste'])->name('stok.liste');
        Route::post('/stok/toplu', [StokController::class, 'toplu'])->name('stok.toplu');
        Route::get('/paydaslar', [\App\Http\Controllers\PaydasController::class, 'index'])->name('paydaslar');
        Route::get('/stok/ice-aktar', [\App\Http\Controllers\StokAktarController::class, 'form'])->name('stok.ice-aktar');
        Route::post('/stok/ice-aktar', [\App\Http\Controllers\StokAktarController::class, 'calistir'])->name('stok.ice-aktar.calistir');
        Route::get('/islem/{tur}', [IslemController::class, 'form'])->name('islem');
        Route::post('/islem/{tur}', [IslemController::class, 'calistir'])->name('islem.calistir');
    });

    Route::middleware('superadmin')->prefix('yonetim')->name('admin.')->group(function () {
        Route::get('/musteriler', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/musteriler', [TenantController::class, 'store'])->name('tenants.store');
        Route::get('/talepler', [\App\Http\Controllers\Admin\TalepController::class, 'index'])->name('talepler');
        Route::post('/talepler/{talep}/durum', [\App\Http\Controllers\Admin\TalepController::class, 'durum'])->name('talepler.durum');
        Route::get('/giris-kayitlari', [\App\Http\Controllers\Admin\GirisKayitController::class, 'index'])->name('giris-kayitlari');
        Route::get('/musteriler/{tenant}/duzelt', [TenantController::class, 'duzelt'])->name('tenants.duzelt');
        Route::post('/musteriler/{tenant}/duzelt', [TenantController::class, 'guncelle'])->name('tenants.guncelle');
        Route::post('/musteriler/{tenant}/sil', [TenantController::class, 'sil'])->name('tenants.sil');
        Route::post('/gln-sorgu', \App\Http\Controllers\Admin\GlnSorguController::class)->middleware('throttle:20,1')->name('gln-sorgu');
        Route::post('/musteriler/{tenant}/tur', [TenantController::class, 'turDegistir'])->name('tenants.tur');
        Route::post('/musteriler/{tenant}/uzat', [TenantController::class, 'uzat'])->name('tenants.uzat');
        Route::get('/musteriler/{tenant}/kullanicilar', [KullaniciController::class, 'index'])->name('kullanici.index');
        Route::post('/musteriler/{tenant}/kullanicilar', [KullaniciController::class, 'store'])->name('kullanici.store');
        Route::post('/musteriler/{tenant}/kullanicilar/{user}/aktif', [KullaniciController::class, 'aktifToggle'])->name('kullanici.aktif');
        Route::post('/musteriler/{tenant}/musteri-gozu', [\App\Http\Controllers\Admin\MusteriGozuController::class, 'basla'])->name('tenants.musteri-gozu');
        Route::get('/musteriler/{tenant}/its', [\App\Http\Controllers\Admin\ItsKimlikController::class, 'form'])->name('tenants.its');
        Route::post('/musteriler/{tenant}/its', [\App\Http\Controllers\Admin\ItsKimlikController::class, 'kaydet'])->name('tenants.its.kaydet');
        Route::post('/musteriler/{tenant}/aktif', [TenantController::class, 'aktifToggle'])->name('tenants.aktif');
    });
});
