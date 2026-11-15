<?php

namespace Tests\Feature;

use App\Models\ItsCredential;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Its\ItsRestClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaydasTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_sayfa_aktif_pasif_gosterir(): void
    {
        $u = $this->user();
        $r = $this->actingAs($u)->get('/paydaslar?sorgula=1&tip=depo&durum=hepsi')->assertOk();
        $r->assertSee('Test Supplier 1')->assertSee('Aktif')->assertSee('Pasif');
        $this->actingAs($u)->get('/paydaslar?sorgula=1&tip=depo&durum=pasif')->assertSee('Test Depo')->assertDontSee('Test Supplier 1');
    }

    public function test_rest_istemci_paydas_yanitini_cozer(): void
    {
        config(['its.base_url' => 'https://its.test']);
        Http::fake([
            '*/token/app/token/' => Http::response(['access_token' => 'T']),
            '*/reference/app/stakeholder/' => Http::response(['companyList' => [['gln' => '8680001000012', 'companyName' => 'X', 'active' => false]]]),
        ]);
        $u = $this->user();
        ItsCredential::create(['tenant_id' => $u->tenant->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        $l = (new ItsRestClient())->paydasListesi($u->tenant, 'depo', true, null);
        $this->assertSame('8680001000012', $l[0]['gln']);
        $this->assertFalse($l[0]['active']);
        $this->assertSame('depo', $l[0]['tip']);
    }
}
