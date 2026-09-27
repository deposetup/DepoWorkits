<?php

namespace App\Notifications;

use App\Models\Depot;
use App\Models\ItsStock;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class DailyStockReportNotification extends Notification
{
    use Queueable;

    public function __construct(public Depot $depot, public Collection $stocks)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = optional($this->stocks->first())->stock_date?->format('d.m.Y') ?? now()->subDay()->format('d.m.Y');

        return (new MailMessage)
            ->subject("İTS Stok Raporu — {$date}")
            ->greeting("Merhaba {$this->depot->company_title},")
            ->line("{$date} gün sonu İTS stok listeniz ektedir ({$this->stocks->count()} ürün).")
            ->action('Stok Sayfasını Aç', route('stocks.index'))
            ->attachData(ItsStock::toCsv($this->stocks), "its-stok-{$date}.csv", ['mime' => 'text/csv']);
    }
}
