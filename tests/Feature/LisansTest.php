<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LisansTest extends TestCase
{
    use RefreshDatabase;

    private function kullanici(string $bitis): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subYear(), 'lisans_bitis' => $bitis, 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'tenant_admin']);
    }

    public function test_aktif_lisans_ana_sayfayi_gorur(): void
    {
        $this->actingAs($this->kullanici(now()->addDays(10)->toDateString()))->get('/')->assertOk()->assertSee('Kalan Süre');
    }

    public function test_suresi_dolan_rapor_salt_okunur_acik(): void
    {
        $this->actingAs($this->kullanici(now()->subDay()->toDateString()))->get('/')->assertOk()->assertSee('Lisans süresi doldu');
    }

    public function test_misafir_girise_yonlenir(): void
    {
        $this->get('/')->assertRedirect('/giris');
    }

    public function test_tenant_kullanicisi_yonetim_paneline_giremez(): void
    {
        $this->actingAs($this->kullanici(now()->addDay()->toDateString()))->get('/yonetim/musteriler')->assertForbidden();
    }

    public function test_hatali_giris_kilitler(): void
    {
        $u = User::factory()->create(['password' => 'dogru-sifre-1']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/giris', ['email' => $u->email, 'password' => 'yanlis']);
        }
        $this->post('/giris', ['email' => $u->email, 'password' => 'dogru-sifre-1'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
