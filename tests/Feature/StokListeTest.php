<?php

namespace Tests\Feature;

use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StokListeTest extends TestCase
{
    use RefreshDatabase;

    private function kurulum(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
        foreach (['A1', 'A2'] as $sn) {
            StokKalemi::create(['tenant_id' => $t->id, 'gtin' => '08699999999990', 'sn' => $sn, 'bn' => 'L1', 'skt' => now()->addYear(), 'durum' => 'stokta']);
        }

        return $u;
    }

    public function test_liste_karekodu_gosterir_ve_toplu_satis_yapar(): void
    {
        $u = $this->kurulum();
        $this->actingAs($u)->get('/stok/liste')->assertOk()->assertSee('(01)08699999999990(21)A1');

        $ids = StokKalemi::pluck('id')->all();
        $this->actingAs($u)->post('/stok/toplu', ['tur' => 'satis', 'secili' => $ids, 'karsi_gln' => '8680001000011'])->assertOk();
        $this->assertSame(2, StokKalemi::where('durum', 'satis')->count());
    }

    public function test_satis_gln_zorunlu(): void
    {
        $u = $this->kurulum();
        $this->actingAs($u)->post('/stok/toplu', ['tur' => 'satis', 'secili' => StokKalemi::pluck('id')->all()])->assertSessionHasErrors('karsi_gln');
        $this->assertSame(2, StokKalemi::where('durum', 'stokta')->count());
    }
}
