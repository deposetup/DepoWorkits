<?php

namespace Tests\Feature;

use App\Models\PtsGonderim;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PtsDosya;
use App\Support\PtsXmlParser;
use App\Support\PtsXmlYazici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtsGonderimTest extends TestCase
{
    use RefreshDatabase;

    private const KOD = '010869961765015317301231'.'10LOT1'."\x1D".'21SN1';
    private const KOD2 = '010869961765015317301231'.'10LOT1'."\x1D".'21SN2';

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    private function satis(User $u, string $kodlar)
    {
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => $kodlar]);

        return $this->actingAs($u)->post('/islem/satis', ['kodlar' => $kodlar, 'karsi_gln' => '8680001000099']);
    }

    public function test_tam_basarili_satis_pts_onayi_ister(): void
    {
        config(['its.pts_gonderim' => true, 'its.yazma' => true]);
        $u = $this->user();
        $r = $this->satis($u, self::KOD."\n".self::KOD2);
        $r->assertOk()->assertSee('PTS verisi gönderimi')->assertSee('onaylıyorum');
        $g = PtsGonderim::first();
        $this->assertSame('bekliyor', $g->durum);
        $this->assertSame(2, $g->adet);
        $this->assertSame('8680001000099', $g->alici_gln);
    }

    public function test_hatali_satir_varsa_pts_sunulmaz(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $r = $this->actingAs($u)->post('/islem/satis', ['kodlar' => self::KOD."\n".self::KOD2, 'karsi_gln' => '8680001000099']); // 2. ürün stokta değil
        $r->assertOk()->assertSee('PTS gönderimi sunulmadı');
        $this->assertSame(0, PtsGonderim::count());
    }

    public function test_onaysiz_gonderilmez_onayla_gonderilir_ve_tek_sefer(): void
    {
        config(['its.pts_gonderim' => true, 'its.yazma' => true]);
        $u = $this->user();
        $this->satis($u, self::KOD);
        $g = PtsGonderim::first();

        $this->actingAs($u)->post(route('pts.gonder', $g))->assertSessionHasErrors('onay');
        $this->assertSame('bekliyor', $g->fresh()->durum);

        $this->actingAs($u)->post(route('pts.gonder', $g), ['onay' => 1])->assertRedirect(route('pts.gonderimler'));
        $g->refresh();
        $this->assertSame('gonderildi', $g->durum);
        $this->assertNotNull($g->transfer_id);

        $this->actingAs($u)->post(route('pts.gonder', $g), ['onay' => 1])->assertSessionHas('hata');
    }

    public function test_ozellik_kapaliyken_gonderilmez(): void
    {
        config(['its.pts_gonderim' => false, 'its.yazma' => true]);
        $u = $this->user();
        $this->satis($u, self::KOD);
        $g = PtsGonderim::first();
        $this->actingAs($u)->post(route('pts.gonder', $g), ['onay' => 1])->assertSessionHas('hata');
        $this->assertSame('bekliyor', $g->fresh()->durum);
    }

    public function test_satis_iptali_bekleyen_pts_kaydindan_cikarir(): void
    {
        $u = $this->user();
        $this->satis($u, self::KOD."\n".self::KOD2);
        $this->actingAs($u)->post('/islem/satis_iptal', ['kodlar' => self::KOD]);
        $g = PtsGonderim::first();
        $this->assertSame(1, $g->adet);
        $this->actingAs($u)->post('/islem/satis_iptal', ['kodlar' => self::KOD2]);
        $this->assertSame('vazgecildi', $g->fresh()->durum);
    }

    public function test_baska_musterinin_kaydina_erisilemez(): void
    {
        $u = $this->user();
        $this->satis($u, self::KOD);
        $g = PtsGonderim::first();
        $t2 = Tenant::create(['unvan' => 'B', 'gln' => '8680001000055', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $b = User::factory()->create(['tenant_id' => $t2->id, 'rol' => 'kullanici']);
        $this->actingAs($b)->post(route('pts.gonder', $g), ['onay' => 1])->assertNotFound();
        $this->actingAs($b)->post(route('pts.vazgec', $g))->assertNotFound();
    }

    public function test_xml_ve_zip_okuyucuyla_geri_okunur(): void
    {
        $xml = PtsXmlYazici::xml('8680001000044', '8680001000099', 'DW1', [['gtin' => '08699617650153', 'sn' => 'A&B1', 'bn' => 'LOT1', 'xd' => '2030-12-31']]);
        $zip = PtsXmlYazici::zip($xml);
        $okunan = PtsXmlParser::parse(array_values(PtsDosya::xmllerCikar($zip))[0]);
        $this->assertCount(1, $okunan['urunler']);
        $this->assertSame('A&B1', $okunan['urunler'][0]['sn']);
        $this->assertSame('8680001000099', $okunan['alici']);
        $this->assertSame('2030-12-31', $okunan['urunler'][0]['xd']);
    }

    public function test_rest_send_package_istegi_ve_kapali_koruma(): void
    {
        $t = $this->user()->tenant;
        \App\Models\ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        \Illuminate\Support\Facades\Http::fake([
            '*/token/app/token/' => \Illuminate\Support\Facades\Http::response(['access_token' => 'T']),
            '*/pts/app/SendPackage' => \Illuminate\Support\Facades\Http::response(['transferId' => 100004870, 'md5CheckSum' => 'abc']),
        ]);
        $c = new \App\Services\Its\ItsRestClient();

        config(['its.yazma' => true, 'its.pts_gonderim' => false]);
        try { $c->ptsPaketGonder($t, '8680001000099', 'ZIP'); $this->fail('kapalıyken gönderilmemeliydi'); } catch (\RuntimeException $e) { $this->assertStringContainsString('kapalı', $e->getMessage()); }
        \Illuminate\Support\Facades\Http::assertNotSent(fn ($q) => str_contains($q->url(), 'SendPackage'));

        config(['its.pts_gonderim' => true]);
        $r = $c->ptsPaketGonder($t, '8680001000099', 'ZIP');
        $this->assertSame('100004870', $r['transferId']);
        \Illuminate\Support\Facades\Http::assertSent(fn ($q) => str_ends_with($q->url(), '/pts/app/SendPackage') && $q['receiver'] === '8680001000099' && $q['file'] === base64_encode('ZIP'));
    }
}
