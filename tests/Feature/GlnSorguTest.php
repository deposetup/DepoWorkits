<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlnSorguTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
    }

    public function test_gln_ile_unvan_il_ilce_gelir(): void
    {
        $r = $this->actingAs($this->admin())->postJson(route('admin.gln-sorgu'), ['gln' => '8680001000012']);
        $r->assertOk()->assertJson(['bulundu' => true, 'unvan' => 'Test Supplier 1', 'il' => 'Ankara', 'ilce' => 'Çankaya']);

        $r = $this->postJson(route('admin.gln-sorgu'), ['gln' => '8680001000029']);
        $r->assertJson(['bulundu' => true, 'il' => 'İstanbul', 'ilce' => 'Kadıköy', 'aktif' => false]);
    }

    public function test_bulunamayan_gln_ve_hatali_giris(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->postJson(route('admin.gln-sorgu'), ['gln' => '8680009999999'])->assertJson(['bulundu' => false]);
        $this->postJson(route('admin.gln-sorgu'), ['gln' => '123'])->assertStatus(422);
    }

    public function test_yalnizca_yonetici_sorgulayabilir(): void
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'tenant_admin']);
        $this->actingAs($u)->postJson(route('admin.gln-sorgu'), ['gln' => '8680001000012'])->assertForbidden();
    }

    public function test_formlar_sorgu_betigini_icerir_ve_yeni_musteri_il_ilce_kaydeder(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.tenants.index'))->assertOk()->assertSee('glnDurum', false)->assertSee('name="ilce"', false);
        $this->post(route('admin.tenants.store'), ['unvan' => 'TEST DEPO', 'gln' => '8680001000029', 'tur' => 'depo', 'il' => 'İstanbul', 'ilce' => 'Kadıköy', 'telefon' => '02161234567',
            'lisans_baslangic' => '2026-01-01', 'lisans_bitis' => '2027-01-01'])->assertSessionHasNoErrors();
        $t = Tenant::where('gln', '8680001000029')->first();
        $this->assertSame(['Test Depo', 'İstanbul', 'Kadıköy'], [$t->unvan, $t->il, $t->ilce]);
        $this->get(route('admin.tenants.duzelt', $t))->assertOk()->assertSee('glnGetir', false);
    }
}
