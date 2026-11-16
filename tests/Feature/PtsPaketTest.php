<?php

namespace Tests\Feature;

use App\Models\PtsPaketi;
use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use App\Support\PtsXmlParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PtsPaketTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_parser_farkli_bicimleri_okur(): void
    {
        $a = PtsXmlParser::parse('<transfer><sourceGLN>8680001000022</sourceGLN><productList><product><GTIN>08699617650153</GTIN><lotNumber>L1</lotNumber><expirationDate>2030-05-01T00:00:00</expirationDate><serialNumber>A1</serialNumber></product></productList></transfer>');
        $this->assertSame([['gtin' => '08699617650153', 'sn' => 'A1', 'bn' => 'L1', 'xd' => '2030-05-01']], $a['urunler']);
        $this->assertSame('8680001000022', $a['kaynak']);

        // birden çok seri numarası tek ürün düğümünde
        $b = PtsXmlParser::parse('<t><product><gtin>8699617650153</gtin><serialNumbers><serialNumber>S1</serialNumber><serialNumber>S2</serialNumber></serialNumbers></product></t>');
        $this->assertCount(2, $b['urunler']);
        $this->assertSame('08699617650153', $b['urunler'][0]['gtin']);

        // öznitelik biçimi
        $c = PtsXmlParser::parse('<t><item GTIN="08699617650153" SN="X9" BN="B" XD="2031-01-01"/></t>');
        $this->assertSame('X9', $c['urunler'][0]['sn']);
    }

    public function test_parser_bilinmeyen_yapida_ozet_dondurur_ve_xxe_reddeder(): void
    {
        $r = PtsXmlParser::parse('<a><b>1</b><b>2</b></a>');
        $this->assertSame([], $r['urunler']);
        $this->assertSame(2, $r['yapi']['b']);

        $this->expectException(\InvalidArgumentException::class);
        PtsXmlParser::parse('<?xml version="1.0"?><!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><x>&e;</x>');
    }

    public function test_paket_indir_onizle_ve_mal_alim(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/pts/al', ['transfer_id' => '100004870'])->assertRedirect();
        $p = PtsPaketi::first();
        $this->assertSame(2, $p->urun_sayisi);
        $this->assertSame('8680001000022', $p->kaynak_gln);

        $this->actingAs($u)->get("/pts/paket/{$p->id}")->assertOk()->assertSee('2 ürün bulundu')->assertSee('LOT77');
        $this->actingAs($u)->postJson("/pts/paket/{$p->id}/mal-alim", ['basla' => 1])->assertOk()
            ->assertJson(['toplam' => 2, 'islenen' => 2, 'basarili' => 2, 'hatali' => 0, 'bitti' => true]);
        $this->assertSame(2, StokKalemi::where('durum', 'stokta')->count());
        $this->assertSame('mal_alindi', $p->fresh()->durum);
    }

    public function test_sorgu_listesinde_kutu_sayisi_endpointi_adet_doner(): void
    {
        $u = $this->user();
        $this->actingAs($u)->get('/pts?baslangic='.now()->toDateString().'&bitis='.now()->toDateString())->assertOk()->assertSee('Toplam kutu')->assertSee('data-transfer="100004870"', false);
        $this->actingAs($u)->postJson('/pts/say', ['transfer_id' => '100004870'])->assertOk()->assertJson(['transfer_id' => '100004870', 'adet' => 2]);
        $this->assertSame(1, PtsPaketi::count());
        $this->actingAs($u)->postJson('/pts/say', ['transfer_id' => '100004870'])->assertOk()->assertJson(['adet' => 2]);
        $this->assertSame(1, PtsPaketi::count());
    }

    public function test_baska_musterinin_paketi_gorulmez(): void
    {
        $u1 = $this->user();
        $this->actingAs($u1)->post('/pts/al', ['transfer_id' => '1']);
        $t2 = Tenant::create(['unvan' => 'T2', 'gln' => '8680001000055', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $u2 = User::factory()->create(['tenant_id' => $t2->id, 'rol' => 'kullanici']);
        $this->actingAs($u2)->get('/pts/paket/'.PtsPaketi::first()->id)->assertNotFound();
    }

    public function test_rest_getpackage_zip_acar_ve_md5_dogrular(): void
    {
        $t = $this->user()->tenant;
        \App\Models\ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        $dosya = tempnam(sys_get_temp_dir(), 'z');
        $zip = new \ZipArchive();
        $zip->open($dosya, \ZipArchive::OVERWRITE);
        $zip->addFromString('a/paket.xml', '<t><product><GTIN>08699617650153</GTIN><serialNumber>Z1</serialNumber></product></t>');
        $zip->addFromString('okubeni.txt', 'x');
        $zip->close();
        $bayt = file_get_contents($dosya);
        \Illuminate\Support\Facades\Http::fake([
            '*/token/app/token/' => \Illuminate\Support\Facades\Http::response(['access_token' => 'T']),
            '*/pts/app/GetPackage' => \Illuminate\Support\Facades\Http::response(['fileStream' => base64_encode($bayt), 'streamMD5checksum' => md5($bayt)]),
        ]);
        $r = (new \App\Services\Its\ItsRestClient())->ptsPaketAl($t, '100004870');
        $this->assertSame(['paket.xml'], array_keys($r['xmller']));
        $this->assertTrue($r['md5_uyumlu']);
        $this->assertSame('Z1', PtsXmlParser::parse($r['xmller']['paket.xml'])['urunler'][0]['sn']);
    }
}
