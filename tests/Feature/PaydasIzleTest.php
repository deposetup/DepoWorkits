<?php

namespace Tests\Feature;

use App\Models\ItsCredential;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaydasIzleTest extends TestCase
{
    use RefreshDatabase;

    private function depo(): Tenant
    {
        $t = Tenant::create(['unvan' => 'D', 'gln' => '8680001000044', 'tur' => 'depo', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'u', 'sifre' => 'p']);

        return $t;
    }

    public function test_ilk_calisma_taban_olusturur_sonra_degisiklikler_kaydedilir(): void
    {
        $this->depo();
        $this->artisan('paydas:izle', ['--mailsiz' => true])->assertSuccessful();
        $this->assertSame(0, DB::table('paydas_degisimleri')->count()); // ilk sorgu bildirim üretmez
        $this->assertSame(1, DB::table('paydas_anliklari')->where('tip', 'depo')->count());

        // Mock listede "Test Depo" pasif. Önceki durumda aktifti, bir depo da listeden kalkmış sayılsın.
        DB::table('paydas_anliklari')->where('gln', '8680001000029')->update(['aktif' => true]);
        DB::table('paydas_anliklari')->insert(['tip' => 'depo', 'gln' => '8680001000099', 'unvan' => 'Eski Depo', 'aktif' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('paydas_anliklari')->where('gln', '8680001000029')->delete(); // "yeni eklendi" senaryosu

        $this->artisan('paydas:izle', ['--mailsiz' => true])->assertSuccessful();
        $olaylar = DB::table('paydas_degisimleri')->pluck('olay', 'gln');
        $this->assertSame('yeni', $olaylar['8680001000029']);
        $this->assertSame('kaldirildi', $olaylar['8680001000099']);

        // aktif -> pasif
        DB::table('paydas_anliklari')->where('gln', '8680001000029')->update(['aktif' => true]);
        $this->artisan('paydas:izle', ['--mailsiz' => true])->assertSuccessful();
        $this->assertSame('pasif', DB::table('paydas_degisimleri')->orderByDesc('id')->value('olay'));

        // değişiklik yoksa yeni kayıt yok
        $n = DB::table('paydas_degisimleri')->count();
        $this->artisan('paydas:izle', ['--mailsiz' => true])->assertSuccessful();
        $this->assertSame($n, DB::table('paydas_degisimleri')->count());
    }

    public function test_mail_ayari_yokken_bildirimler_bekletilir_ve_admin_sayfasinda_gorunur(): void
    {
        $this->depo();
        $this->artisan('paydas:izle')->assertSuccessful();
        DB::table('paydas_anliklari')->where('gln', '8680001000029')->update(['aktif' => true]);
        $this->artisan('paydas:izle')->assertSuccessful();
        $this->assertNull(DB::table('paydas_degisimleri')->value('bildirildi_at'));

        $admin = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        $this->actingAs($admin)->get(route('admin.paydas-degisimleri'))->assertOk()->assertSee('Test Depo')->assertSee('Pasife alındı')->assertSee('halilguler@deposetup.com.tr');
    }

    public function test_musteri_degisim_sayfasina_giremez(): void
    {
        $t = $this->depo();
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'tenant_admin']);
        $this->actingAs($u)->get('/yonetim/paydas-degisimleri')->assertStatus(403);
    }
}
