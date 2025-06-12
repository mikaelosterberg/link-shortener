<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process pending clicks that haven't reached the threshold
// This ensures clicks always get processed even with low traffic
Schedule::command('clicks:process-pending')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
