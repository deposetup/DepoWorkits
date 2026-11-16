<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Hareket;
use App\Models\StokKalemi;
use App\Models\User;
use App\Services\Its\ItsClient;
use App\Support\Gs1Parser;
use InvalidArgumentException;

/**
 * Karekodları ayrıştırır, yerel stok kurallarını denetler, İTS'e bildirir ve stok durumunu günceller.
 * Stok İTS'te sorgulanamadığı için kaynak gerçek burasıdır (docs/its-ozet.md).
 */
class StokServisi
{
    /** tür => [gereken mevcut durum, yeni durum] */
    public const TURLER = [
        'kabul' => [null, 'stokta', 'Mal Kabul'],
        'sarf' => ['stokta', 'sarf', 'Hastane Sarf'],
        'sarf_iptal' => ['sarf', 'stokta', 'Sarf İptal'],
        'iade' => ['stokta', 'iade', 'Ürün İade'],
        'satis' => ['stokta', 'satis', 'Satış'],
        'satis_iptal' => ['satis', 'stokta', 'Satış İptal'],
        'devir' => ['stokta', 'devir', 'Mal Devir'],
        'devir_iptal' => ['devir', 'stokta', 'Mal Devir İptal'],
        'deaktive' => ['stokta', 'deaktive', 'Deaktive'],
        'deaktive_iptal' => ['deaktive', 'stokta', 'Deaktivasyon İptal'],
    ];

    public const DEAKTIVE_SEBEPLER = [
        '10' => 'Sistemden Çıkartma', '20' => 'Üretim Fireleri', '30' => 'Geri Çekme',
        '40' => 'Miat', '50' => 'Revizyon', '60' => 'Sarf',
    ];

    public function __construct(private ItsClient $its) {}

    /**
     * Karekod metnini (her satır bir ürün) ayrıştırıp işler.
     * @return array{satirlar:array<int,array{kod:string,durum:string,mesaj:?string}>,bildirim:?string}
     */
    public function isle(User $user, string $tur, string $metin, ?string $sebep = null, ?string $aciklama = null, ?string $karsiGln = null): array
    {
        $kalemler = [];
        $hatalar = [];
        foreach (preg_split('/\r\n|\r|\n/', $metin) as $kod) {
            $kod = trim($kod);
            if ($kod === '') { continue; }
            try {
                $k = Gs1Parser::parse($kod);
                if (! $k['seri']) { throw new InvalidArgumentException('Seri no (21) yok.'); }
                $kalemler[] = ['kod' => $kod, 'gtin' => $k['gtin'], 'sn' => $k['seri'], 'bn' => $k['lot'] ?? '', 'xd' => $k['skt']];
            } catch (InvalidArgumentException $e) {
                $hatalar[$kod] = ['kod' => $kod, 'durum' => 'hata', 'mesaj' => $e->getMessage()];
            }
        }

        $sonuc = $this->kalemleriIsle($user, $tur, $kalemler, $sebep, $aciklama, $karsiGln);
        $sonuc['satirlar'] = array_merge(array_values($hatalar), $sonuc['satirlar']);

        return $sonuc;
    }

    /**
     * Hazır ürün listesini (karekoddan ya da PTS XML'inden) yerel kurallarla denetler, İTS'e bildirir, stoğu günceller.
     * @param array<int,array{kod:string,gtin:string,sn:string,bn:?string,xd:?string}> $kalemler
     * @return array{satirlar:array<int,array{kod:string,durum:string,mesaj:?string}>,bildirim:?string}
     */
    public function kalemleriIsle(User $user, string $tur, array $kalemler, ?string $sebep = null, ?string $aciklama = null, ?string $karsiGln = null): array
    {
        if (! isset(self::TURLER[$tur])) {
            throw new InvalidArgumentException('Geçersiz işlem türü.');
        }
        [$gereken, $yeni] = self::TURLER[$tur];
        $tenant = $user->tenant;
        if (in_array($tur, ['sarf', 'sarf_iptal'], true) && ! $tenant->sarfYapabilir()) {
            throw new InvalidArgumentException('Ecza depoları sarf bildirimi yapamaz.');
        }

        if (in_array($tur, ['devir', 'devir_iptal'], true) && ! $tenant->devirYapabilir()) {
            throw new InvalidArgumentException('Ecza depoları ve hastaneler Mal Devir kullanmaz.');
        }

        $satirlar = [];
        $gonderilecek = [];
        $goruldu = [];

        foreach ($kalemler as $g) {
            $kod = $g['kod'];
            try {
                $anahtar = $g['gtin'].'/'.$g['sn'];
                if (isset($goruldu[$anahtar])) { throw new InvalidArgumentException('Aynı ürün listede iki kez var.'); }
                $goruldu[$anahtar] = true;

                $kalem = StokKalemi::where(['tenant_id' => $tenant->id, 'gtin' => $g['gtin'], 'sn' => $g['sn']])->first();
                if ($gereken === null && $kalem && $kalem->durum === 'stokta') {
                    throw new InvalidArgumentException('Ürün zaten stokta.');
                }
                if ($gereken !== null && (! $kalem || $kalem->durum !== $gereken)) {
                    throw new InvalidArgumentException('Ürün '.($gereken === 'stokta' ? 'stokta' : ($gereken === 'satis' ? 'satılmış durumda' : ($gereken === 'devir' ? 'devredilmiş durumda' : ($gereken === 'deaktive' ? 'deaktive edilmiş durumda' : 'sarf edilmiş durumda')))).' değil.');
                }
                if ($tur === 'kabul' && ($g['xd'] ?? null) && $g['xd'] < now()->toDateString()) {
                    throw new InvalidArgumentException('Son kullanma tarihi geçmiş.');
                }
                $gonderilecek[] = $g;
                $satirlar[$kod] = ['kod' => $kod, 'durum' => 'bekliyor', 'mesaj' => null];
            } catch (InvalidArgumentException $e) {
                $satirlar[$kod] = ['kod' => $kod, 'durum' => 'hata', 'mesaj' => $e->getMessage()];
            }
        }

        $bildirim = null;
        $basariliKalemler = [];
        // İTS tek istekte çok ürün kabul etmeyebilir; büyük listeler parçalara bölünür.
        foreach (array_chunk($gonderilecek, 100) as $parca) {
            $sonuc = match ($tur) {
                'kabul' => $this->its->malKabul($tenant, $parca),
                'sarf' => $this->its->hastaneSarf($tenant, $parca),
                'sarf_iptal' => $this->its->sarfIptal($tenant, $parca),
                'iade' => $this->its->malIade($tenant, $parca, (string) $karsiGln),
                'satis' => $this->its->satis($tenant, $parca, (string) $karsiGln),
                'satis_iptal' => $this->its->satisIptal($tenant, $parca),
                'devir' => $this->its->malDevir($tenant, $parca, (string) $karsiGln),
                'devir_iptal' => $this->its->malDevirIptal($tenant, $parca),
                'deaktive' => $this->its->deaktive($tenant, $sebep ?? '10', $parca, $aciklama),
                'deaktive_iptal' => $this->its->deaktiveIptal($tenant, $sebep ?? '10', $parca, $aciklama),
            };
            $bildirim = $sonuc->bildirimId ?? $bildirim;
            $uc = collect($sonuc->urunler)->keyBy(fn ($u) => $u['gtin'].'/'.$u['sn']);

            foreach ($parca as $g) {
                $u = $uc[$g['gtin'].'/'.$g['sn']] ?? null;
                $ok = $sonuc->basarili && (! $u || in_array($u['uc'] ?? '00000', [null, '00000'], true));
                $hata = $ok ? null : ($u['hata'] ?? $sonuc->hataMesaji ?? 'İTS hata döndürdü.');

                if ($ok) {
                    $basariliKalemler[] = ['gtin' => $g['gtin'], 'sn' => $g['sn'], 'bn' => ($g['bn'] ?? null) ?: null, 'xd' => $g['xd'] ?? null];
                    StokKalemi::updateOrCreate(
                        ['tenant_id' => $tenant->id, 'gtin' => $g['gtin'], 'sn' => $g['sn']],
                        ['bn' => ($g['bn'] ?? null) ?: null, 'skt' => $g['xd'] ?? null, 'durum' => $yeni, 'son_islem_at' => now()]
                    );
                }
                Hareket::create([
                    'tenant_id' => $tenant->id, 'user_id' => $user->id, 'tur' => $tur,
                    'gtin' => $g['gtin'], 'sn' => $g['sn'], 'bn' => ($g['bn'] ?? null) ?: null, 'skt' => $g['xd'] ?? null,
                    'bildirim_id' => $sonuc->bildirimId, 'basarili' => $ok, 'hata' => $hata, 'aciklama' => $aciklama,
                ]);
                $satirlar[$g['kod']] = ['kod' => $g['kod'], 'durum' => $ok ? 'tamam' : 'hata', 'mesaj' => $hata];
            }
        }
        if ($gonderilecek) {
            AuditLog::kaydet('islem.'.$tur, ['adet' => count($gonderilecek), 'bildirim' => $bildirim]);
        }

        if ($tur === 'satis_iptal' && $basariliKalemler && \Illuminate\Support\Facades\Schema::hasTable('pts_gonderimleri')) {
            $this->bekleyenPtsGonderimlerdenCikar($tenant->id, $basariliKalemler);
        }

        return ['satirlar' => array_values($satirlar), 'bildirim' => $bildirim, 'kalemler_ok' => $basariliKalemler];
    }

    /** Satışı geri alınan ürünler, henüz gönderilmemiş PTS kayıtlarından çıkarılır (iptal edilen satış için PTS gitmesin). */
    private function bekleyenPtsGonderimlerdenCikar(int $tenantId, array $iptalEdilen): void
    {
        $anahtar = array_flip(array_map(fn ($k) => $k['gtin'].'/'.$k['sn'], $iptalEdilen));
        foreach (\App\Models\PtsGonderim::where(['tenant_id' => $tenantId, 'durum' => 'bekliyor'])->get() as $g) {
            $kalan = array_values(array_filter($g->kalemler, fn ($k) => ! isset($anahtar[$k['gtin'].'/'.$k['sn']])));
            if (count($kalan) === count($g->kalemler)) { continue; }
            $g->update($kalan ? ['kalemler' => $kalan, 'adet' => count($kalan)] : ['kalemler' => [], 'adet' => 0, 'durum' => 'vazgecildi', 'hata' => 'Satış geri alındı.']);
        }
    }
}
