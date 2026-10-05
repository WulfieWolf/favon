<?php

namespace App\Services;

use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CurrentOpeningStateService
{
    /**
     * @param Collection<int, int|string> $placeIds
     * @return Collection<int, array{state:string,minutes_until_close:?int,minutes_until_open:?int}>
     */
    public function forPlaces(Collection $placeIds): Collection
    {
        $ids = $placeIds->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $now = CarbonImmutable::now(LocalTime::timezone());
        $today = $now->toDateString();
        $yesterday = $now->subDay()->toDateString();

        $exceptions = DB::table('opening_hour_exceptions')
            ->whereIn('place_id', $ids)
            ->whereNull('feature_id')
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->whereIn('exception_date', [$today, $yesterday])
            ->get()
            ->groupBy(fn ($row) => (int) $row->place_id);

        $periods = DB::table('opening_hour_periods')
            ->whereIn('place_id', $ids)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->get()
            ->filter(fn ($period) => $this->periodContains($period, $now) || $this->periodContains($period, $now->subDay()));

        $periodIds = $periods->pluck('id');
        $hours = $periodIds->isEmpty()
            ? collect()
            : DB::table('opening_hours')
                ->whereIn('period_id', $periodIds)
                ->whereNull('feature_id')
                ->where('is_active', true)
                ->whereNull('version_valid_until')
                ->where('day_type', 'weekday')
                ->whereIn('weekday', [$now->dayOfWeekIso, $now->subDay()->dayOfWeekIso])
                ->get()
                ->groupBy(fn ($row) => (int) $row->place_id);

        $periodsByPlace = $periods->groupBy(fn ($row) => (int) $row->place_id);
        $result = collect();

        foreach ($ids as $placeId) {
            $placeExceptions = collect($exceptions->get($placeId, []));
            $todayExceptions = $placeExceptions->where('exception_date', $today);
            $yesterdayExceptions = $placeExceptions->where('exception_date', $yesterday);

            $todayRows = $todayExceptions->isNotEmpty()
                ? $todayExceptions
                : $this->regularRowsForDate(collect($hours->get($placeId, [])), collect($periodsByPlace->get($placeId, [])), $now);

            $yesterdayRows = $yesterdayExceptions->isNotEmpty()
                ? $yesterdayExceptions
                : $this->regularRowsForDate(collect($hours->get($placeId, [])), collect($periodsByPlace->get($placeId, [])), $now->subDay());

            if ($todayRows->isEmpty() && $yesterdayRows->isEmpty()) {
                continue;
            }

            $openUntil = null;
            foreach ($todayRows as $row) {
                $candidate = $this->openUntil($row, $now, $now);
                if ($candidate && ($openUntil === null || $candidate->greaterThan($openUntil))) {
                    $openUntil = $candidate;
                }
            }
            foreach ($yesterdayRows as $row) {
                $candidate = $this->openUntil($row, $now->subDay(), $now);
                if ($candidate && ($openUntil === null || $candidate->greaterThan($openUntil))) {
                    $openUntil = $candidate;
                }
            }

            if ($openUntil) {
                $minutes = max(0, (int) ceil($now->diffInSeconds($openUntil, false) / 60));
                $result->put($placeId, [
                    'state' => $minutes <= 60 ? 'closing_soon' : 'open',
                    'minutes_until_close' => $minutes,
                    'minutes_until_open' => null,
                ]);
            } else {
                $nextOpen = null;
                foreach ($todayRows as $row) {
                    $candidate = $this->opensAt($row, $now, $now);
                    if ($candidate && ($nextOpen === null || $candidate->lessThan($nextOpen))) {
                        $nextOpen = $candidate;
                    }
                }

                $minutes = $nextOpen
                    ? max(0, (int) ceil($now->diffInSeconds($nextOpen, false) / 60))
                    : null;

                $result->put($placeId, [
                    'state' => $minutes !== null && $minutes <= 60 ? 'opening_soon' : 'closed',
                    'minutes_until_close' => null,
                    'minutes_until_open' => $minutes,
                ]);
            }
        }

        return $result;
    }

    private function regularRowsForDate(Collection $hours, Collection $periods, CarbonImmutable $date): Collection
    {
        $validPeriodIds = $periods
            ->filter(fn ($period) => $this->periodContains($period, $date))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        return $hours
            ->filter(fn ($row) => (int) $row->weekday === $date->dayOfWeekIso && $validPeriodIds->contains((int) $row->period_id))
            ->values();
    }

    private function periodContains(object $period, CarbonImmutable $date): bool
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

    private function opensAt(object $row, CarbonImmutable $scheduleDate, CarbonImmutable $now): ?CarbonImmutable
    {
        if ($row->is_closed || $row->by_appointment_only || $row->is_24_hours || ! $row->opens_at) {
            return null;
        }

        $start = $scheduleDate->setTimeFromTimeString((string) $row->opens_at);

        return $start->greaterThan($now) ? $start : null;
    }

    private function openUntil(object $row, CarbonImmutable $scheduleDate, CarbonImmutable $now): ?CarbonImmutable
    {
        if ($row->is_closed || $row->by_appointment_only) {
            return null;
        }

        if ($row->is_24_hours) {
            $start = $scheduleDate->startOfDay();
            $end = $start->addDay();
            return $now->betweenIncluded($start, $end) ? $end : null;
        }

        if (! $row->opens_at || ! $row->closes_at) {
            return null;
        }

        $start = $scheduleDate->setTimeFromTimeString((string) $row->opens_at);
        $end = $scheduleDate->setTimeFromTimeString((string) $row->closes_at);
        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return $now->betweenIncluded($start, $end) ? $end : null;
    }
}
