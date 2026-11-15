<?php

namespace App\Services\Its;

use App\Models\Tenant;

/**
 * Bakanlık İTS REST API katmanı (docs/its-ozet.md).
 * Gerçek istemci ItsRestClient, geliştirme için MockItsClient kullanılır.
 */
interface ItsClient
{
    /** @return array<int,array{gtin:string,drugName:string,manufacturerGln:string,manufacturerName:string,active:bool}> */
    public function ilacListesi(Tenant $tenant, bool $hepsi = false): array;

    /**
     * Paydaş listesi (reference/app/stakeholder, kılavuz §20). $tip boşsa tüm tipler birleştirilir.
     * @return array<int,array{gln:string,companyName:string,tip:string,authorized:?string,email:?string,phone:?string,city:?string,town:?string,address:?string,active:bool}>
     */
    public function paydasListesi(Tenant $tenant, ?string $tip = null, bool $pasiflerDahil = true, ?int $plaka = null): array;

    /** @param array<int,array{gtin:string,sn:string,bn:string,xd:string}> $urunler */
    public function malKabul(Tenant $tenant, array $urunler): ItsSonuc;

    public function malIade(Tenant $tenant, array $urunler, string $karsiGln): ItsSonuc;

    /** Mal Devir (§13): aynı role sahip paydaşlar arası ürün transferi. */
    public function malDevir(Tenant $tenant, array $urunler, string $karsiGln): ItsSonuc;

    /** Mal Devir İptal (§14). */
    public function malDevirIptal(Tenant $tenant, array $urunler): ItsSonuc;

    public function sarfIptal(Tenant $tenant, array $urunler): ItsSonuc;

    public function hastaneSarf(Tenant $tenant, array $urunler): ItsSonuc;

    /** Deaktivasyon İptal (§45): kılavuz yalnızca eczaneler için tanımlar; depoda İTS reddederse hata mesajı gösterilir. */
    public function deaktiveIptal(Tenant $tenant, string $sebepKodu, array $urunler, ?string $aciklama = null): ItsSonuc;

    public function deaktive(Tenant $tenant, string $sebepKodu, array $urunler, ?string $aciklama = null): ItsSonuc;

    /**
     * PTS paket listesi. Aralık en fazla 31 gün (kılavuz §44).
     * @return array<int,array{transferId:string,gonderen:?string,alici:?string,tarih:?string,alindi:?bool}>
     */
    public function ptsSorgula(Tenant $tenant, ?string $baslangic, ?string $bitis, bool $sadeceAlinmayan = true, ?string $kaynakGln = null): array;

    /** Kayıtlı İTS kullanıcı bilgileriyle token alınabiliyor mu? */
    public function baglantiTest(Tenant $tenant): ItsSonuc;

    /**
     * PTS paketini indirir (kılavuz §43). Zip açılır, içindeki XML metinleri döner.
     * @return array{xmller:array<string,string>,md5_uyumlu:?bool}
     */
    public function ptsPaketAl(Tenant $tenant, string $transferId): array;

    /**
     * Ürün durum sorgusu (kılavuz §16, salt-okunur). 'uc' 00000 ise sorun yok; diğer kodlar ITS_Hata_Kodlari'ndadır.
     * @param array<int,array{gtin:string,sn:string,bn?:?string,xd?:?string}> $urunler
     * @return array<int,array{gtin:string,sn:string,gln1:?string,gln2:?string,uc:?string}>
     */
    public function durumSorgula(Tenant $tenant, array $urunler): array;

    /** Satış bildirimi (kılavuz §6): ürünler alıcı Mal Alım yapana kadar arada bekler. */
    public function satis(Tenant $tenant, array $urunler, string $karsiGln): ItsSonuc;

    /** Satış iptal bildirimi (kılavuz §7). */
    public function satisIptal(Tenant $tenant, array $urunler): ItsSonuc;

    /**
     * PTS paket gönderimi (kılavuz §42). $zip: ham zip baytları (içinde XML). Başarısızlıkta RuntimeException.
     * @return array{transferId:string,md5:?string}
     */
    public function ptsPaketGonder(Tenant $tenant, string $aliciGln, string $zip): array;
}
