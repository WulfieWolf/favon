<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OpeningHoursPeriodService
{
    private const DAYS_IN_YEAR = 366;

    public function currentPeriods(int $placeId): Collection
    {
        $periods = DB::table('opening_hour_periods')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByDesc('is_year_round')
            ->orderBy('start_month')
            ->orderBy('start_day')
            ->get();

        if ($periods->isEmpty()) {
            return collect();
        }

        $hours = DB::table('opening_hours')
            ->whereIn('period_id', $periods->pluck('id'))
            ->whereNull('feature_id')
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByRaw("CASE WHEN day_type = 'weekday' THEN 0 ELSE 1 END")
            ->orderBy('weekday')
            ->orderBy('opens_at')
            ->orderBy('id')
            ->get()
            ->groupBy('period_id');

        return $periods->map(function ($period) use ($hours) {
            $period->hours = $hours->get($period->id, collect())->values();
            $period->label = $this->periodLabel($period);

            return $period;
        });
    }

    public function periodForEdit(int $placeId, ?int $periodId): ?object
    {
        if (! $periodId) {
            return null;
        }

        return $this->currentPeriods($placeId)->firstWhere('id', $periodId);
    }

    /**
     * Derive a long-running closure hint from the currently active recurring
     * opening-hours period without changing the stored operating status.
     *
     * @return array{kind:'year_round'|'period',until:?string}|null
     */
    public function currentClosureHint(int $placeId, ?CarbonImmutable $now = null): ?array
    {
        $now ??= CarbonImmutable::now(config('app.timezone'));

        $period = $this->currentPeriods($placeId)
            ->filter(fn ($period) => $this->periodContainsDate($period, $now))
            ->sortBy(fn ($period) => $period->is_year_round ? 1 : 0)
            ->first();

        if (! $period) {
            return null;
        }

        $weekdayRows = $period->hours
            ->where('day_type', 'weekday')
            ->values();

        if (
            $weekdayRows->pluck('weekday')->filter()->unique()->sort()->values()->all() !== [1, 2, 3, 4, 5, 6, 7]
            || $weekdayRows->contains(fn ($row) => ! $row->is_closed)
        ) {
            return null;
        }

        if ($period->is_year_round) {
            return [
                'kind' => 'year_round',
                'until' => null,
            ];
        }

        return [
            'kind' => 'period',
            'until' => sprintf('%02d.%02d.', $period->end_day, $period->end_month),
        ];
    }

    public function overlapPreview(int $placeId, array $range, ?int $editingPeriodId = null): array
    {
        $periods = $this->currentPeriods($placeId);
        $incomingDays = $this->daysForRange($range);

        $affected = [];
        foreach ($periods as $period) {
            $existingRange = $this->rangeFromPeriod($period);
            $existingDays = $this->daysForRange($existingRange);

            $isEditingPeriod = $editingPeriodId && (int) $period->id === $editingPeriodId;

            if (! $isEditingPeriod && count(array_intersect($incomingDays, $existingDays)) === 0) {
                continue;
            }

            $remainders = $this->subtractRange($existingRange, $range);

            $affected[] = [
                'id' => (int) $period->id,
                'period_uuid' => $period->period_uuid,
                'label' => $period->label,
                'editing' => $editingPeriodId && (int) $period->id === $editingPeriodId,
                'remainders' => array_map(fn ($remainder) => $this->rangeLabel($remainder), $remainders),
                'signature' => (string) ($period->updated_at ?? $period->version_valid_from ?? ''),
            ];
        }

        return $affected;
    }

    public function applyDirect(
        int $placeId,
        int $userId,
        array $range,
        array $schedule,
        ?int $editingPeriodId = null,
    ): array {
        return DB::transaction(function () use ($placeId, $userId, $range, $schedule, $editingPeriodId): array {
            $affected = $this->currentPeriods($placeId)
                ->filter(fn ($period) =>
                    ($editingPeriodId && (int) $period->id === $editingPeriodId)
                    || $this->rangesOverlap($this->rangeFromPeriod($period), $range)
                )
                ->values();

            $result = $this->applyReplacement(
                $placeId,
                $userId,
                $range,
                $schedule,
                $affected,
                'admin',
            );

            DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'entity_type' => 'place',
                'entity_id' => $placeId,
                'action' => 'opening_hours_period_replaced_directly',
                'source' => 'admin',
                'old_values' => json_encode([
                    'affected_period_ids' => $affected->pluck('id')->map(fn ($id) => (int) $id)->all(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => json_encode([
                    'created_period_ids' => $result['created_period_ids'],
                    'range' => $range,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'internal_comment' => null,
                'created_at' => now(),
            ]);

            return $result;
        });
    }

    public function createProposal(
        int $placeId,
        int $userId,
        array $range,
        array $schedule,
        ?string $comment = null,
        ?int $editingPeriodId = null,
    ): int {
        $definition = DB::table('suggestable_fields')
            ->where('target_table', 'opening_hours')
            ->where('target_field', 'period_schedule')
            ->where('is_suggestable', true)
            ->where('is_active', true)
            ->first();

        if (! $definition || ! $definition->allow_create) {
            throw new RuntimeException(__('place_editing.opening_hours.errors.workflow_unavailable'));
        }

        $affected = $this->overlapPreview($placeId, $range, $editingPeriodId);

        $payload = [
            'range' => $range,
            'schedule' => $schedule,
            'editing_period_id' => $editingPeriodId,
            'affected_periods' => array_map(fn ($item) => [
                'id' => $item['id'],
                'signature' => $item['signature'],
            ], $affected),
        ];

        $now = now();

        return (int) DB::table('change_requests')->insertGetId([
            'group_uuid' => (string) Str::uuid(),
            'place_id' => $placeId,
            'suggestable_field_id' => $definition->id,
            'target_record_id' => null,
            'operation' => 'create',
            'original_value' => json_encode([
                'affected_periods' => $affected,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'proposed_value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'submitted_by' => $userId,
            'submitted_at' => $now,
            'user_comment' => $comment ?: 'Öffnungszeiten-Zeitraum aus dem Platzprofil vorgeschlagen.',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'moderator_comment' => null,
            'result_record_id' => null,
            'applied_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function applyProposal(object $request, int $reviewerId, $now): int
    {
        $payload = json_decode((string) $request->proposed_value, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($payload) || ! isset($payload['range'], $payload['schedule'])) {
            throw new RuntimeException(__('place_editing.opening_hours.errors.invalid_proposal'));
        }

        $range = $payload['range'];
        $schedule = $payload['schedule'];
        $expectedAffected = collect($payload['affected_periods'] ?? [])->keyBy('id');
        $editingPeriodId = isset($payload['editing_period_id']) ? (int) $payload['editing_period_id'] : null;

        $currentAffected = $this->currentPeriods((int) $request->place_id)
            ->filter(fn ($period) =>
                ($editingPeriodId && (int) $period->id === $editingPeriodId)
                || $this->rangesOverlap($this->rangeFromPeriod($period), $range)
            )
            ->values();

        $currentIds = $currentAffected->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $expectedIds = $expectedAffected->keys()->map(fn ($id) => (int) $id)->sort()->values();

        if ($currentIds->all() !== $expectedIds->all()) {
            throw new RuntimeException(__('place_editing.opening_hours.errors.overlaps_changed'));
        }

        foreach ($currentAffected as $period) {
            $expected = $expectedAffected->get((int) $period->id);
            $signature = (string) ($period->updated_at ?? $period->version_valid_from ?? '');

            if (! $expected || (string) ($expected['signature'] ?? '') !== $signature) {
                throw new RuntimeException(__('place_editing.opening_hours.errors.period_changed'));
            }
        }

        $result = $this->applyReplacement(
            (int) $request->place_id,
            $reviewerId,
            $range,
            $schedule,
            $currentAffected,
            'approved_suggestion',
            $now,
        );

        return (int) ($result['new_period_id'] ?? 0);
    }

    public function scheduleFromHours(Collection $hours): array
    {
        $result = [];

        foreach (['1', '2', '3', '4', '5', '6', '7', 'holiday'] as $key) {
            $rows = $key === 'holiday'
                ? $hours->where('day_type', 'holiday')->values()
                : $hours->where('day_type', 'weekday')->where('weekday', (int) $key)->values();

            if ($rows->isEmpty()) {
                $result[$key] = [
                    'mode' => 'unknown',
                    'opens_at' => null,
                    'closes_at' => null,
                    'opens_at_2' => null,
                    'closes_at_2' => null,
                ];
                continue;
            }

            $first = $rows->first();
            $mode = $first->is_not_provided_by_operator
                ? 'not_provided'
                : ($first->is_closed
                    ? 'closed'
                    : ($first->is_24_hours
                        ? '24h'
                        : ($first->by_appointment_only ? 'appointment' : 'hours')));

            $second = $mode === 'hours' ? $rows->get(1) : null;

            $result[$key] = [
                'mode' => $mode,
                'opens_at' => $first->opens_at ? substr($first->opens_at, 0, 5) : null,
                'closes_at' => $first->closes_at ? substr($first->closes_at, 0, 5) : null,
                'opens_at_2' => $second?->opens_at ? substr($second->opens_at, 0, 5) : null,
                'closes_at_2' => $second?->closes_at ? substr($second->closes_at, 0, 5) : null,
            ];
        }

        return $result;
    }

    public function normalizeRange(bool $yearRound, ?string $start, ?string $end): array
    {
        if ($yearRound) {
            return [
                'is_year_round' => true,
                'start_month' => null,
                'start_day' => null,
                'end_month' => null,
                'end_day' => null,
            ];
        }

        [$startDay, $startMonth] = $this->parseDayMonth($start);
        [$endDay, $endMonth] = $this->parseDayMonth($end);

        return [
            'is_year_round' => false,
            'start_month' => $startMonth,
            'start_day' => $startDay,
            'end_month' => $endMonth,
            'end_day' => $endDay,
        ];
    }

    public function rangeLabel(array $range): string
    {
        if ($range['is_year_round']) {
            return __('place_profile.year_round');
        }

        return sprintf(
            '%02d.%02d.–%02d.%02d.',
            $range['start_day'],
            $range['start_month'],
            $range['end_day'],
            $range['end_month'],
        );
    }

    private function periodLabel(object $period): string
    {
        return $this->rangeLabel($this->rangeFromPeriod($period));
    }

    private function periodContainsDate(object $period, CarbonImmutable $date): bool
    {
        if ($period->is_year_round) {
            return true;
        }

        if (! $period->start_month || ! $period->start_day || ! $period->end_month || ! $period->end_day) {
            return false;
        }

        $value = ((int) $date->format('n') * 100) + (int) $date->format('j');
        $start = ((int) $period->start_month * 100) + (int) $period->start_day;
        $end = ((int) $period->end_month * 100) + (int) $period->end_day;

        return $start <= $end
            ? $value >= $start && $value <= $end
            : $value >= $start || $value <= $end;
    }

    private function rangeFromPeriod(object $period): array
    {
        return [
            'is_year_round' => (bool) $period->is_year_round,
            'start_month' => $period->start_month !== null ? (int) $period->start_month : null,
            'start_day' => $period->start_day !== null ? (int) $period->start_day : null,
            'end_month' => $period->end_month !== null ? (int) $period->end_month : null,
            'end_day' => $period->end_day !== null ? (int) $period->end_day : null,
        ];
    }

    private function applyReplacement(
        int $placeId,
        int $actorId,
        array $range,
        array $schedule,
        Collection $affected,
        string $source,
        $now = null,
    ): array {
        $now ??= now();
        $createdPeriodIds = [];

        foreach ($affected as $period) {
            $existingRange = $this->rangeFromPeriod($period);
            $remainders = $this->subtractRange($existingRange, $range);
            $existingSchedule = $this->scheduleFromHours($period->hours);

            $this->deactivatePeriod($period, $now);

            foreach ($remainders as $remainder) {
                $createdPeriodIds[] = $this->createPeriod(
                    $placeId,
                    $actorId,
                    $remainder,
                    $existingSchedule,
                    $source.' split remainder',
                    $now,
                );
            }
        }

        $newPeriodId = $this->createPeriod(
            $placeId,
            $actorId,
            $range,
            $schedule,
            $source,
            $now,
        );

        $createdPeriodIds[] = $newPeriodId;

        return [
            'new_period_id' => $newPeriodId,
            'created_period_ids' => $createdPeriodIds,
        ];
    }

    private function deactivatePeriod(object $period, $now): void
    {
        DB::table('opening_hour_periods')
            ->where('id', $period->id)
            ->update([
                'is_active' => false,
                'version_valid_until' => $now,
                'updated_at' => $now,
            ]);

        DB::table('opening_hours')
            ->where('period_id', $period->id)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->update([
                'is_active' => false,
                'version_valid_until' => $now,
                'updated_at' => $now,
            ]);
    }

    private function createPeriod(
        int $placeId,
        int $actorId,
        array $range,
        array $schedule,
        string $comment,
        $now,
    ): int {
        $periodId = (int) DB::table('opening_hour_periods')->insertGetId([
            'place_id' => $placeId,
            'period_uuid' => (string) Str::uuid(),
            'is_year_round' => (bool) $range['is_year_round'],
            'start_month' => $range['start_month'],
            'start_day' => $range['start_day'],
            'end_month' => $range['end_month'],
            'end_day' => $range['end_day'],
            'is_active' => true,
            'version_valid_from' => $now,
            'version_valid_until' => null,
            'internal_comment' => $comment,
            'created_by' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($schedule as $dayKey => $day) {
            foreach ($this->rowsForScheduleDay((string) $dayKey, $day) as $row) {
                DB::table('opening_hours')->insert($row + [
                    'place_id' => $placeId,
                    'period_id' => $periodId,
                    'feature_id' => null,
                    'valid_from' => null,
                    'valid_until' => null,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => $comment,
                    'created_by' => $actorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        return $periodId;
    }

    private function rowsForScheduleDay(string $dayKey, array $day): array
    {
        $mode = $day['mode'] ?? 'unknown';
        $base = [
            'day_type' => $dayKey === 'holiday' ? 'holiday' : 'weekday',
            'weekday' => $dayKey === 'holiday' ? null : (int) $dayKey,
            'opens_at' => null,
            'closes_at' => null,
            'is_closed' => false,
            'is_24_hours' => false,
            'by_appointment_only' => false,
            'is_not_provided_by_operator' => false,
        ];

        if ($mode === 'unknown') {
            return [];
        }

        if ($mode === 'not_provided') {
            return [array_merge($base, ['is_not_provided_by_operator' => true])];
        }

        if ($mode === 'closed') {
            return [array_merge($base, ['is_closed' => true])];
        }

        if ($mode === '24h') {
            return [array_merge($base, ['is_24_hours' => true])];
        }

        if ($mode === 'appointment') {
            return [array_merge($base, ['by_appointment_only' => true])];
        }

        $rows = [
            array_merge($base, [
                'opens_at' => $day['opens_at'] ?? null,
                'closes_at' => $day['closes_at'] ?? null,
            ]),
        ];

        if (filled($day['opens_at_2'] ?? null) && filled($day['closes_at_2'] ?? null)) {
            $rows[] = array_merge($base, [
                'opens_at' => $day['opens_at_2'],
                'closes_at' => $day['closes_at_2'],
            ]);
        }

        return $rows;
    }

    private function rangesOverlap(array $a, array $b): bool
    {
        return count(array_intersect($this->daysForRange($a), $this->daysForRange($b))) > 0;
    }

    private function subtractRange(array $existing, array $incoming): array
    {
        $remaining = array_values(array_diff(
            $this->daysForRange($existing),
            $this->daysForRange($incoming),
        ));

        if ($remaining === []) {
            return [];
        }

        sort($remaining);
        $segments = [];
        $current = [$remaining[0]];

        for ($i = 1, $count = count($remaining); $i < $count; $i++) {
            if ($remaining[$i] === $remaining[$i - 1] + 1) {
                $current[] = $remaining[$i];
                continue;
            }

            $segments[] = $current;
            $current = [$remaining[$i]];
        }
        $segments[] = $current;

        return array_map(function (array $segment): array {
            $start = $segment[0];
            $end = end($segment);
            [$startMonth, $startDay] = $this->monthDayFromOrdinal($start);
            [$endMonth, $endDay] = $this->monthDayFromOrdinal($end);

            return [
                'is_year_round' => count($segment) === self::DAYS_IN_YEAR,
                'start_month' => $startMonth,
                'start_day' => $startDay,
                'end_month' => $endMonth,
                'end_day' => $endDay,
            ];
        }, $segments);
    }

    private function daysForRange(array $range): array
    {
        if ($range['is_year_round']) {
            return range(1, self::DAYS_IN_YEAR);
        }

        $start = $this->ordinal(
            (int) $range['start_month'],
            (int) $range['start_day'],
        );
        $end = $this->ordinal(
            (int) $range['end_month'],
            (int) $range['end_day'],
        );

        if ($start <= $end) {
            return range($start, $end);
        }

        return array_merge(
            range($start, self::DAYS_IN_YEAR),
            range(1, $end),
        );
    }

    private function ordinal(int $month, int $day): int
    {
        $date = new \DateTimeImmutable(sprintf('2000-%02d-%02d', $month, $day));

        return (int) $date->format('z') + 1;
    }

    private function monthDayFromOrdinal(int $ordinal): array
    {
        $date = (new \DateTimeImmutable('2000-01-01'))->modify('+'.($ordinal - 1).' days');

        return [(int) $date->format('n'), (int) $date->format('j')];
    }

    private function parseDayMonth(?string $value): array
    {
        $value = trim((string) $value);

        if (! preg_match('/^(\d{1,2})\.(\d{1,2})\.?$/', $value, $matches)) {
            throw new RuntimeException(__('place_editing.opening_hours.invalid_format'));
        }

        $day = (int) $matches[1];
        $month = (int) $matches[2];

        if (! checkdate($month, $day, 2000)) {
            throw new RuntimeException(__('place_editing.opening_hours.invalid_date'));
        }

        return [$day, $month];
    }
}
