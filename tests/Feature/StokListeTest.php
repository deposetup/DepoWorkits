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

    public function test_liste_skt_lokasyon_ve_ilac_siralanir(): void
    {
        $u = $this->kurulum();
        $tid = $u->tenant_id;
        StokKalemi::create(['tenant_id' => $tid, 'gtin' => '08699999999991', 'sn' => 'B1', 'bn' => 'L2', 'skt' => now()->addYears(3), 'durum' => 'stokta', 'lokasyon' => 'Raf A']);
        foreach (['skt', 'lokasyon', 'ilac'] as $alan) {
            foreach (['asc', 'desc'] as $yon) {
                $this->actingAs($u)->get("/stok/liste?sirala=$alan&yon=$yon")->assertOk();
            }
        }
        $this->actingAs($u)->get('/stok/liste?sirala=skt&yon=desc')->assertOk()->assertSeeInOrder(['21B1', '21A1']);
        $this->actingAs($u)->get('/stok/liste?sirala=skt&yon=asc')->assertOk()->assertSeeInOrder(['21A1', '21B1']);
    }

    public function test_toplu_bildirim_sinirlari(): void
    {
        $u = $this->kurulum();
        // sarf için 600, diğer işlemler için 100
        $this->actingAs($u)->from('/stok/liste')->post('/stok/toplu', ['tur' => 'sarf', 'secili' => range(1, 601)])->assertSessionHasErrors('secili');
        $this->actingAs($u)->from('/stok/liste')->post('/stok/toplu', ['tur' => 'satis', 'secili' => range(1, 101), 'karsi_gln' => '8680001000011'])->assertSessionHasErrors('secili');
        $this->actingAs($u)->postJson('/stok/toplu', ['tur' => 'sarf', 'secili' => range(1, 601)])->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, '600'));
    }

    public function test_sarf_json_yaniti_doner_ve_its_e_200_luk_gruplar_gider(): void
    {
        $u = $this->kurulum();
        $u->tenant->update(['tur' => 'hastane']);
        StokKalemi::query()->delete();
        $ids = [];
        for ($i = 1; $i <= 450; $i++) {
            $ids[] = StokKalemi::create(['tenant_id' => $u->tenant_id, 'gtin' => '08699999999990', 'sn' => 'S'.$i, 'bn' => 'L1', 'skt' => now()->addYear(), 'durum' => 'stokta'])->id;
        }
        $mock = new class extends \App\Services\Its\MockItsClient {
            public array $gruplar = [];

            public function hastaneSarf(\App\Models\Tenant $tenant, array $urunler): \App\Services\Its\ItsSonuc
            {
                $this->gruplar[] = count($urunler);

                return parent::hastaneSarf($tenant, $urunler);
            }
        };
        $this->app->instance(\App\Services\Its\ItsClient::class, $mock);

        $r = $this->actingAs($u)->postJson('/stok/toplu', ['tur' => 'sarf', 'secili' => $ids])->assertOk()->assertJsonStructure(['satirlar', 'bildirim']);
        $this->assertSame([200, 200, 50], $mock->gruplar);
        $this->assertCount(450, $r->json('satirlar'));
        $this->assertSame(450, StokKalemi::where('durum', 'sarf')->count());
    }

    public function test_liste_sayfa_boyutu_secilebilir(): void
    {
        $u = $this->kurulum();
        $this->actingAs($u)->get('/stok/liste?sayfa=600')->assertOk()->assertSee('Sayfa başına')->assertSee('en fazla 600');
        $this->actingAs($u)->get('/stok/liste?sayfa=7')->assertOk();
    }

    public function test_liste_karekodu_gosterir_ve_toplu_satis_yapar(): void
    {
        $u = $this->kurulum();
        $this->actingAs($u)->get('/stok/liste')->assertOk()->assertSee('Adede göre otomatik seç')->assertSee('01' . '08699999999990' . '17' . now()->addYear()->format('ymd') . '10L121A1');

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
