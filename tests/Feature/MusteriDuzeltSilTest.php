<?php

namespace Tests\Feature;

use App\Models\Hareket;
use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MusteriDuzeltSilTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $gln = '8680001000044'): Tenant
    {
        return Tenant::create(['unvan' => 'Eski Ad', 'gln' => $gln, 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
    }

    private function admin(): User
    {
        return User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
    }

    public function test_duzelt_formu_acilir_ve_bilgiler_guncellenir(): void
    {
        $t = $this->tenant();
        $this->actingAs($this->admin())->get(route('admin.tenants.index'))->assertSee('Düzelt');
        $this->get(route('admin.tenants.duzelt', $t))->assertOk()->assertSee('Müşteriyi Sil');
        $this->post(route('admin.tenants.guncelle', $t), [
            'unvan' => 'YENİ ECZA DEPOSU A.Ş.', 'gln' => '8680001000044', 'tur' => 'depo', 'il' => 'Ankara', 'eposta' => 'a@b.com',
            'lisans_baslangic' => '2026-01-01', 'lisans_bitis' => '2027-01-01', 'aktif' => '1',
        ])->assertRedirect(route('admin.tenants.duzelt', $t));
        $t->refresh();
        $this->assertSame('Yeni Ecza Deposu A.Ş.', $t->unvan);
        $this->assertSame('depo', $t->tur);
        $this->assertSame('2027-01-01', $t->lisans_bitis->toDateString());
        $this->assertFalse($t->bitince_salt_okunur); // işaretlenmedi
    }

    public function test_baska_musterinin_glni_kullanilamaz(): void
    {
        $a = $this->tenant();
        $this->tenant('8680001000055');
        $this->actingAs($this->admin())->post(route('admin.tenants.guncelle', $a), [
            'unvan' => 'X', 'gln' => '8680001000055', 'tur' => 'depo', 'lisans_baslangic' => '2026-01-01', 'lisans_bitis' => '2027-01-01',
        ])->assertSessionHasErrors('gln');
    }

    public function test_silme_gln_onayi_ister_ve_bagli_kayitlari_temizler(): void
    {
        $t = $this->tenant();
        $diger = $this->tenant('8680001000055');
        $u = User::factory()->create(['tenant_id' => $t->id]);
        $digerUser = User::factory()->create(['tenant_id' => $diger->id]);
        StokKalemi::create(['tenant_id' => $t->id, 'gtin' => '08699617650153', 'sn' => 'S1', 'durum' => 'stokta']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.tenants.sil', $t), ['onay_gln' => '1234567890123'])->assertSessionHasErrors('onay_gln');
        $this->assertNotNull($t->fresh());

        $this->post(route('admin.tenants.sil', $t), ['onay_gln' => '8680001000044'])->assertRedirect(route('admin.tenants.index'));
        $this->assertNull(Tenant::find($t->id));
        $this->assertNull(User::find($u->id));
        $this->assertSame(0, StokKalemi::count());
        $this->assertNotNull(User::find($admin->id));      // yönetici silinmez
        $this->assertNotNull(User::find($digerUser->id));  // başka müşteri etkilenmez
        $this->assertNotNull(Tenant::find($diger->id));
    }

    public function test_musteri_kullanicisi_duzelt_ve_silemez(): void
    {
        $t = $this->tenant();
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'tenant_admin']);
        $this->actingAs($u)->get(route('admin.tenants.duzelt', $t))->assertForbidden();
        $this->actingAs($u)->post(route('admin.tenants.sil', $t), ['onay_gln' => $t->gln])->assertForbidden();
        $this->assertNotNull($t->fresh());
    }
}
