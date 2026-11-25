<?php

namespace App\Services\Its;

use App\Models\Tenant;
use Illuminate\Support\Str;

/** Bakanlık test kullanıcısı gelene kadar kullanılan sahte istemci. */
class MockItsClient implements ItsClient
{
    public function ilacListesi(Tenant $tenant, bool $hepsi = false): array
    {
        return [
            ['gtin' => '08699617650153', 'drugName' => 'ÖRNEK İLAÇ 5 MG 20 TABLET', 'manufacturerGln' => '8680001000012', 'manufacturerName' => 'Test Supplier 1', 'active' => true],
            ['gtin' => '08699617650160', 'drugName' => 'ÖRNEK ŞURUP 100 ML', 'manufacturerGln' => '8680001000012', 'manufacturerName' => 'Test Supplier 1', 'active' => true],
        ];
    }

    public function paydasListesi(Tenant $tenant, ?string $tip = null, bool $pasiflerDahil = true, ?int $plaka = null): array
    {
        $hepsi = [
            ['gln' => '8680001000012', 'companyName' => 'Test Supplier 1', 'tip' => 'uretici', 'authorized' => 'its teknik', 'email' => 'a@b.com', 'phone' => '03120000000', 'city' => 'ANKARA', 'town' => 'Çankaya', 'address' => '1', 'active' => true],
            ['gln' => '8680001000029', 'companyName' => 'Test Depo', 'tip' => 'depo', 'authorized' => null, 'email' => null, 'phone' => null, 'city' => 'İSTANBUL', 'town' => 'Kadıköy', 'address' => '2', 'active' => false],
        ];

        return array_values(array_filter($hepsi, fn ($p) => ($pasiflerDahil || $p['active'])));
    }

    public function malKabul(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function malDevir(Tenant $tenant, array $urunler, string $karsiGln): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function malDevirIptal(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function malIade(Tenant $tenant, array $urunler, string $karsiGln): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function sarfIptal(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function hastaneSarf(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function deaktiveIptal(Tenant $tenant, string $sebepKodu, array $urunler, ?string $aciklama = null): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function deaktive(Tenant $tenant, string $sebepKodu, array $urunler, ?string $aciklama = null): ItsSonuc
    {
        if (! in_array($sebepKodu, ['10', '20', '30', '40', '50', '60'], true)) {
            return new ItsSonuc(false, hataKodu: '00000', hataMesaji: 'Geçersiz deaktivasyon sebebi (mock).');
        }

        return $this->basari($urunler);
    }

    public function ptsSorgula(Tenant $tenant, ?string $baslangic, ?string $bitis, bool $sadeceAlinmayan = true, ?string $kaynakGln = null): array
    {
        return [['transferId' => '100004870', 'gonderen' => '8680001000022', 'alici' => $tenant->gln, 'tarih' => now()->toDateString(), 'alindi' => false]];
    }

    private function basari(array $urunler): ItsSonuc
    {
        return new ItsSonuc(true, Str::upper(Str::random(20)), array_map(fn ($u) => [
            'gtin' => $u['gtin'], 'sn' => $u['sn'] ?? null, 'uc' => '00000', 'hata' => null,
        ], $urunler));
    }

    public function baglantiTest(Tenant $tenant): ItsSonuc
    {
        return $tenant->itsCredential
            ? new ItsSonuc(true)
            : new ItsSonuc(false, hataKodu: '00000', hataMesaji: 'İTS kullanıcı bilgisi kayıtlı değil.');
    }

    public function ptsPaketGonder(Tenant $tenant, string $aliciGln, string $zip): array
    {
        return ['transferId' => (string) random_int(100000000, 999999999), 'md5' => md5($zip)];
    }

    public function ptsPaketAl(Tenant $tenant, string $transferId): array
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><transfer><sourceGLN>8680001000022</sourceGLN><destinationGLN>'.$tenant->gln.'</destinationGLN>'
            .'<documentNumber>IRS2026001</documentNumber><productList>'
            .'<product><GTIN>08699617650153</GTIN><lotNumber>LOT77</lotNumber><expirationDate>2030-12-31</expirationDate><serialNumber>MOCK0001</serialNumber></product>'
            .'<product><GTIN>08699617650153</GTIN><lotNumber>LOT77</lotNumber><expirationDate>2030-12-31</expirationDate><serialNumber>MOCK0002</serialNumber></product>'
            .'</productList></transfer>';

        return ['xmller' => ['paket.xml' => $xml], 'md5_uyumlu' => null];
    }

    public function durumSorgula(Tenant $tenant, array $urunler): array
    {
        return array_map(fn ($u) => str_ends_with((string) $u['sn'], '9')
            ? ['gtin' => $u['gtin'], 'sn' => $u['sn'], 'gln1' => null, 'gln2' => null, 'uc' => '10201']
            : ['gtin' => $u['gtin'], 'sn' => $u['sn'], 'gln1' => $tenant->gln, 'gln2' => null, 'uc' => '60014'], $urunler);
    }

    public function ihracat(Tenant $tenant, array $urunler, string $alici): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function ihracatIptal(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function satis(Tenant $tenant, array $urunler, string $karsiGln): ItsSonuc
    {
        return $this->basari($urunler);
    }

    public function satisIptal(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->basari($urunler);
    }
}
