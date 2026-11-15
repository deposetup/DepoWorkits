<?php

namespace Tests\Feature;

use App\Models\DestekTalebi;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestekTalebiTest extends TestCase
{
    use RefreshDatabase;

    private function kur(): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => '8680001000044', 'telefon' => '0212 000 00 00', 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_form_gorunur_ve_sifre_uyarisi_vardir(): void
    {
        $this->actingAs($this->kur())->get('/ayarlar/its')->assertOk()
            ->assertSee('Bize Yazın')->assertSee('İTS şifremi değiştirmek istiyorum')->assertSee('yazmayın')->assertSee('0212 000 00 00');
    }

    public function test_talep_kaydedilir(): void
    {
        $u = $this->kur();
        $this->actingAs($u)->post('/ayarlar/its/talep', ['konu' => 'its_sifre', 'mesaj' => 'Şifremi yeniledim.', 'telefon' => '0532 111 22 33'])
            ->assertRedirect('/ayarlar/its')->assertSessionHas('ok');
        $x = DestekTalebi::first();
        $this->assertSame([$u->tenant_id, $u->id, 'its_sifre', 'yeni'], [$x->tenant_id, $x->user_id, $x->konu, $x->durum]);
        $this->actingAs($u)->get('/ayarlar/its')->assertSee('İletildi');
    }

    public function test_dogrulamalar(): void
    {
        $u = $this->kur();
        $this->actingAs($u)->post('/ayarlar/its/talep', ['konu' => 'hack'])->assertSessionHasErrors('konu');
        $this->actingAs($u)->post('/ayarlar/its/talep', ['konu' => 'diger'])->assertSessionHasErrors('mesaj');
        $this->actingAs($u)->post('/ayarlar/its/talep', ['konu' => 'baglanti', 'telefon' => 'abc'])->assertSessionHasErrors('telefon');
        $this->assertSame(0, DestekTalebi::count());
    }
}
