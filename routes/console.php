<?php

use App\Services\DemoDataResetService;
use App\Services\PerformanceDataService;
use App\Services\PlaceDataScoreService;
use App\Services\SiteAccessService;
use App\Services\PerformanceExplainService;
use App\Services\PerformanceReportService;
use App\Services\Imports\BayernAtkisCatalogAnalysisService;
use App\Services\Imports\BayernAtkisSyncService;
use App\Services\Imports\Datex2ParkingDryRunService;
use App\Services\Imports\Datex2ParkingStageService;
use App\Services\Imports\ExternalRecordClassificationService;
use App\Services\Imports\ImportTestResetService;
use App\Services\Imports\NiedersachsenHubCatalogAnalysisService;
use App\Services\Imports\NiedersachsenHubSyncService;
use App\Services\Imports\NrwTfisSyncService;
use App\Services\Imports\OverturePlacesCsvStageService;
use App\Services\Imports\OvertureCandidateExportService;
use App\Services\Imports\OvertureIgnoreListService;
use App\Services\Imports\OverturePlaceTypeReviewService;
use App\Services\Imports\RvrCampingSyncService;
use App\Services\Imports\ExternalDuplicateReviewReportService;
use App\Services\UserNotificationService;
use App\Services\UserDataExportService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Artisan::command('camperwolf:mode {mode : normal, registration_closed or lockdown} {--message= : Optional public notice}', function (SiteAccessService $access) {
    $mode = (string) $this->argument('mode');

    if (! in_array($mode, SiteAccessService::MODES, true)) {
        $this->error('Invalid mode. Use normal, registration_closed or lockdown.');
        return 1;
    }

    $access->set($mode, $this->option('message'));
    $this->info('Camperwolf access mode set to '.$mode.'.');

    return 0;
})->purpose('Changes the Camperwolf site access mode, including emergency recovery from lockdown.');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('places:recalculate-data-scores {--dirty : Nur als veraltet markierte DatenScores neu berechnen}', function (PlaceDataScoreService $scores) {
    $dirtyOnly = (bool) $this->option('dirty');
    $count = $scores->recalculateAll($dirtyOnly);

    $this->info($count.' DatenScores neu berechnet.');

    return 0;
})->purpose('Berechnet DatenScores aus den aktuellen Platzdaten und dem aktuellen Merkmalskatalog neu.');

Schedule::command('places:recalculate-data-scores --dirty')
    ->everyFiveMinutes()
    ->withoutOverlapping();


Artisan::command('notifications:cluster', function (UserNotificationService $notifications) {
    $count = $notifications->clusterDueEvents();
    $this->info($count.' Benachrichtigungs-Cluster erstellt.');
})->purpose('Fasst fällige Benachrichtigungsereignisse pro Benutzer zusammen.');

Artisan::command('notifications:cleanup', function (UserNotificationService $notifications) {
    $result = $notifications->cleanup();
    $this->info('Bereinigung abgeschlossen: '.json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
})->purpose('Entfernt alte gelesene und veraltete unwichtige Benachrichtigungen.');

Schedule::command('notifications:cluster')
    ->everyTenMinutes()
    ->withoutOverlapping();

Schedule::command('notifications:cleanup')
    ->dailyAt('03:20')
    ->withoutOverlapping();


if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
    Schedule::command('telescope:prune --hours=48')
        ->dailyAt('03:40')
        ->withoutOverlapping();
}


Artisan::command('security:cleanup', function () {
    $deleted = \Illuminate\Support\Facades\DB::table('security_events')
        ->where('created_at', '<', now()->subDays(90))
        ->delete();

    $this->info($deleted.' alte Security-Ereignisse gelöscht.');

    return 0;
})->purpose('Entfernt Security-Ereignisse, die älter als 90 Tage sind.');

Schedule::command('security:cleanup')
    ->dailyAt('03:50')
    ->withoutOverlapping();


Artisan::command('notifications:seed-test {--user= : User ID or email; defaults to the first user}', function (UserNotificationService $notifications) {
    $selector = $this->option('user');

    $userQuery = \App\Models\User::query();
    if ($selector) {
        if (ctype_digit((string) $selector)) {
            $userQuery->whereKey((int) $selector);
        } else {
            $userQuery->where('email', (string) $selector);
        }
    } else {
        $userQuery->orderBy('id');
    }

    $user = $userQuery->first();

    if (! $user) {
        $this->error('Kein passender Benutzer gefunden.');
        return 1;
    }

    $places = \Illuminate\Support\Facades\DB::table('places')
        ->where('is_active', true)
        ->orderBy('id')
        ->limit(3)
        ->get(['id', 'name', 'slug', 'publication_status']);

    $notifications->createImmediate(
        (int) $user->id,
        'test_welcome',
        'Willkommen bei Camperwolf',
        'Dies ist eine Testnachricht für die Glocke. Später verweist sie auf das Tutorial im Hilfebereich.',
        null,
        'normal',
        'bell',
    );

    $notifications->createImmediate(
        (int) $user->id,
        'test_update',
        'Neue Funktion verfügbar',
        'Test: Die Merkmalsbearbeitung wurde verbessert. Diese Nachricht dient nur zum Testen der Benachrichtigungsansicht.',
        null,
        'important',
        'bell',
    );

    foreach ($places as $index => $place) {
        $decision = $index === 1 ? 'rejected' : 'approved';
        $notifications->queueEvent(
            (int) $user->id,
            'moderation_decision',
            'moderation:decisions',
            $decision === 'approved' ? 'Vorschlag bestätigt' : 'Vorschlag abgelehnt',
            $decision === 'approved'
                ? 'Dein Test-Vorschlag für „'.$place->name.'“ wurde bestätigt.'
                : 'Dein Test-Vorschlag für „'.$place->name.'“ wurde abgelehnt. Begründung: Testfall für die Detailansicht.',
            $place->publication_status === 'published'
                ? route('places.show', $place->slug)
                : null,
            (int) $place->id,
            [
                'decision' => $decision,
                'test' => true,
            ],
        );
    }

    $notifications->createImmediate(
        (int) $user->id,
        'test_favorite',
        'Änderung an einem Favoriten',
        'Test: Bei einem deiner favorisierten Plätze hat sich etwas geändert.',
        null,
        'normal',
        'bell',
    );

    $this->info('Testbenachrichtigungen für '.$user->email.' wurden erstellt.');
    $this->line('Direkte Nachrichten sind sofort sichtbar.');
    $this->line('Moderations-Events werden regulär nach dem Cluster-Zeitfenster zusammengefasst.');
    $this->line('Für einen sofortigen Cluster-Test kannst du danach php artisan notifications:cluster-test ausführen.');

    return 0;
})->purpose('Erstellt mehrere Testbenachrichtigungen für die UI-Prüfung.');

Artisan::command('notifications:cluster-test {--user= : User ID or email; defaults to all users with open events}', function (UserNotificationService $notifications) {
    \Illuminate\Support\Facades\DB::table('notification_events')
        ->whereNull('processed_at')
        ->when($this->option('user'), function ($query, $selector) {
            $user = ctype_digit((string) $selector)
                ? \App\Models\User::find((int) $selector)
                : \App\Models\User::where('email', (string) $selector)->first();

            if (! $user) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where('user_id', $user->id);
        })
        ->update([
            'occurred_at' => now()->subMinutes(60),
            'updated_at' => now(),
        ]);

    $count = $notifications->clusterDueEvents();
    $this->info($count.' Test-Cluster erstellt.');

    return 0;
})->purpose('Macht offene Events sofort clusterfähig und verarbeitet sie.');


Artisan::command('imports:overture-stage {path? : Pfad zur 06_all_candidates.csv}', function (OverturePlacesCsvStageService $stage) {
    $path = (string) ($this->argument('path') ?: storage_path('app/private/overture-research/places/06_all_candidates.csv'));

    try {
        $result = $stage->stage($path);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info('Overture Places wurden ausschließlich gestaged.');
    $this->table(
        ['Kennzahl', 'Anzahl'],
        [
            ['CSV-Zeilen', number_format($result['csv_rows'], 0, ',', '.')],
            ['Gemappte Records', number_format($result['mapped_records'], 0, ',', '.')],
            ['Neu', number_format($result['staging']['new'], 0, ',', '.')],
            ['Geändert', number_format($result['staging']['changed'], 0, ',', '.')],
            ['Unverändert', number_format($result['staging']['unchanged'], 0, ',', '.')],
            ['Nicht mehr in Quelle', number_format($result['staging']['missing_marked'], 0, ',', '.')],
        ],
    );
    $this->line('Source-ID: '.$result['source_id'].' · Run-ID: '.$result['run_id']);
    $this->comment('Es wurde absichtlich noch keine Kandidaten-/Dublettenklassifizierung ausgeführt.');

    return 0;
})->purpose('Staged den vorbereiteten deutschen Overture-Places-Camping-Datensatz ohne Veröffentlichung oder Dublettenklassifizierung.');


Artisan::command('imports:overture-export-open {path? : Optionaler Ausgabe-Pfad}', function (OvertureCandidateExportService $export) {
    $path = (string) ($this->argument('path') ?: storage_path('app/private/overture-research/places/overture_open_candidates.csv'));

    try {
        $result = $export->exportOpen($path);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info(number_format($result['records'], 0, ',', '.').' offene Overture-Records exportiert.');
    $this->line('Datei: '.$result['path']);

    return 0;
})->purpose('Exportiert offene gestagte Overture-Records für die einmalige inhaltliche Vorprüfung.');


Artisan::command('imports:overture-ignore {path? : Pfad zur versionierten Ignore-CSV} {--apply : Änderungen wirklich schreiben} {--user= : Optionaler Admin-User-ID für Audit}', function (OvertureIgnoreListService $ignore) {
    $path = (string) ($this->argument('path') ?: base_path('scripts/research/overture/2026-09-23.1-ignore.csv'));
    $apply = (bool) $this->option('apply');
    $actorId = $this->option('user');
    $actorId = $actorId !== null && $actorId !== '' ? (int) $actorId : null;

    try {
        $result = $ignore->run($path, $apply, $actorId);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info($apply ? 'Overture-Ignore-Liste angewendet.' : 'Overture-Ignore-Liste Vorschau.');
    $this->table(
        ['Ergebnis', 'Anzahl'],
        [
            ['In Liste', number_format($result['listed'], 0, ',', '.')],
            ['Würde ignorieren', number_format($result['would_ignore'], 0, ',', '.')],
            ['Neu ignoriert', number_format($result['ignored'], 0, ',', '.')],
            ['Bereits ignoriert', number_format($result['already_ignored'], 0, ',', '.')],
            ['Nicht gefunden', number_format(count($result['not_found']), 0, ',', '.')],
            ['Nicht anwendbar', number_format(count($result['blocked']), 0, ',', '.')],
        ],
    );

    if (! $apply && $result['would_ignore'] > 0) {
        $this->comment('Keine Daten geändert. Mit --apply anwenden.');
    }

    if ($result['not_found'] !== []) {
        $this->warn('Nicht gefunden: '.implode(', ', array_slice($result['not_found'], 0, 10)));
    }

    if ($result['blocked'] !== []) {
        $this->warn('Nicht anwendbar: '.implode(', ', array_slice($result['blocked'], 0, 10)));
    }

    return 0;
})->purpose('Prüft oder setzt versioniert freigegebene Overture-Fehlklassifizierungen auf ignored.');


Artisan::command('imports:overture-type-export {path? : Optionaler Ausgabe-Pfad}', function (OverturePlaceTypeReviewService $review) {
    $path = (string) ($this->argument('path') ?: storage_path('app/private/overture-research/places/overture_created_type_review.csv'));

    try {
        $result = $review->exportCreated($path);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info(number_format($result['records'], 0, ',', '.').' bereits übernommene Overture-Plätze für die einmalige Typprüfung exportiert.');
    $this->line('Datei: '.$result['path']);
    $this->comment('Offene mögliche Dubletten sind ausdrücklich nicht enthalten.');

    return 0;
})->purpose('Exportiert ausschließlich bereits angelegte Overture-Plätze für die einmalige Platztypbereinigung.');


Artisan::command('imports:overture-type-review {path? : Pfad zur ausgefüllten Typ-Review-CSV} {--apply : Änderungen wirklich schreiben}', function (OverturePlaceTypeReviewService $review) {
    $path = (string) ($this->argument('path') ?: storage_path('app/private/overture-research/places/overture_created_type_review.csv'));
    $apply = (bool) $this->option('apply');

    try {
        $result = $review->review($path, $apply);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info($apply ? 'Overture-Platztypprüfung angewendet.' : 'Overture-Platztypprüfung Vorschau.');
    $this->table(
        ['Ergebnis', 'Anzahl'],
        [
            ['CSV-Zeilen', number_format($result['rows'], 0, ',', '.')],
            ['Mit Zieltyp', number_format($result['with_target'], 0, ',', '.')],
            ['Würde ändern', number_format($result['would_change'], 0, ',', '.')],
            ['Geändert', number_format($result['changed'], 0, ',', '.')],
            ['Unverändert', number_format($result['unchanged'], 0, ',', '.')],
            ['Blockiert', number_format($result['blocked'], 0, ',', '.')],
        ],
    );

    foreach (array_slice(array_filter(
        $result['changes'],
        fn (array $row): bool => in_array($row['status'], ['would_change', 'changed', 'blocked'], true),
    ), 0, 30) as $row) {
        $this->line(
            '#'.$row['place_id'].' '.$row['name'].' · '.$row['current_place_type'].' -> '
            .$row['target_place_type'].' · '.$row['status'].' · '.$row['message']
        );
    }

    if (! $apply && $result['would_change'] > 0) {
        $this->comment('Keine Platztypen geändert. Nach Prüfung mit --apply anwenden.');
    }

    return $result['blocked'] > 0 ? 2 : 0;
})->purpose('Prüft oder übernimmt eine einmalige, versionierte Platztypklassifizierung bereits angelegter Overture-Plätze.');


Artisan::command('imports:overture-classify', function (ExternalRecordClassificationService $classifier) {
    $sourceId = \Illuminate\Support\Facades\DB::table('external_sources')
        ->where('slug', OverturePlacesCsvStageService::SOURCE_SLUG)
        ->value('id');

    if (! $sourceId) {
        $this->error('Overture-Quelle wurde noch nicht gestaged.');
        return 1;
    }

    try {
        $result = $classifier->classifySource((int) $sourceId, true);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info('Overture-Kandidaten klassifiziert.');
    $this->table(
        ['Klasse', 'Anzahl'],
        [
            ['Neue Kandidaten', number_format($result['new_candidate'], 0, ',', '.')],
            ['Mögliche Dubletten', number_format($result['possible_duplicate'], 0, ',', '.')],
            ['Prüfung nötig', number_format($result['needs_review'], 0, ',', '.')],
            ['Source-only', number_format($result['source_only'], 0, ',', '.')],
            ['Review-Fälle', number_format($result['review_items'], 0, ',', '.')],
            ['Externe Dublettengruppen', number_format($result['duplicate_groups']['groups'] ?? 0, 0, ',', '.')],
        ],
    );

    return 0;
})->purpose('Klassifiziert gestagte Overture-Records nach dem vorgelagerten Ignore-Schritt.');


Artisan::command('imports:reset-source {slug : Externe Quellen-Slug} {--force : Ohne Rückfrage zurücksetzen}', function (ImportTestResetService $reset) {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('Der Quellen-Reset ist ausschließlich in local/testing erlaubt.');
        return 1;
    }

    $slug = (string) $this->argument('slug');

    if (! $this->option('force')) {
        $confirmed = $this->confirm(
            'Lokale Importdaten der Quelle "'.$slug.'" einschließlich daraus erzeugter, nicht von anderen Quellen geteilter Plätze löschen?',
            false,
        );

        if (! $confirmed) {
            $this->warn('Quellen-Reset abgebrochen.');
            return 1;
        }
    }

    try {
        $result = $reset->resetSource($slug);
    } catch (\RuntimeException $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info('Quellen-Reset abgeschlossen: '.json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return 0;
})->purpose('Setzt lokal genau eine externe Importquelle und ihre erzeugten Testplätze zurück.');

Artisan::command('camperwolf:demo-reset {--force : Ohne Rückfrage zurücksetzen}', function (DemoDataResetService $reset) {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('Der Demo-Reset ist ausschließlich in local/testing erlaubt.');
        return 1;
    }

    if (! $this->option('force')) {
        $confirmed = $this->confirm(
            'Alle usergenerierten Camperwolf-Daten werden gelöscht. Der konfigurierte Owner-Account bleibt erhalten. Fortfahren?',
            false,
        );

        if (! $confirmed) {
            $this->warn('Demo-Reset abgebrochen.');
            return 1;
        }
    }

    try {
        $before = $reset->reset();
    } catch (\RuntimeException $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info(
        'Bereinigt: '.$before['places'].' Plätze, '.$before['reviews'].' Reviews, '
        .$before['non_owner_users'].' Nicht-Owner-Accounts.',
    );
    $this->line('Owner bleibt erhalten: '.$before['owner_email'].' (#'.$before['owner_id'].')');

    $exitCode = $this->call('db:seed', [
        '--class' => DemoDataSeeder::class,
        '--force' => true,
    ]);

    if ($exitCode !== 0) {
        $this->error('Demo-Seeding ist fehlgeschlagen.');
        return $exitCode;
    }

    $this->newLine();
    $this->info('Camperwolf Demo-Datensatz wurde neu aufgebaut.');
    $this->line('10 Testuser: demo01@camperwolf.test bis demo10@camperwolf.test');
    $this->line('Passwort für alle Testuser: '.DemoDataSeeder::testPassword());
    $this->line('50 breit verteilte Demo-Plätze inklusive Reviews, Historien, XP, Level, Badges und Achievements.');

    return 0;
})->purpose('Setzt lokale usergenerierte Daten zurück und erzeugt einen vollständigen Camperwolf-Demo-Datensatz.');


Artisan::command('camperwolf:performance-seed {places=10000 : 10000, 25000 oder 50000}', function (PerformanceDataService $performance) {
    $places = (int) $this->argument('places');

    try {
        $result = $performance->seed($places);
    } catch (\RuntimeException $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info('Performance-Datensatz erstellt.');
    $this->table(
        ['Kennzahl', 'Anzahl'],
        [
            ['Plätze', number_format($result['places'], 0, ',', '.')],
            ['Benutzer', number_format($result['users'], 0, ',', '.')],
            ['Reviews', number_format($result['reviews'], 0, ',', '.')],
            ['Fotos', number_format($result['photos'], 0, ',', '.')],
            ['Favoriten', number_format($result['favorites'], 0, ',', '.')],
            ['Preisangebote', number_format($result['price_offers'], 0, ',', '.')],
        ],
    );
    $this->line('Erzeugungsdauer: '.$result['seconds'].' s');
    $this->line('Dummybilder: storage/app/private/'.PerformanceDataService::STORAGE_DIRECTORY);

    return 0;
})->purpose('Erzeugt einen reproduzierbaren lokalen Performance-Datensatz mit 10k, 25k oder 50k Plätzen.');

Artisan::command('camperwolf:performance-clear {--force : Ohne Rückfrage löschen}', function (PerformanceDataService $performance) {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('Performance-Daten dürfen ausschließlich in local/testing gelöscht werden.');
        return 1;
    }

    if (! $performance->hasData()) {
        $this->info('Keine Performance-Daten vorhanden.');
        return 0;
    }

    if (! $this->option('force') && ! $this->confirm('Alle markierten Performance-Daten löschen?', false)) {
        $this->warn('Löschen abgebrochen.');
        return 1;
    }

    try {
        $result = $performance->clear();
    } catch (\RuntimeException $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info(
        'Gelöscht: '.$result['places'].' Plätze, '.$result['users'].' Benutzer, '.$result['photos'].' Foto-Datensätze.',
    );

    return 0;
})->purpose('Entfernt ausschließlich den lokalen Camperwolf-Performance-Datensatz.');


Artisan::command('imports:test-reset {--force : Ohne Rückfrage zurücksetzen}', function (ImportTestResetService $reset) {
    if (! app()->environment(['local', 'testing'])) {
        $this->error('Der Import-Testreset ist ausschließlich in local/testing erlaubt.');
        return 1;
    }

    if (! $this->option('force')) {
        $confirmed = $this->confirm(
            'Performance-Daten sowie alle gestagten Importdaten und daraus erzeugten Import-Plätze löschen? Normale Camperwolf-Plätze bleiben erhalten.',
            false,
        );

        if (! $confirmed) {
            $this->warn('Import-Testreset abgebrochen.');
            return 1;
        }
    }

    try {
        $result = $reset->reset();
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info('Import-Testbestand zurückgesetzt.');
    $this->table(
        ['Bereich', 'Gelöscht'],
        [
            ['Performance-Plätze', number_format($result['performance_places'], 0, ',', '.')],
            ['Performance-Benutzer', number_format($result['performance_users'], 0, ',', '.')],
            ['Performance-Fotos', number_format($result['performance_photos'], 0, ',', '.')],
            ['Aus Import erzeugte Plätze', number_format($result['imported_places'], 0, ',', '.')],
            ['Externe Datensätze', number_format($result['external_records'], 0, ',', '.')],
            ['Import-Läufe', number_format($result['import_runs'], 0, ',', '.')],
            ['Review-Fälle', number_format($result['review_items'], 0, ',', '.')],
            ['Roh-Snapshots', number_format($result['raw_snapshots'], 0, ',', '.')],
        ],
    );
    $this->line('Externe Quellenkonfigurationen wurden beibehalten und ihre letzten Sync-Zeitpunkte zurückgesetzt.');

    return 0;
})->purpose('Setzt lokalen Performance- und Import-Testbestand für einen vollständigen Neuimport zurück.');


Artisan::command('camperwolf:performance-report {--minutes=60 : Auswertungszeitraum in Minuten} {--limit=10 : Maximale Zeilen pro Bereich} {--full : Schlüssel/Queries nicht kürzen}', function (PerformanceReportService $performance) {
    $minutes = max(1, (int) $this->option('minutes'));
    $limit = max(1, (int) $this->option('limit'));
    $full = (bool) $this->option('full');

    $report = $performance->report($minutes, $limit, $full);

    $this->info('Camperwolf Performance Report');
    $this->line('Zeitraum: letzte '.$report['minutes'].' Minuten');
    $this->line('Erstellt: '.$report['generated_at']->format('Y-m-d H:i:s'));
    $this->newLine();

    $this->comment('Slow Requests');
    if ($report['slow_requests']->isEmpty()) {
        $this->line('Keine Einträge.');
    } else {
        $this->table(
            ['Request', 'Anzahl', 'Ø ms', 'Max ms', 'Zuletzt'],
            $report['slow_requests']->map(fn (array $row) => [
                $row['key'],
                $row['occurrences'],
                number_format((float) $row['avg_ms'], 2, ',', '.'),
                number_format((float) $row['max_ms'], 0, ',', '.'),
                $row['latest']->format('H:i:s'),
            ])->all(),
        );
    }

    $this->newLine();
    $this->comment('Slow Queries');
    if ($report['slow_queries']->isEmpty()) {
        $this->line('Keine Einträge.');
    } else {
        $this->table(
            ['Query / Ursprung', 'Anzahl', 'Ø ms', 'Max ms', 'Zuletzt'],
            $report['slow_queries']->map(fn (array $row) => [
                $row['key'],
                $row['occurrences'],
                number_format((float) $row['avg_ms'], 2, ',', '.'),
                number_format((float) $row['max_ms'], 0, ',', '.'),
                $row['latest']->format('H:i:s'),
            ])->all(),
        );
    }

    $this->newLine();
    $this->comment('Server');
    if ($report['servers']->isEmpty()) {
        $this->line('Keine CPU/RAM-Daten. Läuft php artisan pulse:check?');
    } else {
        $this->table(
            ['Metrik', 'Server', 'Ø', 'Max', 'Samples'],
            $report['servers']->map(fn (array $row) => [
                strtoupper($row['type']),
                $row['key'],
                number_format($row['avg'], 2, ',', '.'),
                number_format($row['max'], 2, ',', '.'),
                $row['samples'],
            ])->all(),
        );
    }

    $this->newLine();
    $this->comment('Exceptions');
    if ($report['exceptions']->isEmpty()) {
        $this->line('Keine Einträge.');
    } else {
        $this->table(
            ['Exception / Ursprung', 'Anzahl', 'Zuletzt'],
            $report['exceptions']->map(fn (array $row) => [
                $row['key'],
                $row['occurrences'],
                $row['latest']->format('H:i:s'),
            ])->all(),
        );
    }

    $this->newLine();
    $this->comment('Vorhandene Pulse Entry Types');
    if ($report['entry_types']->isEmpty()) {
        $this->line('Keine Pulse-Entries im gewählten Zeitraum.');
    } else {
        $this->table(
            ['Type', 'Entries'],
            $report['entry_types']->map(fn (array $row) => [
                $row['type'],
                $row['entries'],
            ])->all(),
        );
    }

    return 0;
})->purpose('Gibt einen kompakten Pulse-Performance-Report für Camperwolf aus.');


Artisan::command('camperwolf:performance-explain {--feature= : Optionaler Feature-Slug für den Filterfall}', function (PerformanceExplainService $performance) {
    try {
        $report = $performance->run($this->option('feature') ?: null);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info('Camperwolf Performance Explain');
    $this->line('Datenbank: '.$report['database']);
    $this->line('Feature-Test: '.($report['feature_slug'] ?? '(kein geeignetes Feature gefunden)'));

    foreach ($report['scenarios'] as $name => $scenario) {
        $this->newLine();
        $this->comment(str_repeat('=', 90));
        $this->comment($name);
        $this->comment(str_repeat('=', 90));
        $this->line('Modus: '.$scenario['plan']['mode']);

        if (! empty($scenario['plan']['warning'])) {
            $this->warn($scenario['plan']['warning']);
        }

        $this->line('SQL:');
        $this->line($scenario['sql']);
        $this->line('Bindings: '.json_encode($scenario['bindings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->line('Plan:');

        foreach ($scenario['plan']['rows'] as $row) {
            $this->line(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    return 0;
})->purpose('Führt EXPLAIN ANALYZE für die wichtigsten Browse-Queries aus.');


Artisan::command('accounts:finalize-deletions', function (\App\Services\AccountDeletionService $deletions) {
    $count = $deletions->finalizeDue();
    $this->info($count.' fällige Account-Löschungen abgeschlossen.');

    return 0;
})->purpose('Anonymisiert Accounts nach Ablauf der Lösch-Karenz endgültig.');

Schedule::command('accounts:finalize-deletions')
    ->hourly()
    ->withoutOverlapping();


Artisan::command('data-exports:cleanup', function (UserDataExportService $exports) {
    $count = $exports->cleanupExpired();
    $this->info($count.' abgelaufene Datenexporte bereinigt.');
})->purpose('Löscht abgelaufene Self-Service-Datenexporte.');

Schedule::command('data-exports:cleanup')
    ->hourly()
    ->withoutOverlapping();


Artisan::command('imports:niedersachsen-catalog-analysis {--limit=100 : Datensaetze je Kategorie (1-250)}', function (NiedersachsenHubCatalogAnalysisService $analysis) {
    $limit = max(1, min(250, (int) $this->option('limit')));

    try {
        $result = $analysis->analyze($limit);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info('Niedersachsen-Hub Kataloganalyse');
    $this->line('Analysiert werden ausschließlich strukturierte API-Felder; Freitext wird nicht ausgewertet.');

    $summary = [];
    foreach ($result['categories'] as $category => $data) {
        $summary[] = [
            $category,
            $data['returned'],
            $data['overallcount'],
        ];
    }

    $this->newLine();
    $this->table(['Kategorie', 'Analysiert', 'Gesamt in API'], $summary);

    $sections = [
        'features' => 'Strukturierte Merkmale',
        'payments' => 'Zahlungsarten',
        'number_types' => 'Numerische Feldtypen',
        'attribute_keys' => 'Attribut-Schluessel',
    ];

    foreach ($sections as $key => $label) {
        $values = $result['combined'][$key] ?? [];
        if ($values === []) {
            continue;
        }

        $this->newLine();
        $this->comment($label);
        $this->table(
            ['Wert', 'Anzahl'],
            collect($values)->map(fn (int $count, string $value) => [$value, $count])->values()->all(),
        );
    }

    if (($result['combined']['number_values'] ?? []) !== []) {
        $this->newLine();
        $this->comment('Numerische Wertebereiche');
        $this->table(
            ['Feldtyp', 'Mit Wert', 'Minimum', 'Maximum'],
            collect($result['combined']['number_values'])
                ->map(fn (array $stats, string $type) => [
                    $type,
                    $stats['count'],
                    $stats['min'],
                    $stats['max'],
                ])
                ->values()
                ->all(),
        );
    }

    $path = storage_path('app/niedersachsen-catalog-analysis.json');
    file_put_contents(
        $path,
        json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    );

    $this->newLine();
    $this->info('Analyse gespeichert: '.$path);

    return 0;
})->purpose('Analysiert strukturierte Merkmale, Zahlungsarten und Zahlenfelder von Niedersachsen-Wohnmobilstellplaetzen und Campingplaetzen ohne Freitextauswertung.');


Artisan::command('imports:duplicate-report {--source=niedersachsen-hub-destination-one : Externe Quellen-Slug}', function (ExternalDuplicateReviewReportService $report) {
    try {
        $result = $report->report((string) $this->option('source'));
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info($result['source_name']);
    $this->line('Offene mögliche Dubletten: '.$result['review_count']);

    if ($result['rows'] === []) {
        $this->newLine();
        $this->comment('Keine offenen possible_duplicate-Fälle gefunden.');
        return 0;
    }

    $this->newLine();
    $this->table(
        ['Review', 'Extern', 'Kandidat', 'Entfernung', 'Namensähnlichkeit'],
        collect($result['rows'])->map(fn (array $row) => [
            '#'.$row['review_id'].' · '.$row['external_name'].' ['.$row['external_id'].']',
            $row['candidate_rank'] ? '#'.$row['candidate_rank'] : '-',
            $row['place_id']
                ? '#'.$row['place_id'].' · '.($row['candidate_name'] ?? '-')
                : '-',
            $row['distance_m'] !== null ? $row['distance_m'].' m' : '-',
            $row['name_similarity'] !== null ? number_format($row['name_similarity'], 1, ',', '.').' %' : '-',
        ])->all(),
    );

    $path = storage_path('app/duplicate-report-'.$result['source_slug'].'.json');
    file_put_contents(
        $path,
        json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    );

    $this->newLine();
    $this->info('Vollständiger Report gespeichert: '.$path);

    return 0;
})->purpose('Zeigt offene mögliche Dubletten einer externen Quelle mit Distanz und Namensähnlichkeit.');


Artisan::command('imports:bayern-atkis-diagnose {--limit=30 : Number of changed records to show}', function (BayernAtkisSyncService $service) {
    $result = $service->diagnoseChanges((int) $this->option('limit'));

    $this->info('Bayern-ATKIS Idempotenz-Diagnose');
    $this->line('Geprüfte relevante Datensätze: '.$result['checked']);
    $this->line('Geänderte Beispiele: '.count($result['changed_examples']));

    if ($result['changed_examples'] !== []) {
        $this->table(
            ['ATKIS-ID', 'Name', 'FeatureType', 'FKT', 'Abweichende Felder'],
            collect($result['changed_examples'])
                ->map(fn (array $row) => [
                    $row['external_id'] ?? '-',
                    $row['name'] ?? '-',
                    $row['feature_type'] ?? '-',
                    $row['function'] ?? '-',
                    implode(', ', $row['diff_paths'] ?? []),
                ])
                ->all(),
        );
    }

    return 0;
})->purpose('Vergleicht den aktuellen Bayern-ATKIS-WFS-Stand mit dem lokalen Quellenlayer, ohne Daten zu verändern.');

Artisan::command('imports:bayern-atkis-sync {--no-classify : Nur Quellenlayer aktualisieren, ohne benannte Kandidaten zu klassifizieren}', function (BayernAtkisSyncService $sync) {
    ini_set('memory_limit', '512M');

    try {
        $result = $sync->sync(! (bool) $this->option('no-classify'));
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $stage = $result['staging'];
    $this->info('Bayern-ATKIS Sync abgeschlossen.');
    $this->table(
        ['Kennzahl', 'Anzahl'],
        [
            ['Eindeutige relevante Datensaetze', $result['unique_records']],
            ['Davon mit oeffentlichem Kandidatennamen', $result['eligible_candidates']],
            ['Nur Quellenlayer', $result['source_only']],
            ['Neu', $stage['new']],
            ['Geaendert', $stage['changed']],
            ['Unveraendert', $stage['unchanged']],
            ['Als missing markiert', $stage['missing_marked']],
            ['Betriebsstatus ergänzt', $result['operating_status_filled'] ?? 0],
        ],
    );

    foreach ($result['source_counts'] as $featureType => $counts) {
        $this->newLine();
        $this->comment($featureType);
        $this->table(
            ['FKT', 'Anzahl'],
            collect($counts)->map(fn (int $count, string $function) => [$function, $count])->values()->all(),
        );
    }

    if (is_array($result['classification'] ?? null)) {
        $classification = $result['classification'];
        $this->newLine();
        $this->table(
            ['Klassifikation', 'Anzahl'],
            [
                ['new_candidate', $classification['new_candidate'] ?? 0],
                ['possible_duplicate', $classification['possible_duplicate'] ?? 0],
                ['needs_review', $classification['needs_review'] ?? 0],
                ['source_only', $classification['source_only'] ?? 0],
                ['Review-Items', $classification['review_items'] ?? 0],
            ],
        );
    }

    $this->line('Sync-Run: #'.$result['run_id']);

    return 0;
})->purpose('Synchronisiert relevante Camping-, Wohnmobil-, Parkplatz- und Rastplatzdaten aus Bayern ATKIS in den externen Quellenlayer.');



Artisan::command('imports:bayern-atkis-duplicate-stats', function () {
    $sourceId = (int) \Illuminate\Support\Facades\DB::table('external_sources')
        ->where('slug', 'bayern-atkis-basis-dlm')
        ->value('id');

    if ($sourceId <= 0) {
        $this->error('Bayern-ATKIS-Quelle ist lokal noch nicht vorhanden.');
        return 1;
    }

    $items = \Illuminate\Support\Facades\DB::table('external_import_review_items as ri')
        ->join('external_records as er', 'er.id', '=', 'ri.external_record_id')
        ->where('ri.external_source_id', $sourceId)
        ->where('ri.status', 'pending')
        ->where('ri.type', 'external_duplicate_group')
        ->get(['ri.id', 'ri.details', 'er.id as record_id', 'er.external_id']);

    $groups = $items
        ->map(function ($item) {
            $details = json_decode((string) $item->details, true) ?: [];

            return [
                'group_key' => (string) ($details['group_key'] ?? ''),
                'name' => (string) ($details['group_name'] ?? ''),
                'place_type' => (string) ($details['group_place_type'] ?? ''),
                'record_id' => (int) $item->record_id,
                'external_id' => (string) $item->external_id,
            ];
        })
        ->filter(fn (array $row) => $row['group_key'] !== '')
        ->groupBy('group_key')
        ->map(function ($members) {
            $members = $members->values();

            return [
                'name' => $members->first()['name'],
                'place_type' => $members->first()['place_type'],
                'size' => $members->count(),
                'external_ids' => $members->pluck('external_id')->values()->all(),
            ];
        })
        ->values();

    $sizes = $groups->pluck('size')->sort()->values();
    $groupCount = $groups->count();
    $recordCount = (int) $sizes->sum();
    $average = $groupCount > 0 ? round($recordCount / $groupCount, 2) : 0.0;

    $median = 0.0;
    if ($groupCount > 0) {
        $middle = intdiv($groupCount, 2);
        $median = $groupCount % 2 === 1
            ? (float) $sizes[$middle]
            : round(((float) $sizes[$middle - 1] + (float) $sizes[$middle]) / 2, 2);
    }

    $this->table(
        ['Kennzahl', 'Wert'],
        [
            ['Dubletten-Gruppen', $groupCount],
            ['Records in Gruppen', $recordCount],
            ['Durchschnittliche Gruppengröße', number_format($average, 2, ',', '.')],
            ['Median Gruppengröße', number_format($median, 2, ',', '.')],
            ['Kleinste Gruppe', $groupCount > 0 ? $sizes->first() : 0],
            ['Größte Gruppe', $groupCount > 0 ? $sizes->last() : 0],
        ],
    );

    $largest = $groups
        ->sortByDesc('size')
        ->take(20)
        ->values()
        ->map(fn (array $group) => [
            $group['name'],
            $group['place_type'],
            $group['size'],
            implode(', ', array_slice($group['external_ids'], 0, 6)).(count($group['external_ids']) > 6 ? ' ...' : ''),
        ])
        ->all();

    if ($largest !== []) {
        $this->newLine();
        $this->comment('Größte Gruppen');
        $this->table(['Name', 'Typ', 'Records', 'ATKIS-IDs'], $largest);
    }

    $brombach = $groups
        ->filter(fn (array $group) => mb_strtolower($group['name']) === 'waldcamping brombach')
        ->values();

    $this->newLine();
    $this->comment('Waldcamping Brombach');

    if ($brombach->isEmpty()) {
        $this->line('Keine offene Dubletten-Gruppe gefunden.');
    } else {
        $this->table(
            ['Name', 'Typ', 'Records', 'ATKIS-IDs'],
            $brombach->map(fn (array $group) => [
                $group['name'],
                $group['place_type'],
                $group['size'],
                implode(', ', $group['external_ids']),
            ])->all(),
        );
    }

    return 0;
})->purpose('Zeigt Statistiken zu erkannten Bayern-ATKIS-Dubletten-Gruppen.');

Artisan::command('imports:bayern-atkis-samples {--count=10 : Anzahl benannter Stichproben je ATKIS-Funktionscode}', function (BayernAtkisSyncService $sync) {
    $count = max(1, min(50, (int) $this->option('count')));

    try {
        $groups = $sync->samples($count);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $rows = [];

    foreach ($groups as $group) {
        foreach ($group as $row) {
            $rows[] = $row;
        }
    }

    foreach ($groups as $key => $group) {
        if ($group === []) {
            continue;
        }

        $first = $group[0];
        $this->newLine();
        $this->comment(($first['feature_type'] ?? '-').' / FKT '.($first['function'] ?? '-').' -> '.($first['camperwolf_type'] ?? '-'));
        $this->table(
            ['Name', 'Breite', 'Laenge', 'ATKIS-ID'],
            collect($group)->map(fn (array $row) => [
                $row['name'] ?? '-',
                $row['latitude'] !== null ? number_format((float) $row['latitude'], 6, '.', '') : '-',
                $row['longitude'] !== null ? number_format((float) $row['longitude'], 6, '.', '') : '-',
                $row['external_id'] ?? '-',
            ])->all(),
        );
    }

    $path = storage_path('app/bayern-atkis-samples.csv');
    $handle = fopen($path, 'wb');

    if ($handle === false) {
        $this->error('CSV konnte nicht geschrieben werden: '.$path);
        return 1;
    }

    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, ['feature_type', 'funktion', 'camperwolf_type', 'atkis_id', 'name', 'latitude', 'longitude', 'google_maps_url'], ';');

    foreach ($rows as $row) {
        fputcsv($handle, [
            $row['feature_type'] ?? '',
            $row['function'] ?? '',
            $row['camperwolf_type'] ?? '',
            $row['external_id'] ?? '',
            $row['name'] ?? '',
            $row['latitude'] ?? '',
            $row['longitude'] ?? '',
            $row['google_maps_url'] ?? '',
        ], ';');
    }

    fclose($handle);

    $this->newLine();
    $this->info(count($rows).' Stichproben gespeichert: '.$path);
    $this->line('Die CSV enthaelt je Kandidat einen direkten Google-Maps-Link auf die ATKIS-Koordinate.');

    return 0;
})->purpose('Erzeugt benannte Bayern-ATKIS-Stichproben je relevantem Funktionscode fuer die manuelle Kartenpruefung.');



Artisan::command('imports:bayern-atkis-analyze', function (BayernAtkisCatalogAnalysisService $analysis) {
    try {
        $result = $analysis->analyze();
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info('Bayern ATKIS WFS Analyse');
    $this->line('Dienst: '.$result['base_url']);
    $this->line('FeatureTypes gesamt: '.count($result['feature_types']));
    $this->line('Relevant wirkende FeatureTypes: '.count($result['relevant_feature_types']));

    $this->newLine();
    $this->table(
        ['FeatureType', 'Titel'],
        collect($result['relevant_feature_types'])
            ->map(fn (array $type) => [$type['name'], $type['title'] ?: '-'])
            ->all(),
    );

    foreach ($result['schemas'] as $typeName => $fields) {
        $this->newLine();
        $this->comment($typeName);
        $this->table(
            ['Feld', 'Typ', 'Min', 'Max'],
            collect($fields)
                ->map(fn (array $field) => [
                    $field['name'],
                    $field['type'] ?? '-',
                    $field['min_occurs'] ?? '-',
                    $field['max_occurs'] ?? '-',
                ])
                ->all(),
        );
    }

    if (($result['relevant_conditions'] ?? []) !== []) {
        $this->newLine();
        $this->comment('zustand bei fuer Camperwolf relevanten ATKIS-Typen');

        foreach ($result['relevant_conditions'] as $group) {
            $this->line(($group['feature_type'] ?? '-').' / FKT '.($group['function'] ?? '-'));
            $this->table(
                ['zustand', 'Anzahl'],
                collect($group['counts'] ?? [])
                    ->map(fn (int $count, string $value) => [$value, $count])
                    ->values()
                    ->all(),
            );

            if (($group['examples'] ?? []) !== []) {
                $this->table(
                    ['ID', 'Name', 'zustand'],
                    collect($group['examples'])
                        ->map(fn (array $row) => [
                            $row['id'] ?? '-',
                            $row['name'] ?? '-',
                            $row['condition'] ?? '-',
                        ])
                        ->all(),
                );
            }
        }
    }

    foreach ($result['profiles'] as $typeName => $profile) {
        $this->newLine();
        $this->comment('Datenprofil '.$typeName);
        $this->table(
            ['Kennzahl', 'Wert'],
            [
                ['Treffer laut WFS', $profile['number_matched'] ?? '-'],
                ['Geladen', $profile['fetched']],
                ['Gekuerzt', $profile['truncated'] ? 'ja' : 'nein'],
                ['Mit Name', $profile['with_name']],
                ['Ohne Name', $profile['without_name']],
            ],
        );

        $this->table(
            ['funktion', 'Anzahl'],
            collect($profile['function_values'])
                ->map(fn (int $count, string $value) => [$value, $count])
                ->values()
                ->all(),
        );

        if ($profile['named_examples'] !== []) {
            $this->table(
                ['ID', 'funktion', 'Name'],
                collect($profile['named_examples'])
                    ->map(fn (array $row) => [
                        $row['id'] ?? '-',
                        $row['function'],
                        $row['name'],
                    ])
                    ->all(),
            );
        }

        if (($profile['condition_values'] ?? []) !== []) {
            $this->newLine();
            $this->comment('zustand');
            $this->table(
                ['Wert', 'Anzahl'],
                collect($profile['condition_values'])
                    ->map(fn (int $count, string $value) => [$value, $count])
                    ->values()
                    ->all(),
            );

            foreach (($profile['condition_examples'] ?? []) as $value => $examples) {
                $this->line('Beispiele fuer zustand='.$value.':');
                foreach ($examples as $example) {
                    $this->line('  '.($example['name'] ?: '(ohne Name)').' ['.($example['function'] ?? '-').'] '.($example['id'] ?? '-'));
                }
            }
        }

        if (($profile['designation_values'] ?? []) !== []) {
            $this->newLine();
            $this->comment('bezeichnung');
            $this->line('Eindeutige Werte: '.count($profile['designation_values']));
            $this->table(
                ['Bezeichnung', 'Anzahl'],
                collect($profile['designation_values'])
                    ->take(50)
                    ->map(fn (int $count, string $value) => [$value, $count])
                    ->values()
                    ->all(),
            );

            if (($profile['designation_examples'] ?? []) !== []) {
                $this->table(
                    ['ID', 'funktion', 'Name', 'Bezeichnung'],
                    collect($profile['designation_examples'])
                        ->map(fn (array $row) => [
                            $row['id'] ?? '-',
                            $row['function'] ?? '-',
                            $row['name'] ?? '-',
                            $row['designation'] ?? '-',
                        ])
                        ->all(),
                );
            }
        }
    }

    $path = storage_path('app/bayern-atkis-analysis.json');
    file_put_contents(
        $path,
        json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    );

    $this->newLine();
    $this->info('Analyse gespeichert: '.$path);

    return 0;
})->purpose('Analysiert den offenen Bayern-ATKIS-WFS und zeigt relevante Platz-/Camping-FeatureTypes und deren Schema.');



Artisan::command('imports:niedersachsen-sync {--no-classify : Nur Quellenlayer aktualisieren, ohne neue Kandidaten zu klassifizieren} {--page-size=100 : API-Seitengroesse (1-250)}', function (NiedersachsenHubSyncService $sync) {
    try {
        $result = $sync->sync(
            ! (bool) $this->option('no-classify'),
            max(1, min(250, (int) $this->option('page-size'))),
        );
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $stage = $result['staging'];
    $this->info('Niedersachsen-Hub Sync abgeschlossen.');
    $this->table(
        ['Kennzahl', 'Anzahl'],
        [
            ['Eindeutige externe Datensaetze', $result['unique_records']],
            ['Neu', $stage['new']],
            ['Geaendert', $stage['changed']],
            ['Unveraendert', $stage['unchanged']],
            ['Als missing markiert', $stage['missing_marked']],
            ['Externe Bilder gefunden', $result['photos']['media'] ?? 0],
            ['Davon importiert', $result['photos']['imported'] ?? 0],
            ['Dauerhaft uebersprungen', $result['photos']['ignored'] ?? 0],
            ['Davon offen', $result['photos']['pending'] ?? 0],
        ],
    );

    if (is_array($result['classification'] ?? null)) {
        $classification = $result['classification'];
        $this->newLine();
        $this->table(
            ['Klassifikation', 'Anzahl'],
            [
                ['new_candidate', $classification['new_candidate'] ?? 0],
                ['possible_duplicate', $classification['possible_duplicate'] ?? 0],
                ['needs_review', $classification['needs_review'] ?? 0],
                ['Review-Items', $classification['review_items'] ?? 0],
            ],
        );
    }

    $this->line('Sync-Run: #'.$result['run_id']);

    return 0;
})->purpose('Synchronisiert Niedersachsen-Hub-Daten in den bestehenden externen Quellenlayer, klassifiziert Dubletten und aktualisiert lizenzierte externe Fotos.');

Schedule::command('imports:niedersachsen-sync')
    ->dailyAt('03:10')
    ->withoutOverlapping();


Artisan::command('imports:nrw-tfis-sync {--no-classify : Nur Quellenlayer aktualisieren, ohne benannte Kandidaten zu klassifizieren}', function (NrwTfisSyncService $sync) {
    try {
        $result = $sync->sync(! (bool) $this->option('no-classify'));
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $stage = $result['staging'];
    $this->info('NRW-TFIS Sync abgeschlossen.');
    $this->table(
        ['Kennzahl', 'Anzahl'],
        [
            ['Eindeutige relevante Datensaetze', $result['unique_records']],
            ['Davon mit oeffentlichem Kandidatennamen', $result['eligible_candidates']],
            ['Nur Quellenlayer', $result['source_only']],
            ['Neu', $stage['new']],
            ['Geaendert', $stage['changed']],
            ['Unveraendert', $stage['unchanged']],
            ['Als missing markiert', $stage['missing_marked']],
            ['Betriebsstatus ergänzt', $result['operating_status_filled'] ?? 0],
        ],
    );

    foreach ($result['source_counts'] as $collection => $counts) {
        $this->newLine();
        $this->comment($collection);
        $this->table(
            ['Quelltyp', 'Anzahl'],
            collect($counts)->map(fn (int $count, string $type) => [$type, $count])->values()->all(),
        );
    }

    if (is_array($result['classification'] ?? null)) {
        $classification = $result['classification'];
        $this->newLine();
        $this->table(
            ['Klassifikation', 'Anzahl'],
            [
                ['new_candidate', $classification['new_candidate'] ?? 0],
                ['possible_duplicate', $classification['possible_duplicate'] ?? 0],
                ['needs_review', $classification['needs_review'] ?? 0],
                ['source_only', $classification['source_only'] ?? 0],
                ['Review-Items', $classification['review_items'] ?? 0],
            ],
        );
    }

    $this->line('Sync-Run: #'.$result['run_id']);

    return 0;
})->purpose('Synchronisiert relevante Camping- und Parkplatzdaten aus TFIS NRW in den externen Quellenlayer.');

Schedule::command('imports:nrw-tfis-sync')
    ->dailyAt('03:30')
    ->withoutOverlapping();


Artisan::command('imports:rvr-camping-sync {--no-classify : Nur Quellenlayer aktualisieren, ohne Kandidaten zu klassifizieren}', function (RvrCampingSyncService $sync) {
    try {
        $result = $sync->sync(! (bool) $this->option('no-classify'));
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $stage = $result['staging'];
    $this->info('RVR POI - Camping Sync abgeschlossen.');
    $this->table(
        ['Kennzahl', 'Anzahl'],
        [
            ['Eindeutige relevante Datensaetze', $result['unique_records']],
            ['Davon importfaehige Kandidaten', $result['eligible_candidates']],
            ['Neu', $stage['new']],
            ['Geaendert', $stage['changed']],
            ['Unveraendert', $stage['unchanged']],
            ['Als missing markiert', $stage['missing_marked']],
            ['Betriebsstatus ergaenzt', $result['operating_status_filled'] ?? 0],
            ['Moegliche Wiedereroeffnungen', $result['possible_reopens'] ?? 0],
        ],
    );

    $this->newLine();
    $this->comment('Quellkategorien');
    $this->table(
        ['Kategorie', 'Zuordnungen'],
        collect($result['source_counts'])
            ->map(fn (int $count, string $category) => [$category, $count])
            ->values()
            ->all(),
    );

    if (is_array($result['classification'] ?? null)) {
        $classification = $result['classification'];
        $this->newLine();
        $this->table(
            ['Klassifikation', 'Anzahl'],
            [
                ['new_candidate', $classification['new_candidate'] ?? 0],
                ['possible_duplicate', $classification['possible_duplicate'] ?? 0],
                ['needs_review', $classification['needs_review'] ?? 0],
                ['Review-Items', $classification['review_items'] ?? 0],
            ],
        );
    }

    $this->line('Sync-Run: #'.$result['run_id']);

    return 0;
})->purpose('Synchronisiert Camping- und Wohnmobil-POIs des Regionalverbands Ruhr in den externen Quellenlayer.');

Schedule::command('imports:rvr-camping-sync')
    ->dailyAt('03:45')
    ->withoutOverlapping();

Schedule::command('imports:bayern-atkis-sync')
    ->dailyAt('04:15')
    ->withoutOverlapping();



Artisan::command('imports:datex2-parking-dry-run {path}', function (Datex2ParkingDryRunService $dryRun) {
    $path = (string) $this->argument('path');

    try {
        $result = $dryRun->analyzePath($path);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    foreach ($result['files'] as $fileResult) {
        $stats = $fileResult['stats'];
        $guard = $fileResult['guard'];

        $this->newLine();
        $this->comment(basename($fileResult['file']));
        $this->table(
            ['Kennzahl', 'Anzahl'],
            [
                ['Datensätze', $stats['records']],
                ['Ohne Namen', $stats['missing_name']],
                ['Ohne Koordinaten', $stats['missing_coordinates']],
                ['Kapazität unbekannt', $stats['unknown_total_capacity']],
                ['Mit PKW-Plätzen', $stats['with_car_spaces']],
                ['Mit LKW-Plätzen', $stats['with_lorry_spaces']],
                ['Ausstattungswerte', $stats['equipment_entries']],
                ['Zufahrten', $stats['access_points']],
            ],
        );

        if ($guard['valid']) {
            $this->info('Validierung: OK');
        } else {
            $this->error('Validierung: FEHLER – Datei würde nicht importiert.');
        }

        foreach ($guard['warnings'] as $warning) {
            $this->warn(json_encode($warning, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        foreach ($guard['errors'] as $error) {
            $this->error(json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
    }

    $totals = $result['totals'];

    $this->newLine();
    $this->comment('Gesamtsumme');
    $this->table(
        ['Kennzahl', 'Anzahl'],
        [
            ['XML-Dateien', count($result['files'])],
            ['Datensätze', $totals['records']],
            ['Ohne Namen', $totals['missing_name']],
            ['Ohne Koordinaten', $totals['missing_coordinates']],
            ['Kapazität unbekannt', $totals['unknown_total_capacity']],
            ['Mit PKW-Plätzen', $totals['with_car_spaces']],
            ['Mit LKW-Plätzen', $totals['with_lorry_spaces']],
            ['Ausstattungswerte', $totals['equipment_entries']],
            ['Zufahrten', $totals['access_points']],
        ],
    );

    $this->newLine();
    $this->comment('Feldwert-Verteilungen');

    $labels = [
        'vehicle_types' => 'Fahrzeugtypen',
        'equipment_types' => 'Ausstattungstypen',
        'equipment_availability' => 'Ausstattung: Verfügbarkeit',
        'usage_scenarios' => 'Nutzungsszenarien',
        'site_locations' => 'Standortarten',
        'access_categories' => 'Zugangskategorien',
        'road_types' => 'Straßentypen',
        'security_levels' => 'Sicherheitslevel',
        'service_levels' => 'Servicelevel',
        'certified_secure' => 'Zertifiziert sicher',
    ];

    foreach ($labels as $key => $label) {
        $values = $result['distributions'][$key] ?? [];
        if ($values === []) {
            continue;
        }

        $this->newLine();
        $this->line($label);
        $this->table(
            ['Wert', 'Anzahl'],
            collect($values)->map(fn (int $count, string $value) => [$value, $count])->values()->all(),
        );
    }

    $hasErrors = collect($result['files'])->contains(fn (array $file) => ! $file['guard']['valid']);

    if ($hasErrors) {
        $this->error('Dry Run beendet: Mindestens eine Datei ist durch die Validierung gefallen. Es wurden keine Daten importiert.');
        return 2;
    }

    $this->info('Dry Run erfolgreich. Es wurden keine Daten importiert.');
    return 0;
})->purpose('Analysiert eine DATEX-II-Parking-XML-Datei oder alle XML-Dateien direkt in einem Ordner, ohne Daten zu importieren.');


Artisan::command('imports:datex2-parking-stage {path} {--complete : Behandelt den Datenbestand als vollständigen Snapshot und markiert nicht mehr vorhandene externe IDs als missing}', function (Datex2ParkingStageService $stage) {
    $path = (string) $this->argument('path');
    $complete = (bool) $this->option('complete');

    try {
        $result = $stage->stagePath($path, $complete);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $s = $result['staging'];

    $this->info('DATEX-II-Staging abgeschlossen.');
    $this->table(
        ['Kennzahl', 'Anzahl'],
        [
            ['XML-Dateien', count($result['files'])],
            ['Gemappte Datensätze', $result['mapped_records']],
            ['Neue externe Datensätze', $s['new']],
            ['Geänderte externe Datensätze', $s['changed']],
            ['Unveränderte externe Datensätze', $s['unchanged']],
            ['Als missing markiert', $s['missing_marked']],
            ['Fallback-Koordinaten aus Zufahrt', $result['fallback_coordinates']],
            ['Komplett ohne Koordinaten', $result['without_coordinates']],
            ['Datensätze mit Camperwolf-Kandidaten', $result['candidate_records']],
            ['Kandidatenpaare insgesamt', $result['candidate_pairs']],
        ],
    );

    if ($result['unmapped_equipment'] !== []) {
        $this->newLine();
        $this->comment('Noch nicht gemappte DATEX-II-Ausstattung');
        $this->table(
            ['Wert', 'Anzahl'],
            collect($result['unmapped_equipment'])
                ->map(fn (int $count, string $value) => [$value, $count])
                ->values()
                ->all(),
        );
    }

    if ($result['candidate_examples'] !== []) {
        $this->newLine();
        $this->comment('Beispiele möglicher vorhandener Camperwolf-Plätze');
        foreach ($result['candidate_examples'] as $example) {
            $this->line(($example['external_name'] ?? '(ohne Namen)').' ['.$example['external_id'].']');
            $this->table(
                ['Camperwolf-Platz', 'Entfernung', 'Namensähnlichkeit'],
                collect($example['candidates'])->map(fn (array $candidate) => [
                    $candidate['name'].' (#'.$candidate['place_id'].')',
                    $candidate['distance_m'].' m',
                    number_format((float) $candidate['name_similarity'], 1, ',', '.').' %',
                ])->all(),
            );
        }
    }

    $this->newLine();
    if ($complete) {
        $this->warn('Vollständiger Snapshot: Nicht mehr enthaltene externe IDs wurden nur im Quellenlayer als missing markiert. Camperwolf-Plätze wurden nicht gelöscht oder deaktiviert.');
    } else {
        $this->line('Teil-Snapshot-Modus: Nicht enthaltene externe IDs wurden nicht verändert.');
    }

    $this->info('Es wurden keine Camperwolf-Plätze oder Communitywerte angelegt, überschrieben oder gelöscht.');

    return 0;
})->purpose('Mappt und speichert DATEX-II-Parking-Daten ausschließlich im externen Quellenlayer; Camperwolf-Plätze bleiben unverändert.');


Artisan::command('imports:classify-external {source=mobilithek-itp-bab : Slug der externen Quelle}', function (ExternalRecordClassificationService $classifier) {
    $slug = (string) $this->argument('source');
    $sourceId = \Illuminate\Support\Facades\DB::table('external_sources')
        ->where('slug', $slug)
        ->value('id');

    if (! $sourceId) {
        $this->error('Externe Quelle nicht gefunden: '.$slug);
        return 1;
    }

    try {
        $result = $classifier->classifySource((int) $sourceId);
    } catch (\Throwable $e) {
        $this->error($e->getMessage());
        return 1;
    }

    $this->info('Externe Datensätze klassifiziert.');
    $this->table(
        ['Klasse', 'Anzahl'],
        [
            ['Gesamt', $result['records']],
            ['new_candidate', $result['new_candidate']],
            ['possible_duplicate', $result['possible_duplicate']],
            ['needs_review', $result['needs_review']],
            ['Offene Review-Items neu erzeugt', $result['review_items']],
        ],
    );

    $this->line('Classification run: #'.$result['run_id']);
    $this->warn('possible_duplicate ist aktuell bewusst konservativ: Jeder vorhandene Camperwolf-Platz innerhalb des 1-km-Kandidatenfensters führt zur manuellen Prüfung.');
    $this->info('Es wurden keine Camperwolf-Plätze angelegt, verknüpft, überschrieben oder gelöscht.');

    return 0;
})->purpose('Klassifiziert aktive externe Records in new_candidate, possible_duplicate oder needs_review und befüllt die Review-Queue.');
