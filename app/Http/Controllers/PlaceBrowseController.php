<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlaceBrowseController extends Controller
{
    public function __invoke(Request $request): View
    {
        $queryText = trim((string) $request->query('q', ''));
        $favoritesOnly = (bool) $request->user() && $request->boolean('favorites');

        $requestedTypes = collect($request->query('place_types', []))
            ->filter(fn ($value) => is_string($value) && preg_match('/^[a-z0-9-]+$/', $value))
            ->unique()
            ->values();

        $placesQuery = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->leftJoin('place_addresses as pa', function ($join): void {
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
                'pt.slug as place_type_slug',
                'pa.city',
                'pa.postal_code',
                'pa.street',
                'pa.house_number',
            ]);

        if ($queryText !== '') {
            $placesQuery->where(function ($query) use ($queryText): void {
                $query->where('p.name', 'like', '%'.$queryText.'%')
                    ->orWhere('pa.city', 'like', '%'.$queryText.'%')
                    ->orWhere('pa.postal_code', 'like', '%'.$queryText.'%');
            });
        }

        if ($requestedTypes->isNotEmpty()) {
            $placesQuery->whereIn('pt.slug', $requestedTypes->all());
        }

        if ($favoritesOnly) {
            $placesQuery->whereExists(function ($query) use ($request): void {
                $query->selectRaw('1')
                    ->from('place_favorites as pf')
                    ->whereColumn('pf.place_id', 'p.id')
                    ->where('pf.user_id', $request->user()->id);
            });
        }

        $places = $placesQuery
            ->orderBy('p.name')
            ->paginate(100)
            ->withQueryString();

        $placeTypes = DB::table('place_types')
            ->where('is_active', true)
            ->where('is_searchable', true)
            ->orderBy('sort_order')
            ->get(['slug']);

        $favoriteIds = $request->user()
            ? DB::table('place_favorites')
                ->where('user_id', $request->user()->id)
                ->whereIn('place_id', $places->getCollection()->pluck('id'))
                ->pluck('place_id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        return view('dashboard', [
            'places' => $places,
            'placeTypes' => $placeTypes,
            'selectedTypes' => $requestedTypes->all(),
            'queryText' => $queryText,
            'favoritesOnly' => $favoritesOnly,
            'favoriteIds' => $favoriteIds,
        ]);
    }
}
