<?php

namespace Tests\Feature;

use App\Models\ItsCredential;
use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DevirTest extends TestCase
{
    use RefreshDatabase;

    private const KOD = '010869961765015317301231'.'10LOT1'."\x1D".'21SN1';

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_devir_ve_iptali_stogu_yonetir(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->actingAs($u)->post('/islem/devir', ['kodlar' => self::KOD])->assertSessionHasErrors('karsi_gln');
        $this->actingAs($u)->post('/islem/devir', ['kodlar' => self::KOD, 'karsi_gln' => '8680001000012'])->assertOk()->assertSee('Başarılı');
        $this->assertSame('devir', StokKalemi::first()->durum);
        $this->actingAs($u)->post('/islem/sarf', ['kodlar' => self::KOD])->assertSee('stokta');
        $this->actingAs($u)->post('/islem/devir_iptal', ['kodlar' => self::KOD])->assertOk()->assertSee('Başarılı');
        $this->assertSame('stokta', StokKalemi::first()->durum);
        $this->actingAs($u)->get('/stok/liste?durum=devir')->assertOk();
    }

    public function test_rest_adresleri(): void
    {
        config(['its.driver' => 'rest', 'its.base_url' => 'https://its.test', 'its.yazma' => true]);
        $u = $this->user();
        ItsCredential::create(['tenant_id' => $u->tenant_id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        StokKalemi::create(['tenant_id' => $u->tenant_id, 'gtin' => '08699617650153', 'sn' => 'SN1', 'bn' => 'LOT1', 'skt' => '2030-12-31', 'durum' => 'stokta']);
        Http::fake([
            '*/token/app/token/' => Http::response(['access_token' => 'T']),
            '*/common/app/transfer/' => Http::response(['notification_id' => 'N1', 'productList' => [['gtin' => '08699617650153', 'sn' => 'SN1', 'uc' => '00000']]]),
        ]);
        $this->actingAs($u)->post('/islem/devir', ['kodlar' => self::KOD, 'karsi_gln' => '8680001000012'])->assertSee('Başarılı');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/common/app/transfer/') && $r['togln'] === '8680001000012');
    }

    public function test_deaktivasyon_iptal(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->actingAs($u)->post('/islem/deaktive', ['kodlar' => self::KOD, 'sebep' => '40'])->assertSee('Başarılı');
        $this->assertSame('deaktive', StokKalemi::first()->durum);
        $this->actingAs($u)->post('/islem/deaktive_iptal', ['kodlar' => self::KOD])->assertSessionHasErrors('sebep');
        $this->actingAs($u)->post('/islem/deaktive_iptal', ['kodlar' => self::KOD, 'sebep' => '40'])->assertOk()->assertSee('Başarılı');
        $this->assertSame('stokta', StokKalemi::first()->durum);
        $this->actingAs($u)->get('/stok/liste?durum=deaktive')->assertOk();
    }
}
