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
        $gloryholeOnly = $request->boolean('gloryhole');

        $nearLat = $this->coordinate($request->query('near_lat'), -90, 90);
        $nearLng = $this->coordinate($request->query('near_lng'), -180, 180);
        $nearby = $nearLat !== null && $nearLng !== null;

        $requestedTypes = collect($request->query('place_types', []))
            ->filter(fn ($value) => is_string($value) && preg_match('/^[a-z0-9-]+$/', $value))
            ->unique()
            ->values();

        $requestedAudiences = collect($request->query('target_audience', []))
            ->filter(fn ($value) => is_string($value) && in_array($value, ['mm', 'mf'], true))
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

        if ($nearby) {
            $placesQuery->selectRaw(
                'ST_Distance_Sphere(POINT(p.longitude, p.latitude), POINT(?, ?)) / 1000 AS distance_km',
                [$nearLng, $nearLat],
            );
        }

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

        if ($requestedAudiences->isNotEmpty()) {
            $placesQuery->where(function ($query) use ($requestedAudiences): void {
                if ($requestedAudiences->contains('mm')) {
                    $query->orWhere('p.target_mm', true);
                }
                if ($requestedAudiences->contains('mf')) {
                    $query->orWhere('p.target_mf', true);
                }
            });
        }

        if ($gloryholeOnly) {
            $placesQuery->where('p.has_gloryhole', true);
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
            ->when(
                $nearby,
                fn ($query) => $query->orderBy('distance_km')->orderBy('p.name'),
                fn ($query) => $query->orderBy('p.name'),
            )
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
            'selectedAudiences' => $requestedAudiences->all(),
            'queryText' => $queryText,
            'favoritesOnly' => $favoritesOnly,
            'gloryholeOnly' => $gloryholeOnly,
            'favoriteIds' => $favoriteIds,
            'nearLat' => $nearLat,
            'nearLng' => $nearLng,
            'nearby' => $nearby,
        ]);
    }

    private function coordinate(mixed $value, float $min, float $max): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $coordinate = (float) $value;

        return $coordinate >= $min && $coordinate <= $max ? $coordinate : null;
    }
}
