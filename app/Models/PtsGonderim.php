<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PtsGonderim extends Model
{
    protected $table = 'pts_gonderimleri';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['kalemler' => 'array', 'gonderildi_at' => 'datetime'];
    }

    /** Yeni eklenen sütunlar migration çalışana kadar yoksa, ilgili alanlar sessizce atlanır. */
    public static function mevcutAlanlar(array $alanlar): array
    {
        static $var = [];
        $var['ek'] ??= Schema::hasColumn('pts_gonderimleri', 'belge_no');

        return $var['ek'] ? $alanlar : array_diff_key($alanlar, array_flip(['kaynak_id', 'belge_no', 'alici_unvan']));
    }

    /** Alıcı GLN'inin ünvanı: portal müşterisi ya da daha önce İTS paydaş listesinden getirilmişse. */
    public static function unvanBul(string $gln): ?string
    {
        $t = Tenant::where('gln', $gln)->value('unvan');
        if ($t) {
            return $t;
        }
        $c = Cache::get('gln-sorgu:'.$gln.':-');

        return is_array($c) && ($c['bulundu'] ?? false) ? ($c['unvan'] ?? null) : null;
    }
}
