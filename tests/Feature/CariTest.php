<?php

namespace Tests\Feature;

use App\Models\Cari;
use App\Models\PtsGonderim;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CariTest extends TestCase
{
    use RefreshDatabase;

    private const KOD = '010869961765015317301231'.'10LOT1'."\x1D".'21SN1';

    private function user(string $tur = 'depo', string $gln = '8680001000044'): User
    {
        $t = Tenant::create(['unvan' => 'T', 'gln' => $gln, 'tur' => $tur, 'lisans_baslangic' => now()->subDay(), 'lisans_bitis' => now()->addYear(), 'kvkk_onay_tarihi' => now()]);

        return User::factory()->create(['tenant_id' => $t->id, 'rol' => 'kullanici']);
    }

    public function test_paydas_listesinden_cari_eklenir_ve_cari_ekli_gorunur(): void
    {
        foreach (['depo', 'hastane'] as $i => $tur) {
            $u = $this->user($tur, $i ? '8680001000055' : '8680001000044');
            $sayfa = $this->actingAs($u)->get('/paydaslar?sorgula=1&tip=depo&durum=hepsi');
            $sayfa->assertOk()->assertSee('Cari Ekle')->assertDontSee('Cari ekli');
            $this->post(route('cari.ekle'), ['gln' => '8680001000029', 'unvan' => 'TEST DEPO', 'tip' => 'depo', 'il' => 'İstanbul', 'ilce' => 'Kadıköy', 'aktif' => 0])->assertSessionHasNoErrors();
            $this->get('/paydaslar?sorgula=1&tip=depo&durum=hepsi')->assertSee('Cari ekli');
        }
        $this->assertSame(2, Cari::count()); // her müşterinin kendi carisi
        $this->assertSame('Test Depo', Cari::first()->unvan);
    }

    public function test_cari_listesi_arama_ve_musteri_izolasyonu(): void
    {
        $a = $this->user();
        Cari::create(['tenant_id' => $a->tenant_id, 'gln' => '8680001000029', 'unvan' => 'Ankara Ecza Deposu', 'aktif' => true]);
        $b = $this->user('hastane', '8680001000055');
        Cari::create(['tenant_id' => $b->tenant_id, 'gln' => '8680001000012', 'unvan' => 'Başka Müşterinin Carisi', 'aktif' => true]);

        $this->actingAs($a)->getJson('/cariler/ara?q=ankara')->assertOk()->assertJsonCount(1)->assertJsonFragment(['gln' => '8680001000029']);
        $this->getJson('/cariler/ara?q=Başka')->assertJsonCount(0);
        $this->get('/cariler')->assertOk()->assertSee('Ankara Ecza Deposu')->assertDontSee('Başka Müşterinin');
        $c = Cari::where('tenant_id', $b->tenant_id)->first();
        $this->post(route('cari.sil', $c))->assertNotFound(); // başkasının carisi silinemez
        $this->assertNotNull(Cari::find($c->id));
    }

    public function test_cari_sil_ve_yeniden_ekleme_guncelleme_yapar(): void
    {
        $u = $this->actingAs($this->user())->get('/cariler')->assertOk();
        $this->post(route('cari.ekle'), ['gln' => '8680001000029', 'unvan' => 'ESKİ AD', 'aktif' => 1]);
        $this->post(route('cari.ekle'), ['gln' => '8680001000029', 'unvan' => 'YENİ AD', 'aktif' => 1]);
        $this->assertSame(1, Cari::count());
        $this->assertSame('Yeni Ad', Cari::first()->unvan);
        $this->post(route('cari.sil', Cari::first()));
        $this->assertSame(0, Cari::count());
    }

    public function test_satis_ekraninda_cari_secici_ve_pts_kaydina_cari_adi_yazilir(): void
    {
        $u = $this->user();
        Cari::create(['tenant_id' => $u->tenant_id, 'gln' => '8680001000099', 'unvan' => 'Alıcı Hastane', 'aktif' => true]);
        $this->actingAs($u)->get('/islem/satis')->assertOk()->assertSee('cari-secici', false)->assertSee('karsi_gln_input', false);
        $this->get('/stok/liste')->assertOk()->assertSee('cari-secici', false);
        $this->post('/islem/kabul', ['kodlar' => self::KOD]);
        $this->post('/islem/satis', ['kodlar' => self::KOD, 'karsi_gln' => '8680001000099'])->assertOk();
        $this->assertSame('Alıcı Hastane', PtsGonderim::first()->alici_unvan);
    }

    public function test_pts_sorgu_ve_paket_ekraninda_gonderen_cari_adi_gorunur(): void
    {
        $u = $this->user();
        Cari::create(['tenant_id' => $u->tenant_id, 'gln' => '8680001000022', 'unvan' => 'Tedarikçi Depo', 'aktif' => true]);
        $this->actingAs($u)->get('/pts?baslangic=2026-01-01&bitis=2026-01-10')->assertOk()->assertSee('Tedarikçi Depo');
    }

    public function test_cari_sayfalari_musteri_gozunde_yazma_yapilamaz(): void
    {
        $u = $this->user();
        $admin = User::factory()->create(['rol' => 'super_admin', 'tenant_id' => null]);
        $this->actingAs($admin)->withSession(['musteri_gozu_yonetici_id' => $admin->id])->actingAs($u)
            ->post(route('cari.ekle'), ['gln' => '8680001000029', 'unvan' => 'X'])->assertRedirect();
        $this->assertSame(0, Cari::count());
    }

    public function test_cari_elle_eklenir_duzeltilir_ve_baska_musteri_duzeltemez(): void
    {
        $a = $this->user('depo', '8680001000044');
        $b = $this->user('hastane', '8680001000055');
        $this->actingAs($a)->get(route('cari.yeni'))->assertOk();
        $this->post(route('cari.olustur'), ['gln' => '8680001000029', 'unvan' => 'ELLE CARI', 'telefon' => '0212 555 00 00', 'eposta' => 'a@b.com'])->assertRedirect(route('cari.index'));
        $this->post(route('cari.olustur'), ['gln' => '8680001000029', 'unvan' => 'Tekrar'])->assertSessionHasErrors('gln');
        $c = Cari::first();
        $this->get(route('cari.duzelt', $c))->assertOk()->assertSee('a@b.com');
        $this->post(route('cari.guncelle', $c), ['unvan' => 'Yeni Ad', 'telefon' => '0216 111 22 33', 'eposta' => 'x@y.com'])->assertRedirect();
        $this->assertSame('0216 111 22 33', $c->fresh()->telefon);
        $this->get(route('cari.index'))->assertSee('x@y.com')->assertSee('Düzelt');
        $this->actingAs($b)->get(route('cari.duzelt', $c))->assertNotFound();
        $this->post(route('cari.guncelle', $c), ['unvan' => 'Hack'])->assertNotFound();
    }

    public function test_pts_sorguda_gonderen_cari_ise_mavi_degilse_kirmizi_ve_kim_oldugu_gorunur(): void
    {
        $u = $this->user();
        $url = '/pts?baslangic='.now()->subDays(3)->toDateString().'&bitis='.now()->toDateString();
        // Cari değil, paydaş listesinde de yok
        $this->actingAs($u)->get($url)->assertOk()->assertSee('Cari değil, paydaş listesinde bulunamadı');
        // Cari değil ama depo listesinde var: kırmızı + ünvan
        \Illuminate\Support\Facades\DB::table('paydas_anliklari')->insert(['tip' => 'depo', 'gln' => '8680001000022', 'unvan' => 'ORNEK ECZA DEPOSU', 'aktif' => true, 'created_at' => now(), 'updated_at' => now()]);
        $this->get($url)->assertSee('Ornek Ecza Deposu')->assertSee('cari değil)', false)->assertSee('text-danger', false);
        // Cari ise mavi + cari adı
        Cari::create(['tenant_id' => $u->tenant_id, 'gln' => '8680001000022', 'unvan' => 'Benim Carim']);
        Cari::onbellekTemizle();
        $this->get($url)->assertSee('Benim Carim')->assertDontSee('Ornek Ecza Deposu');
    }
}
