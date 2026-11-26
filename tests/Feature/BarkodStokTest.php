<?php

namespace Tests\Feature;

use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarkodStokTest extends TestCase
{
    use RefreshDatabase;

    private function veri(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $diger = Tenant::create(['unvan' => 'D', 'gln' => '8680001000051', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear()]);
        $ekle = fn ($tid, $gtin, $sn, $bn, $skt, $durum = 'stokta') => StokKalemi::create(['tenant_id' => $tid, 'gtin' => $gtin, 'sn' => $sn, 'bn' => $bn, 'skt' => $skt, 'durum' => $durum]);
        $ekle($t->id, '08699617650153', 'A1', 'L1', '2030-01-31');
        $ekle($t->id, '08699617650153', 'A2', 'L1', '2030-01-31');
        $ekle($t->id, '08699617650153', 'A3', 'L2', '2031-05-31');
        $ekle($t->id, '08699617650153', 'A4', 'L2', '2031-05-31', 'sarf'); // stokta değil
        $ekle($t->id, '08699617650160', 'B1', 'L9', '2020-01-31');
        $ekle($diger->id, '08699617650153', 'Z1', 'LX', '2030-01-31'); // başka müşteri

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_gtin_bazinda_toplam_ve_parti_kirilimi(): void
    {
        $u = $this->veri();
        $this->actingAs($u)->get('/stok/barkod')->assertOk()
            ->assertSee('08699617650153')->assertSee('08699617650160')
            ->assertSee('Ürün çeşidi: <strong>2</strong>', false)
            ->assertSee('Toplam adet: <strong>4</strong>', false)
            ->assertSee('SKT geçti');
    }

    public function test_arama_ve_excel(): void
    {
        $u = $this->veri();
        $this->actingAs($u)->get('/stok/barkod?ara=650160')->assertOk()->assertSee('08699617650160')->assertDontSee('08699617650153');
        $r = $this->actingAs($u)->get('/stok/barkod?excel=1');
        $r->assertOk();
        $this->assertStringContainsString('spreadsheetml', $r->headers->get('content-type'));
    }

    public function test_miadi_gecen_adet_red_odasi_isaretiyle_gosterilir(): void
    {
        $u = $this->veri();
        $r = $this->actingAs($u)->get('/stok/barkod')->assertOk();
        $r->assertSee('SKT\'si geçen (Red Odası): <strong>1</strong>', false)->assertSee('1 adedinin son kullanma tarihi geçmiş', false)->assertSee('Red Odası');
        $r->assertSee('Tamamı SKT&#039;si geçmiş', false);
        $this->actingAs($u)->get('/stok/barkod?ara=650153')->assertDontSee('son kullanma tarihi geçmiş ve Red', false);
    }
}
