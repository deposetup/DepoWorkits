<?php

namespace Tests\Feature;

use App\Models\ItsCredential;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Its\ItsRestClient;
use App\Support\ItsHata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DurumTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_hata_kodlari_yuklu(): void
    {
        $this->assertSame('Ürün üzerinize kayıtlıdır.', ItsHata::aciklama('60014'));
        $this->assertTrue(ItsHata::basarili('00000'));
        $this->assertGreaterThan(50, count(config('its_hata_kodlari')));
    }

    public function test_durum_ekrani_mock_ile_calisir(): void
    {
        $u = $this->user();
        $kod = '010869961765015317301231'.'10LOT1'."\x1D".'21SN1';
        $this->actingAs($u)->post('/durum', ['kodlar' => $kod])->assertOk()->assertSee('Ürün üzerinize kayıtlıdır')->assertSee('8680001000044');
    }

    public function test_rest_durum_istegi_ve_cevabi(): void
    {
        $t = $this->user()->tenant;
        ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        Http::fake([
            '*/token/app/token/' => Http::response(['access_token' => 'T']),
            '*/reference/app/check_status/' => Http::response(['notificationid' => 1, 'responseObjectList' => [['gln1' => '8680001000011', 'gln2' => '', 'gtin' => '08694444444449', 'sn' => 'S1', 'uc' => '60015']]]),
        ]);
        $r = (new ItsRestClient())->durumSorgula($t, [['gtin' => '08694444444449', 'sn' => 'S1', 'bn' => 'B', 'xd' => '2030-01-01']]);
        $this->assertSame('60015', $r[0]['uc']);
        $this->assertSame('8680001000011', $r[0]['gln1']);
        $this->assertNull($r[0]['gln2']);
        Http::assertSent(fn ($q) => str_ends_with($q->url(), '/check_status/') && $q['productList'][0]['sn'] === 'S1');
    }

    public function test_paket_dogrulama(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/pts/al', ['transfer_id' => '7']);
        $p = \App\Models\PtsPaketi::first();
        $this->actingAs($u)->post("/pts/paket/{$p->id}/dogrula")->assertOk()->assertSee('MOCK0001');
    }
}
