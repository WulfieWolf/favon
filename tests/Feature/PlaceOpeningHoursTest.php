<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ChangeRequestModerationService;
use App\Services\CurrentOpeningStateService;
use App\Services\OpeningHoursPeriodService;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlaceOpeningHoursTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_hours_editor_system_texts_are_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $this->createPlace($user);

        $this->actingAs($user)
            ->withSession(['locale' => 'de'])
            ->get(route('places.opening-hours.edit', 'opening-hours-testplatz'))
            ->assertOk()
            ->assertSee('Öffnungszeiten vorschlagen')
            ->assertSee('Neuen Zeitraum hinzufügen')
            ->assertSee('Jeden Tag geschlossen')
            ->assertSee('Für alle Wochentage übernehmen');

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get(route('places.opening-hours.edit', 'opening-hours-testplatz'))
            ->assertOk()
            ->assertSee('Suggest opening hours')
            ->assertSee('Add a new period')
            ->assertSee('Opening Hours Testplatz');
    }

    public function test_registered_user_can_submit_recurring_opening_hours_proposal_without_changing_live_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $placeId = $this->createPlace($user);

        $payload = $this->requestPayload('seasonal', '02.05.', '01.06.');
        $payload['days']['1'] = [
            'mode' => 'hours',
            'opens_at' => '08:00',
            'closes_at' => '12:00',
            'opens_at_2' => '14:00',
            'closes_at_2' => '18:00',
        ];

        $this->actingAs($user)
            ->put(route('places.opening-hours.update', 'opening-hours-testplatz'), $payload)
            ->assertRedirect(route('places.show', 'opening-hours-testplatz'));

        $this->assertDatabaseMissing('opening_hour_periods', [
            'place_id' => $placeId,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('change_requests', [
            'place_id' => $placeId,
            'operation' => 'create',
            'status' => 'pending',
            'submitted_by' => $user->id,
        ]);

        $this->assertSame(
            'period_schedule',
            DB::table('change_requests as cr')
                ->join('suggestable_fields as sf', 'sf.id', '=', 'cr.suggestable_field_id')
                ->where('cr.place_id', $placeId)
                ->value('sf.target_field'),
        );
    }

    public function test_unknown_is_not_persisted_but_operator_not_provided_is_a_real_opening_hour_statement(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        $periods = app(OpeningHoursPeriodService::class);
        $schedule = $this->blankSchedule();

        $schedule['1'] = [
            'mode' => 'hours',
            'opens_at' => '08:00',
            'closes_at' => '18:00',
            'opens_at_2' => null,
            'closes_at_2' => null,
        ];

        // Tuesday remains unknown: no row should exist for it.
        $schedule['holiday'] = [
            'mode' => 'not_provided',
            'opens_at' => null,
            'closes_at' => null,
            'opens_at_2' => null,
            'closes_at_2' => null,
        ];

        $result = $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(true, null, null),
            $schedule,
        );

        $periodId = $result['new_period_id'];

        $this->assertDatabaseMissing('opening_hours', [
            'period_id' => $periodId,
            'day_type' => 'weekday',
            'weekday' => 2,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('opening_hours', [
            'period_id' => $periodId,
            'day_type' => 'holiday',
            'weekday' => null,
            'is_not_provided_by_operator' => true,
            'is_active' => true,
        ]);

        $saved = $periods->currentPeriods($placeId)->firstWhere('id', $periodId);
        $roundTrip = $periods->scheduleFromHours($saved->hours);

        $this->assertSame('unknown', $roundTrip['2']['mode']);
        $this->assertSame('not_provided', $roundTrip['holiday']['mode']);
    }

    public function test_current_opening_state_uses_berlin_summer_time_instead_of_utc(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'app.timezone' => 'UTC',
            'app.display_timezone' => 'Europe/Berlin',
        ]);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        $periods = app(OpeningHoursPeriodService::class);
        $schedule = $this->blankSchedule();
        $schedule['1'] = [
            'mode' => 'hours',
            'opens_at' => '08:00',
            'closes_at' => '12:00',
            'opens_at_2' => null,
            'closes_at_2' => null,
        ];

        $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(true, null, null),
            $schedule,
        );

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-05 07:05:00', 'UTC'));

        try {
            $state = app(CurrentOpeningStateService::class)
                ->forPlaces(collect([$placeId]))
                ->get($placeId);

            $this->assertSame('open', $state['state']);
            $this->assertSame(175, $state['minutes_until_close']);
            $this->assertNull($state['minutes_until_open']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_current_opening_state_uses_berlin_winter_time_instead_of_utc(): void
    {
        $this->seed(DatabaseSeeder::class);
        config([
            'app.timezone' => 'UTC',
            'app.display_timezone' => 'Europe/Berlin',
        ]);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        $periods = app(OpeningHoursPeriodService::class);
        $schedule = $this->blankSchedule();
        $schedule['1'] = [
            'mode' => 'hours',
            'opens_at' => '08:00',
            'closes_at' => '12:00',
            'opens_at_2' => null,
            'closes_at_2' => null,
        ];

        $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(true, null, null),
            $schedule,
        );

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-05 07:30:00', 'UTC'));

        try {
            $state = app(CurrentOpeningStateService::class)
                ->forPlaces(collect([$placeId]))
                ->get($placeId);

            $this->assertSame('open', $state['state']);
            $this->assertSame(210, $state['minutes_until_close']);
            $this->assertNull($state['minutes_until_open']);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_approved_seasonal_proposal_splits_existing_year_round_period_only_on_approval(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        $periods = app(OpeningHoursPeriodService::class);

        $baselineSchedule = $this->blankSchedule();
        $baselineSchedule['1'] = [
            'mode' => 'hours',
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'opens_at_2' => null,
            'closes_at_2' => null,
        ];

        $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(true, null, null),
            $baselineSchedule,
        );

        $this->assertSame(1, DB::table('opening_hour_periods')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->count());

        $proposalSchedule = $this->blankSchedule();
        $proposalSchedule['1'] = [
            'mode' => 'hours',
            'opens_at' => '07:00',
            'closes_at' => '20:00',
            'opens_at_2' => null,
            'closes_at_2' => null,
        ];

        $range = $periods->normalizeRange(false, '02.05.', '01.06.');
        $changeRequestId = $periods->createProposal(
            $placeId,
            $user->id,
            $range,
            $proposalSchedule,
            'Saisonzeiten',
        );

        // Proposal submission must not alter currently published periods.
        $this->assertSame(1, DB::table('opening_hour_periods')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->count());

        app(ChangeRequestModerationService::class)->approve(
            $moderator,
            $changeRequestId,
            'Bestätigt.',
        );

        $active = DB::table('opening_hour_periods')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderBy('start_month')
            ->orderBy('start_day')
            ->get();

        $this->assertCount(3, $active);

        $labels = $active
            ->map(fn ($period) => $periods->rangeLabel([
                'is_year_round' => (bool) $period->is_year_round,
                'start_month' => $period->start_month,
                'start_day' => $period->start_day,
                'end_month' => $period->end_month,
                'end_day' => $period->end_day,
            ]))
            ->sort()
            ->values()
            ->all();

        $this->assertContains('01.01.–01.05.', $labels);
        $this->assertContains('02.05.–01.06.', $labels);
        $this->assertContains('02.06.–31.12.', $labels);

        $newPeriod = $active->first(fn ($period) =>
            (int) $period->start_month === 5
            && (int) $period->start_day === 2
            && (int) $period->end_month === 6
            && (int) $period->end_day === 1
        );

        $this->assertNotNull($newPeriod);

        $this->assertDatabaseHas('opening_hours', [
            'period_id' => $newPeriod->id,
            'weekday' => 1,
            'opens_at' => '07:00',
            'closes_at' => '20:00',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('change_requests', [
            'id' => $changeRequestId,
            'status' => 'approved',
            'result_record_id' => $newPeriod->id,
        ]);
    }


    public function test_current_seasonal_all_week_closed_period_returns_end_date_hint(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        $periods = app(OpeningHoursPeriodService::class);
        $closedSchedule = $this->closedWeekSchedule();

        $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(false, '10.10.', '24.12.'),
            $closedSchedule,
        );

        $hint = $periods->currentClosureHint(
            $placeId,
            CarbonImmutable::parse('2026-10-15 12:00:00', config('app.timezone')),
        );

        $this->assertSame([
            'kind' => 'period',
            'until' => '24.12.',
        ], $hint);
    }

    public function test_explicit_full_year_date_range_is_not_treated_as_year_round_closure(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        $periods = app(OpeningHoursPeriodService::class);
        $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(false, '01.01.', '31.12.'),
            $this->closedWeekSchedule(),
        );

        $hint = $periods->currentClosureHint(
            $placeId,
            CarbonImmutable::parse('2026-06-15 12:00:00', config('app.timezone')),
        );

        $this->assertSame([
            'kind' => 'period',
            'until' => '31.12.',
        ], $hint);
    }

    public function test_year_round_all_week_closed_period_returns_probable_permanent_hint(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        $periods = app(OpeningHoursPeriodService::class);
        $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(true, null, null),
            $this->closedWeekSchedule(),
        );

        $hint = $periods->currentClosureHint(
            $placeId,
            CarbonImmutable::parse('2026-06-15 12:00:00', config('app.timezone')),
        );

        $this->assertSame([
            'kind' => 'year_round',
            'until' => null,
        ], $hint);
    }

    public function test_place_profile_shows_seasonal_closure_hint_below_operating_status(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        DB::table('places')->where('id', $placeId)->update(['opening_status' => 'open']);

        $periods = app(OpeningHoursPeriodService::class);
        $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(false, '10.10.', '24.12.'),
            $this->closedWeekSchedule(),
        );

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-10-15 12:00:00', config('app.timezone')),
        );

        try {
            $this->withSession(['locale' => 'de'])
                ->get(route('places.show', 'opening-hours-testplatz'))
                ->assertOk()
                ->assertSee('In Betrieb')
                ->assertSee('Aufgrund der hinterlegten Öffnungszeiten bis 24.12. geschlossen');
        } finally {
            CarbonImmutable::setTestNow();
        }
    }


    public function test_approved_year_round_proposal_replaces_all_existing_seasonal_periods(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::factory()->create();
        $moderator = User::factory()->create();
        $this->assignRole($moderator, 'mod');
        $placeId = $this->createPlace($user);

        $periods = app(OpeningHoursPeriodService::class);
        $schedule = $this->blankSchedule();
        $schedule['1'] = [
            'mode' => 'hours',
            'opens_at' => '09:00',
            'closes_at' => '17:00',
            'opens_at_2' => null,
            'closes_at_2' => null,
        ];

        $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(false, '01.01.', '30.04.'),
            $schedule,
        );

        $periods->applyDirect(
            $placeId,
            $moderator->id,
            $periods->normalizeRange(false, '01.05.', '31.12.'),
            $schedule,
        );

        $this->assertSame(2, DB::table('opening_hour_periods')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->count());

        $yearRoundSchedule = $this->blankSchedule();
        $yearRoundSchedule['1'] = [
            'mode' => 'hours',
            'opens_at' => '06:00',
            'closes_at' => '22:00',
            'opens_at_2' => null,
            'closes_at_2' => null,
        ];

        $changeRequestId = $periods->createProposal(
            $placeId,
            $user->id,
            $periods->normalizeRange(true, null, null),
            $yearRoundSchedule,
            'Neue ganzjährige Zeiten',
        );

        app(ChangeRequestModerationService::class)->approve(
            $moderator,
            $changeRequestId,
            'Bestätigt.',
        );

        $active = DB::table('opening_hour_periods')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->get();

        $this->assertCount(1, $active);
        $this->assertTrue((bool) $active->first()->is_year_round);
    }

    private function createPlace(User $user): int
    {
        $placeTypeId = DB::table('place_types')->where('is_active', true)->value('id');

        return DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Opening Hours Testplatz',
            'slug' => 'opening-hours-testplatz',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assignRole(User $user, string $slug): void
    {
        $roleId = DB::table('roles')->where('slug', $slug)->value('id');

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $roleId],
            [
                'assigned_by' => null,
                'assigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function closedWeekSchedule(): array
    {
        $schedule = $this->blankSchedule();

        foreach (['1', '2', '3', '4', '5', '6', '7'] as $key) {
            $schedule[$key] = [
                'mode' => 'closed',
                'opens_at' => null,
                'closes_at' => null,
                'opens_at_2' => null,
                'closes_at_2' => null,
            ];
        }

        return $schedule;
    }

    private function requestPayload(string $periodMode, ?string $start, ?string $end): array
    {
        return [
            'period_mode' => $periodMode,
            'start_md' => $start,
            'end_md' => $end,
            'days' => $this->blankSchedule(),
        ];
    }

    private function blankSchedule(): array
    {
        $schedule = [];

        foreach (['1', '2', '3', '4', '5', '6', '7', 'holiday'] as $key) {
            $schedule[$key] = [
                'mode' => 'unknown',
                'opens_at' => null,
                'closes_at' => null,
                'opens_at_2' => null,
                'closes_at_2' => null,
            ];
        }

        return $schedule;
    }
}
