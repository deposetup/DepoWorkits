<?php

namespace Tests\Feature;

use App\Models\DestekTalebi;
use App\Models\GirisKaydi;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Metin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlnGirisTest extends TestCase
{
    use RefreshDatabase;

    private function tenant(string $gln = '8680001000044'): Tenant
    {
        return Tenant::create(['unvan' => 'T', 'gln' => $gln, 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
    }

    public function test_gln_ve_sifre_ile_giris_ve_kayit(): void
    {
        $t = $this->tenant();
        $u = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici', 'email' => null, 'password' => 'GucluSifre123']);
        $this->post('/giris', ['email' => '8680001000044', 'password' => 'GucluSifre123'])->assertRedirect('/');
        $this->assertAuthenticatedAs($u);
        $k = GirisKaydi::first();
        $this->assertTrue($k->basarili);
        $this->assertSame('gln', $k->yontem);
        $this->assertSame($t->id, $k->tenant_id);
        $this->assertNotNull($k->created_at);
    }

    public function test_yanlis_sifre_ve_bilinmeyen_gln_reddedilir_ve_kaydedilir(): void
    {
        $t = $this->tenant();
        User::factory()->create(['tenant_id' => $t->id, 'password' => 'GucluSifre123']);
        $this->post('/giris', ['email' => '8680001000044', 'password' => 'yanlis'])->assertSessionHasErrors('email');
        $this->post('/giris', ['email' => '8680009999999', 'password' => 'GucluSifre123'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(2, GirisKaydi::where('basarili', false)->count());
    }

    public function test_gln_denemesi_sinirlanir(): void
    {
        $t = $this->tenant();
        User::factory()->create(['tenant_id' => $t->id, 'password' => 'GucluSifre123']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/giris', ['email' => '8680001000044', 'password' => 'x'.$i]);
        }
        $this->post('/giris', ['email' => '8680001000044', 'password' => 'GucluSifre123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_eposta_girisi_surer_ve_kaydedilir(): void
    {
        $t = $this->tenant();
        User::factory()->create(['tenant_id' => $t->id, 'email' => 'a@b.com', 'password' => 'GucluSifre123']);
        $this->post('/giris', ['email' => 'a@b.com', 'password' => 'GucluSifre123'])->assertRedirect('/');
        $this->assertSame('eposta', GirisKaydi::first()->yontem);
    }

    public function test_sifre_talebi_gln_ya_da_epostayla_acilir_ve_hesap_sizdirilmaz(): void
    {
        $t = $this->tenant();
        User::factory()->create(['tenant_id' => $t->id, 'email' => 'a@b.com']);
        $mesaj = 'Talebiniz alındı';
        $this->post('/sifremi-unuttum', ['kimlik' => '8680001000044'])->assertSessionHas('ok');
        $this->assertSame(1, DestekTalebi::where('konu', 'sifre_unuttum')->count());
        $this->post('/sifremi-unuttum', ['kimlik' => 'a@b.com'])->assertSessionHas('ok'); // 1 saat içinde tekrar açılmaz
        $this->assertSame(1, DestekTalebi::count());
        DestekTalebi::query()->delete();
        $this->post('/sifremi-unuttum', ['kimlik' => 'a@b.com'])->assertSessionHas('ok');
        $this->assertSame(1, DestekTalebi::count());
        $r = $this->post('/sifremi-unuttum', ['kimlik' => 'yok@yok.com']);
        $r->assertSessionHas('ok');
        $this->assertSame(1, DestekTalebi::count());
        $this->get('/sifremi-unuttum')->assertOk()->assertSee('Şifre talebi');
    }

    public function test_admin_talep_ve_giris_kayitlarini_gorur(): void
    {
        $t = $this->tenant();
        $admin = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        GirisKaydi::kaydet(User::factory()->create(['tenant_id' => $t->id]), 'x@y.com', 'eposta', true);
        $this->actingAs($admin)->get('/yonetim/giris-kayitlari')->assertOk()->assertSee('Giriş Kayıtları')->assertSee('x@y.com');
        $this->actingAs($admin)->get('/yonetim/talepler')->assertOk()->assertSee('Destek Talepleri');
    }

    public function test_kullanici_epostasiz_olusturulur_ve_ayni_sifre_reddedilir(): void
    {
        $t = $this->tenant();
        $admin = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        $this->actingAs($admin)->post(route('admin.kullanici.store', $t), ['name' => 'A', 'rol' => 'kullanici', 'password' => 'GucluSifre123'])->assertSessionHasNoErrors();
        $this->assertNull(User::where('tenant_id', $t->id)->first()->email);
        $this->actingAs($admin)->post(route('admin.kullanici.store', $t), ['name' => 'B', 'rol' => 'kullanici', 'password' => 'GucluSifre123'])->assertSessionHasErrors('password');
        $this->assertSame(1, User::where('tenant_id', $t->id)->count());
    }

    public function test_unvan_standardi(): void
    {
        $this->assertSame('Ecza Deposu A.Ş.', Metin::baslik('ECZA DEPOSU A.Ş.'));
        $this->assertSame('Işık Tıp Merkezi Ltd. Şti.', Metin::baslik('IŞIK TIP MERKEZİ LTD. ŞTİ.'));
        $this->assertSame('İnci Sağlık ve Danışmanlık', Metin::baslik('  İNCİ  SAĞLIK VE DANIŞMANLIK '));
        $this->assertSame('Depo-Work Ecza', Metin::baslik('depo-work ecza'));
        $t = Tenant::create(['unvan' => 'ÖZGÜR ECZA DEPOSU', 'gln' => '8680001000099', 'lisans_baslangic' => now(), 'lisans_bitis' => now()->addYear()]);
        $this->assertSame('Özgür Ecza Deposu', $t->fresh()->unvan);
    }

    public function test_yonetici_sayfasindan_olusturulan_kullanici_gln_ve_sifreyle_girer(): void
    {
        $t = $this->tenant();
        $admin = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        $this->actingAs($admin)->get(route('admin.kullanici.index', $t))->assertOk()->assertSee('GLN (8680001000044) + kendi şifresi', false)->assertSee('goz-btn', false);
        $this->post(route('admin.kullanici.store', $t), ['name' => 'Ayşe Yılmaz', 'rol' => 'tenant_admin', 'password' => 'GucluSifre123'])->assertSessionHasNoErrors();
        auth()->logout();
        $this->post('/giris', ['email' => '8680001000044', 'password' => 'GucluSifre123'])->assertRedirect('/');
        $this->assertSame('Ayşe Yılmaz', auth()->user()->name);
    }
}
