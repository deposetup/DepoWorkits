<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Its\ItsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/** İTS paydaş listesini (varsayılan: depolar) karşılaştırır; yeni/pasife alınan/aktifleşen/kaldırılanları kaydeder ve e-posta ile bildirir. */
class PaydasIzle extends Command
{
    protected $signature = 'paydas:izle {--tip=depo : Paydaş tipi} {--tenant= : Sorguda kullanılacak müşteri id (boşsa İTS bilgisi olan ilk depo)} {--mailsiz : E-posta gönderme}';
    protected $description = 'İTS paydaş listesindeki değişiklikleri tespit eder ve bildirir';

    private const OLAYLAR = ['yeni' => 'Yeni eklendi', 'pasif' => 'Pasife alındı', 'aktif' => 'Yeniden aktif oldu', 'kaldirildi' => 'Listeden kalktı'];

    public function handle(ItsClient $its): int
    {
        $tip = (string) $this->option('tip');
        $tenant = $this->option('tenant')
            ? Tenant::find($this->option('tenant'))
            : Tenant::whereHas('itsCredential')->where('tur', 'depo')->orderBy('id')->first();
        if (! $tenant) {
            $this->error('Sorgu için İTS bilgisi olan müşteri bulunamadı.');

            return self::FAILURE;
        }

        try {
            $liste = collect($its->paydasListesi($tenant, $tip, true))->where('tip', $tip)->filter(fn ($p) => preg_match('/^\d{13}$/', $p['gln']))->keyBy('gln');
        } catch (\Throwable $e) {
            Log::warning('paydas:izle alınamadı: '.$e->getMessage());
            $this->error('Paydaş listesi alınamadı: '.$e->getMessage());

            return self::FAILURE;
        }
        if ($liste->isEmpty()) { // boş yanıt "hepsi kalktı" sayılmaz
            $this->error('İTS boş liste döndürdü; karşılaştırma yapılmadı.');

            return self::FAILURE;
        }

        $eski = DB::table('paydas_anliklari')->where('tip', $tip)->get()->keyBy('gln');
        $ilk = $eski->isEmpty();
        $simdi = now();
        $degisimler = [];

        foreach ($liste as $gln => $p) {
            $o = $eski[$gln] ?? null;
            $olay = null;
            if (! $o) { $olay = 'yeni'; }
            elseif ($o->aktif && ! $p['active']) { $olay = 'pasif'; }
            elseif (! $o->aktif && $p['active']) { $olay = 'aktif'; }
            if ($olay && ! $ilk) {
                $degisimler[] = ['tip' => $tip, 'gln' => $gln, 'unvan' => $p['companyName'], 'olay' => $olay, 'il' => $p['city'], 'created_at' => $simdi];
            }
        }
        foreach ($eski as $gln => $o) {
            if (! $liste->has($gln)) {
                $degisimler[] = ['tip' => $tip, 'gln' => $gln, 'unvan' => $o->unvan, 'olay' => 'kaldirildi', 'il' => $o->il, 'created_at' => $simdi];
            }
        }

        DB::transaction(function () use ($liste, $tip, $degisimler, $simdi) {
            foreach ($liste->chunk(500) as $parca) {
                DB::table('paydas_anliklari')->upsert($parca->map(fn ($p) => [
                    'tip' => $tip, 'gln' => $p['gln'], 'unvan' => $p['companyName'], 'aktif' => $p['active'], 'il' => $p['city'], 'ilce' => $p['town'], 'created_at' => $simdi, 'updated_at' => $simdi,
                ])->values()->all(), ['tip', 'gln'], ['unvan', 'aktif', 'il', 'ilce', 'updated_at']);
            }
            DB::table('paydas_anliklari')->where('tip', $tip)->whereNotIn('gln', $liste->keys()->all())->delete();
            if ($degisimler) { DB::table('paydas_degisimleri')->insert($degisimler); }
        });

        $this->info($ilk ? 'İlk kayıt alındı: '.$liste->count().' '.$tip.' (karşılaştırma bir sonraki sorguda).' : count($degisimler).' değişiklik tespit edildi ('.$liste->count().' kayıt).');

        if (! $this->option('mailsiz')) {
            $this->bildir($tip);
        }

        return self::SUCCESS;
    }

    /** Henüz iletilmemiş tüm değişiklikleri tek e-postada gönderir; e-posta ayarı yoksa kayıtlar bekletilir. */
    private function bildir(string $tip): void
    {
        $bekleyen = DB::table('paydas_degisimleri')->where('tip', $tip)->whereNull('bildirildi_at')->orderBy('id')->get();
        if ($bekleyen->isEmpty()) {
            return;
        }
        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->warn($bekleyen->count().' değişiklik e-posta ayarı yapılana kadar bekletiliyor (Yönetim → Paydaş Değişimleri sayfasında görünür).');

            return;
        }
        $satirlar = $bekleyen->map(fn ($d) => '• '.(self::OLAYLAR[$d->olay] ?? $d->olay).': '.$d->unvan.' (GLN '.$d->gln.($d->il ? ', '.$d->il : '').') - '.\Illuminate\Support\Carbon::parse($d->created_at)->timezone('Europe/Istanbul')->format('d.m.Y H:i'))->implode("\n");
        try {
            Mail::raw("İTS ".ucfirst($tip)." paydaş listesinde değişiklik tespit edildi:\n\n".$satirlar."\n\nDepoWork İTS", function ($m) use ($bekleyen, $tip) {
                $m->to(\App\Support\MailAyari::alicilar())->subject('İTS '.ucfirst($tip).' listesi değişti ('.$bekleyen->count().' kayıt)');
            });
            DB::table('paydas_degisimleri')->whereIn('id', $bekleyen->pluck('id'))->update(['bildirildi_at' => now()]);
            $this->info('E-posta gönderildi: '.config('its.bildirim_eposta'));
        } catch (\Throwable $e) {
            Log::warning('paydas:izle e-posta gönderilemedi: '.$e->getMessage());
            $this->warn('E-posta gönderilemedi, kayıtlar bekletiliyor: '.$e->getMessage());
        }
    }
}
