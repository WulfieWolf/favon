<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PlaceContactController extends Controller
{
    public function email(string $slug): JsonResponse
    {
        $placeId = DB::table('places')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->value('id');

        abort_unless($placeId, 404);

        $email = DB::table('place_contacts')
            ->where('place_id', $placeId)
            ->where('contact_type', 'email')
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('value');

        abort_unless(filled($email), 404);

        return response()->json([
            'email' => (string) $email,
        ])->header('Cache-Control', 'private, no-store, max-age=0');
    }
}
