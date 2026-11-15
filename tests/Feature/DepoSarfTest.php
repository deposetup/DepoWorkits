<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepoSarfTest extends TestCase
{
    use RefreshDatabase;

    private const KOD = '010869961765015317301231'.'10LOT1'."\x1D".'21SN1';

    private function user(string $tur): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'tur' => $tur, 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_depo_menude_sarf_gormez_ve_ekranlara_giremez(): void
    {
        $u = $this->user('depo');
        $this->actingAs($u)->get('/')->assertOk()->assertDontSee('Hastane Sarf')->assertDontSee('Sarf İptal');
        $this->actingAs($u)->get('/islem/sarf')->assertForbidden();
        $this->actingAs($u)->get('/islem/sarf_iptal')->assertForbidden();
        $this->actingAs($u)->get('/stok/liste')->assertOk()->assertDontSee('Hastane Sarf');
    }

    public function test_depo_sarf_bildirimi_gonderemez(): void
    {
        $u = $this->user('depo');
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->actingAs($u)->post('/islem/sarf', ['kodlar' => self::KOD])->assertForbidden();
        $this->assertSame('stokta', \App\Models\StokKalemi::first()->durum);
        $this->actingAs($u)->post('/stok/toplu', ['tur' => 'sarf', 'secili' => [\App\Models\StokKalemi::first()->id]])->assertForbidden();
        $this->assertSame(0, \App\Models\Hareket::where('tur', 'sarf')->count());
    }

    public function test_hastane_ve_eczane_sarf_yapabilir(): void
    {
        foreach (['hastane', 'eczane'] as $tur) {
            \App\Models\StokKalemi::query()->delete();
            Tenant::query()->delete();
            $u = $this->user($tur);
            $this->actingAs($u)->get('/')->assertSee('Hastane Sarf');
            $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
            $this->actingAs($u)->post('/islem/sarf', ['kodlar' => self::KOD])->assertOk()->assertSee('Başarılı');
        }
    }

    public function test_yonetici_musteri_turunu_degistirebilir(): void
    {
        $u = $this->user('eczane');
        $admin = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        $this->actingAs($admin)->post(route('admin.tenants.tur', $u->tenant), ['tur' => 'depo'])->assertRedirect();
        $this->assertSame('depo', $u->tenant->fresh()->tur);
        $this->actingAs($u->fresh())->get('/islem/sarf')->assertForbidden();
    }

    public function test_depo_mal_devir_gormez_ve_kullanamaz_hastane_kullanir(): void
    {
        $u = $this->user('depo');
        $this->actingAs($u)->get('/')->assertOk()->assertDontSee('Mal Devir');
        $this->actingAs($u)->get('/islem/devir')->assertForbidden();
        $this->actingAs($u)->get('/islem/devir_iptal')->assertForbidden();
        $this->actingAs($u)->post('/islem/devir', ['kodlar' => self::KOD, 'karsi_gln' => '8680001000012'])->assertForbidden();
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->actingAs($u)->post('/stok/toplu', ['tur' => 'devir', 'secili' => [\App\Models\StokKalemi::first()->id], 'karsi_gln' => '8680001000012'])->assertForbidden();
        $this->assertSame('stokta', \App\Models\StokKalemi::first()->durum);
        $this->actingAs($u)->get('/stok/liste')->assertOk()->assertDontSee('Mal Devir');

        \App\Models\StokKalemi::query()->delete();
        \App\Models\Tenant::query()->delete();
        $h = $this->user('hastane');
        $this->actingAs($h)->get('/')->assertSee('Mal Devir');
        $this->actingAs($h)->get('/islem/devir')->assertOk();
    }
}
