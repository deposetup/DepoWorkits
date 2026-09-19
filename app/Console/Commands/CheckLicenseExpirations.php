<?php

namespace App\Console\Commands;

use App\Models\License;
use App\Models\LicenseReminder;
use App\Notifications\LicenseExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckLicenseExpirations extends Command
{
    protected $signature = 'licenses:check-expirations';

    protected $description = 'Süresi yaklaşan lisanslar için depo yetkililerine hatırlatma maili gönderir.';

    public function handle(): int
    {
        $thresholds = collect(explode(',', config('services.its.license_reminder_days', '30,14,7,1')))
            ->map(fn ($d) => (int) trim($d));

        $licenses = License::query()
            ->where('status', 'active')
            ->whereDate('ends_at', '>=', now())
            ->with('depot')
            ->get();

        foreach ($licenses as $license) {
            $daysRemaining = $license->daysRemaining();

            if (! $thresholds->contains($daysRemaining)) {
                continue;
            }

            $alreadySent = LicenseReminder::where('license_id', $license->id)
                ->where('days_before', $daysRemaining)
                ->exists();

            if ($alreadySent || ! $license->depot?->email) {
                continue;
            }

            Notification::route('mail', $license->depot->email)
                ->notify(new LicenseExpiringNotification($license, $daysRemaining));

            LicenseReminder::create([
                'license_id' => $license->id,
                'days_before' => $daysRemaining,
                'sent_at' => now(),
            ]);

            $this->info("Hatırlatma gönderildi: {$license->depot->company_title} ({$daysRemaining} gün kaldı)");
        }

        return self::SUCCESS;
    }
}
