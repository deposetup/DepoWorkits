<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KarekodOlusturTest extends TestCase
{
    use RefreshDatabase;

    public function test_giris_gerekir_ve_sayfa_acilir(): void
    {
        $this->get('/karekod-olustur')->assertRedirect('/giris');
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'tur' => 'depo', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
        $this->actingAs($u)->get('/karekod-olustur')->assertOk()->assertSee('Karekod Oluşturucu')->assertSee('gs1datamatrix', false);
        $this->actingAs($u)->get('/')->assertSee('Karekod Oluşturucu');
    }
}
