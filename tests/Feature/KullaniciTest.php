<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KullaniciTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(): Tenant
    {
        return Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
    }

    public function test_superadmin_musteriye_kullanici_ekler(): void
    {
        $t = $this->tenant();
        $sa = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        $this->actingAs($sa)->post("/yonetim/musteriler/{$t->id}/kullanicilar", ['name' => 'A', 'email' => 'a@x.com', 'rol' => 'kullanici', 'password' => 'Sifre12345'])->assertSessionHasNoErrors();
        $u = User::where('email', 'a@x.com')->first();
        $this->assertSame($t->id, $u->tenant_id);
        $this->assertTrue($u->aktif);
        $this->assertTrue(Hash::check('Sifre12345', $u->password));
    }

    public function test_zayif_sifre_reddedilir(): void
    {
        $t = $this->tenant();
        $sa = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        $this->actingAs($sa)->post("/yonetim/musteriler/{$t->id}/kullanicilar", ['name' => 'A', 'email' => 'a@x.com', 'rol' => 'kullanici', 'password' => 'kisa'])->assertSessionHasErrors('password');
    }

    public function test_tenant_kullanicisi_kullanici_ekleyemez(): void
    {
        $t = $this->tenant();
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'tenant_admin']);
        $this->actingAs($u)->post("/yonetim/musteriler/{$t->id}/kullanicilar", [])->assertForbidden();
    }

    public function test_sifre_degistirme(): void
    {
        $t = $this->tenant();
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici', 'password' => 'EskiSifre123']);
        $this->actingAs($u)->post('/sifre', ['mevcut' => 'yanlis', 'password' => 'YeniSifre123', 'password_confirmation' => 'YeniSifre123'])->assertSessionHasErrors('mevcut');
        $this->actingAs($u)->post('/sifre', ['mevcut' => 'EskiSifre123', 'password' => 'YeniSifre123', 'password_confirmation' => 'YeniSifre123'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('YeniSifre123', $u->fresh()->password));
    }

    public function test_admin_kullanicinin_sifresini_degistirir(): void
    {
        $admin = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        $t = \App\Models\Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'tur' => 'depo', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $a = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici', 'password' => 'EskiSifre12345']);
        $b = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici', 'password' => 'DigerSifre12345']);
        $this->actingAs($admin)->get(route('admin.kullanici.index', $t))->assertOk()->assertSee('Şifre Değiştir');
        $this->post(route('admin.kullanici.sifre', [$t, $a]), ['password' => 'kisa'])->assertSessionHasErrors('password');
        $this->post(route('admin.kullanici.sifre', [$t, $a]), ['password' => 'DigerSifre12345'])->assertSessionHasErrors('password');
        $this->post(route('admin.kullanici.sifre', [$t, $a]), ['password' => 'YeniSifre12345'])->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('YeniSifre12345', $a->fresh()->password));
        $diger = \App\Models\Tenant::create(['unvan' => 'D', 'gln' => '8680001000055', 'tur' => 'depo', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear()]);
        $this->post(route('admin.kullanici.sifre', [$diger, $a]), ['password' => 'BaskaSifre12345'])->assertNotFound();
        $m = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'tenant_admin']);
        $this->actingAs($m)->post(route('admin.kullanici.sifre', [$t, $a]), ['password' => 'Hack12345678'])->assertForbidden();
    }
}
