<?php

namespace Tests\Feature;

use App\Models\ItsCredential;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Its\ItsRestClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ItsTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(bool $kvkk = true): Tenant
    {
        return Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => $kvkk ? now() : null]);
    }

    public function test_kvkk_onaylanmadan_ekranlar_kilitli(): void
    {
        $u = User::factory()->create(['tenant_id' => $this->tenant(false)->id, 'rol' => 'kullanici']);
        $this->actingAs($u)->get('/stok')->assertRedirect('/kvkk-onay');
        $this->actingAs($u)->get('/kvkk-onay')->assertOk();
        $this->actingAs($u)->post('/kvkk-onay');
        $this->actingAs($u)->get('/stok')->assertOk();
    }

    public function test_its_kimligini_yalnizca_yonetici_girer_ve_sifreli_saklanir(): void
    {
        $t = $this->tenant();
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'tenant_admin']);
        $sa = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);

        // müşteri kullanıcısı (tenant_admin dahil) kimlik kaydedemez
        $this->actingAs($u)->post('/ayarlar/its', ['kullanici_adi' => '8680001000044', 'sifre' => 'GizliSifre1'])->assertStatus(405);
        $this->actingAs($u)->post("/yonetim/musteriler/{$t->id}/its", ['kullanici_adi' => 'x', 'sifre' => 'y'])->assertForbidden();
        $this->assertSame(0, \DB::table('its_credentials')->count());

        $this->actingAs($sa)->post("/yonetim/musteriler/{$t->id}/its", ['kullanici_adi' => '8680001000044', 'sifre' => 'GizliSifre1'])->assertSessionHasNoErrors();
        $ham = \DB::table('its_credentials')->first();
        $this->assertStringNotContainsString('GizliSifre1', $ham->sifre);
        $this->assertSame('GizliSifre1', $t->fresh()->itsCredential->sifre);
        $this->assertSame(0, \DB::table('audit_logs')->where('detay', 'like', '%GizliSifre1%')->count());

        // ne müşteri ekranı ne yönetici ekranı değeri gösterir
        $this->actingAs($u)->get('/ayarlar/its')->assertOk()->assertDontSee('GizliSifre1')->assertDontSee('8680001000044')->assertSee('Bağlantıyı Sına');
        $this->actingAs($sa)->get("/yonetim/musteriler/{$t->id}/its")->assertOk()->assertDontSee('GizliSifre1');
    }

    public function test_rest_istemci_dogru_istek_gonderir(): void
    {
        $t = $this->tenant();
        ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'kul', 'sifre' => 'sif']);
        config(['its.base_url' => 'https://itstest2.saglik.gov.tr']);
        config(['its.yazma' => true]);
        Http::fake([
            '*/token/app/token/' => Http::response(['access_token' => 'TKN', 'refresh_token' => 'R', 'token_type' => 'Bearer']),
            '*/consume/app/consume/' => Http::response(['notificationid' => 2224208, 'productList' => [['gtin' => '08694444444449', 'sn' => 'S1', 'uc' => '00000']]]),
            '*/common/app/return/' => Http::response(['fc' => '10001', 'fm' => 'Hatalı GLN']),
        ]);
        $c = new ItsRestClient();
        $urun = [['gtin' => '08694444444449', 'sn' => 'S1', 'bn' => 'B1', 'xd' => '2030-01-01']];

        $s = $c->hastaneSarf($t, $urun);
        $this->assertTrue($s->basarili);
        $this->assertSame('2224208', $s->bildirimId);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/consume/app/consume/') && $r['dt'] === 'D' && $r['fr'] === '8680001000044'
            && $r->hasHeader('Authorization', 'Bearer TKN'));

        $h = $c->malIade($t, $urun, '8680001000012');
        $this->assertFalse($h->basarili);
        $this->assertSame('10001', $h->hataKodu);
    }

    public function test_iade_karsi_gln_ister(): void
    {
        $u = User::factory()->create(['tenant_id' => $this->tenant()->id, 'rol' => 'kullanici']);
        $this->actingAs($u)->post('/islem/iade', ['kodlar' => 'x'])->assertSessionHasErrors('karsi_gln');
    }

    public function test_pts_sorgu_gercek_istegi_dogru_kurar(): void
    {
        $t = $this->tenant();
        ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        config(['its.base_url' => 'https://its2.saglik.gov.tr']);
        Http::fake([
            '*/token/app/token/' => Http::response(['access_token' => 'T']),
            '*/pts/app/search' => Http::response(['transferDetails' => [
                ['sourceGln' => '8680001000012', 'destinationGln' => '8680001000044', 'transferId' => 100006426, 'transferDate' => '2022-03-29T13:32:58.085081Z'],
                ['sourceGln' => '8680001000012', 'destinationGln' => '8680001000044', 'transferId' => 100006619, 'transferDate' => '2022-03-29T14:35:39Z'],
            ]]),
        ]);
        $r = (new ItsRestClient())->ptsSorgula($t, '2026-11-01', '2026-11-14', true, '8680001000012');
        $this->assertCount(2, $r);
        $this->assertSame('100006619', $r[1]['transferId']);
        Http::assertSent(fn ($q) => str_ends_with($q->url(), '/pts/app/search') && $q['destinationGln'] === '8680001000044'
            && $q['bringNotReceivedTransferInfo'] === 1 && $q['sourceGln'] === '8680001000012' && $q['startDate'] === '2026-11-01');
    }

    public function test_yazma_kapaliyken_bildirim_gonderilmez(): void
    {
        $t = $this->tenant();
        ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        config(['its.yazma' => false]);
        Http::fake();
        $s = (new ItsRestClient())->hastaneSarf($t, [['gtin' => '08694444444449', 'sn' => 'S1', 'bn' => 'B', 'xd' => '2030-01-01']]);
        $this->assertFalse($s->basarili);
        $this->assertSame('YAZMA-KAPALI', $s->hataKodu);
        Http::assertNothingSent();
    }

    public function test_pts_aralik_31_gunu_asamaz(): void
    {
        $u = User::factory()->create(['tenant_id' => $this->tenant()->id, 'rol' => 'kullanici']);
        $this->actingAs($u)->get('/pts?baslangic=2026-01-01&bitis=2026-03-01')->assertOk()->assertSee('en fazla 31 gün');
    }

    public function test_pts_gondericisiz_denemeler_calisan_bicimi_bulur(): void
    {
        $t = $this->tenant();
        ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        Http::fake([
            '*/token/app/token/' => Http::response(['access_token' => 'T']),
            // yalnızca boş sourceGln gönderilirse çalışır
            '*/pts/app/search' => fn ($r) => array_key_exists('sourceGln', $r->data()) && $r['sourceGln'] === ''
                ? Http::response(['transferDetails' => [['sourceGln' => '868000999', 'destinationGln' => '8680001000044', 'transferId' => 1, 'transferDate' => '2026-11-10T10:00:00Z']]])
                : Http::response(['status' => 500, 'error' => 'Internal Server Error'], 500),
        ]);
        $r = (new ItsRestClient())->ptsSorgula($t, '2026-11-09', '2026-11-14', false, null);
        $this->assertCount(1, $r);
        $this->assertSame('bos-kaynak', \Cache::get('its_pts_varyant_'.$t->id));
    }

    public function test_pts_tum_biçimler_basarisizsa_hepsini_raporlar(): void
    {
        $t = $this->tenant();
        ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        Http::fake(['*/token/app/token/' => Http::response(['access_token' => 'T']), '*/pts/app/search' => Http::response(['error' => 'Internal Server Error'], 500)]);
        $this->expectExceptionMessageMatches('/kaynaksiz.*bos-kaynak.*kaynaksiz-saatli.*bos-kaynak-saatli/s');
        (new ItsRestClient())->ptsSorgula($t, '2026-11-09', '2026-11-14', false, null);
    }
}
