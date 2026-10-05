<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PerformanceExplainService
{
    private const NEGATIVE_FEATURE_STATUSES = ['unknown', 'unavailable', 'no'];

    public function run(?string $featureSlug = null): array
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('performance-explain unterstützt aktuell nur MySQL/MariaDB.');
        }

        $featureSlug = $featureSlug ?: $this->defaultFeatureSlug();

        $base = $this->placesQuery();
        $filtered = clone $base;

        if ($featureSlug !== null) {
            $this->applyFeatureFilter($filtered, $featureSlug);
        }

        $scenarios = [
            'base_ids_unfiltered' => (clone $base)
                ->reorder()
                ->select('p.id')
                ->distinct(),
            'markers_unfiltered' => (clone $base)
                ->reorder()
                ->orderBy('p.id')
                ->select([
                    'p.id',
                    'p.name',
                    'p.slug',
                    'p.latitude',
                    'p.longitude',
                    'pa.city',
                ]),
            'facet_catalogue_unfiltered' => $this->featureCatalogueQuery(
                (clone $base)->reorder()->select('p.id')->distinct()
            ),
        ];

        if ($featureSlug !== null) {
            $scenarios['base_ids_feature_filter'] = (clone $filtered)
                ->reorder()
                ->select('p.id')
                ->distinct();

            $scenarios['markers_feature_filter'] = (clone $filtered)
                ->reorder()
                ->orderBy('p.id')
                ->select([
                    'p.id',
                    'p.name',
                    'p.slug',
                    'p.latitude',
                    'p.longitude',
                    'pa.city',
                ]);

            $scenarios['facet_catalogue_feature_filter'] = $this->featureCatalogueQuery(
                (clone $filtered)->reorder()->select('p.id')->distinct()
            );
        }

        $results = [];

        foreach ($scenarios as $name => $query) {
            $results[$name] = [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'plan' => $this->explainAnalyze($query),
            ];
        }

        return [
            'database' => DB::selectOne('select version() as version')->version ?? 'unknown',
            'feature_slug' => $featureSlug,
            'scenarios' => $results,
        ];
    }

    private function placesQuery(): Builder
    {
        return DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->leftJoin('place_addresses as pa', function ($join) {
                $join->on('pa.place_id', '=', 'p.id')
                    ->where('pa.is_active', true)
                    ->whereNull('pa.version_valid_until');
            })
            ->where('p.is_active', true)
            ->where('p.publication_status', 'published')
            ->select([
                'p.id',
                'p.name',
                'p.slug',
                'p.latitude',
                'p.longitude',
                'p.legal_status',
                'p.opening_status',
                'p.created_at',
                'pt.id as place_type_id',
                'pt.slug as place_type_slug',
                'pa.postal_code',
                'pa.city',
                'pa.street',
                'pa.house_number',
            ]);
    }

    private function applyFeatureFilter(Builder $query, string $featureSlug): void
    {
        $query->whereExists(function ($featureQuery) use ($featureSlug) {
            $featureQuery->selectRaw('1')
                ->from('place_features as pf_filter')
                ->join('features as f_filter', 'f_filter.id', '=', 'pf_filter.feature_id')
                ->whereColumn('pf_filter.place_id', 'p.id')
                ->where('pf_filter.is_active', true)
                ->whereNull('pf_filter.valid_until')
                ->whereNotIn('pf_filter.status', self::NEGATIVE_FEATURE_STATUSES)
                ->where('f_filter.is_active', true)
                ->where('f_filter.slug', $featureSlug);
        });
    }

    private function featureCatalogueQuery(Builder $filteredPlaceIdsQuery): Builder
    {
        $remainingFeatureCountsQuery = DB::table('place_features as pf_remaining')
            ->joinSub($filteredPlaceIdsQuery, 'filtered_place', function ($join) {
                $join->on('filtered_place.id', '=', 'pf_remaining.place_id');
            })
            ->where('pf_remaining.is_active', true)
            ->whereNull('pf_remaining.valid_until')
            ->whereNotIn('pf_remaining.status', self::NEGATIVE_FEATURE_STATUSES)
            ->groupBy('pf_remaining.feature_id')
            ->selectRaw('pf_remaining.feature_id, COUNT(DISTINCT pf_remaining.place_id) as remaining_count');

        return DB::table('features as f')
            ->leftJoin('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->leftJoinSub($remainingFeatureCountsQuery, 'feature_remaining', function ($join) {
                $join->on('feature_remaining.feature_id', '=', 'f.id');
            })
            ->where('f.is_active', true)
            ->where('f.is_searchable', true)
            ->where(function ($query) {
                $query->whereNull('fc.id')
                    ->orWhere(function ($categoryQuery) {
                        $categoryQuery->where('fc.is_active', true)
                            ->where('fc.is_searchable', true);
                    });
            })
            ->orderByRaw('COALESCE(fc.filter_sort_order, fc.sort_order, 9999)')
            ->orderBy('f.sort_order')
            ->orderBy('f.slug')
            ->selectRaw(
                'f.id, f.slug, f.sort_order, f.filter_priority, fc.id as category_id, fc.slug as category_slug, '
                .'fc.sort_order as category_sort_order, fc.filter_sort_order as category_filter_sort_order, '
                .'COALESCE(feature_remaining.remaining_count, 0) as remaining_count'
            );
    }

    private function defaultFeatureSlug(): ?string
    {
        return DB::table('features as f')
            ->join('place_features as pf', 'pf.feature_id', '=', 'f.id')
            ->where('f.is_active', true)
            ->where('f.is_searchable', true)
            ->where('pf.is_active', true)
            ->whereNull('pf.valid_until')
            ->whereNotIn('pf.status', self::NEGATIVE_FEATURE_STATUSES)
            ->groupBy('f.id', 'f.slug')
            ->orderByRaw('COUNT(*) DESC')
            ->value('f.slug');
    }

    private function explainAnalyze(Builder $query): array
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        try {
            $rows = DB::select('EXPLAIN ANALYZE '.$sql, $bindings);

            return [
                'mode' => 'EXPLAIN ANALYZE',
                'rows' => array_map(fn ($row) => (array) $row, $rows),
            ];
        } catch (\Throwable $e) {
            $rows = DB::select('EXPLAIN '.$sql, $bindings);

            return [
                'mode' => 'EXPLAIN',
                'warning' => 'EXPLAIN ANALYZE nicht verfügbar: '.$e->getMessage(),
                'rows' => array_map(fn ($row) => (array) $row, $rows),
            ];
        }
    }
}
