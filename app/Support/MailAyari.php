<?php

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Yönetim ekranındaki "Mail Ayarları". Değerler ayarlar tablosunda tutulur ve doluysa .env değerlerinin yerine geçer;
 * boş bırakılan alanlar .env / config değerleriyle çalışmaya devam eder. Şifre şifreli saklanır.
 */
class MailAyari
{
    private const ONEK = 'mail.';

    public const ALANLAR = ['aktif', 'host', 'port', 'sifreleme', 'kullanici', 'sifre', 'gonderen_adres', 'gonderen_ad', 'alicilar'];

    public static function tabloVar(): bool
    {
        try {
            return Schema::hasTable('ayarlar');
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<string,?string> Kayıtlı ham değerler (şifre çözülmüş değil, şifreli kalır). */
    public static function kayitli(): array
    {
        if (! self::tabloVar()) {
            return [];
        }
        $sonuc = [];
        foreach (DB::table('ayarlar')->where('anahtar', 'like', self::ONEK.'%')->get() as $r) {
            $sonuc[substr($r->anahtar, strlen(self::ONEK))] = $r->deger;
        }

        return $sonuc;
    }

    public static function kaydet(array $degerler): void
    {
        foreach ($degerler as $alan => $deger) {
            if (! in_array($alan, self::ALANLAR, true)) {
                continue;
            }
            DB::table('ayarlar')->updateOrInsert(['anahtar' => self::ONEK.$alan], ['deger' => $deger, 'updated_at' => now(), 'created_at' => now()]);
        }
    }

    public static function sifreKayitliMi(): bool
    {
        return ! empty(self::kayitli()['sifre']);
    }

    /** Kayıtlı ayarları çalışma anındaki config'e uygular (web, kuyruk ve zamanlanmış komutlarda). */
    public static function uygula(): void
    {
        $k = self::kayitli();
        if (! $k) {
            return;
        }
        $dolu = fn (string $a) => isset($k[$a]) && trim((string) $k[$a]) !== '';

        if (($k['aktif'] ?? null) === '0') {
            config(['mail.default' => 'log']);
        } elseif (($k['aktif'] ?? null) === '1') {
            config(['mail.default' => 'smtp']);
        }
        if ($dolu('host')) { config(['mail.mailers.smtp.host' => trim($k['host'])]); }
        if ($dolu('port')) { config(['mail.mailers.smtp.port' => (int) $k['port']]); }
        if (isset($k['sifreleme']) && $k['sifreleme'] !== '') {
            // ssl => smtps (genelde 465), tls/yok => STARTTLS gerekirse otomatik (genelde 587)
            config(['mail.mailers.smtp.scheme' => $k['sifreleme'] === 'ssl' ? 'smtps' : null]);
        }
        if ($dolu('kullanici')) { config(['mail.mailers.smtp.username' => trim($k['kullanici'])]); }
        if ($dolu('sifre')) {
            try {
                config(['mail.mailers.smtp.password' => Crypt::decryptString($k['sifre'])]);
            } catch (\Throwable) {
                // APP_KEY değiştiyse şifre çözülemez; .env değeri kullanılır.
            }
        }
        if ($dolu('gonderen_adres')) { config(['mail.from.address' => trim($k['gonderen_adres'])]); }
        if ($dolu('gonderen_ad')) { config(['mail.from.name' => trim($k['gonderen_ad'])]); }
        if ($dolu('alicilar')) { config(['its.bildirim_eposta' => trim($k['alicilar'])]); }
    }

    /** Paydaş değişikliği bildirimlerinin gideceği adresler (virgül, noktalı virgül veya satır ile ayrılmış liste). */
    public static function alicilar(): array
    {
        $liste = preg_split('/[\s,;]+/', (string) config('its.bildirim_eposta'), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique($liste));
    }

    public static function gonderimAcik(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }
}
