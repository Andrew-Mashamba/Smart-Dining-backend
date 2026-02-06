<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Production Scheduled Tasks
// Ensure cron is configured: * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

// Daily database backup at 2 AM (requires spatie/laravel-backup package)
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('02:00');

// Daily sales summary email at 8 AM
Schedule::call(function () {
    // Send daily sales summary to management
    Artisan::call('reports:daily-sales-summary');
})->dailyAt('08:00')->name('daily-sales-summary')->onOneServer();

// Clear expired sessions daily at 3 AM
Schedule::command('session:gc')->daily()->at('03:00');

// Cleanup old log files (older than 30 days) weekly on Sunday at 1 AM
Schedule::command('log:clear')->weeklyOn(0, '01:00');

// Prune old notifications every week
Schedule::command('model:prune', [
    '--model' => [\App\Models\Notification::class],
])->weekly();

// Update order statuses every 5 minutes (for payment confirmations, etc.)
Schedule::call(function () {
    Artisan::call('orders:update-statuses');
})->everyFiveMinutes()->name('update-order-statuses')->onOneServer();

// Send reminder notifications for pending orders every hour during business hours
Schedule::call(function () {
    Artisan::call('notifications:send-reminders');
})->hourly()->between('09:00', '22:00')->name('send-reminders');

// Queue monitoring - alert if queue is too long
Schedule::call(function () {
    $queueSize = \Illuminate\Support\Facades\Queue::size('default');
    if ($queueSize > 100) {
        \Illuminate\Support\Facades\Log::error('Queue size is too large', ['size' => $queueSize]);
    }
})->everyFiveMinutes()->name('monitor-queue-size');

// Clean failed jobs older than 7 days
Schedule::command('queue:prune-failed', ['--hours' => 168])->daily();

// Optimize application daily at 4 AM (caches config, routes, views)
Schedule::command('optimize')->daily()->at('04:00');
