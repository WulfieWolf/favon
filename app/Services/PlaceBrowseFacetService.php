<?php

namespace App\Services;

use App\Support\LocaleConfiguration;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class PlaceBrowseFacetService
{
    public function analyze(Request $request, Collection $basePlaceIds, array $placeTypeIds = [], ?callable $profileMark = null, ?Builder $basePlaceIdsQuery = null): array
    {
        $mark = fn (string $label) => $profileMark ? $profileMark('Facet · '.$label) : null;

        $basePlaceIds = $basePlaceIds->map(fn ($id) => (int) $id)->unique()->values();
        $placeTypeIds = collect($placeTypeIds)->map(fn ($id) => (int) $id)->unique()->values()->all();
        $definitions = $this->featureDefinitions($placeTypeIds);
        $mark('definitions');

        $featureValues = $this->featureValues($basePlaceIds, $definitions, $basePlaceIdsQuery);
        $mark('feature values');

        $priceDefinitions = [];
        $priceValues = [];

        $ratingDefinition = $this->ratingDefinition();
        $ratingValues = $this->ratingValues($basePlaceIds, $basePlaceIdsQuery);
        $mark('rating values');

        $selections = $this->selections($request, $definitions, $priceDefinitions, $ratingDefinition);
        $selectionMatches = $this->selectionMatches($selections, $featureValues, $priceValues, $ratingValues);
        $filteredPlaceIds = $this->intersectMatches($basePlaceIds, $selectionMatches);
        $mark('selection matching');

        $numericFacets = [];
        $optionFacets = [];

        foreach ($definitions as $key => $definition) {
            $candidateIds = $this->intersectMatches(
                $basePlaceIds,
                collect($selectionMatches)->except($key)->all(),
            );
            $available = collect($featureValues[$key] ?? [])->only($candidateIds->all());

            if ($available->isEmpty()) {
                continue;
            }

            if ($definition['kind'] === 'number') {
                $numbers = $available->map(fn ($value) => (float) $value)->values();
                $numericFacets[$key] = $definition + [
                    'known_count' => $available->count(),
                    'total_count' => $candidateIds->count(),
                    'place_count' => $available->count().'/'.$candidateIds->count(),
                    'available_min' => (float) $numbers->min(),
                    'available_max' => (float) $numbers->max(),
                    'selected_min' => $selections[$key]['min'] ?? null,
                    'selected_max' => $selections[$key]['max'] ?? null,
                ];
            } else {
                $counts = $available
                    ->map(fn ($value) => (string) $value)
                    ->countBy()
                    ->all();
                $options = collect($definition['options'])
                    ->filter(fn ($option) => isset($counts[$option['value']]))
                    ->map(fn ($option) => $option + ['count' => $counts[$option['value']]])
                    ->values()
                    ->all();

                if ($options === []) {
                    continue;
                }

                $optionFacets[$key] = array_merge($definition, [
                    'known_count' => $available->count(),
                    'total_count' => $candidateIds->count(),
                    'place_count' => $available->count().'/'.$candidateIds->count(),
                    'options' => $options,
                    'selected' => $selections[$key]['values'] ?? [],
                ]);
            }
        }

        $priceFacets = [];
        foreach ($priceDefinitions as $key => $definition) {
            $candidateIds = $this->intersectMatches(
                $basePlaceIds,
                collect($selectionMatches)->except($key)->all(),
            );
            $available = collect($priceValues[$key] ?? [])->only($candidateIds->all());
            if ($available->isEmpty()) {
                continue;
            }

            $numbers = $available->map(fn ($value) => (float) $value)->values();
            $priceFacets[$key] = $definition + [
                'known_count' => $available->count(),
                'total_count' => $candidateIds->count(),
                'place_count' => $available->count().'/'.$candidateIds->count(),
                'available_min' => (float) $numbers->min(),
                'available_max' => (float) $numbers->max(),
                'selected_min' => $selections[$key]['min'] ?? null,
                'selected_max' => $selections[$key]['max'] ?? null,
            ];
        }

        $ratingFacet = null;
        $ratingKey = $ratingDefinition['key'];
        $ratingCandidateIds = $this->intersectMatches(
            $basePlaceIds,
            collect($selectionMatches)->except($ratingKey)->all(),
        );
        $availableRatings = collect($ratingValues)->only($ratingCandidateIds->all());
        if ($availableRatings->isNotEmpty()) {
            $ratingFacet = $ratingDefinition + [
                'known_count' => $availableRatings->count(),
                'total_count' => $ratingCandidateIds->count(),
                'place_count' => $availableRatings->count().'/'.$ratingCandidateIds->count(),
                'available_min' => (float) $availableRatings->min(),
                'available_max' => (float) $availableRatings->max(),
                'selected_min' => $selections[$ratingKey]['min'] ?? null,
                'selected_max' => $selections[$ratingKey]['max'] ?? null,
            ];
        }

        $mark('facet aggregation');

        $query = $this->selectionQuery($selections);
        $active = $this->activeFilters($selections, $numericFacets, $optionFacets, $priceFacets, $ratingFacet, $query);
        $mark('facet presentation');

        return [
            'filtered_place_ids' => $filteredPlaceIds,
            'has_selections' => $selections !== [],
            'query' => $query,
            'numeric_facets' => collect($numericFacets)->values(),
            'option_facets' => collect($optionFacets)->values(),
            'price_facets' => collect($priceFacets)->values(),
            'rating_facet' => $ratingFacet,
            'feature_groups' => $this->featureGroups($numericFacets, $optionFacets, 'standard', false),
            'priority_feature_groups' => $this->featureGroups($numericFacets, $optionFacets, null, true),
            'extended_feature_groups' => $this->featureGroups($numericFacets, $optionFacets, 'extended', false),
            'active_filters' => collect($active),
        ];
    }

    private function featureDefinitions(array $placeTypeIds = []): array
    {
        $locale = app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        $rows = DB::table('feature_workflows as fw')
            ->join('features as f', 'f.id', '=', 'fw.feature_id')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->leftJoin('translations as ft', function ($join) use ($locale): void {
                $join->on('ft.entity_id', '=', 'f.id')
                    ->where('ft.entity_type', 'feature')
                    ->where('ft.locale', $locale)
                    ->where('ft.field', 'name')
                    ->where('ft.is_active', true);
            })
            ->leftJoin('translations as fte', function ($join) use ($fallback): void {
                $join->on('fte.entity_id', '=', 'f.id')
                    ->where('fte.entity_type', 'feature')
                    ->where('fte.locale', $fallback)
                    ->where('fte.field', 'name')
                    ->where('fte.is_active', true);
            })
            ->leftJoin('translations as ct', function ($join) use ($locale): void {
                $join->on('ct.entity_id', '=', 'fc.id')
                    ->where('ct.entity_type', 'feature_category')
                    ->where('ct.locale', $locale)
                    ->where('ct.field', 'name')
                    ->where('ct.is_active', true);
            })
            ->leftJoin('translations as cte', function ($join) use ($fallback): void {
                $join->on('cte.entity_id', '=', 'fc.id')
                    ->where('cte.entity_type', 'feature_category')
                    ->where('cte.locale', $fallback)
                    ->where('cte.field', 'name')
                    ->where('cte.is_active', true);
            })
            ->where('fw.is_active', true)
            ->where('f.is_active', true)
            ->where('f.is_searchable', true)
            ->where('fc.is_active', true)
            ->where('fc.is_searchable', true)
            ->orderByRaw('COALESCE(fc.filter_sort_order, fc.sort_order)')
            ->orderBy('f.sort_order')
            ->get([
                'f.id as feature_id',
                'f.slug as feature_slug',
                'f.unit_type',
                'fw.config',
                'fc.slug as category_slug',
                'fc.sort_order as category_sort_order',
                'fc.filter_sort_order as category_filter_sort_order',
                'f.filter_priority as global_filter_priority',
                DB::raw('COALESCE(ft.value, fte.value, f.slug) as feature_label'),
                DB::raw('COALESCE(ct.value, cte.value, fc.slug) as category_label'),
            ]);

        $relations = $rows->isEmpty()
            ? collect()
            : DB::table('feature_place_types')
                ->whereIn('feature_id', $rows->pluck('feature_id'))
                ->when($placeTypeIds !== [], fn ($query) => $query->whereIn('place_type_id', $placeTypeIds))
                ->get(['feature_id', 'place_type_id', 'visibility', 'filter_priority'])
                ->groupBy('feature_id');

        $definitions = [];

        foreach ($rows as $row) {
            $featureRelations = collect($relations->get((int) $row->feature_id, []));

            $visibility = $featureRelations->contains(fn ($item) => $item->visibility === 'standard')
                ? 'standard'
                : ($featureRelations->contains(fn ($item) => $item->visibility === 'extended') ? 'extended' : 'hidden');

            if ($placeTypeIds !== [] && $visibility === 'hidden') {
                continue;
            }

            $contextPriority = $placeTypeIds !== []
                ? $featureRelations
                    ->filter(fn ($item) => $item->visibility !== 'hidden')
                    ->pluck('filter_priority')
                    ->filter(fn ($value) => $value !== null)
                    ->map(fn ($value) => (int) $value)
                    ->min()
                : null;

            $effectivePriority = $placeTypeIds !== []
                ? ($contextPriority ?? $row->global_filter_priority)
                : $row->global_filter_priority;

            $config = json_decode((string) $row->config, true) ?: [];

            foreach ($config['details'] ?? [] as $detail) {
                $type = (string) ($detail['type'] ?? '');
                if (! in_array($type, ['number', 'number_unit', 'select'], true)) {
                    continue;
                }

                $detailKey = (string) ($detail['key'] ?? '');
                if ($detailKey === '') {
                    continue;
                }

                $key = 'feature:'.$row->feature_slug.':'.$detailKey;
                $definition = [
                    'key' => $key,
                    'source' => 'feature',
                    'feature_id' => (int) $row->feature_id,
                    'feature_slug' => (string) $row->feature_slug,
                    'detail_key' => $detailKey,
                    'detail_type' => $type,
                    'label' => (string) $row->feature_label,
                    'detail_label' => $this->localized($detail['label'] ?? [], $locale, $detailKey),
                    'category_slug' => (string) $row->category_slug,
                    'category_label' => (string) $row->category_label,
                    'category_sort_order' => (int) ($row->category_filter_sort_order ?? $row->category_sort_order),
                    'filter_visibility' => $visibility,
                    'filter_priority' => $effectivePriority,
                    'direction' => str_starts_with((string) $row->feature_slug, 'distance-to-') ? 'maximum' : 'minimum',
                    'unit' => $this->displayUnit((string) ($row->unit_type ?? ''), $detail),
                    'unit_type' => (string) ($row->unit_type ?? ''),
                ];

                if ($type === 'select') {
                    $definition['kind'] = 'option';
                    $definition['options'] = collect($detail['options'] ?? [])
                        ->map(fn ($option) => [
                            'value' => (string) ($option['value'] ?? ''),
                            'label' => $this->localized($option['label'] ?? [], $locale, (string) ($option['value'] ?? '')),
                        ])
                        ->filter(fn ($option) => $option['value'] !== '')
                        ->values()
                        ->all();
                } else {
                    $definition['kind'] = 'number';
                    $definition['units'] = $detail['units'] ?? [];
                }

                $definitions[$key] = $definition;
            }
        }

        return $definitions;
    }

    private function featureValues(Collection $placeIds, array $definitions, ?Builder $basePlaceIdsQuery = null): array
    {
        if ($placeIds->isEmpty() || $definitions === []) {
            return [];
        }

        $definitionsByFeature = collect($definitions)->groupBy('feature_id');
        $query = DB::table('place_features as pf')
            ->whereIn('pf.feature_id', $definitionsByFeature->keys())
            ->where('pf.is_active', true)
            ->whereNull('pf.valid_until')
            ->whereNotIn('pf.status', ['unknown', 'unavailable', 'no'])
            ->whereNotNull('pf.metadata');

        if ($basePlaceIdsQuery) {
            $query->joinSub(clone $basePlaceIdsQuery, 'base_place', function ($join) {
                $join->on('base_place.id', '=', 'pf.place_id');
            });
        } else {
            $query->whereIn('pf.place_id', $placeIds);
        }

        $rows = $query->get(['pf.place_id', 'pf.feature_id', 'pf.metadata']);

        $values = [];

        foreach ($rows as $row) {
            $metadata = json_decode((string) $row->metadata, true);
            if (! is_array($metadata)) {
                continue;
            }

            foreach ($definitionsByFeature->get((int) $row->feature_id, collect()) as $definition) {
                if (! array_key_exists($definition['detail_key'], $metadata)) {
                    continue;
                }

                $raw = $metadata[$definition['detail_key']];
                if ($definition['kind'] === 'number') {
                    $number = $this->normalizedNumber($raw, $definition);
                    if ($number !== null) {
                        $values[$definition['key']][(int) $row->place_id] = $number;
                    }
                } elseif (is_scalar($raw) && (string) $raw !== '') {
                    $values[$definition['key']][(int) $row->place_id] = (string) $raw;
                }
            }
        }

        return $values;
    }

    private function ratingDefinition(): array
    {
        return [
            'key' => 'rating:overall',
            'source' => 'rating',
            'label' => __('ui.browse.rating'),
            'kind' => 'number',
            'unit' => '★',
            'priority' => 20,
        ];
    }

    private function ratingValues(Collection $placeIds, ?Builder $basePlaceIdsQuery = null): array
    {
        if ($placeIds->isEmpty()) {
            return [];
        }

        $query = DB::table('place_reviews as pr')
            ->join('place_review_versions as prv', 'prv.id', '=', 'pr.current_version_id');

        if ($basePlaceIdsQuery) {
            $query->joinSub(clone $basePlaceIdsQuery, 'base_place', function ($join) {
                $join->on('base_place.id', '=', 'pr.place_id');
            });
        } else {
            $query->whereIn('pr.place_id', $placeIds);
        }

        return $query
            ->where('pr.status', 'active')
            ->where('prv.is_public', true)
            ->where('prv.valid_from', '<=', now())
            ->where('prv.valid_until', '>', now())
            ->groupBy('pr.place_id')
            ->selectRaw('pr.place_id, AVG(prv.overall_score) as rating')
            ->pluck('rating', 'pr.place_id')
            ->map(fn ($value) => round((float) $value, 2))
            ->all();
    }

    private function selections(Request $request, array $definitions, array $priceDefinitions, array $ratingDefinition): array
    {
        $selections = [];
        $numberInput = $request->query('feature_values', []);
        $optionInput = $request->query('option_values', []);
        $priceInput = $request->query('price_values', []);

        foreach ($definitions as $key => $definition) {
            $featureSlug = $definition['feature_slug'];
            $detailKey = $definition['detail_key'];

            if ($definition['kind'] === 'number') {
                $input = $numberInput[$featureSlug][$detailKey] ?? null;
                if (! is_array($input)) {
                    continue;
                }
                $min = $this->numberOrNull($input['min'] ?? null);
                $max = $this->numberOrNull($input['max'] ?? null);
                if ($min === null && $max === null) {
                    continue;
                }
                if ($min !== null && $max !== null && $min > $max) {
                    [$min, $max] = [$max, $min];
                }
                $selections[$key] = $definition + ['min' => $min, 'max' => $max];
            } else {
                $values = $optionInput[$featureSlug][$detailKey] ?? [];
                $values = is_array($values) ? $values : [$values];
                $allowed = collect($definition['options'])->pluck('value')->all();
                $values = collect($values)
                    ->filter(fn ($value) => is_string($value) && in_array($value, $allowed, true))
                    ->unique()
                    ->values()
                    ->all();
                if ($values !== []) {
                    $selections[$key] = $definition + ['values' => $values];
                }
            }
        }

        foreach ($priceDefinitions as $key => $definition) {
            $input = $priceInput[$definition['product_slug']] ?? null;
            if (! is_array($input)) {
                continue;
            }
            $min = $this->numberOrNull($input['min'] ?? null);
            $max = $this->numberOrNull($input['max'] ?? null);
            if ($min === null && $max === null) {
                continue;
            }
            if ($min !== null && $max !== null && $min > $max) {
                [$min, $max] = [$max, $min];
            }
            $selections[$key] = $definition + ['min' => $min, 'max' => $max];
        }

        $ratingInput = $request->query('rating', []);
        if (is_array($ratingInput)) {
            $min = $this->numberOrNull($ratingInput['min'] ?? null);
            $max = $this->numberOrNull($ratingInput['max'] ?? null);
            if ($min !== null || $max !== null) {
                $min = $min !== null ? max(1, min(5, $min)) : null;
                $max = $max !== null ? max(1, min(5, $max)) : null;
                if ($min !== null && $max !== null && $min > $max) {
                    [$min, $max] = [$max, $min];
                }
                $selections[$ratingDefinition['key']] = $ratingDefinition + ['min' => $min, 'max' => $max];
            }
        }

        return $selections;
    }

    private function selectionMatches(array $selections, array $featureValues, array $priceValues, array $ratingValues): array
    {
        $matches = [];

        foreach ($selections as $key => $selection) {
            $values = collect(match ($selection['source']) {
                'price' => $priceValues[$key] ?? [],
                'rating' => $ratingValues,
                default => $featureValues[$key] ?? [],
            });

            if ($selection['kind'] === 'option') {
                $wanted = $selection['values'];
                $matches[$key] = $values
                    ->filter(fn ($value) => in_array((string) $value, $wanted, true))
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->values();
            } else {
                $matches[$key] = $values
                    ->filter(function ($value) use ($selection): bool {
                        $value = (float) $value;
                        return ($selection['min'] === null || $value >= $selection['min'])
                            && ($selection['max'] === null || $value <= $selection['max']);
                    })
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->values();
            }
        }

        return $matches;
    }

    private function intersectMatches(Collection $basePlaceIds, array $matches): Collection
    {
        $result = $basePlaceIds;

        foreach ($matches as $ids) {
            $result = $result->intersect($ids)->values();
            if ($result->isEmpty()) {
                break;
            }
        }

        return $result;
    }

    private function featureGroups(
        array $numericFacets,
        array $optionFacets,
        ?string $visibility = null,
        bool $priorityOnly = false,
    ): Collection {
        return collect(array_merge(array_values($numericFacets), array_values($optionFacets)))
            ->filter(function (array $facet) use ($visibility, $priorityOnly): bool {
                $hasPriority = $facet['filter_priority'] !== null;
                if ($priorityOnly) {
                    return $hasPriority;
                }
                if ($hasPriority) {
                    return false;
                }

                return $visibility === null || ($facet['filter_visibility'] ?? 'standard') === $visibility;
            })
            ->groupBy('category_label')
            ->map(function (Collection $facets, string $label) {
                return (object) [
                    'label' => $label,
                    'sort_order' => (int) $facets->min('category_sort_order'),
                    'features' => $facets
                        ->groupBy('feature_slug')
                        ->map(function (Collection $featureFacets) {
                            $first = $featureFacets->first();

                            return (object) [
                                'slug' => $first['feature_slug'],
                                'label' => $first['label'],
                                'filter_priority' => $featureFacets->min('filter_priority'),
                                'place_count' => $featureFacets->max('known_count').'/'.$featureFacets->max('total_count'),
                                'facets' => $featureFacets->values(),
                            ];
                        })
                        ->sortBy(fn ($feature) => [
                            $feature->filter_priority ?? PHP_INT_MAX,
                            $feature->label,
                        ])
                        ->values(),
                ];
            })
            ->sortBy('sort_order')
            ->values();
    }

    private function selectionQuery(array $selections): array
    {
        $query = [];

        foreach ($selections as $selection) {
            if ($selection['source'] === 'price') {
                $query['price_values'][$selection['product_slug']] = array_filter([
                    'min' => $selection['min'],
                    'max' => $selection['max'],
                ], fn ($value) => $value !== null);
            } elseif ($selection['source'] === 'rating') {
                $query['rating'] = array_filter([
                    'min' => $selection['min'],
                    'max' => $selection['max'],
                ], fn ($value) => $value !== null);
            } elseif ($selection['kind'] === 'number') {
                $query['feature_values'][$selection['feature_slug']][$selection['detail_key']] = array_filter([
                    'min' => $selection['min'],
                    'max' => $selection['max'],
                ], fn ($value) => $value !== null);
            } else {
                $query['option_values'][$selection['feature_slug']][$selection['detail_key']] = $selection['values'];
            }
        }

        return $query;
    }

    private function activeFilters(
        array $selections,
        array $numericFacets,
        array $optionFacets,
        array $priceFacets,
        ?array $ratingFacet,
        array $query,
    ): array {
        $active = [];

        foreach ($selections as $key => $selection) {
            $facet = $numericFacets[$key] ?? $optionFacets[$key] ?? $priceFacets[$key] ?? ($selection['source'] === 'rating' ? $ratingFacet : null) ?? $selection;
            $remaining = $query;

            if ($selection['source'] === 'price') {
                unset($remaining['price_values'][$selection['product_slug']]);
                if (($remaining['price_values'] ?? []) === []) {
                    unset($remaining['price_values']);
                }
            } elseif ($selection['source'] === 'rating') {
                unset($remaining['rating']);
            } elseif ($selection['kind'] === 'number') {
                unset($remaining['feature_values'][$selection['feature_slug']][$selection['detail_key']]);
                if (($remaining['feature_values'][$selection['feature_slug']] ?? []) === []) {
                    unset($remaining['feature_values'][$selection['feature_slug']]);
                }
                if (($remaining['feature_values'] ?? []) === []) {
                    unset($remaining['feature_values']);
                }
            } else {
                unset($remaining['option_values'][$selection['feature_slug']][$selection['detail_key']]);
                if (($remaining['option_values'][$selection['feature_slug']] ?? []) === []) {
                    unset($remaining['option_values'][$selection['feature_slug']]);
                }
                if (($remaining['option_values'] ?? []) === []) {
                    unset($remaining['option_values']);
                }
            }

            if ($selection['kind'] === 'option') {
                $labels = collect($facet['options'] ?? $selection['options'])
                    ->whereIn('value', $selection['values'])
                    ->pluck('label')
                    ->implode(', ');
                $label = $selection['label'].': '.$labels;
            } else {
                $knownOnly = isset($facet['available_min'], $facet['available_max'])
                    && $selection['min'] !== null
                    && $selection['max'] !== null
                    && abs($selection['min'] - (float) $facet['available_min']) < 0.0001
                    && abs($selection['max'] - (float) $facet['available_max']) < 0.0001;

                $filterLabel = $selection['source'] === 'feature'
                    ? $selection['label'].' · '.$selection['detail_label']
                    : $selection['label'];

                if ($knownOnly) {
                    $label = $filterLabel.': '.__('ui.browse.known');
                } elseif ($selection['min'] !== null && $selection['max'] !== null) {
                    $label = $filterLabel.' '.$this->format($selection['min']).'–'.$this->format($selection['max']).' '.$selection['unit'];
                } elseif ($selection['min'] !== null) {
                    $label = $filterLabel.' ≥ '.$this->format($selection['min']).' '.$selection['unit'];
                } else {
                    $label = $filterLabel.' ≤ '.$this->format($selection['max']).' '.$selection['unit'];
                }
            }

            $active[] = [
                'key' => $key,
                'label' => trim($label),
                'remaining_query' => $remaining,
            ];
        }

        return $active;
    }

    private function normalizedNumber(mixed $raw, array $definition): ?float
    {
        $unit = null;
        if (is_array($raw)) {
            $unit = $raw['unit'] ?? null;
            $raw = $raw['value'] ?? null;
        }

        if (! is_numeric($raw)) {
            return null;
        }

        $number = (float) $raw;
        $unitType = $definition['unit_type'];

        return match ($unitType) {
            'time' => match ($unit) {
                'hour', 'h' => $number / 24,
                default => $number,
            },
            'distance' => match ($unit) {
                'm' => $number / 1000,
                'cm' => $number / 100000,
                default => $number,
            },
            'length', 'width' => match ($unit) {
                'cm' => $number / 100,
                'km' => $number * 1000,
                default => $number,
            },
            'weight' => match ($unit) {
                'kg' => $number / 1000,
                default => $number,
            },
            default => $number,
        };
    }

    private function displayUnit(string $unitType, array $detail): string
    {
        if (($detail['type'] ?? null) === 'number' && isset($detail['unit'])) {
            return (string) $detail['unit'];
        }

        return match ($unitType) {
            'time' => __('ui.browse.units.day'),
            'distance' => 'km',
            'length', 'width' => 'm',
            'weight' => 't',
            'current' => 'A',
            'power' => 'kW',
            'energy' => 'kWh',
            'volume' => 'l',
            default => '',
        };
    }

    private function periodIsCurrent(object $row): bool
    {
        if ($row->is_year_round) {
            return true;
        }

        if (! $row->start_month || ! $row->start_day || ! $row->end_month || ! $row->end_day) {
            return false;
        }

        $today = (int) now()->format('md');
        $start = ((int) $row->start_month * 100) + (int) $row->start_day;
        $end = ((int) $row->end_month * 100) + (int) $row->end_day;

        return $start <= $end
            ? $today >= $start && $today <= $end
            : $today >= $start || $today <= $end;
    }

    private function localized(mixed $labels, string $locale, string $fallback): string
    {
        if (! is_array($labels)) {
            return $fallback;
        }

        $value = $labels[$locale] ?? $labels[LocaleConfiguration::fallback()] ?? reset($labels);

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    private function numberOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function format(float $value): string
    {
        $decimals = floor($value) === $value ? 0 : 2;

        return Number::format($value, $decimals, $decimals, app()->getLocale());
    }
}
