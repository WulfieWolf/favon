<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlaceProfileController extends Controller
{
    public function __invoke(Request $request, string $slug): View
    {
        $place = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->leftJoin('place_addresses as pa', function ($join): void {
                $join->on('pa.place_id', '=', 'p.id')
                    ->where('pa.is_active', true)
                    ->whereNull('pa.version_valid_until');
            })
            ->where('p.slug', $slug)
            ->where('p.is_active', true)
            ->where('p.publication_status', 'published')
            ->first([
                'p.id',
                'p.name',
                'p.slug',
                'p.latitude',
                'p.longitude',
                'pt.slug as place_type_slug',
                'pa.country_code',
                'pa.postal_code',
                'pa.city',
                'pa.street',
                'pa.house_number',
                'pa.address_addition',
            ]);

        abort_unless($place, 404);

        $isFavorite = $request->user()
            ? DB::table('place_favorites')
                ->where('user_id', $request->user()->id)
                ->where('place_id', $place->id)
                ->exists()
            : false;

        return view('places.show', [
            'place' => $place,
            'isFavorite' => $isFavorite,
        ]);
    }
}
