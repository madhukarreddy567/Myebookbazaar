<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 7: check GitHub for updates every 6 hours (the command itself
// skips when updates or auto-check are disabled). Never applies anything.
Schedule::command('updates:check')->everySixHours();

// Phase 7: daily automatic backup (retention handled by BackupService).
Schedule::command('backups:auto')->daily();
