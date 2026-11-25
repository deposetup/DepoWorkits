<?php

namespace Tests\Feature;

use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IhracatTest extends TestCase
{
    use RefreshDatabase;

    private const KOD = '010869961765015317301231'.'10LOT1'."\x1D".'21SN1';

    private function user(string $tur): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '86800010'.str_pad((string) random_int(0, 99999), 5, '0'), 'tur' => $tur, 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_depo_ihracat_ve_iptali_yapar(): void
    {
        $u = $this->user('depo');
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->actingAs($u)->post('/islem/ihracat', ['kodlar' => self::KOD])->assertSessionHasErrors('aciklama');
        $this->actingAs($u)->post('/islem/ihracat', ['kodlar' => self::KOD, 'aciklama' => 'ABC Ltd Almanya'])->assertOk()->assertSee('Başarılı')->assertSee('İhracatı geri al');
        $this->assertSame('ihracat', StokKalemi::first()->durum);
        $this->actingAs($u)->get('/stok/liste?durum=ihracat')->assertOk();
        $this->actingAs($u)->post('/islem/ihracat_iptal', ['kodlar' => self::KOD])->assertOk()->assertSee('Başarılı');
        $this->assertSame('stokta', StokKalemi::first()->durum);
    }

    public function test_yetkili_turler_menude_ihracat_gorur(): void
    {
        foreach (['uretici', 'ihracatci', 'ihracata_yetkili'] as $tur) {
            $this->actingAs($this->user($tur))->get('/islem/ihracat')->assertOk();
        }
        foreach (['ruhsat_sahibi', 'geri_odeme_kurumu', 'yurtdisi_tedarikci'] as $tur) {
            $this->actingAs($this->user($tur))->get('/islem/ihracat')->assertForbidden();
        }
    }

    public function test_depo_disindakiler_ihracat_yapamaz(): void
    {
        foreach (['eczane', 'hastane'] as $tur) {
            $u = $this->user($tur);
            $this->actingAs($u)->get('/islem/ihracat')->assertForbidden();
            $this->actingAs($u)->get('/islem/ihracat_iptal')->assertForbidden();
            $this->actingAs($u)->get('/stok/liste?durum=ihracat')->assertNotFound();
        }
    }
}
