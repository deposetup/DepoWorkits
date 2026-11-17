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

    public function test_dogrulamali_aktarma_adim_adim_calisir(): void
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001087815', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
        // Mock İTS: sonu 9 ile bitmeyenlerin son sahibi bu paydaş (stoğa alınır), 9 ile bitenler alınmaz
        $kodlar = implode("\n", array_map(fn ($i) => '(01)08699617650153(21)SN'.$i, range(1, 450)));
        $h = $this->actingAs($u)->postJson(route('stok.ice-aktar.dogrula.baslat'), ['kaynak' => 'kodlar', 'kodlar' => $kodlar])
            ->assertOk()->assertJsonPath('asama', 'sorgu')->assertJsonPath('b.toplam', 450)->json();
        $adim = 0;
        do {
            $d = $this->postJson(route('stok.ice-aktar.dogrula.adim'), ['token' => $h['token']])->assertOk()->json();
            $adim++;
        } while (! $d['bitti'] && $adim < 10);
        $this->assertSame(3, $adim); // 200 + 200 + 50
        $this->assertSame(450, $d['b']['islenen']);
        $this->assertSame(450, $d['alinan'] + $d['digerSay']);
        $this->assertSame($d['alinan'], StokKalemi::count());
        $this->postJson(route('stok.ice-aktar.dogrula.adim'), ['token' => $h['token']])->assertStatus(404);
    }

    public function test_pts_paketleri_once_okunur_sonra_sorgulanir(): void
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001087815', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
        $h = $this->actingAs($u)->postJson(route('stok.ice-aktar.dogrula.baslat'), ['kaynak' => 'pts'])->assertOk()->assertJsonPath('asama', 'sorgu')->json();
        $d = $this->postJson(route('stok.ice-aktar.dogrula.adim'), ['token' => $h['token']])->assertOk()->json();
        $this->assertTrue($d['bitti']);
        $this->assertSame(0, $d['alinan']);
    }
}
