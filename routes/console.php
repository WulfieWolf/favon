<?php

use App\Services\AccountDeletionService;
use App\Services\UserDataExportService;
use App\Services\UserNotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:cluster', function (UserNotificationService $notifications) {
    $count = $notifications->clusterDueEvents();
    $this->info($count.' Benachrichtigungs-Cluster erstellt.');
})->purpose('Fasst fällige Benachrichtigungsereignisse pro Benutzer zusammen.');

Artisan::command('notifications:cleanup', function (UserNotificationService $notifications) {
    $result = $notifications->cleanup();
    $this->info('Bereinigung abgeschlossen: '.json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
})->purpose('Entfernt alte gelesene und veraltete unwichtige Benachrichtigungen.');

Schedule::command('notifications:cluster')->everyTenMinutes()->withoutOverlapping();
Schedule::command('notifications:cleanup')->dailyAt('03:20')->withoutOverlapping();

if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
    Schedule::command('telescope:prune --hours=48')->dailyAt('03:40')->withoutOverlapping();
}

Artisan::command('security:cleanup', function () {
    $deleted = DB::table('security_events')
        ->where('created_at', '<', now()->subDays(90))
        ->delete();

    $this->info($deleted.' alte Security-Ereignisse gelöscht.');

    return 0;
})->purpose('Entfernt Security-Ereignisse, die älter als 90 Tage sind.');

Schedule::command('security:cleanup')->dailyAt('03:50')->withoutOverlapping();

Artisan::command('accounts:finalize-deletions', function (AccountDeletionService $deletions) {
    $count = $deletions->finalizeDue();
    $this->info($count.' fällige Account-Löschungen abgeschlossen.');

    return 0;
})->purpose('Anonymisiert Accounts nach Ablauf der Lösch-Karenz endgültig.');

Schedule::command('accounts:finalize-deletions')->hourly()->withoutOverlapping();

Artisan::command('data-exports:cleanup', function (UserDataExportService $exports) {
    $count = $exports->cleanupExpired();
    $this->info($count.' abgelaufene Datenexporte bereinigt.');
})->purpose('Löscht abgelaufene Self-Service-Datenexporte.');

Schedule::command('data-exports:cleanup')->hourly()->withoutOverlapping();
