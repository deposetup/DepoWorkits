<?php

namespace Tests\Feature;

use App\Models\PtsPaketi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PtsYukleTest extends TestCase
{
    use RefreshDatabase;

    private const XML = '<?xml version="1.0"?><transfer><sourceGln>8680001000012</sourceGln><destinationGln>8680001000044</destinationGln><product><gtin>08699617650153</gtin><serialNumber>S1</serialNumber><batchNumber>B1</batchNumber><expirationDate>2030-12-31</expirationDate></product><product><gtin>08699617650153</gtin><serialNumber>S2</serialNumber><batchNumber>B1</batchNumber><expirationDate>2030-12-31</expirationDate></product></transfer>';

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_xml_yukleme_paketi_olusturur(): void
    {
        $u = $this->user();
        $r = $this->actingAs($u)->post('/pts/yukle', ['dosya' => UploadedFile::fake()->createWithContent('paket.xml', self::XML)]);
        $paket = PtsPaketi::first();
        $r->assertRedirect(route('pts.paket', $paket));
        $this->assertSame(2, $paket->urun_sayisi);
        $this->assertSame('8680001000012', $paket->kaynak_gln);
        $this->actingAs($u)->get(route('pts.paket', $paket))->assertSee('2 ürün bulundu');
    }

    public function test_zip_yukleme_ve_ayni_dosya_tekrar_paketi_cogaltmaz(): void
    {
        $u = $this->user();
        $yol = tempnam(sys_get_temp_dir(), 'z');
        $z = new \ZipArchive(); $z->open($yol, \ZipArchive::OVERWRITE); $z->addFromString('alt/a.xml', self::XML); $z->close();
        $dosya = new UploadedFile($yol, 'paket.zip', 'application/zip', null, true);
        $this->actingAs($u)->post('/pts/yukle', ['dosya' => $dosya])->assertRedirect();
        $dosya2 = new UploadedFile($yol, 'paket.zip', 'application/zip', null, true);
        $this->actingAs($u)->post('/pts/yukle', ['dosya' => $dosya2]);
        $this->assertSame(1, PtsPaketi::count());
    }

    public function test_gecersiz_dosyalar_reddedilir(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/pts/yukle', ['dosya' => UploadedFile::fake()->createWithContent('x.exe', 'MZ')])->assertSessionHasErrors('dosya');
        $this->actingAs($u)->post('/pts/yukle', ['dosya' => UploadedFile::fake()->createWithContent('bos.xml', '<a><b>1</b></a>')])->assertSessionHasErrors('pts');
        $this->actingAs($u)->post('/pts/yukle', ['dosya' => UploadedFile::fake()->createWithContent('xxe.xml', '<?xml version="1.0"?><!DOCTYPE a [<!ENTITY x SYSTEM "file:///etc/passwd">]><a>&x;</a>')])->assertSessionHasErrors('pts');
        $this->assertSame(0, PtsPaketi::count());
    }
}
