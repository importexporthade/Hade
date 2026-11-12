<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Each task runs inside the scheduler's own PHP process. Schedule::command() starts a new
 * process for it, which needs proc_open, and Hostinger disables it: every task failed there.
 */
Schedule::call(fn () => Artisan::call('hade:complaint-reminders'))
    ->name('hade:complaint-reminders')
    ->dailyAt('08:00');

// Hostinger deploys without running artisan, so the caches catch up within a minute
Schedule::call(fn () => Artisan::call('hade:refresh-caches'))
    ->name('hade:refresh-caches')
    ->everyMinute()
    ->withoutOverlapping();
