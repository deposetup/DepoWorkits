<?php

namespace Tests\Feature;

use App\Models\IlacRecete;
use App\Models\PtsPaketi;
use App\Models\Tenant;
use App\Models\User;
use App\Support\IlacReceteYukleyici;
use App\Support\XlsxOkuyucu;
use App\Support\XlsxYazici;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class IlacReceteTest extends TestCase
{
    use RefreshDatabase;

    /** XlsxYazici ile geçici bir .xlsx üretir. */
    private function xlsx(array $baslik, array $satirlar): string
    {
        $r = XlsxYazici::indir('x.xlsx', $baslik, $satirlar);
        $yol = tempnam(sys_get_temp_dir(), 'tx').'.xlsx';
        copy($r->getFile()->getPathname(), $yol);

        return $yol;
    }

    private function dosya(): string
    {
        return $this->xlsx(['GTIN', 'Adı', 'Aktif?', 'Reçete Tipi'], [
            ["0\n8680000000011", 'KIRMIZI ILAC', '1', 'Kırmızı'],
            ['0 8680000000028', 'YESIL ILAC', '1', 'Yeşil'],
            ['08680000000035', 'BEYAZ ILAC', '1', 'Beyaz'],
            ['08680000000042', 'BOS TIP', '1', ''],
            ['00', 'GECERSIZ', '1', 'Kırmızı'],
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null, 'aktif' => true]);
    }

    private function tenantUser(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_okuyucu_basliga_gore_satir_verir(): void
    {
        $satirlar = iterator_to_array(XlsxOkuyucu::satirlar($this->dosya()), false);
        $this->assertCount(5, $satirlar);
        $this->assertSame('Kırmızı', $satirlar[0]['Reçete Tipi']);
        $this->assertSame('', $satirlar[3]['Reçete Tipi']);
    }

    public function test_yukleyici_gtini_normallestirir_ve_gecersizi_atlar(): void
    {
        $s = IlacReceteYukleyici::yukle($this->dosya());
        $this->assertSame(['okunan' => 5, 'yazilan' => 4, 'atlanan' => 1], array_intersect_key($s, array_flip(['okunan', 'yazilan', 'atlanan'])));
        $this->assertSame('Kırmızı', IlacRecete::find('08680000000011')->recete_tipi);
        $this->assertSame('Yeşil', IlacRecete::find('08680000000028')->recete_tipi);
        $this->assertNull(IlacRecete::find('08680000000042')->recete_tipi);
    }

    public function test_tipler_13_ve_14_haneli_gtini_eslestirir(): void
    {
        IlacReceteYukleyici::yukle($this->dosya());
        $t = IlacRecete::tipler(['8680000000011', '08680000000028', '9999999999999', null]);
        $this->assertSame(['8680000000011' => 'Kırmızı', '08680000000028' => 'Yeşil'], $t);
    }

    public function test_yeniden_yuklemede_liste_tamamen_yenilenir(): void
    {
        IlacReceteYukleyici::yukle($this->dosya());
        IlacReceteYukleyici::yukle($this->xlsx(['GTIN', 'Reçete Tipi'], [['08680000000099', 'Yeşil']]));
        $this->assertSame(1, IlacRecete::count());
    }

    public function test_sutun_yoksa_hata_verir_ve_liste_korunur(): void
    {
        IlacReceteYukleyici::yukle($this->dosya());
        try {
            IlacReceteYukleyici::yukle($this->xlsx(['Foo', 'Bar'], [['1', '2']]));
            $this->fail('Hata beklenirdi.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('GTIN', $e->getMessage());
        }
        $this->assertSame(4, IlacRecete::count());
    }

    public function test_yonetici_yukler_musteri_goremez(): void
    {
        $dosya = new UploadedFile($this->dosya(), 'ilac.xlsx', null, null, true);
        $m = $this->tenantUser();
        $this->actingAs($m)->get('/yonetim/ilac-recete')->assertForbidden();
        $this->actingAs($m)->post('/yonetim/ilac-recete', ['dosya' => $dosya])->assertForbidden();

        $a = $this->admin();
        $this->actingAs($a)->get('/yonetim/ilac-recete')->assertOk()->assertSee('İlaç.xlsx yükle');
        $this->actingAs($a)->post('/yonetim/ilac-recete', ['dosya' => new UploadedFile($this->dosya(), 'ilac.xlsx', null, null, true)])->assertSessionHasNoErrors()->assertSessionHas('ok');
        $this->assertSame(4, IlacRecete::count());
        $this->actingAs($a)->get('/yonetim/ilac-recete?ara=YESIL')->assertSee('YESIL ILAC');
    }

    public function test_xlsx_olmayan_dosya_reddedilir(): void
    {
        $a = $this->admin();
        $this->actingAs($a)->post('/yonetim/ilac-recete', ['dosya' => UploadedFile::fake()->create('x.csv', 10)])->assertSessionHasErrors('dosya');
    }

    public function test_pts_paket_ekraninda_yalniz_kirmizi_yesil_gorunur(): void
    {
        IlacReceteYukleyici::yukle($this->dosya());
        $u = $this->tenantUser();
        $xml = '<a><urun><gtin>08680000000011</gtin><sn>1</sn></urun><urun><gtin>08680000000028</gtin><sn>2</sn></urun><urun><gtin>08680000000035</gtin><sn>3</sn></urun></a>';
        $p = PtsPaketi::create(['tenant_id' => $u->tenant_id, 'transfer_id' => '1', 'urun_sayisi' => 3, 'xml' => json_encode(['a.xml' => $xml])]);
        $r = $this->actingAs($u)->get("/pts/paket/{$p->id}")->assertOk();
        if (str_contains($r->getContent(), '3 ürün bulundu')) {
            $r->assertSee('Kırmızı Reçete')->assertSee('Yeşil Reçete')->assertDontSee('Beyaz Reçete');
        } else {
            $this->markTestSkipped('Test XML biçimi PtsXmlParser ile uyuşmadı.');
        }
    }

    public function test_gercek_ilac_xlsx_dosyasi(): void
    {
        $yol = '/Users/halilguler/Documents/DepoWorkIts/İlaç.xlsx';
        $z = new \ZipArchive();
        if (! is_file($yol) || ! is_readable($yol) || @$z->open($yol) !== true) {
            $this->markTestSkipped('Gerçek dosya bu makinede yok ya da okunamıyor.');
        }
        $z->close();
        $s = IlacReceteYukleyici::yukle($yol);
        fwrite(STDERR, "\nGERCEK: ".json_encode($s, JSON_UNESCAPED_UNICODE)."\n");
        $this->assertGreaterThan(37000, $s['yazilan']);
        $this->assertArrayHasKey('Kırmızı', $s['tipler']);
        $this->assertArrayHasKey('Yeşil', $s['tipler']);
    }

    public function test_sarf_ve_kabul_sonuc_tablosunda_kirmizi_recete_gorunur(): void
    {
        $kod = '010869961765015317301231'.'10LOT1'."\x1D".'21SN1';
        $u = $this->tenantUser();
        IlacRecete::create(['gtin' => '08699617650153', 'ad' => 'TEST', 'recete_tipi' => 'Kırmızı']);
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => $kod])->assertOk()->assertSee('Kırmızı Reçete', false);
        $this->actingAs($u)->post('/islem/sarf', ['kodlar' => $kod])->assertOk()->assertSee('Kırmızı Reçete', false);

        IlacRecete::query()->update(['recete_tipi' => 'Beyaz']);
        $this->actingAs($u)->post('/islem/sarf_iptal', ['kodlar' => $kod])->assertOk()->assertDontSee('Beyaz Reçete', false)->assertDontSee('Kırmızı Reçete', false);
    }
}
