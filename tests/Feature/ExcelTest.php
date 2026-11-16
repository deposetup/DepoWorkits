<?php

namespace Tests\Feature;

use App\Models\PtsPaketi;
use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExcelTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    private function xlsxIcerik($response): string
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()) === true);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        return $xml;
    }

    public function test_paydas_excel(): void
    {
        $r = $this->actingAs($this->user())->get('/paydaslar?sorgula=1&tip=depo&durum=hepsi&excel=1')->assertOk();
        $r->assertHeader('content-disposition');
        $xml = $this->xlsxIcerik($r);
        $this->assertStringContainsString('Test Supplier 1', $xml);
        $this->assertStringContainsString('Pasif', $xml);
    }

    public function test_stok_ve_pts_paket_excel(): void
    {
        $u = $this->user();
        StokKalemi::create(['tenant_id' => $u->tenant_id, 'gtin' => '08699617650153', 'sn' => 'S1', 'bn' => 'L1', 'skt' => '2030-12-31', 'durum' => 'stokta']);
        $xml = $this->xlsxIcerik($this->actingAs($u)->get('/stok/liste?excel=1')->assertOk());
        $this->assertStringContainsString('01086996176501531730123110L121S1', $xml);

        $paket = PtsPaketi::create(['tenant_id' => $u->tenant_id, 'transfer_id' => '123', 'urun_sayisi' => 1, 'xml' => json_encode(['a.xml' => '<?xml version="1.0"?><transfer><product><gtin>08699617650153</gtin><serialNumber>P1</serialNumber><batchNumber>B1</batchNumber><expirationDate>2030-12-31</expirationDate></product></transfer>'])]);
        $r = $this->actingAs($u)->get(route('pts.excel', $paket))->assertOk();
        $this->assertStringContainsString('P1', $this->xlsxIcerik($r));
        $this->actingAs($u)->get('/stok/liste')->assertSee('btn-success', false);
    }

    public function test_ana_sayfada_kalan_gun_gorunur(): void
    {
        $u = $this->user();
        $u->tenant->update(['lisans_bitis' => now()->addDays(20)]);
        $this->actingAs($u)->get('/')->assertSee('Kalan süre: 20 gün')->assertSee('text-bg-warning', false);
    }
}
