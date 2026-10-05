<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Temporary compatibility shim while inherited Camperwolf place views are
 * slimmed down. Favon itself does not support place or review photos.
 */
class PlacePhotoService
{
    public function publicPhotosForReviews(Collection $reviewIds, ?int $viewerId = null): Collection
    {
        return collect();
    }

    public function publicPhotosForPlace(int $placeId, ?int $viewerId = null, int $perPage = 12): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, 1, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    public function photosForOwnerReview(int $reviewId, int $userId): Collection
    {
        return collect();
    }

    public function thumbnailsForPlaces(Collection $placeIds): Collection
    {
        return collect();
    }
}
