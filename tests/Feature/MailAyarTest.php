<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Support\MailAyari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MailAyarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null, 'aktif' => true]);
    }

    private function govde(array $ek = []): array
    {
        return array_merge(['aktif' => '1', 'host' => 'smtp.ornek.com', 'port' => 587, 'sifreleme' => 'tls', 'kullanici' => 'u@ornek.com', 'sifre' => 'gizli123',
            'gonderen_adres' => 'noreply@ornek.com', 'gonderen_ad' => 'DepoWork', 'alicilar' => "a@ornek.com\nb@ornek.com; c@ornek.com"], $ek);
    }

    public function test_sadece_yonetici_gorur(): void
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);
        $m = User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
        $this->actingAs($m)->get('/yonetim/mail-ayarlari')->assertForbidden();
        $this->actingAs($this->admin())->get('/yonetim/mail-ayarlari')->assertOk()->assertSee('Mail Ayarları')->assertSee('Alıcı e-posta adresleri');
    }

    public function test_kaydeder_sifreyi_sifreler_ve_config_e_uygular(): void
    {
        $a = $this->admin();
        $this->actingAs($a)->post('/yonetim/mail-ayarlari', $this->govde())->assertRedirect(route('admin.mail-ayarlari'))->assertSessionHas('ok');
        $this->assertNotSame('gizli123', DB::table('ayarlar')->where('anahtar', 'mail.sifre')->value('deger'));

        MailAyari::uygula();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.ornek.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertNull(config('mail.mailers.smtp.scheme'));
        $this->assertSame('gizli123', config('mail.mailers.smtp.password'));
        $this->assertSame(['a@ornek.com', 'b@ornek.com', 'c@ornek.com'], MailAyari::alicilar());
        $this->actingAs($a)->get('/yonetim/mail-ayarlari')->assertDontSee('gizli123');
    }

    public function test_bos_sifre_mevcut_sifreyi_korur_ve_kapali_mod_loga_doner(): void
    {
        $a = $this->admin();
        $this->actingAs($a)->post('/yonetim/mail-ayarlari', $this->govde());
        $this->actingAs($a)->post('/yonetim/mail-ayarlari', $this->govde(['sifre' => '', 'aktif' => '0']))->assertSessionHas('ok');
        MailAyari::uygula();
        $this->assertSame('gizli123', config('mail.mailers.smtp.password'));
        $this->assertSame('log', config('mail.default'));
        $this->assertFalse(MailAyari::gonderimAcik());
    }

    public function test_gecersiz_alici_reddedilir(): void
    {
        $this->actingAs($this->admin())->post('/yonetim/mail-ayarlari', $this->govde(['alicilar' => 'gecersiz-adres']))->assertSessionHasErrors('alicilar');
        $this->actingAs($this->admin())->post('/yonetim/mail-ayarlari', $this->govde(['host' => '']))->assertSessionHasErrors('host');
        $this->assertSame(0, DB::table('ayarlar')->count());
    }
}
