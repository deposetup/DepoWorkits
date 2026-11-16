<?php

namespace Tests\Feature;

use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LokasyonTest extends TestCase
{
    use RefreshDatabase;

    private const ESKI = '0108699617650153'.'17301231'.'10LOT1'."\x1D".'21SN1'; // SKT 2030-12-31

    private function user(string $gln = '8680001000044'): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => $gln, 'tur' => 'depo', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    private function kalem(User $u, string $sn, ?string $skt, ?string $lok = null): StokKalemi
    {
        return StokKalemi::create(['tenant_id' => $u->tenant_id, 'gtin' => '08699617650153', 'sn' => $sn, 'bn' => 'L1', 'skt' => $skt, 'durum' => 'stokta', 'lokasyon' => $lok]);
    }

    public function test_skt_gecenler_red_odasina_alinir_ve_listelenir(): void
    {
        $u = $this->user();
        $gecmis = $this->kalem($u, '1', now()->subDays(5)->toDateString(), 'Reyon A');
        $gecerli = $this->kalem($u, '2', now()->addYear()->toDateString(), 'Reyon A');
        $b = $this->user('8680001000055');
        $this->kalem($b, '9', now()->subDay()->toDateString());

        $this->actingAs($u)->get(route('stok.red-odasi'))->assertOk()->assertSee('Red Odası')->assertSee('08699617650153');
        $this->assertSame('Red Odası', $gecmis->fresh()->lokasyon);
        $this->assertSame('Reyon A', $gecerli->fresh()->lokasyon);
        $this->assertNull(StokKalemi::where('tenant_id', $b->tenant_id)->first()->lokasyon); // başka müşteriye dokunulmaz
    }

    public function test_karantina_odasi_ve_reyon_raf_ozeti(): void
    {
        $u = $this->user();
        $k = $this->kalem($u, '1', now()->addYear()->toDateString(), 'Reyon A');
        $this->actingAs($u)->post(route('stok.lokasyon'), ['secili' => [$k->id], 'lokasyon' => StokKalemi::KARANTINA_ODASI])->assertSessionHasNoErrors();
        $this->assertSame('Karantina Odası', $k->fresh()->lokasyon);
        $this->actingAs($u)->get(route('stok.karantina'))->assertOk()->assertSee('Karantina Odası')->assertSee('08699617650153');
        $this->actingAs($u)->get(route('stok.reyon-raf'))->assertOk()->assertSee('Karantina Odası');
        $this->actingAs($u)->get('/stok/red-odasi')->assertRedirect('/reyon-raf/red-odasi');
        $this->actingAs($u)->get('/')->assertSee('Reyon - Raf');
    }

    public function test_toplu_lokasyon_atama_skt_gecenleri_red_odasinda_birakir(): void
    {
        $u = $this->user();
        $gecmis = $this->kalem($u, '1', now()->subDay()->toDateString(), 'Red Odası');
        $gecerli = $this->kalem($u, '2', now()->addYear()->toDateString());
        $this->actingAs($u)->post(route('stok.lokasyon'), ['secili' => [$gecmis->id, $gecerli->id], 'lokasyon' => 'reyon b raf 3'])->assertSessionHasNoErrors();
        $this->assertSame('Reyon B Raf 3', $gecerli->fresh()->lokasyon);
        $this->assertSame('Red Odası', $gecmis->fresh()->lokasyon);
        $this->actingAs($u)->get(route('stok.liste'))->assertOk()->assertSee('Reyon B Raf 3')->assertSee('Lokasyon Ata');
    }

    public function test_baska_musterinin_kalemine_lokasyon_atanamaz(): void
    {
        $a = $this->user();
        $b = $this->user('8680001000055');
        $k = $this->kalem($b, '1', now()->addYear()->toDateString());
        $this->actingAs($a)->post(route('stok.lokasyon'), ['secili' => [$k->id], 'lokasyon' => 'X']);
        $this->assertNull($k->fresh()->lokasyon);
    }

    public function test_mal_kabulde_girilen_lokasyon_yazilir(): void
    {
        $u = $this->user();
        $r = $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::ESKI, 'lokasyon' => 'Reyon C']);
        $r->assertOk();
        $this->assertSame('Reyon C', StokKalemi::first()->lokasyon);
    }

    public function test_kritik_miad_sayfasi_cok_sayida_suresi_gecen_urunle_acilir(): void
    {
        $u = $this->user();
        $simdi = now();
        $satirlar = [];
        for ($i = 0; $i < 3000; $i++) {
            $satirlar[] = ['tenant_id' => $u->tenant_id, 'gtin' => '0869984475'.str_pad((string) ($i % 50), 4, '0', STR_PAD_LEFT), 'sn' => 'S'.$i, 'bn' => 'B'.($i % 7), 'skt' => now()->subDays(1 + $i % 40)->toDateString(), 'durum' => 'stokta', 'created_at' => $simdi, 'updated_at' => $simdi];
        }
        foreach (array_chunk($satirlar, 500) as $c) { \Illuminate\Support\Facades\DB::table('stok_kalemleri')->insert($c); }
        $this->kalem($u, 'Y1', now()->addDays(30)->toDateString());
        $this->actingAs($u)->get(route('kritik-miad'))->assertOk()->assertSee('Süresi Geçenler')->assertSee('3.000 adet')->assertSee('Miadı Yaklaşan');
    }
}
