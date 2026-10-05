<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlaceDataScoreService;
use App\Support\LocaleConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FeatureCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $fallback = LocaleConfiguration::fallback();
        $section = $request->string('section')->toString() === 'categories' ? 'categories' : 'features';
        $sort = $request->string('sort')->toString() ?: 'category';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $sortColumns = [
            'name' => 'name_de',
            'slug' => 'f.slug',
            'category' => 'category_sort_order',
            'type' => 'f.value_type',
            'usage' => 'usage_count',
            'active' => 'f.is_active',
            'searchable' => 'f.is_searchable',
            'order' => 'f.sort_order',
        ];
        $sort = array_key_exists($sort, $sortColumns) ? $sort : 'category';

        $features = DB::table('features as f')
            ->leftJoin('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->leftJoin('feature_workflows as fw', 'fw.feature_id', '=', 'f.id')
            ->select([
                'f.id', 'f.slug', 'f.value_type', 'f.unit_type', 'f.sort_order', 'f.filter_priority', 'f.is_active',
                'f.is_searchable', 'f.is_system', 'f.internal_comment', 'fc.id as category_id',
                'fc.slug as category_slug', 'fc.sort_order as category_sort_order', 'fc.filter_sort_order as category_filter_sort_order',
                'fw.config as workflow_config', 'fw.is_active as workflow_active',
            ])
            ->selectSub($this->translationQuery('feature', 'f.id', $locale), 'name_localized')
            ->selectSub($this->translationQuery('feature', 'f.id', $fallback), 'name_fallback')
            ->selectSub($this->translationQuery('feature', 'f.id', 'de'), 'name_de')
            ->selectSub($this->translationQuery('feature', 'f.id', 'en'), 'name_en')
            ->selectSub($this->translationQuery('feature_category', 'fc.id', $locale), 'category_name_localized')
            ->selectSub($this->translationQuery('feature_category', 'fc.id', $fallback), 'category_name_fallback')
            ->selectSub($this->translationQuery('feature_category', 'fc.id', 'de'), 'category_name_de')
            ->selectSub(function ($query) {
                $query->from('place_features as pf')->selectRaw('COUNT(*)')->whereColumn('pf.feature_id', 'f.id');
            }, 'usage_count')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $request->string('search')->toString()).'%';
                $query->where(function ($nested) use ($term) {
                    $nested->where('f.slug', 'like', $term)
                        ->orWhereExists(function ($translation) use ($term) {
                            $translation->selectRaw('1')->from('translations as ts')
                                ->whereColumn('ts.entity_id', 'f.id')
                                ->where('ts.entity_type', 'feature')->where('ts.field', 'name')
                                ->where('ts.value', 'like', $term);
                        });
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('fc.slug', $request->string('category')->toString()))
            ->when(in_array($request->string('activity')->toString(), ['active', 'inactive'], true),
                fn ($query) => $query->where('f.is_active', $request->string('activity')->toString() === 'active'))
            ->when($request->filled('place_type'), function ($query) use ($request) {
                $query->whereExists(function ($visibility) use ($request) {
                    $visibility->selectRaw('1')->from('feature_place_types as fpt')
                        ->join('place_types as pt', 'pt.id', '=', 'fpt.place_type_id')
                        ->whereColumn('fpt.feature_id', 'f.id')
                        ->where('pt.slug', $request->string('place_type')->toString())
                        ->where('fpt.visibility', '!=', 'hidden');
                });
            })
            ->orderBy($sortColumns[$sort], $direction)
            ->orderBy('f.id')
            ->paginate(30)
            ->withQueryString();

        $featureIds = $features->getCollection()->pluck('id');
        $visibility = DB::table('feature_place_types')
            ->whereIn('feature_id', $featureIds)
            ->get(['feature_id', 'place_type_id', 'visibility', 'filter_priority'])
            ->groupBy('feature_id');

        $features->getCollection()->transform(function ($feature) use ($visibility) {
            $feature->workflow = json_decode($feature->workflow_config ?: '{}', true) ?: [];
            $rows = collect($visibility->get($feature->id, []));
            $feature->visibility = $rows->pluck('visibility', 'place_type_id')->all();
            $feature->place_type_filter_priority = $rows->pluck('filter_priority', 'place_type_id')->all();

            return $feature;
        });

        $categories = DB::table('feature_categories as fc')
            ->select(['fc.*'])
            ->selectSub($this->translationQuery('feature_category', 'fc.id', $locale), 'name_localized')
            ->selectSub($this->translationQuery('feature_category', 'fc.id', $fallback), 'name_fallback')
            ->selectSub($this->translationQuery('feature_category', 'fc.id', 'de'), 'name_de')
            ->selectSub($this->translationQuery('feature_category', 'fc.id', 'en'), 'name_en')
            ->selectSub(function ($query) {
                $query->from('features as f')->selectRaw('COUNT(*)')->whereColumn('f.category_id', 'fc.id');
            }, 'feature_count')
            ->orderBy('fc.sort_order')
            ->orderBy('fc.slug')
            ->get();

        return view('admin.features.index', [
            'section' => $section,
            'features' => $features,
            'categories' => $categories,
            'placeTypes' => DB::table('place_types as pt')
                ->where('pt.is_active', true)
                ->select(['pt.id', 'pt.slug'])
                ->selectSub($this->translationQuery('place_type', 'pt.id', $locale), 'name_localized')
                ->selectSub($this->translationQuery('place_type', 'pt.id', $fallback), 'name_fallback')
                ->selectSub($this->translationQuery('place_type', 'pt.id', 'de'), 'name_de')
                ->orderBy('pt.sort_order')->get(),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'category' => $request->string('category')->toString(),
                'activity' => $request->string('activity')->toString() ?: 'all',
                'place_type' => $request->string('place_type')->toString(),
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function storeFeature(Request $request): RedirectResponse
    {
        $data = $this->validateFeature($request);

        DB::transaction(function () use ($request, $data): void {
            $featureId = DB::table('features')->insertGetId([
                'category_id' => $data['category_id'],
                'slug' => $data['slug'],
                'value_type' => $data['value_type'],
                'unit_type' => ($data['unit_type'] ?? null) ?: null,
                'sort_order' => $data['sort_order'],
                'filter_priority' => $data['filter_priority'],
                'is_active' => $data['is_active'],
                'is_searchable' => $data['is_searchable'],
                'is_system' => false,
                'approval_status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'internal_comment' => ($data['internal_comment'] ?? null) ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->saveFeatureRelations($featureId, $data);
            $this->audit($request->user()->id, 'feature', $featureId, 'feature_catalog_created', null, $data);
        });

        app(PlaceDataScoreService::class)->markAllDirty();

        return back()->with('ui_toast', __('admin.feature_catalog.status_feature_created'));
    }

    public function updateFeature(Request $request, int $feature): RedirectResponse
    {
        $existing = DB::table('features')->where('id', $feature)->first();
        abort_unless($existing, 404);
        $data = $this->validateFeature($request, $feature);
        $usageCount = DB::table('place_features')->where('feature_id', $feature)->count();

        if ($usageCount > 0 && $data['slug'] !== $existing->slug) {
            throw ValidationException::withMessages(['slug' => __('admin.feature_catalog.errors.used_feature_slug')]);
        }

        DB::transaction(function () use ($request, $feature, $existing, $data): void {
            DB::table('features')->where('id', $feature)->update([
                'category_id' => $data['category_id'],
                'slug' => $data['slug'],
                'value_type' => $data['value_type'],
                'unit_type' => ($data['unit_type'] ?? null) ?: null,
                'sort_order' => $data['sort_order'],
                'filter_priority' => $data['filter_priority'],
                'is_active' => $data['is_active'],
                'is_searchable' => $data['is_searchable'],
                'internal_comment' => ($data['internal_comment'] ?? null) ?: null,
                'updated_at' => now(),
            ]);

            $this->saveFeatureRelations($feature, $data);
            $this->audit($request->user()->id, 'feature', $feature, 'feature_catalog_updated', (array) $existing, $data);
        });

        app(PlaceDataScoreService::class)->markAllDirty();

        return back()->with('ui_toast', __('admin.feature_catalog.status_feature_updated'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $this->validateCategory($request);

        DB::transaction(function () use ($request, $data): void {
            $id = DB::table('feature_categories')->insertGetId([
                'slug' => $data['slug'],
                'sort_order' => $data['sort_order'],
                'filter_sort_order' => $data['filter_sort_order'],
                'is_active' => $data['is_active'],
                'is_searchable' => $data['is_searchable'],
                'is_system' => false,
                'internal_comment' => ($data['internal_comment'] ?? null) ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->saveTranslations('feature_category', $id, $data['name_de'], $data['name_en']);
            $this->audit($request->user()->id, 'feature_category', $id, 'feature_category_created', null, $data);
        });

        app(PlaceDataScoreService::class)->markAllDirty();

        return back()->with('ui_toast', __('admin.feature_catalog.status_category_created'));
    }

    public function updateCategory(Request $request, int $category): RedirectResponse
    {
        $existing = DB::table('feature_categories')->where('id', $category)->first();
        abort_unless($existing, 404);
        $data = $this->validateCategory($request, $category);

        if ($data['slug'] !== $existing->slug && DB::table('features')->where('category_id', $category)->exists()) {
            throw ValidationException::withMessages(['slug' => __('admin.feature_catalog.errors.used_category_slug')]);
        }

        DB::transaction(function () use ($request, $category, $existing, $data): void {
            DB::table('feature_categories')->where('id', $category)->update([
                'slug' => $data['slug'],
                'sort_order' => $data['sort_order'],
                'filter_sort_order' => $data['filter_sort_order'],
                'is_active' => $data['is_active'],
                'is_searchable' => $data['is_searchable'],
                'internal_comment' => ($data['internal_comment'] ?? null) ?: null,
                'updated_at' => now(),
            ]);
            $this->saveTranslations('feature_category', $category, $data['name_de'], $data['name_en']);
            $this->audit($request->user()->id, 'feature_category', $category, 'feature_category_updated', (array) $existing, $data);
        });

        app(PlaceDataScoreService::class)->markAllDirty();

        return back()->with('ui_toast', __('admin.feature_catalog.status_category_updated'));
    }

    private function validateFeature(Request $request, ?int $featureId = null): array
    {
        $validated = $request->validate([
            'name_de' => ['required', 'string', 'max:160'],
            'name_en' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('features', 'slug')->ignore($featureId)],
            'category_id' => ['required', 'integer', 'exists:feature_categories,id'],
            'value_type' => ['required', Rule::in(['boolean', 'number', 'option'])],
            'unit_type' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'filter_priority' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'place_type_filter_priority' => ['nullable', 'array'],
            'place_type_filter_priority.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
            'internal_comment' => ['nullable', 'string', 'max:2000'],
            'status_mode' => ['required', 'string', 'max:32'],
            'statuses' => ['required', 'array', 'min:1'],
            'statuses.*.value' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_\-]+$/'],
            'statuses.*.de' => ['required', 'string', 'max:100'],
            'statuses.*.en' => ['required', 'string', 'max:100'],
            'details' => ['nullable', 'array'],
            'details.*.key' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9_\-]+$/'],
            'details.*.type' => ['nullable', Rule::in(['number', 'select', 'number_unit'])],
            'details.*.de' => ['nullable', 'string', 'max:160'],
            'details.*.en' => ['nullable', 'string', 'max:160'],
            'details.*.unit' => ['nullable', 'string', 'max:16'],
            'details.*.show_for' => ['nullable', 'string', 'max:250'],
            'details.*.options_text' => ['nullable', 'string', 'max:10000'],
            'visibility' => ['nullable', 'array'],
            'visibility.*' => [Rule::in(['standard', 'extended', 'hidden'])],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_searchable'] = $request->boolean('is_searchable');
        $validated['filter_priority'] = filled($validated['filter_priority'] ?? null)
            ? (int) $validated['filter_priority']
            : null;
        $validated['workflow'] = $this->normalizeWorkflow($validated);

        return $validated;
    }

    private function normalizeWorkflow(array $data): array
    {
        $statuses = collect($data['statuses'])->map(fn ($status) => [
            'value' => $status['value'],
            'label' => ['de' => $status['de'], 'en' => $status['en']],
        ])->values()->all();
        $statusValues = collect($statuses)->pluck('value')->all();

        $details = collect($data['details'] ?? [])->filter(fn ($detail) => filled($detail['key'] ?? null))->map(function ($detail) use ($statusValues) {
            $showFor = collect(explode(',', $detail['show_for'] ?? ''))->map(fn ($value) => trim($value))->filter()
                ->filter(fn ($value) => in_array($value, $statusValues, true))->values()->all();
            $normalized = [
                'key' => $detail['key'],
                'type' => $detail['type'],
                'label' => ['de' => $detail['de'], 'en' => $detail['en']],
                'show_for' => $showFor,
            ];

            if (in_array($detail['type'], ['number', 'number_unit'], true)) {
                $normalized['unit'] = $detail['unit'] ?? '';
            }
            if ($detail['type'] === 'select') {
                $normalized['options'] = collect(preg_split('/\R/', $detail['options_text'] ?? ''))->filter()->map(function ($line) {
                    $parts = array_map('trim', explode('|', $line, 3));
                    if (count($parts) !== 3 || $parts[0] === '') {
                        throw ValidationException::withMessages(['details' => __('admin.feature_catalog.errors.option_format')]);
                    }

                    return ['value' => $parts[0], 'label' => ['de' => $parts[1], 'en' => $parts[2]]];
                })->values()->all();
            }

            return $normalized;
        })->values()->all();

        return ['status_mode' => $data['status_mode'], 'status_options' => $statuses, 'details' => $details, 'comment' => true];
    }

    private function saveFeatureRelations(int $featureId, array $data): void
    {
        $this->saveTranslations('feature', $featureId, $data['name_de'], $data['name_en']);

        DB::table('feature_workflows')->updateOrInsert(
            ['feature_id' => $featureId],
            [
                'config' => json_encode($data['workflow'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'sort_order' => $data['sort_order'],
                'is_active' => $data['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        foreach (DB::table('place_types')->where('is_active', true)->pluck('id') as $placeTypeId) {
            DB::table('feature_place_types')->updateOrInsert(
                ['feature_id' => $featureId, 'place_type_id' => $placeTypeId],
                [
                    'visibility' => $data['visibility'][$placeTypeId] ?? 'hidden',
                    'filter_priority' => filled($data['place_type_filter_priority'][$placeTypeId] ?? null)
                        ? (int) $data['place_type_filter_priority'][$placeTypeId]
                        : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function validateCategory(Request $request, ?int $categoryId = null): array
    {
        $data = $request->validate([
            'name_de' => ['required', 'string', 'max:160'],
            'name_en' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('feature_categories', 'slug')->ignore($categoryId)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'filter_sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
            'internal_comment' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_searchable'] = $request->boolean('is_searchable');
        $data['filter_sort_order'] = filled($data['filter_sort_order'] ?? null)
            ? (int) $data['filter_sort_order']
            : null;

        return $data;
    }

    private function saveTranslations(string $entityType, int $entityId, string $de, string $en): void
    {
        foreach (['de' => $de, 'en' => $en] as $locale => $value) {
            DB::table('translations')->updateOrInsert(
                ['entity_type' => $entityType, 'entity_id' => $entityId, 'locale' => $locale, 'field' => 'name'],
                ['value' => $value, 'is_active' => true, 'internal_comment' => null, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    private function translationQuery(string $entityType, string $entityColumn, string $locale)
    {
        return DB::table('translations as tr')->select('tr.value')
            ->where('tr.entity_type', $entityType)->whereColumn('tr.entity_id', $entityColumn)
            ->where('tr.locale', $locale)->where('tr.field', 'name')->where('tr.is_active', true)->limit(1);
    }

    private function audit(int $userId, string $entityType, int $entityId, string $action, ?array $old, array $new): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'source' => 'admin',
            'old_values' => $old ? json_encode($old, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'new_values' => json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'internal_comment' => null,
            'created_at' => now(),
        ]);
    }
}
