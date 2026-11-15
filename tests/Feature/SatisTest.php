<?php

namespace Tests\Feature;

use App\Models\ItsCredential;
use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Its\ItsRestClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SatisTest extends TestCase
{
    use RefreshDatabase;

    private const KOD = '010869961765015317301231'.'10LOT1'."\x1D".'21SN1';

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_satis_ve_iptali_stogu_yonetir(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->actingAs($u)->post('/islem/satis', ['kodlar' => self::KOD])->assertSessionHasErrors('karsi_gln');
        $this->actingAs($u)->post('/islem/satis', ['kodlar' => self::KOD, 'karsi_gln' => '8680001000012'])->assertOk()->assertSee('Başarılı');
        $this->assertSame('satis', StokKalemi::first()->durum);

        // satılmış ürün tekrar satılamaz / sarf edilemez
        $this->actingAs($u)->post('/islem/sarf', ['kodlar' => self::KOD])->assertSee('stokta');
        $this->actingAs($u)->post('/islem/satis_iptal', ['kodlar' => self::KOD])->assertOk()->assertSee('Başarılı');
        $this->assertSame('stokta', StokKalemi::first()->durum);
    }

    public function test_stokta_olmayan_satilamaz(): void
    {
        $this->actingAs($this->user())->post('/islem/satis', ['kodlar' => self::KOD, 'karsi_gln' => '8680001000012'])->assertSee('stokta');
        $this->assertSame(0, StokKalemi::count());
    }

    public function test_rest_satis_adresleri(): void
    {
        $t = $this->user()->tenant;
        ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        config(['its.yazma' => true]);
        Http::fake([
            '*/token/app/token/' => Http::response(['access_token' => 'T']),
            '*/wholesale/app/dispatch/' => Http::response(['notificationid' => 5, 'productList' => [['gtin' => '08694444444449', 'sn' => 'S1', 'uc' => '10205']]]),
            '*/wholesale/app/dispatchcancel/' => Http::response(['notificationid' => 6, 'productList' => []]),
        ]);
        $c = new ItsRestClient();
        $u = [['gtin' => '08694444444449', 'sn' => 'S1', 'bn' => 'B', 'xd' => '2030-01-01']];
        $s = $c->satis($t, $u, '8699822000019');
        $this->assertSame('5', $s->bildirimId);
        $this->assertStringContainsString('10205', $s->urunler[0]['hata']);
        Http::assertSent(fn ($q) => str_ends_with($q->url(), '/wholesale/app/dispatch/') && $q['togln'] === '8699822000019');
        $this->assertTrue($c->satisIptal($t, $u)->basarili);
    }
}
