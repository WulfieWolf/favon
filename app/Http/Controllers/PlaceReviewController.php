<?php

namespace App\Http\Controllers;

use App\Services\PermissionService;
use App\Services\PlaceReviewService;
use App\Services\UsageAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PlaceReviewController extends Controller
{
    public function feed(
        Request $request,
        string $slug,
        PlaceReviewService $reviews,
        PermissionService $permissions,
    ): JsonResponse {
        $place = $this->publishedPlace($slug);
        $requestedSort = $request->string('sort')->toString();
        $sort = in_array($requestedSort, ['newest', 'oldest', 'best', 'worst'], true)
            ? $requestedSort
            : 'newest';
        $page = $reviews->reviewsForPlace((int) $place->id, $sort, 5);
        $collection = $page->getCollection();

        $html = view('places._review-cards', [
            'place' => $place,
            'placeReviews' => $collection,
            'reviewPresenters' => $reviews->presentersFor($collection, $request->user()),
            'reviewPhotos' => collect(),
            'dimensionMeta' => $reviews->dimensions(),
            'canManagePhotoCovers' => false,
            'placePhotoSetting' => null,
        ])->render();

        return response()->json([
            'html' => $html,
            'count' => $collection->count(),
            'next_cursor' => $page->nextCursor()?->encode(),
        ]);
    }

    public function store(
        Request $request,
        string $slug,
        PlaceReviewService $reviews,
        PermissionService $permissions,
        UsageAnalyticsService $analytics,
    ): RedirectResponse
    {
        $place = $this->publishedPlace($slug);

        $ratingRules = [];
        $validationMessages = [
            'review_text.min' => __('reviews.validation.text_min'),
            'review_text.max' => __('reviews.validation.text_max'),
        ];

        foreach ($reviews->dimensions() as $dimension => $meta) {
            $ratingRules[$dimension] = ['required', 'integer', 'between:1,5'];
            $validationMessages[$dimension.'.required'] = __('reviews.validation.dimension_required', [
                'dimension' => $meta['label'],
            ]);
            $validationMessages[$dimension.'.integer'] = __('reviews.validation.dimension_invalid', [
                'dimension' => $meta['label'],
            ]);
            $validationMessages[$dimension.'.between'] = __('reviews.validation.dimension_invalid', [
                'dimension' => $meta['label'],
            ]);
        }

        $data = $request->validate($ratingRules + [
            'review_text' => ['nullable', 'string', 'min:20', 'max:2000'],
        ], $validationMessages);


        try {
            $result = $reviews->submit(
                $request->user(),
                (int) $place->id,
                collect(array_keys(PlaceReviewService::DIMENSIONS))
                    ->mapWithKeys(fn (string $dimension): array => [$dimension => (int) $data[$dimension]])
                    ->all(),
                $data['review_text'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['review' => $e->getMessage()]);
        }

        $message = $result['is_correction']
            ? __('reviews.flash.corrected')
            : __('reviews.flash.published');

        $analytics->track($request, 'review_submitted', 'reviews', 'place', (int) $place->id, [
            'correction' => (bool) $result['is_correction'],
        ]);

        return redirect()
            ->route('places.show', ['slug' => $place->slug, 'sort' => 'newest'])
            ->with('ui_dialog', [
                'variant' => 'success',
                'message' => $message,
                'details' => [],
            ]);
    }

    public function destroy(
        Request $request,
        string $slug,
        PlaceReviewService $reviews,
        UsageAnalyticsService $analytics,
    ): RedirectResponse
    {
        $place = $this->publishedPlace($slug);

        $deleted = $reviews->deleteCurrent($request->user(), (int) $place->id);

        if ($deleted) {
            $analytics->track($request, 'review_removed', 'reviews', 'place', (int) $place->id);
        }

        return redirect()
            ->route('places.show', $place->slug)
            ->with('ui_dialog', [
                'variant' => $deleted ? 'success' : 'info',
                'message' => $deleted
                    ? __('reviews.flash.deleted')
                    : __('reviews.flash.nothing_to_delete'),
            ]);
    }

    public function history(int $review, PlaceReviewService $reviews): View
    {
        [$item, $versions] = $reviews->reviewHistory($review);

        abort_unless($item && $versions->isNotEmpty(), 404);

        return view('reviews.history', [
            'review' => $item,
            'versions' => $versions,
            'dimensions' => $reviews->dimensions(),
        ]);
    }

    public function hideVersion(
        Request $request,
        int $review,
        int $version,
        PlaceReviewService $reviews,
    ): RedirectResponse {
        abort_unless($reviews->hideHistoricalVersion($request->user(), $review, $version), 404);

        return back()->with('ui_toast', __('reviews.flash.history_hidden'));
    }

    public function report(
        Request $request,
        int $review,
        PlaceReviewService $reviews,
        UsageAnalyticsService $analytics,
    ): RedirectResponse {
        $data = $request->validate([
            'reason' => [
                'required',
                Rule::in(['illegal_inappropriate', 'abuse_threat_discrimination', 'personal_data', 'false_invented', 'serious_accusation', 'personal_dispute', 'unrelated', 'advertising_spam', 'manipulated_conflict', 'copyright', 'other']),
            ],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $created = $reviews->report(
            $request->user(),
            $review,
            $data['reason'],
            $data['comment'] ?? null,
        );

        if ($created) {
            $analytics->track($request, 'review_reported', 'reviews', 'review', $review);
        }

        return back()->with(
            'status',
            $created
                ? __('reviews.flash.reported')
                : __('reviews.flash.report_duplicate'),
        );
    }

    private function publishedPlace(string $slug): object
    {
        $place = DB::table('places')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->first(['id', 'name', 'slug']);

        abort_unless($place, 404);

        return $place;
    }
}
