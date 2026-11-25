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

    public function test_kilavuz_tur_yetki_tablosunu_gosterir(): void
    {
        $this->actingAs($this->user('depo'))->get('/kilavuz')->assertOk()->assertSee('Kurum türüne göre işlem yetkileri')->assertSee('Yurt Dışı İlaç Tedarikçisi');
    }

    public function test_geri_odeme_kurumu_bildirim_yapamaz_ama_sorgu_ekranlari_acik(): void
    {
        $u = $this->user('geri_odeme_kurumu');
        foreach (['kabul', 'satis', 'sarf', 'iade', 'deaktive', 'ihracat', 'devir'] as $tur) {
            $this->actingAs($u)->get('/islem/'.$tur)->assertForbidden();
        }
        $this->actingAs($u)->get('/pts')->assertForbidden();
        $this->actingAs($u)->get('/manuel-ekle')->assertForbidden();
        $this->actingAs($u)->post('/stok/toplu', ['tur' => 'satis', 'secili' => [1]])->assertForbidden();
        $this->actingAs($u)->get('/')->assertOk()->assertDontSee('Mal Kabul');
        $this->actingAs($u)->get('/durum')->assertOk();
        $this->actingAs($u)->get('/paydaslar')->assertOk();
        $this->actingAs($u)->get('/analiz')->assertOk();
    }
}
