<?php

namespace App\Providers;

use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $allowedIPs = array_map('trim', explode(',', config('app.debug_allowed_ips', '')));

        $allowedIPs = array_filter($allowedIPs);

        if (empty($allowedIPs)) {
            return;
        }

        if (in_array(Request::ip(), $allowedIPs)) {
            Debugbar::enable();
        } else {
            Debugbar::disable();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_contains(config('filesystems.disks.public.url', ''), 'localhost')) {
            config(['filesystems.disks.public.url' => 'https://mutindoexpress.com/storage']);
            \Illuminate\Support\Facades\Storage::forgetDisk('public');
            \Illuminate\Support\Facades\Storage::forgetDisk('local');
        }

        if (config('app.url') === 'http://localhost' || !str_starts_with(config('app.url'), 'https://')) {
            URL::forceRootUrl('https://mutindoexpress.com');
            URL::forceScheme('https');
        }

        ParallelTesting::setUpTestDatabase(function (string $database, int $token) {
            Artisan::call('db:seed');
        });
    }
}
