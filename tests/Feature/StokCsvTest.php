<?php

namespace Tests\Feature;

use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StokCsvTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $gln = '8683420314647'): User
    {
        $t = Tenant::create(['unvan' => 'M', 'gln' => $gln, 'tur' => 'depo', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    private const CSV = "GTIN;SN;BN;EXP_DATE\r\n08699844750091;40000000084036;5241001;2028-09-30\r\n08699844750091;40000000084659;5241001;2028-09-30\r\n08699844750114;1;P1;2020-01-31\r\n8699844750114;2;;\r\nBOZUK;3;P;2028-01-01\r\n08699844750091;40000000084036;5241001;2028-09-30\r\n";

    public function test_csv_stoga_alinir_gecersizler_ve_tekrarlar_ayiklanir(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post(route('stok.ice-aktar.csv'), ['dosya' => UploadedFile::fake()->createWithContent('mymed stok 8683420314647_x.csv', self::CSV)])->assertOk()->assertSee('Dosya içe aktarıldı');
        $this->assertSame(4, StokKalemi::count());
        $k = StokKalemi::where('sn', '2')->first();
        $this->assertSame('08699844750114', $k->gtin);
        $this->assertNull($k->bn);
        $this->assertSame('Red Odası', StokKalemi::where('sn', '1')->value('lokasyon')); // SKT geçmiş
        $this->assertSame('stokta', $k->durum);
        // ikinci yükleme tekrar eklemez
        $this->post(route('stok.ice-aktar.csv'), ['dosya' => UploadedFile::fake()->createWithContent('a.csv', self::CSV)])->assertOk();
        $this->assertSame(4, StokKalemi::count());
    }

    public function test_baska_glnin_dosyasi_reddedilir_ve_sarf_edilen_ezilmez(): void
    {
        $u = $this->user();
        $this->actingAs($u)->post(route('stok.ice-aktar.csv'), ['dosya' => UploadedFile::fake()->createWithContent('stok 8680001087815_x.csv', self::CSV)])->assertSessionHas('hata');
        $this->assertSame(0, StokKalemi::count());
        StokKalemi::create(['tenant_id' => $u->tenant_id, 'gtin' => '08699844750091', 'sn' => '40000000084036', 'durum' => 'satis']);
        $this->post(route('stok.ice-aktar.csv'), ['dosya' => UploadedFile::fake()->createWithContent('b.csv', self::CSV)])->assertOk();
        $this->assertSame('satis', StokKalemi::where('sn', '40000000084036')->value('durum'));
    }

    public function test_admin_musteri_adina_yukler_musteri_baskasininkini_goremez(): void
    {
        $u = $this->user();
        $admin = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        $this->actingAs($admin)->get(route('admin.stok-csv', $u->tenant))->assertOk();
        $this->post(route('admin.stok-csv.yukle', $u->tenant), ['dosya' => UploadedFile::fake()->createWithContent('x.csv', self::CSV)])->assertOk();
        $this->assertSame(4, StokKalemi::where('tenant_id', $u->tenant_id)->count());
        $this->actingAs($u)->get(route('admin.stok-csv', $u->tenant))->assertStatus(403);
    }
}
