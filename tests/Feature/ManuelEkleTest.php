<?php

namespace Tests\Feature;

use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ManuelEkleTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'tur' => 'depo', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_menude_gorunur_csv_okunur_karekod_uretilir_ve_mal_kabul_ile_stoga_alinir(): void
    {
        $u = $this->user();
        $this->actingAs($u)->get('/')->assertSee('Manuel Ekle');
        $csv = "GTIN;SN;BN;SKT\r\n08680199751130;36000024820004;405002980;2028-04-30\r\n08680199751130;36000024820000;405002980;2028-04-30\r\n08680199751130;36000024820000;405002980;2028-04-30\r\n8680199751130;1;OLD;2020-01-31\r\nBOZUK;2;B;2028-01-01\r\n";
        $r = $this->post(route('manuel.onizle'), ['dosya' => UploadedFile::fake()->createWithContent('ptsverisiolmayan.csv', $csv)])->assertOk();
        $r->assertSee('3</b> ürün okundu', false)->assertSee('(01)08680199751130(17)280430(10)405002980(21)36000024820004', false)->assertSee('SKT geçmiş')->assertSee('Satır 6: GTIN 14 hane değil');
        // geçmiş SKT'li ürün Mal Kabul listesine alınmaz
        $r->assertSee('Mal Kabul bildir (2 ürün)');

        // Mal Kabul akışı karekodları kabul eder
        $kodlar = "(01)08680199751130(17)280430(10)405002980(21)36000024820004\n(01)08680199751130(17)280430(10)405002980(21)36000024820000";
        $this->post('/islem/kabul', ['kodlar' => $kodlar, 'lokasyon' => 'Reyon A'])->assertOk()->assertSee('Başarılı');
        $this->assertSame(2, StokKalemi::where('durum', 'stokta')->count());
        $this->assertSame('Reyon A', StokKalemi::first()->lokasyon);
    }

    public function test_basliksiz_virgullu_ve_bos_girdi(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post(route('manuel.onizle'), ['metin' => '08680199751130,SN1,B1,30.04.2028'])->assertOk()->assertSee('1</b> ürün okundu', false);
        $this->post(route('manuel.onizle'), ['metin' => ''])->assertSessionHasErrors('dosya');
    }
}
