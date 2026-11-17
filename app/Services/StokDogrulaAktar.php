<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\PtsPaketi;
use App\Models\StokKalemi;
use App\Models\Tenant;
use App\Services\Its\ItsClient;
use App\Support\Gs1Parser;
use App\Support\ItsHata;
use App\Support\PtsXmlParser;

/**
 * İTS doğrulamalı stok içe aktarma, parça parça (zaman aşımı olmadan) çalışır:
 *  1. aşama: aday ürünler geçici bir dosyaya yazılır (PTS paketlerinde her adımda bir paket çözülür),
 *  2. aşama: adaylar SORGU_ADIM'lık gruplar halinde İTS Durum Sorgu ile doğrulanır; son sahibi bu paydaşsa stoğa alınır.
 * Durum bir dizidir; çağıran (controller) cache'te saklar.
 */
class StokDogrulaAktar
{
    public const SORGU_ADIM = 200;

    public const ORNEK = 300;

    public function __construct(private ItsClient $its) {}

    public function baslat(Tenant $tenant, string $kaynak, ?string $kodlar = null): array
    {
        $klasor = storage_path('app/stok-aktar');
        if (! is_dir($klasor)) { @mkdir($klasor, 0775, true); }
        $yol = tempnam($klasor, 'aday');
        $d = [
            'kaynak' => $kaynak, 'yol' => $yol, 'asama' => 'sorgu', 'paketler' => [], 'paketSira' => 0, 'yazilan' => 0,
            'konum' => 0, 'islenen' => 0, 'alinan' => 0, 'zatenVar' => 0, 'digerSay' => 0, 'digerleri' => [], 'hatalar' => [], 'bitti' => false, 'goruldu' => 0,
        ];
        if ($kaynak === 'pts') {
            $d['asama'] = 'paket';
            $d['paketler'] = PtsPaketi::where('tenant_id', $tenant->id)->orderBy('id')->pluck('id')->all();
            if (! $d['paketler']) { $d['asama'] = 'sorgu'; }

            return $d;
        }
        $f = fopen($yol, 'a');
        $gor = [];
        foreach (preg_split('/\r\n|\r|\n/', (string) $kodlar) as $kod) {
            $kod = trim($kod);
            if ($kod === '') { continue; }
            try {
                $k = Gs1Parser::parse($kod);
                if (! $k['seri']) { throw new \InvalidArgumentException('Seri no (21) yok.'); }
                if (isset($gor[$k['gtin'].'/'.$k['seri']])) { continue; }
                $gor[$k['gtin'].'/'.$k['seri']] = true;
                fwrite($f, json_encode([$k['gtin'], $k['seri'], $k['lot'] ?? null, $k['skt'] ?? null])."\n");
                $d['yazilan']++;
            } catch (\InvalidArgumentException $e) {
                $d['hatalar'][] = mb_substr($kod, 0, 60).' → '.$e->getMessage();
            }
        }
        fclose($f);

        return $d;
    }

    /** Bir adım ilerletir. İTS hatasında istisna fırlatır (durum değişmez, adım tekrarlanabilir). */
    public function adim(Tenant $tenant, array $d): array
    {
        @set_time_limit(280);
        if ($d['asama'] === 'paket') {
            $id = $d['paketler'][$d['paketSira']] ?? null;
            if ($id) {
                $paket = PtsPaketi::where('tenant_id', $tenant->id)->find($id);
                if ($paket) {
                    $f = fopen($d['yol'], 'a');
                    foreach (json_decode($paket->xml, true) ?: [] as $xml) {
                        try {
                            foreach (PtsXmlParser::parse((string) $xml)['urunler'] as $u) {
                                fwrite($f, json_encode([$u['gtin'], $u['sn'], $u['bn'] ?? null, $u['xd'] ?? null])."\n");
                                $d['yazilan']++;
                            }
                        } catch (\InvalidArgumentException) {}
                    }
                    fclose($f);
                }
                $d['paketSira']++;
            }
            if ($d['paketSira'] >= count($d['paketler'])) {
                $d['asama'] = 'sorgu';
            }

            return $d;
        }

        // sorgu aşaması
        $f = fopen($d['yol'], 'r');
        fseek($f, $d['konum']);
        $adaylar = [];
        $okunan = 0;
        while ($okunan < self::SORGU_ADIM && ($satir = fgets($f)) !== false) {
            $okunan++;
            [$gtin, $sn, $bn, $xd] = json_decode($satir, true);
            $adaylar[$gtin.'/'.$sn] = ['gtin' => $gtin, 'sn' => $sn, 'bn' => $bn, 'xd' => $xd];
        }
        $yeniKonum = ftell($f);
        $bitti = feof($f) || fgets($f) === false;
        fclose($f);

        $zatenVar = 0;
        $alinan = 0;
        $digerleri = [];
        $digerSay = 0;
        if ($adaylar) {
            $mevcut = [];
            foreach (array_chunk(array_unique(array_column($adaylar, 'gtin')), 500) as $g) {
                foreach (StokKalemi::where('tenant_id', $tenant->id)->where('durum', 'stokta')->whereIn('gtin', $g)->get(['gtin', 'sn']) as $k) {
                    $mevcut[$k->gtin.'/'.$k->sn] = true;
                }
            }
            $zatenVar = count(array_intersect_key($adaylar, $mevcut));
            $sorulacak = array_diff_key($adaylar, $mevcut);
            if ($sorulacak) {
                $durumlar = $this->its->durumSorgula($tenant, array_values($sorulacak));
                foreach ($durumlar as $s) {
                    $a = $sorulacak[$s['gtin'].'/'.$s['sn']] ?? null;
                    if (! $a) { continue; }
                    $bizde = ($s['gln1'] ?? null) === $tenant->gln && empty($s['gln2']) && ItsHata::basarili($s['uc']);
                    if ($bizde) {
                        StokKalemi::updateOrCreate(
                            ['tenant_id' => $tenant->id, 'gtin' => $a['gtin'], 'sn' => $a['sn']],
                            ['bn' => ($a['bn'] ?? null) ?: null, 'skt' => $a['xd'] ?? null, 'durum' => 'stokta', 'son_islem_at' => now()]
                        );
                        $alinan++;
                    } else {
                        $digerSay++;
                        $digerleri[] = $a + ['gln1' => $s['gln1'], 'gln2' => $s['gln2'], 'neden' => ItsHata::basarili($s['uc'])
                            ? ($s['gln1'] === $tenant->gln ? 'Ürün arada (alıcı henüz Mal Alım yapmamış)' : 'Son sahibi başka paydaş')
                            : (ItsHata::aciklama($s['uc']) ?? 'Bilinmeyen kod ('.$s['uc'].')')];
                    }
                }
            }
        }

        $d['konum'] = $yeniKonum;
        $d['islenen'] += $okunan;
        $d['alinan'] += $alinan;
        $d['zatenVar'] += $zatenVar;
        $d['digerSay'] += $digerSay;
        $d['digerleri'] = array_slice(array_merge($d['digerleri'], $digerleri), 0, self::ORNEK);
        $d['bitti'] = $bitti;
        if ($bitti) {
            StokKalemi::redOdasinaAl($tenant->id);
            AuditLog::kaydet('stok.ice-aktar', ['alinan' => $d['alinan'], 'atlanan' => $d['digerSay']]);
        }

        return $d;
    }

    public function sonuc(array $d): array
    {
        return ['alinan' => $d['alinan'], 'zatenVar' => $d['zatenVar'], 'digerSay' => $d['digerSay'], 'digerleri' => $d['digerleri'], 'hatalar' => $d['hatalar'], 'sorgulanan' => $d['islenen'] - $d['zatenVar']];
    }

    /** Aday sayısı (2. aşama toplamı). */
    public function adayToplam(array $d): int
    {
        return $d['asama'] === 'sorgu' ? $d['yazilan'] : 0;
    }

    public function temizle(array $d): void
    {
        if (! empty($d['yol'])) { @unlink($d['yol']); }
    }
}
