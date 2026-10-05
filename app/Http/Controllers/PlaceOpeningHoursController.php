<?php

namespace App\Http\Controllers;

use App\Services\OpeningHoursPeriodService;
use App\Services\PermissionService;
use App\Services\UsageAnalyticsService;
use App\Services\UserNotificationService;
use App\Services\XpService;
use App\Services\BadgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class PlaceOpeningHoursController extends Controller
{
    private const DAY_KEYS = ['1', '2', '3', '4', '5', '6', '7', 'holiday'];

    public function edit(
        Request $request,
        string $slug,
        PermissionService $permissions,
        OpeningHoursPeriodService $periods,
    ): View {
        $place = $this->place($slug);

        abort_unless(
            $permissions->can($request->user(), 'places.edit')
                || $permissions->can($request->user(), 'places.suggest'),
            404,
        );

        $currentPeriods = $periods->currentPeriods((int) $place->id);
        $editingPeriod = $periods->periodForEdit(
            (int) $place->id,
            $request->integer('period') ?: null,
        );

        $schedule = $editingPeriod
            ? $periods->scheduleFromHours($editingPeriod->hours)
            : $this->blankSchedule();

        return view('places.opening-hours-edit', [
            'place' => $place,
            'periods' => $currentPeriods,
            'editingPeriod' => $editingPeriod,
            'days' => $schedule,
            'periodMode' => $editingPeriod
                ? ($editingPeriod->is_year_round ? 'year_round' : 'seasonal')
                : 'year_round',
            'startMd' => $editingPeriod && ! $editingPeriod->is_year_round
                ? sprintf('%02d.%02d.', $editingPeriod->start_day, $editingPeriod->start_month)
                : null,
            'endMd' => $editingPeriod && ! $editingPeriod->is_year_round
                ? sprintf('%02d.%02d.', $editingPeriod->end_day, $editingPeriod->end_month)
                : null,
            'canDirectEdit' => $permissions->can($request->user(), 'places.edit'),
            'overlapPreview' => session('opening_overlap_preview'),
        ]);
    }

    public function update(
        Request $request,
        string $slug,
        PermissionService $permissions,
        UserNotificationService $notifications,
        OpeningHoursPeriodService $periods,
    ): RedirectResponse {
        $place = $this->place($slug);
        $canDirectEdit = $permissions->can($request->user(), 'places.edit');

        abort_unless(
            $canDirectEdit || $permissions->can($request->user(), 'places.suggest'),
            403,
        );

        $rules = [
            'period_id' => ['nullable', 'integer'],
            'period_mode' => ['required', 'in:year_round,seasonal'],
            'start_md' => ['nullable', 'string', 'max:6'],
            'end_md' => ['nullable', 'string', 'max:6'],
            'confirm_overlap' => ['nullable', 'boolean'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'days' => ['required', 'array'],
        ];

        foreach (self::DAY_KEYS as $dayKey) {
            $rules["days.{$dayKey}.mode"] = ['required', 'in:unknown,not_provided,closed,24h,appointment,hours'];
            $rules["days.{$dayKey}.opens_at"] = ['nullable', 'date_format:H:i'];
            $rules["days.{$dayKey}.closes_at"] = ['nullable', 'date_format:H:i'];
            $rules["days.{$dayKey}.opens_at_2"] = ['nullable', 'date_format:H:i'];
            $rules["days.{$dayKey}.closes_at_2"] = ['nullable', 'date_format:H:i'];
        }

        $data = $request->validate($rules);

        foreach (self::DAY_KEYS as $dayKey) {
            $day = $data['days'][$dayKey];

            if ($day['mode'] !== 'hours') {
                continue;
            }

            if (blank($day['opens_at'] ?? null) || blank($day['closes_at'] ?? null)) {
                return back()->withInput()->withErrors([
                    "days.{$dayKey}.opens_at" => __('place_editing.opening_hours.normal_hours_required'),
                ]);
            }

            $secondStarted = filled($day['opens_at_2'] ?? null) || filled($day['closes_at_2'] ?? null);
            if ($secondStarted && (blank($day['opens_at_2'] ?? null) || blank($day['closes_at_2'] ?? null))) {
                return back()->withInput()->withErrors([
                    "days.{$dayKey}.opens_at_2" => __('place_editing.opening_hours.second_hours_required'),
                ]);
            }
        }

        try {
            $range = $periods->normalizeRange(
                $data['period_mode'] === 'year_round',
                $data['start_md'] ?? null,
                $data['end_md'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['start_md' => $e->getMessage()]);
        }

        $editingPeriodId = isset($data['period_id']) ? (int) $data['period_id'] : null;
        if ($editingPeriodId) {
            $editingPeriod = $periods->periodForEdit((int) $place->id, $editingPeriodId);
            abort_unless($editingPeriod, 404);
        }

        $schedule = [];
        foreach (self::DAY_KEYS as $dayKey) {
            $schedule[$dayKey] = [
                'mode' => $data['days'][$dayKey]['mode'],
                'opens_at' => $data['days'][$dayKey]['opens_at'] ?? null,
                'closes_at' => $data['days'][$dayKey]['closes_at'] ?? null,
                'opens_at_2' => $data['days'][$dayKey]['opens_at_2'] ?? null,
                'closes_at_2' => $data['days'][$dayKey]['closes_at_2'] ?? null,
            ];
        }

        $overlapPreview = $periods->overlapPreview(
            (int) $place->id,
            $range,
            $editingPeriodId,
        );

        $requiresOverlapConfirmation = collect($overlapPreview)
            ->contains(fn ($affected) => ! ($affected['editing'] ?? false));

        if ($requiresOverlapConfirmation && ! ($data['confirm_overlap'] ?? false)) {
            return back()
                ->withInput()
                ->with('opening_overlap_preview', [
                    'range' => $periods->rangeLabel($range),
                    'affected' => $overlapPreview,
                ]);
        }

        if ($canDirectEdit) {
            $result = $periods->applyDirect(
                (int) $place->id,
                (int) $request->user()->id,
                $range,
                $schedule,
                $editingPeriodId,
            );

            $newPeriodId = (int) ($result['new_period_id'] ?? 0);
            $hasInformation = collect($schedule)
                ->contains(fn (array $day) => ($day['mode'] ?? 'unknown') !== 'unknown');

            if ($newPeriodId > 0 && $hasInformation) {
                app(XpService::class)->awardPlaceField(
                    (int) $request->user()->id,
                    (int) $place->id,
                    'opening_hours.period_schedule',
                    (int) config('xp.place_info.default_xp', 1),
                    'Öffnungszeiten ergänzt oder aktualisiert',
                    'opening_hour_period',
                    $newPeriodId,
                );

                $rewardRequest = (object) [
                    'submitted_by' => (int) $request->user()->id,
                    'place_id' => (int) $place->id,
                    'target_table' => 'opening_hours',
                    'target_field' => 'period_schedule',
                    'proposed_value' => json_encode([
                        'range' => $range,
                        'schedule' => $schedule,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];

                app(BadgeService::class)->recordApprovedPlaceChange(
                    $rewardRequest,
                    $newPeriodId,
                    (string) $place->name,
                );
            }

            $notifications->queueFavoritePlaceChange(
                (int) $place->id,
                [(int) $request->user()->id],
                'direct_opening_hours_edit',
            );

            app(UsageAnalyticsService::class)->track(
                $request,
                'opening_hours_submitted',
                'opening_hours',
                'place',
                (int) $place->id,
                ['direct' => true],
            );

            return redirect()
                ->route('places.show', $place->slug)
                ->with('ui_dialog', [
                    'variant' => 'success',
                    'message' => __('place_editing.opening_hours.status_saved'),
                ]);
        }

        $changeRequestId = $periods->createProposal(
            (int) $place->id,
            (int) $request->user()->id,
            $range,
            $schedule,
            $data['comment'] ?? null,
            $editingPeriodId,
        );

        DB::table('audit_logs')->insert([
            'user_id' => $request->user()->id,
            'entity_type' => 'place',
            'entity_id' => $place->id,
            'action' => 'opening_hours_period_change_suggested',
            'source' => 'user',
            'old_values' => null,
            'new_values' => json_encode([
                'change_request_id' => $changeRequestId,
                'range' => $range,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'internal_comment' => null,
            'created_at' => now(),
        ]);

        app(UsageAnalyticsService::class)->track(
            $request,
            'opening_hours_submitted',
            'opening_hours',
            'place',
            (int) $place->id,
            ['direct' => false],
        );

        return redirect()
            ->route('places.show', $place->slug)
            ->with('ui_dialog', [
                'variant' => 'success',
                'message' => __('place_editing.opening_hours.status_submitted', ['id' => $changeRequestId]),
            ]);
    }

    private function place(string $slug): object
    {
        $place = DB::table('places')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->first(['id', 'name', 'slug']);

        abort_unless($place, 404);

        return $place;
    }

    private function blankSchedule(): array
    {
        $schedule = [];

        foreach (self::DAY_KEYS as $dayKey) {
            $schedule[$dayKey] = [
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
