<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Deponun İTS'deki stok görüntüsü (her gece its:fetch-stocks ile güncellenir).
 * İleride deponun kendi stoğu ayrı bir sütun olarak yanına eklenecek.
 */
class ItsStock extends Model
{
    protected $fillable = [
        'depot_id',
        'stock_date',
        'gtin',
        'product_name',
        'its_quantity',
    ];

    protected function casts(): array
    {
        return [
            'stock_date' => 'date',
        ];
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class);
    }

    /**
     * Excel'in doğrudan açabileceği CSV üretir (UTF-8 BOM + ";" ayraç,
     * Türkçe Excel ayarlarıyla uyumlu).
     */
    public static function toCsv(Collection $stocks): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['GTIN', 'Ürün Adı', 'İTS Stok'], ';', '"', '');

        foreach ($stocks as $stock) {
            fputcsv($handle, [$stock->gtin, $stock->product_name, $stock->its_quantity], ';', '"', '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
