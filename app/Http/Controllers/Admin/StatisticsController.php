<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            ->selectRaw('pt.id, pt.slug, COUNT(p.id) as count')
            ->get()
            ->map(fn ($row) => [
                'label' => $this->translatedReferenceName('place_type', (int) $row->id, (string) $row->slug),
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

        $changeStatuses = DB::table('change_requests')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn ($count) => (int) $count);

        $photoStatuses = DB::table('photos')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
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

        $currentReviewCount = DB::table('place_reviews')
            ->where('status', 'active')
            ->count();

        $reviewTextCount = DB::table('place_reviews as pr')
            ->join('place_review_versions as prv', 'prv.id', '=', 'pr.current_version_id')
            ->where('pr.status', 'active')
            ->where('prv.is_public', true)
            ->whereNotNull('prv.review_text')
            ->where('prv.review_text', '!=', '')
            ->count();

        $inventory = [
            'places_total' => DB::table('places')->count(),
            'places_published' => DB::table('places')->where('is_active', true)->where('publication_status', 'published')->count(),
            'places_inactive' => DB::table('places')->where('is_active', false)->count(),
            'users_total' => DB::table('users')->count(),
            'users_active' => DB::table('users')->where('account_status', 'active')->count(),
            'users_verified' => DB::table('users')->whereNotNull('email_verified_at')->count(),
            'reviews_total' => $currentReviewCount,
            'review_texts' => $reviewTextCount,
            'rating_values' => $currentReviewCount * 5,
            'photos_total' => DB::table('photos')->count(),
            'photos_community' => DB::table('photos')->whereNotNull('user_id')->count(),
            'photos_external' => DB::table('photos')->whereNotNull('external_source_id')->count(),
            'features_active' => DB::table('features')->where('is_active', true)->count(),
            'feature_categories_active' => DB::table('feature_categories')->where('is_active', true)->count(),
            'place_features_active' => DB::table('place_features')->where('is_active', true)->count(),
            'favorites' => DB::table('place_favorites')->count(),
            'change_requests' => DB::table('change_requests')->count(),
            'support_tickets' => DB::table('support_tickets')->count(),
            'external_sources' => DB::table('external_sources')->count(),
            'external_records' => DB::table('external_records')->count(),
            'external_linked' => DB::table('external_records')->whereNotNull('place_id')->count(),
            'import_runs' => DB::table('external_import_runs')->count(),
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
            'periodStart',
            'inventory',
            'placeTypes',
            'roles',
            'supportStatuses',
            'changeStatuses',
            'photoStatuses',
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
            'reviews' => ['table' => 'place_reviews', 'column' => 'created_at'],
            'photos' => ['table' => 'photos', 'column' => 'created_at'],
            'changes' => ['table' => 'change_requests', 'column' => 'created_at'],
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
                'reviews' => (int) ($series['reviews'][$key] ?? 0),
                'photos' => (int) ($series['photos'][$key] ?? 0),
                'changes' => (int) ($series['changes'][$key] ?? 0),
                'support' => (int) ($series['support'][$key] ?? 0),
                'usage' => (int) ($series['usage'][$key] ?? 0),
            ];
        })->all();
    }

    private function groupedActivityValues(
        string $table,
        string $column,
        bool $daily,
        ?CarbonInterface $start,
    ) {
        $driver = DB::connection()->getDriverName();

        $bucketExpression = match ($driver) {
            'sqlite' => $daily
                ? "strftime('%Y-%m-%d', {$column})"
                : "strftime('%Y-%m', {$column})",
            default => $daily
                ? "DATE_FORMAT({$column}, '%Y-%m-%d')"
                : "DATE_FORMAT({$column}, '%Y-%m')",
        };

        $query = DB::table($table)
            ->whereNotNull($column)
            ->selectRaw($bucketExpression.' as bucket, COUNT(*) as count')
            ->groupByRaw($bucketExpression)
            ->orderBy('bucket');

        if ($start) {
            $query->where($column, '>=', $start);
        }

        return $query
            ->get()
            ->filter(fn ($row) => is_string($row->bucket) && $row->bucket !== '')
            ->mapWithKeys(fn ($row) => [(string) $row->bucket => (int) $row->count]);
    }

    private function periodKeys(string $period, ?CarbonInterface $start, array $existingKeys): array
    {
        if ($period === '30d' && $start) {
            $keys = [];
            $cursor = $start->copy()->startOfDay();
            while ($cursor->lte(now())) {
                $keys[] = $cursor->format('Y-m-d');
                $cursor = $cursor->addDay();
            }

            return $keys;
        }

        if ($period === '12m' && $start) {
            $keys = [];
            $cursor = $start->copy()->startOfMonth();
            while ($cursor->lte(now()->startOfMonth())) {
                $keys[] = $cursor->format('Y-m');
                $cursor = $cursor->addMonth();
            }

            return $keys;
        }

        return $existingKeys;
    }

    private function translatedReferenceName(string $entityType, int $entityId, string $fallback): string
    {
        $locale = app()->getLocale();

        $value = DB::table('translations')
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('field', 'name')
            ->where('locale', $locale)
            ->where('is_active', true)
            ->value('value');

        return is_string($value) && $value !== ''
            ? $value
            : Str::headline(str_replace('-', ' ', $fallback));
    }
}
