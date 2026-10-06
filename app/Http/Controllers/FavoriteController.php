<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use App\Services\UsageAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
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
