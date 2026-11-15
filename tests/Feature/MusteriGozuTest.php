<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MusteriGozuTest extends TestCase
{
    use RefreshDatabase;

    private function kur(): array
    {
        $t = Tenant::create(['unvan' => 'Depo A', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear()]);
        $m = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici', 'aktif' => true]);
        $sa = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);

        return [$t, $m, $sa];
    }

    public function test_yonetici_musteri_gozune_gecer_ve_doner(): void
    {
        [$t, $m, $sa] = $this->kur();
        $this->actingAs($sa)->post("/yonetim/musteriler/{$t->id}/musteri-gozu")->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($m);
        $this->get('/')->assertOk()->assertSee('Müşteri gözü')->assertSee('Depo A');
        $this->get('/yonetim/musteriler')->assertForbidden();
        $this->post('/musteri-gozu/bitir')->assertRedirect(route('admin.tenants.index'));
        $this->assertAuthenticatedAs($sa);
    }

    public function test_musteri_gozunde_yazma_engellenir_kvkk_istenmez(): void
    {
        [$t, $m, $sa] = $this->kur();
        $this->actingAs($sa)->post("/yonetim/musteriler/{$t->id}/musteri-gozu");
        $this->get('/')->assertOk(); // kvkk onaysız olsa da yönlendirmez
        $this->post('/kvkk-onay')->assertSessionHas('hata');
        $this->assertNull($t->fresh()->kvkk_onay_tarihi);
    }

    public function test_musteri_kullanici_baslatamaz(): void
    {
        [$t, $m] = $this->kur();
        $t->update(['kvkk_onay_tarihi' => now()]);
        $this->actingAs($m)->post("/yonetim/musteriler/{$t->id}/musteri-gozu")->assertForbidden();
    }

    public function test_aktif_kullanici_yoksa_hata(): void
    {
        [$t, $m, $sa] = $this->kur();
        $m->update(['aktif' => false]);
        $this->actingAs($sa)->post("/yonetim/musteriler/{$t->id}/musteri-gozu")->assertSessionHas('hata');
        $this->assertAuthenticatedAs($sa);
    }
}
