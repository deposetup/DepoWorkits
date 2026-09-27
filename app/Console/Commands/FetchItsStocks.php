<?php

namespace App\Console\Commands;

use App\Models\Depot;
use App\Models\ItsStock;
use App\Notifications\DailyStockReportNotification;
use App\Services\ItsClient;
use App\Services\ItsIntegrationException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FetchItsStocks extends Command
{
    protected $signature = 'its:fetch-stocks';

    protected $description = 'Aktif depoların İTS stoğunu çeker ve depo yetkilisine Excel (CSV) ekli mail gönderir.';

    public function handle(ItsClient $itsClient): int
    {
        // İTS stok raporu KDS'den gelir ve bir önceki günün gün sonunu yansıtır.
        $stockDate = now()->subDay()->toDateString();
        $failed = false;

        foreach (Depot::where('status', 'active')->get() as $depot) {
            try {
                $rows = $itsClient->fetchStock($depot);
            } catch (ItsIntegrationException $e) {
                $this->error("{$depot->company_title}: {$e->getMessage()}");
                $failed = true;

                continue;
            }

            // Aynı gün tekrar çalıştırılırsa o günün verisi yenilenir.
            DB::transaction(function () use ($depot, $stockDate, $rows) {
                $depot->itsStocks()->whereDate('stock_date', $stockDate)->delete();

                foreach ($rows as $row) {
                    $depot->itsStocks()->create($row + ['stock_date' => $stockDate]);
                }
            });

            if ($depot->email) {
                Notification::route('mail', $depot->email)
                    ->notify(new DailyStockReportNotification($depot, $depot->itsStocks()->whereDate('stock_date', $stockDate)->orderBy('product_name')->get()));
            }

            $this->info("{$depot->company_title}: ".count($rows).' ürün alındı.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
