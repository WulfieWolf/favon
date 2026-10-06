<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->string('period')->toString();
        if (! in_array($period, ['30d', '12m', 'all'], true)) {
            $period = '12m';
        }

        $periodStart = match ($period) {
            '30d' => now()->subDays(29)->startOfDay(),
            '12m' => now()->subMonthsNoOverflow(11)->startOfMonth(),
            default => null,
        };

        $placeTypes = DB::table('place_types as pt')
            ->leftJoin('places as p', 'p.place_type_id', '=', 'pt.id')
            ->where('pt.is_active', true)
            ->groupBy('pt.id', 'pt.slug', 'pt.sort_order')
            ->orderBy('pt.sort_order')
            ->selectRaw('pt.slug, COUNT(p.id) as count')
            ->get()
            ->map(fn ($row) => [
                'label' => str((string) $row->slug)->replace('-', ' ')->headline()->toString(),
                'count' => (int) $row->count,
            ]);

        $roles = DB::table('roles as r')
            ->leftJoin('user_roles as ur', 'ur.role_id', '=', 'r.id')
            ->where('r.is_active', true)
            ->where('r.slug', '!=', 'guest')
            ->groupBy('r.id', 'r.slug', 'r.name', 'r.sort_order')
            ->orderBy('r.sort_order')
            ->selectRaw('r.slug, r.name, COUNT(DISTINCT ur.user_id) as count')
            ->get()
            ->map(fn ($row) => [
                'slug' => (string) $row->slug,
                'label' => (string) $row->name,
                'count' => (int) $row->count,
            ]);

        $supportStatuses = DB::table('support_tickets')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('status')
            ->pluck('count', 'status')
            ->map(fn ($count) => (int) $count);

        $usageQuery = $this->periodQuery(DB::table('usage_events'), $periodStart);
        $usageTotal = (clone $usageQuery)->count();

        $usageByAudience = (clone $usageQuery)
            ->selectRaw('audience, COUNT(*) as count')
            ->groupBy('audience')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->audience, 'count' => (int) $row->count]);

        $guestTraffic = (clone $usageQuery)
            ->where('audience', 'guest')
            ->selectRaw('traffic_type, COUNT(*) as count')
            ->groupBy('traffic_type')
            ->get()
            ->reduce(function ($totals, $row) {
                $key = in_array($row->traffic_type, [null, 'unknown'], true)
                    ? 'unclassified'
                    : (string) $row->traffic_type;

                $totals[$key] = ($totals[$key] ?? 0) + (int) $row->count;

                return $totals;
            }, collect());

        $usageByEvent = (clone $usageQuery)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit(20)
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->event_type, 'count' => (int) $row->count]);

        $pageViewsByArea = (clone $usageQuery)
            ->where('event_type', 'page_view')
            ->selectRaw('area, COUNT(*) as count')
            ->groupBy('area')
            ->orderByDesc('count')
            ->limit(25)
            ->get()
            ->map(fn ($row) => ['label' => (string) $row->area, 'count' => (int) $row->count]);

        $topPlaces = $this->periodQuery(
            DB::table('usage_events as ue')
                ->join('places as p', 'p.id', '=', 'ue.content_id')
                ->where('ue.event_type', 'page_view')
                ->where('ue.content_type', 'place')
                ->where('ue.area', 'places.show'),
            $periodStart,
            'ue.created_at',
        )
            ->groupBy('p.id', 'p.name', 'p.slug')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(20)
            ->get([
                'p.id',
                'p.name',
                'p.slug',
                DB::raw('COUNT(*) as views'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'slug' => (string) $row->slug,
                'views' => (int) $row->views,
            ]);

        $inventory = [
            'places_total' => DB::table('places')->count(),
            'places_published' => DB::table('places')->where('is_active', true)->where('publication_status', 'published')->count(),
            'places_inactive' => DB::table('places')->where('is_active', false)->count(),
            'users_total' => DB::table('users')->count(),
            'users_active' => DB::table('users')->where('account_status', 'active')->count(),
            'users_verified' => DB::table('users')->whereNotNull('email_verified_at')->count(),
            'favorites' => DB::table('place_favorites')->count(),
            'support_tickets' => DB::table('support_tickets')->count(),
            'audit_events' => DB::table('audit_logs')->count(),
            'merges' => DB::table('place_merges')->count(),
        ];

        $userStatuses = DB::table('users')
            ->selectRaw('account_status, COUNT(*) as count')
            ->groupBy('account_status')
            ->pluck('count', 'account_status')
            ->map(fn ($count) => (int) $count);

        $activitySeries = $this->activitySeries($period, $periodStart);

        return view('admin.statistics.index', compact(
            'period',
            'inventory',
            'placeTypes',
            'roles',
            'supportStatuses',
            'userStatuses',
            'usageTotal',
            'usageByAudience',
            'guestTraffic',
            'usageByEvent',
            'pageViewsByArea',
            'topPlaces',
            'activitySeries',
        ));
    }

    private function periodQuery(Builder $query, ?CarbonInterface $start, string $column = 'created_at'): Builder
    {
        return $start ? $query->where($column, '>=', $start) : $query;
    }

    private function activitySeries(string $period, ?CarbonInterface $start): array
    {
        $daily = $period === '30d';
        $tables = [
            'users' => ['table' => 'users', 'column' => 'created_at'],
            'places' => ['table' => 'places', 'column' => 'created_at'],
            'favorites' => ['table' => 'place_favorites', 'column' => 'created_at'],
            'support' => ['table' => 'support_tickets', 'column' => 'created_at'],
            'usage' => ['table' => 'usage_events', 'column' => 'created_at'],
        ];

        $series = [];
        $allDates = collect();

        foreach ($tables as $key => $definition) {
            $values = $this->groupedActivityValues(
                $definition['table'],
                $definition['column'],
                $daily,
                $start,
            );

            $series[$key] = $values;
            $allDates = $allDates->merge($values->keys());
        }

        $keys = $this->periodKeys($period, $start, $allDates->unique()->sort()->values()->all());

        return collect($keys)->map(function (string $key) use ($series, $daily): array {
            $date = $daily
                ? Carbon::createFromFormat('Y-m-d', $key)
                : Carbon::createFromFormat('Y-m', $key)->startOfMonth();

            return [
                'key' => $key,
                'label' => $daily ? $date->format('d.m.') : $date->format('m/Y'),
                'users' => (int) ($series['users'][$key] ?? 0),
                'places' => (int) ($series['places'][$key] ?? 0),
                'favorites' => (int) ($series['favorites'][$key] ?? 0),
                'support' => (int) ($series['support'][$key] ?? 0),
                'usage' => (int) ($series['usage'][$key] ?? 0),
            ];
        })->values()->all();
    }

    private function groupedActivityValues(string $table, string $column, bool $daily, ?CarbonInterface $start)
    {
        $format = $daily ? '%Y-%m-%d' : '%Y-%m';
        $driver = DB::connection()->getDriverName();
        $periodExpression = $driver === 'sqlite'
            ? "strftime(?, {$column})"
            : "DATE_FORMAT({$column}, ?)";

        return $this->periodQuery(DB::table($table), $start, $column)
            ->selectRaw("{$periodExpression} as period_key, COUNT(*) as count", [$format])
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->map(fn ($count) => (int) $count);
    }

    private function periodKeys(string $period, ?CarbonInterface $start, array $existing): array
    {
        if ($period === '30d' && $start) {
            return collect(range(0, 29))
                ->map(fn ($offset) => $start->copy()->addDays($offset)->format('Y-m-d'))
                ->all();
        }

        if ($period === '12m' && $start) {
            return collect(range(0, 11))
                ->map(fn ($offset) => $start->copy()->addMonthsNoOverflow($offset)->format('Y-m'))
                ->all();
        }

        return $existing;
    }
}
