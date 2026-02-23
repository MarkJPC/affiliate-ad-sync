<?php

namespace App\Providers;

use App\Models\SyncLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $failedSyncsCount = Cache::remember('failed_syncs_24h', 300, function () {
                return SyncLog::where('status', 'failed')
                    ->where('started_at', '>=', now()->subDay())
                    ->count();
            });

            $view->with('failedSyncsCount', $failedSyncsCount);
        });
    }
}
