<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('hade:complaint-reminders')->dailyAt('08:00');

// Hostinger deploys without running artisan, so the caches catch up within a minute
Schedule::command('hade:refresh-caches')->everyMinute()->withoutOverlapping();
