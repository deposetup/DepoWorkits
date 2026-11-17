<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Parça parça (JSON) gönderilen bildirimlerin sonuçlarını sunucuda biriktirir;
 * bitince aynı sonuç ekranı bu birikimden çizilir (PTS adımı, geri al vb. korunur).
 */
class TopluSonuc
{
    private static function anahtar(Request $r, string $tur): ?string
    {
        $id = $r->input('toplu_id');

        return is_string($id) && preg_match('/^[A-Za-z0-9-]{8,64}$/', $id) ? 'toplu:'.$r->user()->tenant_id.':'.$tur.':'.$id : null;
    }

    public static function ekle(Request $r, string $tur, array $sonuc): void
    {
        $k = self::anahtar($r, $tur);
        if (! $k) {
            return;
        }
        $d = Cache::get($k, ['satirlar' => [], 'bildirim' => null, 'kalemler_ok' => []]);
        $d['satirlar'] = array_merge($d['satirlar'], $sonuc['satirlar']);
        $d['bildirim'] = $sonuc['bildirim'] ?? $d['bildirim'];
        $d['kalemler_ok'] = array_merge($d['kalemler_ok'], $sonuc['kalemler_ok'] ?? []);
        Cache::put($k, $d, now()->addHour());
    }

    public static function al(Request $r, string $tur): ?array
    {
        $k = self::anahtar($r, $tur);

        return $k ? Cache::pull($k) : null;
    }
}
