<?php

namespace Tests\Feature;

use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IslemTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subYear(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'tenant_admin']);
    }

    private const KOD = '010869961765015317301231'.'10LOT1'."\x1D".'21SN1';

    public function test_kabul_sonra_sarf_stogu_gunceller(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD])->assertOk()->assertSee('Başarılı');
        $this->assertSame('stokta', StokKalemi::first()->durum);

        $this->actingAs($u)->post('/islem/sarf', ['kodlar' => self::KOD])->assertOk();
        $this->assertSame('sarf', StokKalemi::first()->durum);

        $this->actingAs($u)->post('/islem/sarf_iptal', ['kodlar' => self::KOD]);
        $this->assertSame('stokta', StokKalemi::first()->durum);
    }

    public function test_stokta_olmayan_urun_sarf_edilemez(): void
    {
        $this->actingAs($this->user())->post('/islem/sarf', ['kodlar' => self::KOD])->assertOk()->assertSee('stokta');
        $this->assertSame(0, StokKalemi::count());
    }

    public function test_ayni_urun_iki_kez_kabul_edilemez(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->actingAs($u)->post('/islem/kabul', ['kodlar' => self::KOD])->assertSee('zaten stokta');
    }

    public function test_ekranlar_acilir(): void
    {
        $u = $this->user();
        foreach (['/stok', '/kritik-miad', '/analiz', '/pts', '/islem/deaktive'] as $url) {
            $this->actingAs($u)->get($url)->assertOk();
        }
    }
}
