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
            $this->actingAs($u)->post('/islem/sarf', ['kodlar' => self::KOD])->assertOk()->assertSee('Başarılı')->assertDontSee('(01)', false)->assertSee('ÖRNEK İLAÇ');
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

    public function test_sonuc_ekraninda_basarisizlar_ustte_ve_excel_vardir(): void
    {
        $h = $this->user('hastane');
        $this->actingAs($h)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $kotu = '010869961765015317301231'."10LOT9\x1D".'21YOKSERI';
        $r = $this->actingAs($h)->post('/islem/sarf', ['kodlar' => self::KOD."\n".$kotu]);
        $r->assertOk()->assertSee('1 başarısız')->assertSee('1 başarılı')->assertSee('islem-sonuc/excel', false);
        $r->assertSeeInOrder(['YOKSERI', 'SN1'], false);
        $x = $this->actingAs($h)->post('/islem-sonuc/excel', ['veri' => json_encode([['kod' => self::KOD, 'durum' => 'tamam'], ['kod' => $kotu, 'durum' => 'hata', 'mesaj' => 'x']])]);
        $x->assertOk();
    }

    public function test_analiz_saatleri_istanbul_saatiyle_gosterilir(): void
    {
        $h = $this->user('hastane');
        \App\Models\Hareket::create(['tenant_id' => $h->tenant_id, 'tur' => 'sarf', 'gtin' => '08699617650153', 'sn' => 'Z1', 'basarili' => true, 'created_at' => '2026-11-16 04:51:00']);
        $this->actingAs($h)->get('/analiz')->assertOk()->assertSee('16.11.2026 07:51');
    }

    public function test_admin_pts_havuzunu_sifirlar(): void
    {
        $h = $this->user('hastane');
        $p = \App\Models\PtsPaketi::create(['tenant_id' => $h->tenant_id, 'transfer_id' => 'T1', 'urun_sayisi' => 3, 'durum' => 'indirildi', 'xml' => '<x/>']);
        $m = \App\Models\PtsPaketi::create(['tenant_id' => $h->tenant_id, 'transfer_id' => 'T2', 'urun_sayisi' => 1, 'durum' => 'mal_alindi', 'xml' => '<x/>']);
        $admin = User::factory()->create(['tenant_id' => null, 'rol' => 'super_admin']);
        $this->actingAs($admin)->post(route('admin.tenants.pts-sifirla', $h->tenant_id))->assertSessionHas('ok');
        $this->assertSame('sifirlandi', $p->fresh()->durum);
        $this->assertSame('mal_alindi', $m->fresh()->durum);
        $this->actingAs($h->fresh())->get('/')->assertOk()->assertSee('Mal Alım bekleyen paket yok');
    }

    public function test_hastane_satis_yapamaz_sarf_ve_iade_yapar(): void
    {
        $h = $this->user('hastane');
        $this->actingAs($h)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->actingAs($h)->get('/')->assertOk()->assertDontSee('Satış İptal')->assertDontSee('PTS Gönderimleri');
        $this->actingAs($h)->get('/islem/satis')->assertForbidden();
        $this->actingAs($h)->get('/islem/satis_iptal')->assertForbidden();
        $this->actingAs($h)->post('/islem/satis', ['kodlar' => self::KOD, 'karsi_gln' => '8680001000012'])->assertForbidden();
        $this->actingAs($h)->post('/stok/toplu', ['tur' => 'satis', 'secili' => [\App\Models\StokKalemi::first()->id], 'karsi_gln' => '8680001000012'])->assertForbidden();
        $this->assertSame('stokta', \App\Models\StokKalemi::first()->durum);
        $this->actingAs($h)->get('/stok/liste?durum=satis')->assertNotFound();
        $this->actingAs($h)->get('/stok/liste')->assertOk()->assertDontSee('Satıldı')->assertSee('Hastane Sarf');
        $this->actingAs($h)->get('/islem/iade')->assertOk();
        $this->actingAs($h)->get('/analiz')->assertOk()->assertDontSee('Satış İptal');
    }

    public function test_depo_ve_hastane_mal_devir_gormez_eczane_kullanir(): void
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
        $this->actingAs($h)->get('/')->assertDontSee('Mal Devir');
        $this->actingAs($h)->get('/islem/devir')->assertForbidden();
        $this->actingAs($h)->get('/islem/devir_iptal')->assertForbidden();

        \App\Models\Tenant::query()->delete();
        $e = $this->user('eczane');
        $this->actingAs($e)->get('/')->assertSee('Mal Devir');
        $this->actingAs($e)->get('/islem/devir')->assertOk();
    }

    public function test_analiz_depoda_sarf_ve_devir_alanlarini_gostermez(): void
    {
        $this->actingAs($this->user('depo'))->get('/analiz')->assertOk()->assertDontSee('Hastane Sarf')->assertDontSee('Sarf İptal')->assertDontSee('Mal Devir')->assertSee('Mal Kabul')->assertSee('Satış');
        \App\Models\Tenant::query()->delete();
        $this->actingAs($this->user('hastane'))->get('/analiz')->assertOk()->assertSee('Hastane Sarf')->assertDontSee('Mal Devir');
    }

    public function test_sarf_ekrani_durum_paneli_ve_json_yaniti(): void
    {
        $u = $this->user('hastane');
        $this->actingAs($u)->get('/islem/sarf')->assertOk()->assertSee('bildirimDurum', false)->assertSee('bdBar', false);
        $this->actingAs($u)->get('/islem/kabul')->assertOk()->assertDontSee('bildirimDurum', false);
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->actingAs($u)->postJson('/islem/sarf', ['kodlar' => self::KOD])->assertOk()
            ->assertJsonStructure(['satirlar' => [['kod', 'durum', 'mesaj']], 'bildirim'])->assertJsonPath('satirlar.0.durum', 'tamam');
    }
}
