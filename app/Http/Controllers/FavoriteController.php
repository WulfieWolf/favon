<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use App\Services\UsageAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $favorites = DB::table('place_favorites as pf')
            ->join('places as p', 'p.id', '=', 'pf.place_id')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->leftJoin('place_addresses as pa', function ($join): void {
                $join->on('pa.place_id', '=', 'p.id')
                    ->where('pa.is_active', true)
                    ->whereNull('pa.version_valid_until');
            })
            ->where('pf.user_id', $request->user()->id)
            ->where('p.is_active', true)
            ->where('p.publication_status', 'published')
            ->orderByDesc('pf.created_at')
            ->select([
                'pf.id as favorite_id',
                'pf.notify_changes',
                'pf.created_at as favorited_at',
                'p.id',
                'p.name',
                'p.slug',
                'p.legal_status',
                'p.opening_status',
                'pt.slug as place_type_slug',
                'pa.postal_code',
                'pa.city',
                'pa.country_code',
            ])
            ->paginate(24)
            ->withQueryString();

        return view('favorites.index', compact('favorites'));
    }

    public function store(
        Request $request,
        string $slug,
        PermissionService $permissions,
        UsageAnalyticsService $analytics,
    ): RedirectResponse {
        abort_unless($permissions->can($request->user(), 'favorites.manage_own'), 403);

        $place = DB::table('places')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->first(['id', 'name']);

        abort_unless($place, 404);

        $exists = DB::table('place_favorites')
            ->where('user_id', $request->user()->id)
            ->where('place_id', $place->id)
            ->exists();

        if (! $exists) {
            DB::table('place_favorites')->insert([
                'user_id' => $request->user()->id,
                'place_id' => $place->id,
                'notify_changes' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id,
                'entity_type' => 'place',
                'entity_id' => $place->id,
                'action' => 'favorite_added',
                'source' => 'user',
                'old_values' => null,
                'new_values' => json_encode(['favorite' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'internal_comment' => null,
                'created_at' => now(),
            ]);

            $analytics->track($request, 'favorite_added', 'favorites', 'place', (int) $place->id);
        }

        return back()->with('ui_toast', __('ui.favorite_status.added'));
    }

    public function destroy(
        Request $request,
        string $slug,
        PermissionService $permissions,
        UsageAnalyticsService $analytics,
    ): RedirectResponse {
        abort_unless($permissions->can($request->user(), 'favorites.manage_own'), 403);

        $placeId = DB::table('places')
            ->where('slug', $slug)
            ->value('id');

        abort_unless($placeId, 404);

        $deleted = DB::table('place_favorites')
            ->where('user_id', $request->user()->id)
            ->where('place_id', $placeId)
            ->delete();

        if ($deleted > 0) {
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id,
                'entity_type' => 'place',
                'entity_id' => $placeId,
                'action' => 'favorite_removed',
                'source' => 'user',
                'old_values' => json_encode(['favorite' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'new_values' => json_encode(['favorite' => false], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'internal_comment' => null,
                'created_at' => now(),
            ]);

            $analytics->track($request, 'favorite_removed', 'favorites', 'place', (int) $placeId);
        }

        return back()->with('ui_toast', __('ui.favorite_status.removed'));
    }
}
