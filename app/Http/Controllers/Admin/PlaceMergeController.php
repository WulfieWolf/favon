<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlaceMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class PlaceMergeController extends Controller
{
    public function index(Request $request, PlaceMergeService $merges): View
    {
        $mainSearch = trim($request->string('main_search')->toString());
        $duplicateSearch = trim($request->string('duplicate_search')->toString());
        $mainId = $request->integer('main');
        $duplicateId = $request->integer('duplicate');

        $mainResults = $this->searchPlaces($mainSearch, $duplicateId);
        $duplicateResults = $this->searchPlaces($duplicateSearch, $mainId);
        $mainPlace = $mainId ? $this->placePreview($mainId) : null;
        $duplicatePlace = $duplicateId ? $this->placePreview($duplicateId) : null;

        $comparison = null;
        $error = null;
        if ($mainId && $duplicateId && $request->boolean('compare')) {
            try {
                $comparison = $merges->comparison($mainId, $duplicateId);
            } catch (RuntimeException $exception) {
                $error = $exception->getMessage();
            }
        }

        $recentMerges = DB::table('place_merges as pm')
            ->join('places as source', 'source.id', '=', 'pm.source_place_id')
            ->join('places as target', 'target.id', '=', 'pm.target_place_id')
            ->leftJoin('users as u', 'u.id', '=', 'pm.merged_by')
            ->orderByDesc('pm.merged_at')
            ->limit(20)
            ->get([
                'pm.id',
                'pm.status',
                'pm.merged_at',
                'source.name as source_name',
                'target.name as target_name',
                'u.name as actor_name',
            ]);

        return view('admin.place-merges.index', compact(
            'mainSearch',
            'duplicateSearch',
            'mainResults',
            'duplicateResults',
            'mainId',
            'duplicateId',
            'mainPlace',
            'duplicatePlace',
            'comparison',
            'error',
            'recentMerges',
        ));
    }

    public function store(Request $request, PlaceMergeService $merges): RedirectResponse
    {
        $data = $request->validate([
            'main_id' => ['required', 'integer', 'different:duplicate_id', 'exists:places,id'],
            'duplicate_id' => ['required', 'integer', 'different:main_id', 'exists:places,id'],
            'fields' => ['required', 'array'],
            'fields.*' => ['required', 'in:main,duplicate,delete'],
            'records' => ['nullable', 'array'],
            'records.*' => ['required', 'in:main,duplicate,delete'],
            'confirmation' => ['accepted'],
        ]);

        try {
            $merges->merge(
                $request->user(),
                (int) $data['main_id'],
                (int) $data['duplicate_id'],
                $data['fields'],
                $data['records'] ?? [],
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['merge' => $exception->getMessage()]);
        }

        $slug = DB::table('places')->where('id', $data['main_id'])->value('slug');

        return redirect()->route('places.show', $slug)->with('ui_toast', __('admin.place_merges.status_merged'));
    }

    public function reverse(Request $request, int $merge, PlaceMergeService $merges): RedirectResponse
    {
        try {
            $merges->reverse($request->user(), $merge);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['merge' => $exception->getMessage()]);
        }

        return back()->with('ui_toast', __('admin.place_merges.status_reversed'));
    }

    private function searchPlaces(string $search, int $excludeId = 0)
    {
        if ($search === '') {
            return collect();
        }

        $query = DB::table('places as p')
            ->leftJoin('place_addresses as pa', function ($join): void {
                $join->on('pa.place_id', '=', 'p.id')
                    ->where('pa.is_active', true)
                    ->whereNull('pa.version_valid_until');
            })
            ->where('p.is_active', true)
            ->when($excludeId > 0, fn ($query) => $query->where('p.id', '!=', $excludeId));

        if (ctype_digit($search)) {
            $query->where(function ($nested) use ($search): void {
                $nested->where('p.id', (int) $search)
                    ->orWhere('p.name', 'like', '%'.$search.'%')
                    ->orWhere('p.slug', 'like', '%'.$search.'%');
            });
        } else {
            $query->where(function ($nested) use ($search): void {
                $nested->where('p.name', 'like', '%'.$search.'%')
                    ->orWhere('p.slug', 'like', '%'.$search.'%');
            });
        }

        return $query
            ->orderByRaw('CASE WHEN p.name = ? THEN 0 WHEN p.name LIKE ? THEN 1 ELSE 2 END', [$search, $search.'%'])
            ->orderBy('p.name')
            ->limit(20)
            ->get([
                'p.id',
                'p.name',
                'p.slug',
                'p.publication_status',
                'pa.city',
            ]);
    }

    private function placePreview(int $placeId): ?object
    {
        $locale = app()->getLocale();

        return DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->leftJoin('translations as tr', function ($join) use ($locale): void {
                $join->on('tr.entity_id', '=', 'pt.id')
                    ->where('tr.entity_type', 'place_type')
                    ->where('tr.field', 'name')
                    ->where('tr.locale', $locale)
                    ->where('tr.is_active', true);
            })
            ->leftJoin('place_addresses as pa', function ($join): void {
                $join->on('pa.place_id', '=', 'p.id')
                    ->where('pa.is_active', true)
                    ->whereNull('pa.version_valid_until');
            })
            ->where('p.id', $placeId)
            ->where('p.is_active', true)
            ->first([
                'p.id',
                'p.name',
                'p.slug',
                'p.latitude',
                'p.longitude',
                'p.publication_status',
                'p.opening_status',
                'pt.slug as place_type_slug',
                DB::raw('COALESCE(tr.value, pt.slug) as place_type_label'),
                'pa.street',
                'pa.house_number',
                'pa.postal_code',
                'pa.city',
                'pa.country_code',
            ]);
    }
}
