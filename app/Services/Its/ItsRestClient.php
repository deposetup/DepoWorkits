<?php

namespace App\Services\Its;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Bakanlık İTS REST API istemcisi (docs/its-ozet.md, REST API Servis Kılavuzu).
 * Yalnızca bildirim servisleri ve token bu aşamada yazıldı; ilaç listesi ve PTS sorgusu
 * alan adları test ortamında doğrulanınca eklenecek (docs/acik-sorular.md #2).
 */
class ItsRestClient implements ItsClient
{
    public function ilacListesi(Tenant $tenant, bool $hepsi = false): array
    {
        @ini_set('memory_limit', '512M'); // ilaç listesi on binlerce kayıt içerir
        $y = Http::withToken($this->token($tenant))->acceptJson()->timeout(120)->post($this->taban().'/reference/app/drug/', ['getAll' => $hepsi]);
        $j = $y->json() ?? [];
        if (! $y->successful() || isset($j['fc'])) {
            throw new RuntimeException('İTS ilaç listesi alınamadı: '.($j['fm'] ?? 'HTTP '.$y->status()));
        }
        $out = [];
        foreach ($j['drugList'] ?? [] as $r) {
            $out[] = [
                'gtin' => (string) ($r['gtin'] ?? ''), 'drugName' => (string) ($r['drugName'] ?? $r['drugname'] ?? ''),
                'manufacturerGln' => (string) ($r['manufacturerGln'] ?? ''), 'manufacturerName' => (string) ($r['manufacturerName'] ?? ''),
                'active' => (bool) ($r['active'] ?? $r['acitve'] ?? true),
            ];
        }

        return $out;
    }

    public function paydasListesi(Tenant $tenant, ?string $tip = null, bool $pasiflerDahil = true, ?int $plaka = null): array
    {
        @ini_set('memory_limit', '512M'); // eczane listesi on binlerce kayıt içerir; önbelleğe alınmaz (bellek)
        $tipler = $tip ? [$tip] : ['uretici', 'depo', 'eczane', 'hastane', 'ihracatci'];
        $token = $this->token($tenant);
        $out = [];
        foreach ($tipler as $t) {
            $govde = array_filter(['stakeholderType' => $t, 'getAll' => $pasiflerDahil, 'cityPlate' => $plaka], fn ($v) => $v !== null);
            $y = Http::withToken($token)->acceptJson()->timeout(120)->post($this->taban().'/reference/app/stakeholder/', $govde);
            $j = $y->json() ?? [];
            unset($y);
            if (isset($j['fc']) || ! isset($j['companyList'])) {
                throw new RuntimeException('İTS paydaş listesi alınamadı ('.$t.'): '.($j['fm'] ?? 'beklenmeyen yanıt').(isset($j['fc']) ? ' ('.$j['fc'].')' : ''));
            }
            foreach ($j['companyList'] as $r) {
                $out[] = [
                    'gln' => (string) ($r['gln'] ?? ''), 'companyName' => (string) ($r['companyName'] ?? ''), 'tip' => $t,
                    'authorized' => $r['authorized'] ?? null, 'email' => $r['email'] ?? null, 'phone' => $r['phone'] ?? null,
                    'city' => $r['city'] ?? null, 'town' => $r['town'] ?? null, 'address' => null,
                    'active' => (bool) ($r['active'] ?? true),
                ];
            }
            unset($j);
        }

        return $out;
    }

    public function ptsSorgula(Tenant $tenant, ?string $baslangic, ?string $bitis, bool $sadeceAlinmayan = true, ?string $kaynakGln = null): array
    {
        $temel = [
            'destinationGln' => $tenant->gln,
            'bringNotReceivedTransferInfo' => $sadeceAlinmayan ? 1 : 0,
        ];

        // Kılavuz sourceGln'i örneklerde hep dolu gösteriyor; boş bırakılınca İTS 500 verebilir.
        // Gönderen verilmediyse sırayla farklı (salt-okunur) istek biçimleri denenir; çalışan biçim önbelleğe alınır.
        $tarihler = ['startDate' => $baslangic, 'endDate' => $bitis];
        $tarihSaatli = ['startDate' => $baslangic ? $baslangic.'T00:00:00' : null, 'endDate' => $bitis ? $bitis.'T23:59:59' : null];
        $kaynakVar = $kaynakGln !== null && $kaynakGln !== '';

        $varyantlar = $kaynakVar
            ? ['normal' => $temel + ['sourceGln' => $kaynakGln] + $tarihler]
            : [
                'kaynaksiz' => $temel + $tarihler,
                'bos-kaynak' => $temel + ['sourceGln' => ''] + $tarihler,
                'kaynaksiz-saatli' => $temel + $tarihSaatli,
                'bos-kaynak-saatli' => $temel + ['sourceGln' => ''] + $tarihSaatli,
            ];

        $onceki = Cache::get('its_pts_varyant_'.$tenant->id);
        if ($onceki && isset($varyantlar[$onceki])) {
            $varyantlar = [$onceki => $varyantlar[$onceki]] + $varyantlar;
        }

        $token = $this->token($tenant);
        $hatalar = [];
        foreach ($varyantlar as $ad => $govde) {
            $govde = array_filter($govde, fn ($v) => $v !== null);
            $y = Http::withToken($token)->acceptJson()->timeout(30)->post($this->taban().'/pts/app/search', $govde);
            $j = $y->json() ?? [];
            if ($y->successful() && ! isset($j['fc'])) {
                Cache::forever('its_pts_varyant_'.$tenant->id, $ad);

                return array_map(fn ($r) => [
                    'transferId' => (string) ($r['transferId'] ?? ''),
                    'gonderen' => $r['sourceGln'] ?? null,
                    'alici' => $r['destinationGln'] ?? null,
                    'tarih' => $r['transferDate'] ?? null,
                    // Servis "alındı" bilgisi vermez; yalnızca filtre 1 iken kayıtlar alınmamıştır.
                    'alindi' => $sadeceAlinmayan ? false : null,
                ], $j['transferDetails'] ?? []);
            }
            $ozet = $j['fm'] ?? ($j['error'] ?? 'HTTP '.$y->status());
            \Illuminate\Support\Facades\Log::warning('İTS PTS sorgu hatası', ['varyant' => $ad, 'http' => $y->status(), 'govde' => $govde, 'yanit' => mb_substr($y->body(), 0, 500)]);
            $hatalar[] = $ad.' → HTTP '.$y->status().' '.$ozet.(isset($j['fc']) ? ' ('.$j['fc'].')' : '');
        }

        throw new RuntimeException('İTS PTS sorgusu başarısız. Denenen biçimler: '.implode(' | ', $hatalar));
    }

    public function ptsPaketGonder(Tenant $tenant, string $aliciGln, string $zip): array
    {
        if (! config('its.yazma') || ! config('its.pts_gonderim')) {
            throw new RuntimeException('PTS paket gönderimi bu ortamda kapalı (ITS_YAZMA ve ITS_PTS_GONDERIM açık olmalı). Paket gönderilmedi.');
        }
        $y = Http::withToken($this->token($tenant))->acceptJson()->timeout(120)
            ->post($this->taban().'/pts/app/SendPackage', ['receiver' => $aliciGln, 'file' => base64_encode($zip)]);
        $j = $y->json() ?? [];
        if (! $y->successful() || isset($j['fc']) || empty($j['transferId'])) {
            throw new RuntimeException('İTS PTS gönderimi başarısız: '.($j['fm'] ?? 'HTTP '.$y->status()).(isset($j['fc']) ? ' ('.$j['fc'].')' : ''));
        }

        return ['transferId' => (string) $j['transferId'], 'md5' => $j['md5Checksum'] ?? $j['md5CheckSum'] ?? null];
    }

    public function ptsPaketAl(Tenant $tenant, string $transferId): array
    {
        $y = Http::withToken($this->token($tenant))->acceptJson()->timeout(120)->post($this->taban().'/pts/app/GetPackage', ['transferId' => $transferId]);
        $j = $y->json() ?? [];
        if (! $y->successful() || isset($j['fc']) || empty($j['fileStream'])) {
            throw new RuntimeException('İTS paket alımı başarısız: '.($j['fm'] ?? 'HTTP '.$y->status()).(isset($j['fc']) ? ' ('.$j['fc'].')' : ''));
        }
        $bayt = base64_decode((string) $j['fileStream'], true);
        if ($bayt === false) {
            throw new RuntimeException('İTS paket verisi çözülemedi (base64).');
        }
        $beklenen = $j['streamMD5checksum'] ?? $j['streamMD5CheckSum'] ?? null;
        $md5 = $beklenen ? strcasecmp(md5($bayt), (string) $beklenen) === 0 : null;

        // Zip ise aç (zip-bomb koruması: toplam 50 MB sınırı); değilse doğrudan XML kabul et.
        $xmller = \App\Support\PtsDosya::xmllerCikar($bayt);

        return ['xmller' => $xmller, 'md5_uyumlu' => $md5];
    }

    public function durumSorgula(Tenant $tenant, array $urunler): array
    {
        $token = $this->token($tenant);
        $out = [];
        foreach (array_chunk($urunler, 100) as $parca) {
            $y = Http::withToken($token)->acceptJson()->timeout(60)->post($this->taban().'/reference/app/check_status/', ['productList' => $this->liste($parca)]);
            $j = $y->json() ?? [];
            if (! $y->successful() || isset($j['fc'])) {
                throw new RuntimeException('İTS durum sorgusu başarısız: '.($j['fm'] ?? 'HTTP '.$y->status()).(isset($j['fc']) ? ' ('.$j['fc'].')' : ''));
            }
            foreach ($j['responseObjectList'] ?? [] as $r) {
                $out[] = [
                    'gtin' => $r['gtin'] ?? '', 'sn' => $r['sn'] ?? '',
                    'gln1' => ($r['gln1'] ?? $r['Gln1'] ?? null) ?: null, 'gln2' => ($r['gln2'] ?? $r['Gln2'] ?? null) ?: null,
                    'uc' => $r['uc'] ?? null,
                ];
            }
        }

        return $out;
    }

    public function satis(Tenant $tenant, array $urunler, string $karsiGln): ItsSonuc
    {
        return $this->bildir($tenant, '/wholesale/app/dispatch/', ['togln' => $karsiGln, 'productList' => $this->liste($urunler)], $urunler);
    }

    public function satisIptal(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->bildir($tenant, '/wholesale/app/dispatchcancel/', ['productList' => $this->liste($urunler)], $urunler);
    }

    public function malKabul(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->bildir($tenant, '/common/app/accept/', ['productList' => $this->liste($urunler)], $urunler);
    }

    public function malIade(Tenant $tenant, array $urunler, string $karsiGln): ItsSonuc
    {
        return $this->bildir($tenant, '/common/app/return/', ['togln' => $karsiGln, 'productList' => $this->liste($urunler)], $urunler);
    }

    public function malDevir(Tenant $tenant, array $urunler, string $karsiGln): ItsSonuc
    {
        return $this->bildir($tenant, '/common/app/transfer/', ['togln' => $karsiGln, 'productList' => $this->liste($urunler)], $urunler);
    }

    public function malDevirIptal(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->bildir($tenant, '/common/app/transfercancel/', ['productList' => $this->liste($urunler)], $urunler);
    }

    public function hastaneSarf(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->bildir($tenant, '/consume/app/consume/', ['dt' => 'D', 'fr' => $tenant->gln, 'productList' => $this->liste($urunler)], $urunler);
    }

    public function sarfIptal(Tenant $tenant, array $urunler): ItsSonuc
    {
        return $this->bildir($tenant, '/consume/app/consumecancel/', ['dt' => 'C', 'fr' => $tenant->gln, 'productList' => $this->liste($urunler)], $urunler);
    }

    public function deaktive(Tenant $tenant, string $sebepKodu, array $urunler, ?string $aciklama = null): ItsSonuc
    {
        return $this->bildir($tenant, '/common/app/deactivation/', [
            'dt' => 'D', 'frGln' => $tenant->gln, 'ds' => $sebepKodu, 'description' => $aciklama ?? '',
            'productList' => $this->liste($urunler),
        ], $urunler);
    }

    public function deaktiveIptal(Tenant $tenant, string $sebepKodu, array $urunler, ?string $aciklama = null): ItsSonuc
    {
        return $this->bildir($tenant, '/common/app/deactivationcancel/', [
            'dt' => 'C', 'frGln' => $tenant->gln, 'ds' => $sebepKodu, 'description' => $aciklama ?? '',
            'productList' => $this->liste($urunler),
        ], $urunler);
    }

    public function baglantiTest(Tenant $tenant): ItsSonuc
    {
        try {
            $this->token($tenant, true);

            return new ItsSonuc(true);
        } catch (RuntimeException $e) {
            return new ItsSonuc(false, hataMesaji: $e->getMessage());
        }
    }

    /** @return array<int,array{gtin:string,sn:string,bn:string,xd:?string}> */
    private function liste(array $urunler): array
    {
        return array_map(fn ($u) => array_filter([
            'gtin' => $u['gtin'], 'sn' => $u['sn'] ?? null, 'bn' => $u['bn'] ?? null, 'xd' => $u['xd'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''), $urunler);
    }

    private function bildir(Tenant $tenant, string $yol, array $govde, array $urunler): ItsSonuc
    {
        // Güvenlik: ITS_YAZMA=true olmadıkça gerçek İTS'e hiçbir bildirim gönderilmez (yalnızca okuma).
        if (! config('its.yazma')) {
            return new ItsSonuc(false, hataKodu: 'YAZMA-KAPALI', hataMesaji: 'Gerçek İTS bildirimleri kapalı (salt-okunur mod). Bildirim gönderilmedi.');
        }

        try {
            $yanit = Http::withToken($this->token($tenant))->acceptJson()->timeout(30)->post($this->taban().$yol, $govde);
        } catch (\Throwable $e) {
            return new ItsSonuc(false, hataMesaji: 'İTS bağlantı hatası: '.$e->getMessage());
        }

        $j = $yanit->json() ?? [];
        if (! $yanit->successful() || isset($j['fc'])) {
            return new ItsSonuc(false, hataKodu: $j['fc'] ?? (string) $yanit->status(), hataMesaji: $j['fm'] ?? 'İTS hata döndürdü.');
        }

        // Kılavuzda alan adı hem notification_id hem notificationid geçiyor; ikisini de kabul et.
        $id = $j['notification_id'] ?? $j['notificationid'] ?? null;

        return new ItsSonuc(true, $id !== null ? (string) $id : null, array_map(fn ($u) => [
            'gtin' => $u['gtin'] ?? '', 'sn' => $u['sn'] ?? null, 'uc' => $u['uc'] ?? null,
            'hata' => $u['fm'] ?? (\App\Support\ItsHata::basarili($u['uc'] ?? null) ? null : ((\App\Support\ItsHata::aciklama($u['uc']) ?? 'İTS hatası').' ('.$u['uc'].')')),
        ], $j['productList'] ?? []));
    }

    private function taban(): string
    {
        return rtrim(config('its.base_url'), '/');
    }

    /** Tenant başına token önbelleği (1 saat geçerli, 5 dk erken yenilenir). */
    private function token(Tenant $tenant, bool $yenile = false): string
    {
        $anahtar = 'its_token_'.$tenant->id;
        if (! $yenile && ($t = Cache::get($anahtar))) {
            return $t;
        }

        $kimlik = $tenant->itsCredential;
        if (! $kimlik) {
            throw new RuntimeException('İTS kullanıcı bilgisi kayıtlı değil.');
        }

        $y = Http::acceptJson()->timeout(20)->post($this->taban().'/token/app/token/', [
            'username' => $kimlik->kullanici_adi, 'password' => $kimlik->sifre,
        ]);
        $j = $y->json() ?? [];
        $token = $j['access_token'] ?? $j['token'] ?? null; // kılavuzlar farklı ad kullanıyor
        if (! $y->successful() || ! $token) {
            throw new RuntimeException('İTS token alınamadı: '.($j['fm'] ?? 'HTTP '.$y->status()));
        }
        Cache::put($anahtar, $token, now()->addMinutes(55));

        return $token;
    }
}
