<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YoneticiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null, 'aktif' => true]);
    }

    public function test_sadece_super_admin_gorur(): void
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000099', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $m = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'tenant_admin']);
        $this->actingAs($m)->get('/yonetim/kullanicilar')->assertForbidden();
        $this->actingAs($this->admin())->get('/yonetim/kullanicilar')->assertOk()->assertSee('Yeni Yönetici Ekle');
    }

    public function test_yonetici_eklenir_ve_giris_yapabilir(): void
    {
        $a = $this->admin();
        $this->actingAs($a)->post('/yonetim/kullanicilar', ['name' => 'Ayşe Yılmaz', 'email' => 'ayse@example.com', 'password' => 'GucluSifre12'])->assertSessionHasNoErrors();
        $yeni = User::where('email', 'ayse@example.com')->first();
        $this->assertSame('super_admin', $yeni->rol);
        $this->assertNull($yeni->tenant_id);
        $this->assertTrue($yeni->aktif);

        auth()->logout();
        $this->post('/giris', ['email' => 'ayse@example.com', 'password' => 'GucluSifre12'])->assertRedirect();
        $this->assertAuthenticatedAs($yeni);
    }

    public function test_zayif_sifre_ve_tekrar_eden_eposta_reddedilir(): void
    {
        $a = $this->admin();
        $this->actingAs($a)->post('/yonetim/kullanicilar', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'kisa'])->assertSessionHasErrors('password');
        $this->actingAs($a)->post('/yonetim/kullanicilar', ['name' => 'X', 'email' => $a->email, 'password' => 'GucluSifre12'])->assertSessionHasErrors('email');
    }

    public function test_pasiflestirme_sifre_degistirme_ve_kendini_pasiflestirememe(): void
    {
        $a = $this->admin();
        $b = $this->admin();
        $this->actingAs($a)->post("/yonetim/kullanicilar/{$b->id}/aktif")->assertSessionHasNoErrors();
        $this->assertFalse($b->fresh()->aktif);
        $this->actingAs($a)->post("/yonetim/kullanicilar/{$a->id}/aktif")->assertSessionHasErrors('yonetici');
        $this->assertTrue($a->fresh()->aktif);
        $this->actingAs($a)->post("/yonetim/kullanicilar/{$b->id}/sifre", ['password' => 'YeniSifre1234'])->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('YeniSifre1234', $b->fresh()->password));
    }

    public function test_musteri_kullanicisi_bu_ekrandan_degistirilemez(): void
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000098', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $m = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
        $this->actingAs($this->admin())->post("/yonetim/kullanicilar/{$m->id}/sifre", ['password' => 'YeniSifre1234'])->assertNotFound();
        $this->actingAs($this->admin())->post("/yonetim/kullanicilar/{$m->id}/aktif")->assertNotFound();
    }

    public function test_yonetici_musteri_sayfasina_girince_hata_yerine_yonlendirilir(): void
    {
        $this->actingAs($this->admin())->get('/pts?baslangic='.now()->toDateString().'&bitis='.now()->toDateString())
            ->assertRedirect(route('admin.tenants.index'))->assertSessionHas('hata');
        $this->actingAs($this->admin())->get('/stok/liste')->assertRedirect(route('admin.tenants.index'));
    }
}
