<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DepoDegisimDuyuruTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $tur): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '86800010'.str_pad((string) random_int(0, 99999), 5, '0'), 'tur' => $tur, 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    private function degisim(string $olay, string $unvan, $zaman, string $tip = 'depo'): void
    {
        DB::table('paydas_degisimleri')->insert(['tip' => $tip, 'gln' => '8680001999'.random_int(100, 999), 'unvan' => $unvan, 'olay' => $olay, 'il' => 'Ankara', 'created_at' => $zaman]);
    }

    public function test_depo_musterisi_son_24_saatin_yeni_ve_kapanan_depolarini_gorur(): void
    {
        $this->degisim('yeni', 'Yeni Aciklanan Depo', now()->subHours(3));
        $this->degisim('pasif', 'Kapanan Depo', now()->subHours(20));
        $this->degisim('yeni', 'Eski Depo Kaydi', now()->subHours(30));
        $this->degisim('aktif', 'Yeniden Aktif Depo', now()->subHours(2));
        $this->degisim('yeni', 'Eczane Kaydi', now()->subHours(2), 'eczane');

        $this->actingAs($this->user('depo'))->get('/')->assertOk()
            ->assertSee('Depo listesi değişiklikleri')->assertSee('Yeni Aciklanan Depo')->assertSee('Kapanan Depo')->assertSee('Yeni eklendi')->assertSee('Kapandı (pasif)')
            ->assertDontSee('Eski Depo Kaydi')->assertDontSee('Yeniden Aktif Depo')->assertDontSee('Eczane Kaydi');
    }

    public function test_depo_olmayan_musteriler_gormez_ve_degisim_yoksa_kutu_cikmaz(): void
    {
        $this->degisim('yeni', 'Yeni Aciklanan Depo', now()->subHours(1));
        foreach (['eczane', 'hastane', 'uretici'] as $tur) {
            $this->actingAs($this->user($tur))->get('/')->assertOk()->assertDontSee('Depo listesi değişiklikleri')->assertDontSee('Yeni Aciklanan Depo');
        }
        DB::table('paydas_degisimleri')->delete();
        $this->actingAs($this->user('depo'))->get('/')->assertOk()->assertDontSee('Depo listesi değişiklikleri');
    }
}
