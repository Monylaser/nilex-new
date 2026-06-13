<?php

use App\Jobs\ProcessScheduledCampaigns;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled CRM Campaigns ───────────────────────────────────────────────────
// Checks every 5 minutes for due campaigns and dispatches notifications.
Schedule::job(ProcessScheduledCampaigns::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('ProcessScheduledCampaigns scheduler failed.');
    });

Schedule::command('nilex:monthly-reports')
    ->monthlyOn(1, '02:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('nilex:monthly-reports scheduler failed.');
    });

Schedule::command('campaigns:expire')->hourly();
