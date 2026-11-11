<?php

namespace App\Modules\Gallery\Console;

use App\Modules\Gallery\Models\GallerySetting;
use App\Modules\Gallery\Support\InstagramFeed;
use Illuminate\Console\Command;
use Throwable;

/**
 * Renews the Instagram token before it expires (they last 60 days). Runs
 * daily from the scheduler; does nothing until the token is due.
 */
class RefreshInstagramToken extends Command
{
    protected $signature = 'gallery:refresh-instagram {--force : Renew even when the token is not due yet}';

    protected $description = 'Renew the Instagram access token used by the public gallery';

    public function handle(InstagramFeed $instagram): int
    {
        $setting = GallerySetting::current();

        if (blank($setting->instagram_token)) {
            $this->info('No Instagram account connected.');

            return self::SUCCESS;
        }

        $due = $setting->instagram_expires_at === null
            || $setting->instagram_expires_at->lte(now()->addDays((int) config('modules.gallery.instagram.refresh_days_before')));

        if (! $due && ! $this->option('force')) {
            $this->info('Token is not due yet.');

            return self::SUCCESS;
        }

        try {
            $fresh = $instagram->refresh((string) $setting->instagram_token);
        } catch (Throwable $exception) {
            $this->error('Instagram did not renew the token: '.$exception->getMessage());

            return self::FAILURE;
        }

        $setting->update([
            'instagram_token' => $fresh['token'],
            'instagram_expires_at' => $fresh['expires_at'],
        ]);

        $this->info('Token renewed until '.$fresh['expires_at']->toDateString().'.');

        return self::SUCCESS;
    }
}
