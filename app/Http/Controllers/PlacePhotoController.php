<?php

namespace App\Http\Controllers;

use App\Services\PlacePhotoService;
use App\Services\UsageAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlacePhotoController extends Controller
{
    public function store(
        Request $request,
        string $slug,
        PlacePhotoService $photos,
        UsageAnalyticsService $analytics,
    ): RedirectResponse
    {
        $data = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['required', 'file', 'max:'.config('photos.upload_max_kilobytes', 51200)],
        ]);

        $place = DB::table('places')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->first(['id', 'slug']);
        abort_unless($place, 404);

        $review = DB::table('place_reviews')
            ->where('place_id', $place->id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->first(['id']);
        abort_unless($review, 404);

        try {
            $count = $photos->queueUploads($request->user(), (int) $place->id, (int) $review->id, $data['photos']);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['photos' => $exception->getMessage()]);
        }

        $analytics->track(
            $request,
            'photo_upload_submitted',
            'photos',
            'place',
            (int) $place->id,
            ['count' => (int) $count],
        );

        return redirect()->route('places.show', $place->slug)->with('ui_dialog', [
            'variant' => 'success',
            'message' => trans_choice('photos.flash.queued', $count, ['count' => $count]),
        ]);
    }

    public function destroy(
        Request $request,
        int $photo,
        PlacePhotoService $photos,
        UsageAnalyticsService $analytics,
    ): RedirectResponse {
        abort_unless($photos->deleteOwn($request->user(), $photo), 404);

        $analytics->track($request, 'photo_removed', 'photos', 'photo', $photo);

        return back()->with('ui_toast', __('photos.flash.removed'));
    }
}
