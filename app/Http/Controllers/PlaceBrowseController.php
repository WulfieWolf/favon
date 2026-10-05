<?php

namespace App\Http\Controllers;

use App\Services\AdminDebugService;
use App\Services\PlaceBrowseFacetService;
use App\Services\PlacePhotoService;
use App\Support\LocaleConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlaceBrowseController extends Controller
{
    private const BROWSE_RESULT_LIMIT = 1000;
    private const MAP_MARKER_LIMIT = self::BROWSE_RESULT_LIMIT;
    private const MAP_MARKER_SHUFFLE_MULTIPLIER = 2654435761;
    private const MAP_MARKER_SHUFFLE_MODULUS = 4294967296;
    private const BROWSE_CACHE_TTL_SECONDS = 60;
    private const NEGATIVE_FEATURE_STATUSES = ['unknown', 'unavailable', 'no'];

    public function __invoke(
        Request $request,
        PlacePhotoService $photoService,
        PlaceBrowseFacetService $facetService,
        AdminDebugService $debug,
    ): View|JsonResponse
    {
        $profileEnabled = $debug->enabled($request->user());
        $browseProfile = [];
        $browseProfileFlags = [];
        $profileStarted = microtime(true);
        $profileLast = $profileStarted;
        $profileMark = function (string $label) use (&$browseProfile, &$profileLast, $profileEnabled): void {
            if (! $profileEnabled) {
                return;
            }

            $now = microtime(true);
            $browseProfile[$label] = round(($now - $profileLast) * 1000, 2);
            $profileLast = $now;
        };

        $q = trim((string) $request->query('q', ''));
        $isAuthenticated = (bool) $request->user();

        // Keep non-sensitive browse preferences for the lifetime of the Laravel
        // session. Search text, map position/bounds and geolocation are
        // intentionally excluded. Query values explicitly supplied by the
        // current request win; missing values fall back to the session.
        $persistentKeys = ['sort', 'sort_direction', 'place_types', 'features', 'feature_values', 'option_values', 'rating', 'favorites'];

        if ($request->boolean('reset_filters')) {
            $request->session()->forget('browse_filters');
        } else {
            $storedFilters = $request->session()->get('browse_filters', []);

            // Forms whose values may legitimately become empty send a marker,
            // because browsers omit an unchecked checkbox group entirely.
            foreach (['place_types'] as $multiSelectKey) {
                if ($request->boolean($multiSelectKey.'_filter') && ! $request->query->has($multiSelectKey)) {
                    $request->query->set($multiSelectKey, []);
                }
            }

            // Active-filter chips describe the complete filter state after the
            // click. Remove only the requested filter before session fallback.
            $clearFilter = (string) $request->query('clear_filter', '');
            if (in_array($clearFilter, $persistentKeys, true)) {
                unset($storedFilters[$clearFilter]);
                $request->query->set($clearFilter, $clearFilter === 'favorites' ? 0 : []);
            }

            foreach ($persistentKeys as $key) {
                if (! $request->query->has($key) && array_key_exists($key, $storedFilters)) {
                    $request->query->set($key, $storedFilters[$key]);
                }
            }

            // Persist the effective state, but never persist empty/false values.
            // This prevents a cleared filter from being resurrected later.
            $filtersToStore = [];
            foreach ($persistentKeys as $key) {
                if (! $request->query->has($key)) {
                    continue;
                }

                $value = $request->query($key);
                $isEmpty = $value === null
                    || $value === ''
                    || $value === false
                    || $value === 0
                    || $value === '0'
                    || (is_array($value) && $value === []);

                if (! $isEmpty) {
                    $filtersToStore[$key] = $value;
                }
            }
            $request->session()->put('browse_filters', $filtersToStore);
        }

        $sortRequested = $request->query->has('sort');
        $sort = (string) $request->query('sort', 'standard');
        $sortDirectionRequested = $request->query->has('sort_direction');
        $sortDirection = strtolower((string) $request->query('sort_direction', ''));
        $favoritesOnly = $isAuthenticated && $request->boolean('favorites');
        $requestedPlaceTypes = $request->query('place_types', []);
        if (! is_array($requestedPlaceTypes)) {
            $requestedPlaceTypes = [];
        }

        $legacyPlaceType = $request->string('place_type')->toString();
        if ($requestedPlaceTypes === [] && $legacyPlaceType !== '') {
            $requestedPlaceTypes = [$legacyPlaceType];
        }

        $requestedPlaceTypes = collect($requestedPlaceTypes)
            ->filter(fn ($value) => is_string($value) && preg_match('/^[a-z0-9-]+$/', $value))
            ->unique()
            ->take(20)
            ->values();

        $selectedPlaceTypeRows = $requestedPlaceTypes->isEmpty()
            ? collect()
            : DB::table('place_types')
                ->whereIn('slug', $requestedPlaceTypes)
                ->where('is_active', true)
                ->where('is_searchable', true)
                ->get(['id', 'slug']);

        $selectedPlaceTypes = $requestedPlaceTypes
            ->filter(fn ($slug) => $selectedPlaceTypeRows->contains('slug', $slug))
            ->values()
            ->all();

        $allSearchablePlaceTypeCount = DB::table('place_types')
            ->where('is_active', true)
            ->where('is_searchable', true)
            ->count();

        if ($selectedPlaceTypes !== [] && count($selectedPlaceTypes) === $allSearchablePlaceTypeCount) {
            $selectedPlaceTypes = [];
            $selectedPlaceTypeRows = collect();
        }

        $selectedPlaceTypeIds = $selectedPlaceTypeRows
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (! in_array($sort, ['standard', 'newest', 'changed', 'name', 'city', 'score', 'data_score'], true)) {
            $sort = 'standard';
        }

        if (! in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = in_array($sort, ['name', 'city'], true) ? 'asc' : 'desc';
        }

        $selectedVehicleTypes = [];
        $selectedVehicleTypeIds = [];

        $selectedFeatures = collect($request->query('features', []))
            ->filter(fn ($value) => is_string($value) && preg_match('/^[a-z0-9-]+$/', $value))
            ->unique()
            ->take(20)
            ->values()
            ->all();

        $mapBounds = $this->mapBounds($request);
        $mapFilter = $request->boolean('map_filter') && $mapBounds !== null;
        $mapView = $mapFilter ? $this->mapView($request) : null;

        $latestHistoryQuery = DB::table('place_history')
            ->selectRaw('place_id, MAX(created_at) as last_changed_at')
            ->groupBy('place_id');

        $placesQuery = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->leftJoinSub($latestHistoryQuery, 'latest_history', function ($join) {
                $join->on('latest_history.place_id', '=', 'p.id');
            })
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
                'p.data_score',
                'p.created_at',
                'pt.id as place_type_id',
                'pt.slug as place_type_slug',
                'pa.postal_code',
                'pa.city',
                'pa.street',
                'pa.house_number',
            ]);

        if ($sort === 'score') {
            $scoreSortQuery = DB::table('place_reviews as pr_sort')
                ->join('place_review_versions as prv_sort', 'prv_sort.id', '=', 'pr_sort.current_version_id')
                ->where('pr_sort.status', 'active')
                ->where('prv_sort.is_public', true)
                ->where('prv_sort.valid_from', '<=', now())
                ->where('prv_sort.valid_until', '>', now())
                ->groupBy('pr_sort.place_id')
                ->selectRaw('pr_sort.place_id, AVG(prv_sort.overall_score) as score');

            $placesQuery
                ->leftJoinSub($scoreSortQuery, 'score_sort', function ($join) {
                    $join->on('score_sort.place_id', '=', 'p.id');
                })
                ->addSelect('score_sort.score as browse_score');
        }

        if ($selectedPlaceTypeIds !== []) {
            $placesQuery->whereIn('p.place_type_id', $selectedPlaceTypeIds);
        }

        if ($favoritesOnly) {
            $placesQuery->whereExists(function ($favoriteQuery) use ($request): void {
                $favoriteQuery->selectRaw('1')
                    ->from('place_favorites as pf_favorite')
                    ->whereColumn('pf_favorite.place_id', 'p.id')
                    ->where('pf_favorite.user_id', $request->user()->id);
            });
        }

        if ($q !== '') {
            $like = '%'.$q.'%';

            $placesQuery->where(function ($query) use ($like) {
                $query->where('p.name', 'like', $like)
                    ->orWhere('pa.city', 'like', $like)
                    ->orWhere('pa.postal_code', 'like', $like)
                    ->orWhereExists(function ($featureQuery) use ($like) {
                        $featureQuery->selectRaw('1')
                            ->from('place_features as pf_search')
                            ->join('features as f_search', 'f_search.id', '=', 'pf_search.feature_id')
                            ->whereColumn('pf_search.place_id', 'p.id')
                            ->where('pf_search.is_active', true)
                            ->whereNull('pf_search.valid_until')
                            ->whereNotIn('pf_search.status', self::NEGATIVE_FEATURE_STATUSES)
                            ->where('f_search.is_active', true)
                            ->where('f_search.slug', 'like', $like);
                    });
            });
        }

        foreach ($selectedFeatures as $featureSlug) {
            $placesQuery->whereExists(function ($featureQuery) use ($featureSlug) {
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

        if ($mapFilter) {
            $placesQuery
                ->whereBetween('p.latitude', [$mapBounds['south'], $mapBounds['north']])
                ->whereBetween('p.longitude', [$mapBounds['west'], $mapBounds['east']]);
        }

        $hasFacetInputs = collect(['feature_values', 'option_values', 'rating'])
            ->contains(fn ($key) => $request->query($key) !== null);

        $isPlainBrowseInput = $q === ''
            && $selectedFeatures === []
            && $selectedPlaceTypeIds === []
            && ! $mapFilter
            && ! $favoritesOnly
            && ! $hasFacetInputs;

        $browseCacheAllowed = ! app()->environment('testing');

        $basePlaceIdsQuery = (clone $placesQuery)
            ->reorder()
            ->select('p.id')
            ->distinct();

        $basePlaceIds = (clone $basePlaceIdsQuery)->pluck('p.id');
        $profileMark('Base place IDs');

        $facetStarted = $profileEnabled ? microtime(true) : null;

        if ($isPlainBrowseInput && $browseCacheAllowed) {
            $facetCacheKey = 'browse:facets:v1:'.app()->getLocale();
            $facetCacheHit = Cache::has($facetCacheKey);
            $browseProfileFlags['Facet cache'] = $facetCacheHit ? 'HIT' : 'MISS';

            $facetState = Cache::remember(
                $facetCacheKey,
                now()->addSeconds(self::BROWSE_CACHE_TTL_SECONDS),
                fn () => $facetService->analyze(
                    $request,
                    $basePlaceIds,
                    $selectedPlaceTypeIds,
                    $profileEnabled ? $profileMark : null,
                    $basePlaceIdsQuery,
                    false,
                ),
            );
        } else {
            $browseProfileFlags['Facet cache'] = $isPlainBrowseInput ? 'DISABLED' : 'BYPASS';
            $facetState = $facetService->analyze(
                $request,
                $basePlaceIds,
                $selectedPlaceTypeIds,
                $profileEnabled ? $profileMark : null,
                $basePlaceIdsQuery,
                false,
            );
        }

        if ($profileEnabled && $facetStarted !== null) {
            $now = microtime(true);
            $browseProfile['Facet service total'] = round(($now - $facetStarted) * 1000, 2);
            $profileLast = $now;
        }

        if ($facetState['has_selections']) {
            $filteredIds = $facetState['filtered_place_ids']->all();
            if ($filteredIds === []) {
                $placesQuery->whereRaw('1 = 0');
            } else {
                $placesQuery->whereIn('p.id', $filteredIds);
            }
        }

        if ($request->boolean('nearby_candidates')) {
            $nearbyCandidates = (clone $placesQuery)
                ->reorder()
                ->whereNotNull('p.latitude')
                ->whereNotNull('p.longitude')
                ->distinct()
                ->get([
                    'p.id',
                    'p.name',
                    'p.slug',
                    'p.latitude',
                    'p.longitude',
                    'pa.city',
                ])
                ->map(fn ($place) => [
                    'id' => (int) $place->id,
                    'name' => $place->name,
                    'latitude' => (float) $place->latitude,
                    'longitude' => (float) $place->longitude,
                    'city' => $place->city,
                    'url' => route('places.show', $place->slug),
                ])
                ->values();

            return response()
                ->json(['places' => $nearbyCandidates])
                ->header('Cache-Control', 'no-store, private');
        }

        $filteredPlaceIdsQuery = (clone $placesQuery)
            ->reorder()
            ->select('p.id')
            ->distinct();

        $filteredPlaceCount = DB::query()
            ->fromSub((clone $filteredPlaceIdsQuery), 'filtered_place_count')
            ->count();
        $profileMark('Filtered result count');

        $shuffleOrder = '(p.id * '.self::MAP_MARKER_SHUFFLE_MULTIPLIER.') % '.self::MAP_MARKER_SHUFFLE_MODULUS;

        if ($filteredPlaceCount > self::BROWSE_RESULT_LIMIT) {
            $limitQuery = (clone $placesQuery)->reorder();

            if ($sortRequested || $sortDirectionRequested) {
                match ($sort) {
                    'changed' => $limitQuery
                        ->orderByRaw('COALESCE(latest_history.last_changed_at, p.created_at) '.$sortDirection)
                        ->orderByRaw($shuffleOrder)
                        ->orderBy('p.id'),
                    'name' => $limitQuery
                        ->orderBy('p.name', $sortDirection)
                        ->orderByRaw($shuffleOrder)
                        ->orderBy('p.id'),
                    'city' => $limitQuery
                        ->orderByRaw('pa.city IS NULL')
                        ->orderBy('pa.city', $sortDirection)
                        ->orderBy('p.name', $sortDirection)
                        ->orderByRaw($shuffleOrder)
                        ->orderBy('p.id'),
                    'score' => $limitQuery
                        ->orderByRaw('score_sort.score IS NULL')
                        ->orderBy('score_sort.score', $sortDirection)
                        ->orderBy('p.name')
                        ->orderByRaw($shuffleOrder)
                        ->orderBy('p.id'),
                    'data_score' => $limitQuery
                        ->orderByRaw('p.data_score IS NULL')
                        ->orderBy('p.data_score', $sortDirection)
                        ->orderBy('p.name')
                        ->orderByRaw($shuffleOrder)
                        ->orderBy('p.id'),
                    'newest' => $limitQuery
                        ->orderBy('p.created_at', $sortDirection)
                        ->orderByRaw($shuffleOrder)
                        ->orderBy('p.id'),
                    default => $limitQuery
                        ->orderByRaw($shuffleOrder)
                        ->orderBy('p.id'),
                };
            } else {
                $limitQuery
                    ->orderByRaw($shuffleOrder)
                    ->orderBy('p.id');
            }

            $limitedResultIds = $limitQuery
                ->limit(self::BROWSE_RESULT_LIMIT)
                ->pluck('p.id');

            $placesQuery->whereIn('p.id', $limitedResultIds);
        }
        $profileMark('Result limit IDs');

        $markerRows = (clone $placesQuery)
            ->reorder()
            ->orderByRaw($shuffleOrder)
            ->orderBy('p.id')
            ->limit(self::MAP_MARKER_LIMIT)
            ->get([
                'p.id',
                'p.name',
                'p.slug',
                'p.latitude',
                'p.longitude',
                'pa.city',
            ]);
        $profileMark('Marker query');

        match ($sort) {
            'changed' => $placesQuery
                ->orderByRaw('COALESCE(latest_history.last_changed_at, p.created_at) '.$sortDirection)
                ->orderBy('p.id', $sortDirection),
            'name' => $placesQuery->orderBy('p.name', $sortDirection)->orderBy('p.id', $sortDirection),
            'city' => $placesQuery
                ->orderByRaw('pa.city IS NULL')
                ->orderBy('pa.city', $sortDirection)
                ->orderBy('p.name', $sortDirection),
            'score' => $placesQuery
                ->orderByRaw('score_sort.score IS NULL')
                ->orderBy('score_sort.score', $sortDirection)
                ->orderBy('p.name'),
            'data_score' => $placesQuery
                ->orderByRaw('p.data_score IS NULL')
                ->orderBy('p.data_score', $sortDirection)
                ->orderBy('p.name'),
            'newest' => $placesQuery->orderBy('p.created_at', $sortDirection)->orderBy('p.id', $sortDirection),
            default => $placesQuery->orderByRaw($shuffleOrder)->orderBy('p.id'),
        };

        $places = $placesQuery->paginate(30)->withQueryString();
        $profileMark('Pagination');
        $placeIds = $places->getCollection()->pluck('id');
        $thumbnails = $photoService->thumbnailsForPlaces($placeIds);
        $currentOpeningStates = collect();
        $profileMark('Page opening states');

        $reviewStats = $placeIds->isEmpty()
            ? collect()
            : DB::table('place_reviews as pr')
                ->join('place_review_versions as prv', 'prv.id', '=', 'pr.current_version_id')
                ->whereIn('pr.place_id', $placeIds)
                ->where('pr.status', 'active')
                ->where('prv.is_public', true)
                ->where('prv.valid_from', '<=', now())
                ->where('prv.valid_until', '>', now())
                ->groupBy('pr.place_id')
                ->selectRaw('pr.place_id, AVG(prv.overall_score) as score, COUNT(*) as review_count')
                ->get()
                ->keyBy('place_id');
        $profileMark('Page review stats');

        $favoriteCount = $isAuthenticated
            ? DB::table('place_favorites as pf_count')
                ->join('places as p_count', 'p_count.id', '=', 'pf_count.place_id')
                ->where('pf_count.user_id', $request->user()->id)
                ->where('p_count.is_active', true)
                ->where('p_count.publication_status', 'published')
                ->count()
            : 0;

        $favoritePlaceIds = ! $isAuthenticated
            ? collect()
            : DB::table('place_favorites')
                ->where('user_id', $request->user()->id)
                ->pluck('place_id')
                ->map(fn ($id) => (int) $id);
        $profileMark('Favorites');

        $placeFeatureRows = $placeIds->isEmpty()
            ? collect()
            : DB::table('place_features as pf')
                ->join('features as f', 'f.id', '=', 'pf.feature_id')
                ->leftJoin('feature_categories as fc', 'fc.id', '=', 'f.category_id')
                ->whereIn('pf.place_id', $placeIds)
                ->where('pf.is_active', true)
                ->whereNull('pf.valid_until')
                ->whereNotIn('pf.status', self::NEGATIVE_FEATURE_STATUSES)
                ->where('f.is_active', true)
                ->orderBy('fc.sort_order')
                ->orderBy('f.sort_order')
                ->get([
                    'pf.place_id',
                    'f.id as feature_id',
                    'f.slug',
                    'fc.id as category_id',
                    'fc.slug as category_slug',
                ]);
        $profileMark('Page feature rows');

        $isUnfilteredBrowse = $isPlainBrowseInput && ! $facetState['has_selections'];

        $remainingFeatureCountsQuery = DB::table('place_features as pf_remaining');

        if ($isUnfilteredBrowse) {
            $remainingFeatureCountsQuery
                ->join('places as p_remaining', 'p_remaining.id', '=', 'pf_remaining.place_id')
                ->where('p_remaining.is_active', true)
                ->where('p_remaining.publication_status', 'published');
        } else {
            $remainingFeatureCountsQuery
                ->joinSub($filteredPlaceIdsQuery, 'filtered_place', function ($join) {
                    $join->on('filtered_place.id', '=', 'pf_remaining.place_id');
                });
        }

        $remainingFeatureCountsQuery
            ->where('pf_remaining.is_active', true)
            ->whereNull('pf_remaining.valid_until')
            ->whereNotIn('pf_remaining.status', self::NEGATIVE_FEATURE_STATUSES)
            ->groupBy('pf_remaining.feature_id')
            ->selectRaw('pf_remaining.feature_id, COUNT(DISTINCT pf_remaining.place_id) as remaining_count');

        $buildFeatureCatalogue = function () use ($remainingFeatureCountsQuery, $filteredPlaceCount) {
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
                )
                ->get()
                ->map(function ($row) use ($filteredPlaceCount) {
                    $row->total_count = (int) $filteredPlaceCount;
                    $row->remaining_count = (int) $row->remaining_count;
                    $row->place_count = $row->remaining_count.'/'.$row->total_count;

                    return $row;
                })
                ->filter(fn ($row) => $row->remaining_count > 0)
                ->values();
        };

        if ($isUnfilteredBrowse && $browseCacheAllowed) {
            $featureCounterCacheKey = 'browse:feature-counters:v1';
            $featureCounterCacheHit = Cache::has($featureCounterCacheKey);
            $browseProfileFlags['Feature counter cache'] = $featureCounterCacheHit ? 'HIT' : 'MISS';

            $featureCatalogue = Cache::remember(
                $featureCounterCacheKey,
                now()->addSeconds(self::BROWSE_CACHE_TTL_SECONDS),
                $buildFeatureCatalogue,
            );
        } else {
            $browseProfileFlags['Feature counter cache'] = $isUnfilteredBrowse ? 'DISABLED' : 'BYPASS';
            $featureCatalogue = $buildFeatureCatalogue();
        }
        $profileMark('Feature counters');

        $featureTypeRelations = $featureCatalogue->isEmpty()
            ? collect()
            : DB::table('feature_place_types')
                ->whereIn('feature_id', $featureCatalogue->pluck('id'))
                ->when($selectedPlaceTypeIds !== [], fn ($query) => $query->whereIn('place_type_id', $selectedPlaceTypeIds))
                ->get(['feature_id', 'place_type_id', 'visibility', 'filter_priority'])
                ->groupBy('feature_id');

        $featureCatalogue = $featureCatalogue
            ->map(function ($row) use ($featureTypeRelations, $selectedPlaceTypeIds) {
                $relations = collect($featureTypeRelations->get($row->id, []));

                $row->filter_visibility = $relations->contains(fn ($item) => $item->visibility === 'standard')
                    ? 'standard'
                    : ($relations->contains(fn ($item) => $item->visibility === 'extended') ? 'extended' : 'hidden');

                if ($selectedPlaceTypeIds !== []) {
                    $contextPriority = $relations
                        ->filter(fn ($item) => $item->visibility !== 'hidden')
                        ->pluck('filter_priority')
                        ->filter(fn ($value) => $value !== null)
                        ->map(fn ($value) => (int) $value)
                        ->min();
                    $row->effective_filter_priority = $contextPriority ?? $row->filter_priority;
                } else {
                    $row->effective_filter_priority = $row->filter_priority;
                }

                return $row;
            })
            ->filter(fn ($row) => $row->filter_visibility !== 'hidden')
            ->values();

        $featureIds = $featureCatalogue->pluck('id')->merge($placeFeatureRows->pluck('feature_id'))->unique()->values();
        $categoryIds = $featureCatalogue->pluck('category_id')->merge($placeFeatureRows->pluck('category_id'))->filter()->unique()->values();
        $placeTypeIds = $places->getCollection()->pluck('place_type_id')->unique()->values();
        $filterPlaceTypes = DB::table('place_types as pt')
            ->where('pt.is_active', true)
            ->where('pt.is_searchable', true)
            ->orderBy('pt.sort_order')
            ->get(['pt.id', 'pt.slug']);
        $filterPlaceTypeLabels = $this->translationLabels(['place_type', 'place_types'], $filterPlaceTypes->pluck('id'));
        $filterPlaceTypes->each(function ($type) use ($filterPlaceTypeLabels) {
            $type->label = $filterPlaceTypeLabels[(int) $type->id]
                ?? Str::headline(str_replace('-', ' ', $type->slug));
        });

        $filterVehicleTypes = collect();

        $featureLabels = $this->translationLabels(['feature', 'features'], $featureIds);
        $categoryLabels = $this->translationLabels(['feature_category', 'feature_categories'], $categoryIds);
        $placeTypeLabels = $this->translationLabels(['place_type', 'place_types'], $placeTypeIds);

        $featureLabel = fn ($row) => $featureLabels[(int) $row->feature_id]
            ?? $featureLabels[(int) ($row->id ?? 0)]
            ?? Str::headline(str_replace('-', ' ', $row->slug));

        $placeFeatures = $placeFeatureRows
            ->map(function ($row) use ($featureLabel) {
                $row->label = $featureLabel($row);
                return $row;
            })
            ->groupBy('place_id');

        $places->setCollection(
            $places->getCollection()->map(function ($place) use ($placeFeatures, $favoritePlaceIds, $thumbnails, $placeTypeLabels, $reviewStats, $currentOpeningStates) {
                $place->features = $placeFeatures->get($place->id, collect());
                $place->is_favorite = $favoritePlaceIds->contains((int) $place->id);
                $place->thumbnail = $thumbnails->get((int) $place->id);
                $place->place_type_label = $placeTypeLabels[(int) $place->place_type_id]
                    ?? Str::headline(str_replace('-', ' ', $place->place_type_slug));

                $stats = $reviewStats->get((int) $place->id);
                $place->score = $stats ? round((float) $stats->score, 1) : null;
                $place->review_count = $stats ? (int) $stats->review_count : 0;
                $place->current_opening_state = $currentOpeningStates->get((int) $place->id);

                return $place;
            })
        );

        $presentFeatureRows = $featureCatalogue
            ->map(function ($row) use ($featureLabels, $categoryLabels) {
                $row->label = $featureLabels[(int) $row->id] ?? Str::headline(str_replace('-', ' ', $row->slug));
                $row->category_label = $row->category_id
                    ? ($categoryLabels[(int) $row->category_id] ?? Str::headline(str_replace('-', ' ', (string) $row->category_slug)))
                    : __('ui.browse.other');
                return $row;
            });

        $priorityFeatures = $presentFeatureRows
            ->filter(fn ($row) => $row->effective_filter_priority !== null)
            ->sortBy(fn ($row) => [(int) $row->effective_filter_priority, (int) $row->sort_order, (string) $row->slug])
            ->values();

        $featureGroups = $presentFeatureRows
            ->filter(fn ($row) => $row->effective_filter_priority === null && $row->filter_visibility === 'standard')
            ->groupBy(fn ($row) => $row->category_label);

        $extendedFeatureGroups = $presentFeatureRows
            ->filter(fn ($row) => $row->effective_filter_priority === null && $row->filter_visibility === 'extended')
            ->groupBy(fn ($row) => $row->category_label);

        $markers = $markerRows->map(fn ($place) => [
            'id' => $place->id,
            'name' => $place->name,
            'latitude' => (float) $place->latitude,
            'longitude' => (float) $place->longitude,
            'city' => $place->city,
            'slug' => $place->slug,
            'url' => route('places.show', $place->slug),
            'is_favorite' => $favoritePlaceIds->contains((int) $place->id),
        ])->values();

        $profileMark('Labels and view preparation');
        if ($profileEnabled) {
            $browseProfile['Server total'] = round((microtime(true) - $profileStarted) * 1000, 2);
        }

        return view('dashboard', [
            'places' => $places,
            'featureGroups' => $featureGroups,
            'extendedFeatureGroups' => $extendedFeatureGroups,
            'priorityFeatures' => $priorityFeatures,
            'selectedFeatures' => $selectedFeatures,
            'filterPlaceTypes' => $filterPlaceTypes,
            'selectedPlaceTypes' => $selectedPlaceTypes,
            'filterVehicleTypes' => $filterVehicleTypes,
            'selectedVehicleTypes' => $selectedVehicleTypes,
            'markers' => $markers,
            'markerLimit' => self::MAP_MARKER_LIMIT,
            'resultLimit' => self::BROWSE_RESULT_LIMIT,
            'filteredPlaceCount' => $filteredPlaceCount,
            'q' => $q,
            'sort' => $sort,
            'sortDirection' => $sortDirection,
            'mapFilter' => $mapFilter,
            'mapBounds' => $mapBounds,
            'mapView' => $mapView,
            'favoritesOnly' => $favoritesOnly,
            'favoriteCount' => $favoriteCount,
            'isAuthenticated' => $isAuthenticated,
            'facetState' => $facetState,
            'profileEnabled' => $profileEnabled,
            'browseProfile' => $browseProfile,
            'browseProfileFlags' => $browseProfileFlags,
        ]);
    }

    private function mapBounds(Request $request): ?array
    {
        $keys = ['north', 'south', 'east', 'west'];
        $values = [];

        foreach ($keys as $key) {
            $value = $request->query($key);
            if (! is_numeric($value)) {
                return null;
            }
            $values[$key] = (float) $value;
        }

        if ($values['north'] <= $values['south']
            || $values['north'] > 90 || $values['south'] < -90
            || $values['east'] > 180 || $values['west'] < -180
            || $values['east'] <= $values['west']) {
            return null;
        }

        return $values;
    }

    private function mapView(Request $request): ?array
    {
        $latitude = $request->query('map_lat');
        $longitude = $request->query('map_lng');
        $zoom = $request->query('map_zoom');

        if (! is_numeric($latitude) || ! is_numeric($longitude) || ! is_numeric($zoom)) {
            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        $zoom = (int) $zoom;

        if ($latitude < -90 || $latitude > 90
            || $longitude < -180 || $longitude > 180
            || $zoom < 2 || $zoom > 19) {
            return null;
        }

        return ['latitude' => $latitude, 'longitude' => $longitude, 'zoom' => $zoom];
    }

    private function translationLabels(array $entityTypes, $entityIds): array
    {
        if ($entityIds->isEmpty()) {
            return [];
        }

        $locale = app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        $rows = DB::table('translations')
            ->whereIn('entity_type', $entityTypes)
            ->whereIn('entity_id', $entityIds)
            ->where('field', 'name')
            ->where('is_active', true)
            ->whereIn('locale', array_values(array_unique([$locale, $fallback])))
            ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$locale])
            ->get(['entity_id', 'locale', 'value']);

        $labels = [];
        foreach ($rows as $row) {
            $id = (int) $row->entity_id;
            if (! array_key_exists($id, $labels)) {
                $labels[$id] = $row->value;
            }
        }

        return $labels;
    }
}
