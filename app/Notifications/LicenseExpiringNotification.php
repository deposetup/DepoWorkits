<?php

namespace App\Notifications;

use App\Models\License;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public License $license, public int $daysRemaining)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $depot = $this->license->depot;

        return (new MailMessage)
            ->subject("İTS Lisansınızın Süresi Yaklaşıyor — {$this->daysRemaining} gün kaldı")
            ->greeting("Merhaba {$depot->company_title},")
            ->line("DepoWork İTS lisansınızın bitiş tarihi: {$this->license->ends_at->format('d.m.Y')}.")
            ->line("Süre dolmasına {$this->daysRemaining} gün kaldı.")
            ->line('Kesintisiz kullanım için lisansınızı yenilemenizi rica ederiz.');
    }
}
