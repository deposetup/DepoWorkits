<?php

namespace Tests\Feature;

use App\Models\Hareket;
use App\Models\PtsPaketi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnaSayfaOzetTest extends TestCase
{
    use RefreshDatabase;

    private function kur(): array
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return [$t, User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici'])];
    }

    private function h(Tenant $t, string $tur, string $gtin, $tarih = null, bool $ok = true): void
    {
        Hareket::create(['tenant_id' => $t->id, 'tur' => $tur, 'gtin' => $gtin, 'sn' => uniqid(), 'basarili' => $ok, 'created_at' => $tarih ?? now()]);
    }

    public function test_sarf_yapmayan_musteri_sarf_tablosunu_gormez(): void
    {
        [$t, $u] = $this->kur();
        $this->actingAs($u)->get('/')->assertOk()->assertDontSee('Aylık Ürün Sarf Miktarı')->assertSee('PTS Havuzu');
    }

    public function test_aylik_sarf_net_ve_cesit(): void
    {
        [$t, $u] = $this->kur();
        $this->h($t, 'sarf', '08699617650153');
        $this->h($t, 'sarf', '08699617650153');
        $this->h($t, 'sarf', '08699617650160');
        $this->h($t, 'sarf_iptal', '08699617650153');
        $this->h($t, 'sarf', '08699617650153', now()->subMonthNoOverflow()->startOfMonth()->addDay());
        $this->h($t, 'sarf', '08699617650153', now(), false); // başarısız sayılmaz
        $r = $this->actingAs($u)->get('/')->assertOk()->assertSee('Aylık Ürün Sarf Miktarı');
        $a = $r->viewData('sarf')['aylar'];
        $this->assertSame([3, 1, 2, 2], [$a[0]['sarf'], $a[0]['iptal'], $a[0]['net'], $a[0]['cesit']]);
        $this->assertSame(1, $a[1]['sarf']);
    }

    public function test_pts_havuzu_bekleyen_paketler(): void
    {
        [$t, $u] = $this->kur();
        $diger = Tenant::create(['unvan' => 'D', 'gln' => '8680001000051', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear()]);
        $p = fn ($tid, $tr, $durum, $adet) => PtsPaketi::create(['tenant_id' => $tid, 'transfer_id' => $tr, 'urun_sayisi' => $adet, 'durum' => $durum, 'xml' => '[]']);
        $p($t->id, 'A', 'indirildi', 10);
        $p($t->id, 'B', 'indirildi', 5);
        $p($t->id, 'C', 'mal_alindi', 7);
        $p($diger->id, 'Z', 'indirildi', 99);
        $v = $this->actingAs($u)->get('/')->assertOk()->viewData('pts');
        $this->assertSame(2, $v['bekleyen_paket']);
        $this->assertSame(15, $v['bekleyen_urun']);
        $this->assertSame(1, $v['son30_alinan']);
    }
}
