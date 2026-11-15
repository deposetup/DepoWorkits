<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ItsCredential;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MusteriItsBilgisiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
    }

    private const TARIH = ['lisans_baslangic' => '2026-01-01', 'lisans_bitis' => '2027-01-01'];

    public function test_yeni_musteri_kaydinda_its_bilgisi_kullanici_olusmadan_kaydedilir(): void
    {
        $this->actingAs($this->admin())->post(route('admin.tenants.store'), [
            'unvan' => 'Test Depo', 'gln' => '8680001000044', 'tur' => 'depo', 'its_kullanici_adi' => '8680001000044', 'its_sifre' => 'GizliSifre!1',
        ] + self::TARIH)->assertSessionHasNoErrors();
        $t = Tenant::first();
        $this->assertSame('8680001000044', $t->itsCredential->kullanici_adi);
        $this->assertSame('GizliSifre!1', $t->itsCredential->sifre);
        $this->assertSame(0, User::where('tenant_id', $t->id)->count()); // kullanıcı oluşmadı
        $this->assertDatabaseMissing('its_credentials', ['sifre' => 'GizliSifre!1']); // şifreli saklanır
        $this->assertStringNotContainsString('GizliSifre', json_encode(AuditLog::all()->toArray()));
    }

    public function test_its_bilgisi_olmadan_da_kaydedilir_ama_yarim_girilemez(): void
    {
        $a = $this->admin();
        $this->actingAs($a)->post(route('admin.tenants.store'), ['unvan' => 'A', 'gln' => '8680001000044', 'tur' => 'depo'] + self::TARIH)->assertSessionHasNoErrors();
        $this->assertNull(Tenant::first()->itsCredential);
        $this->post(route('admin.tenants.store'), ['unvan' => 'B', 'gln' => '8680001000055', 'tur' => 'depo', 'its_kullanici_adi' => 'x'] + self::TARIH)->assertSessionHasErrors('its_sifre');
        $this->assertSame(1, Tenant::count());
    }

    public function test_duzelt_sayfasindan_its_bilgisi_girilir_ve_deger_gosterilmez(): void
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now(), 'lisans_bitis' => now()->addYear()]);
        $a = $this->admin();
        $this->actingAs($a)->get(route('admin.tenants.duzelt', $t))->assertOk()->assertSee('İTS Portal Kullanıcı Bilgileri')->assertSee('Kayıtlı bilgi yok');
        $this->from(route('admin.tenants.duzelt', $t))->post(route('admin.tenants.its.kaydet', $t), ['kullanici_adi' => 'kullanici1', 'sifre' => 'GizliSifre!2'])->assertRedirect(route('admin.tenants.duzelt', $t));
        $r = $this->get(route('admin.tenants.duzelt', $t));
        $r->assertSee('Bilgiler kayıtlı')->assertDontSee('GizliSifre!2')->assertDontSee('kullanici1');
        $this->assertSame('GizliSifre!2', ItsCredential::where('tenant_id', $t->id)->first()->sifre);
    }
}
