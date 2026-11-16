<?php

namespace Tests\Feature;

use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StokSiralamaTest extends TestCase
{
    use RefreshDatabase;

    private function hazirla(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
        Cache::put('ilac_adlari_'.$t->id, ['8680000000011' => 'ZETA AMPUL', '8680000000028' => 'ALFA TABLET', '8680000000035' => 'MAVI SURUP']);
        foreach ([['8680000000011', 5], ['8680000000028', 20], ['8680000000035', 1]] as [$gtin, $adet]) {
            for ($i = 1; $i <= $adet; $i++) {
                StokKalemi::create(['tenant_id' => $t->id, 'gtin' => $gtin, 'sn' => 'S'.$i, 'durum' => 'stokta', 'skt' => now()->addYear()->toDateString(), 'son_islem_at' => now()]);
            }
        }

        return $u;
    }

    public function test_ilac_adina_gore_artan_ve_azalan(): void
    {
        $u = $this->hazirla();
        $this->actingAs($u)->get('/stok?sirala=ad&yon=asc')->assertSeeInOrder(['ALFA TABLET', 'MAVI SURUP', 'ZETA AMPUL']);
        $this->actingAs($u)->get('/stok?sirala=ad&yon=desc')->assertSeeInOrder(['ZETA AMPUL', 'MAVI SURUP', 'ALFA TABLET']);
    }

    public function test_adete_gore_artan_ve_azalan(): void
    {
        $u = $this->hazirla();
        $this->actingAs($u)->get('/stok?sirala=adet&yon=asc')->assertSeeInOrder(['MAVI SURUP', 'ZETA AMPUL', 'ALFA TABLET']);
        $this->actingAs($u)->get('/stok?sirala=adet&yon=desc')->assertSeeInOrder(['ALFA TABLET', 'ZETA AMPUL', 'MAVI SURUP']);
    }

    public function test_gecersiz_siralama_degeri_hata_vermez(): void
    {
        $this->actingAs($this->hazirla())->get('/stok?sirala=drop&yon=x')->assertOk();
    }

    public function test_barkod_bazli_stokta_ad_adet_ve_skt_siralamasi(): void
    {
        $u = $this->hazirla();
        $this->actingAs($u)->get('/stok/barkod?sirala=ad&yon=asc')->assertSeeInOrder(['ALFA TABLET', 'MAVI SURUP', 'ZETA AMPUL']);
        $this->actingAs($u)->get('/stok/barkod?sirala=ad&yon=desc')->assertSeeInOrder(['ZETA AMPUL', 'MAVI SURUP', 'ALFA TABLET']);
        $this->actingAs($u)->get('/stok/barkod?sirala=adet&yon=desc')->assertSeeInOrder(['ALFA TABLET', 'ZETA AMPUL', 'MAVI SURUP']);
        $this->actingAs($u)->get('/stok/barkod?sirala=adet&yon=asc')->assertSeeInOrder(['MAVI SURUP', 'ZETA AMPUL', 'ALFA TABLET']);
        $this->actingAs($u)->get('/stok/barkod?sirala=skt&yon=desc')->assertOk();
        $this->actingAs($u)->get('/stok/barkod?sirala=zzz&yon=x')->assertOk();
    }
}
