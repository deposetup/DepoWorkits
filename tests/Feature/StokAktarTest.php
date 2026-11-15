<?php

namespace Tests\Feature;

use App\Models\ItsCredential;
use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StokAktarTest extends TestCase
{
    use RefreshDatabase;

    public function test_yalnizca_son_sahibi_biz_olanlar_stoga_alinir(): void
    {
        config(['its.driver' => 'rest', 'its.base_url' => 'https://its.test']);
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001087815', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        ItsCredential::create(['tenant_id' => $t->id, 'kullanici_adi' => 'k', 'sifre' => 's']);
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
        Http::fake([
            '*/token/app/token/' => Http::response(['access_token' => 'T']),
            '*/check_status/' => Http::response(['responseObjectList' => [
                ['gln1' => '8680001087815', 'gln2' => '', 'gtin' => '08699617650153', 'sn' => 'A1', 'uc' => '00000'],
                ['gln1' => '8680001087815', 'gln2' => '8680001000012', 'gtin' => '08699617650153', 'sn' => 'A2', 'uc' => '00000'],
                ['gln1' => '8680009999999', 'gln2' => '', 'gtin' => '08699617650153', 'sn' => 'A3', 'uc' => '00000'],
            ]]),
        ]);
        $kodlar = implode("\n", array_map(fn ($sn) => '(01)08699617650153(21)'.$sn, ['A1', 'A2', 'A3']));
        $this->actingAs($u)->post('/stok/ice-aktar', ['kaynak' => 'kodlar', 'kodlar' => $kodlar])->assertOk()->assertSee('Son sahibi başka paydaş');
        $this->assertSame(['A1'], StokKalemi::pluck('sn')->all());
        $this->actingAs($u)->get('/stok/ice-aktar')->assertOk();
    }
}
