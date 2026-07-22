<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('invoices:mark-overdue')->daily();

// Run monthly asset depreciation on the last day of every month at 23:30.
// Targets the month that is closing (so the default `--month` is correct).
Schedule::command('assets:post-monthly-depreciation')->lastDayOfMonth('23:30');

// Catch drift between MySQL and pgvector caused by raw deletes / FK cascades
// that bypass Eloquent. Idempotent — safe to run when nothing is orphaned.
Schedule::command('vectors:reconcile')->dailyAt('03:15');

// Email listener runs via `php artisan emails:listen` as a long-running
// IMAP IDLE process (managed by RoadRunner service plugin or supervisor).
// No polling scheduler needed — emails are pushed in real time.
