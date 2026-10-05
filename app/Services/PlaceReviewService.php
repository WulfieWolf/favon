<?php

namespace App\Services;

use App\Support\LocalTime;

use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PlaceReviewService
{
    public const DIMENSIONS = [
        'cleanliness' => [
            'field' => 'rating_cleanliness',
        ],
        'functionality' => [
            'field' => 'rating_functionality',
        ],
        'condition' => [
            'field' => 'rating_condition',
        ],
        'safety' => [
            'field' => 'rating_safety',
        ],
        'usability' => [
            'field' => 'rating_usability',
        ],
    ];

    public const VALID_MONTHS = 12;

    public const UPDATE_COOLDOWN_DAYS = 28;

    public const CORRECTION_MINUTES = 30;

    public function dimensions(): array
    {
        return collect(self::DIMENSIONS)
            ->map(fn (array $dimension, string $key): array => $dimension + [
                'label' => __("reviews.dimensions.{$key}.label"),
                'help' => __("reviews.dimensions.{$key}.help"),
                'examples' => __("reviews.dimensions.{$key}.examples"),
            ])
            ->all();
    }

    public function submit(User $user, int $placeId, array $ratings, ?string $text): array
    {
        $ratings = $this->validatedRatings($ratings);
        $now = now();
        $score = round(array_sum($ratings) / count($ratings), 1);
        $text = $text !== null ? trim($text) : null;
        $text = $text === '' ? null : $text;

        $result = DB::transaction(function () use ($user, $placeId, $ratings, $text, $score, $now): array {
            DB::table('place_reviews')->insertOrIgnore([
                'place_id' => $placeId,
                'user_id' => $user->id,
                'current_version_id' => null,
                'status' => 'active',
                'current_published_at' => null,
                'current_expires_at' => null,
                'verified_visit' => false,
                'verified_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $review = DB::table('place_reviews')
                ->where('place_id', $placeId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $review) {
                throw new RuntimeException(__('reviews.errors.create_failed'));
            }

            if ($review && $review->status === 'active' && $review->current_version_id && $review->current_published_at) {
                $publishedAt = Carbon::parse($review->current_published_at);

                if ($now->lte($publishedAt->copy()->addMinutes(self::CORRECTION_MINUTES))) {
                    DB::table('place_review_versions')
                        ->where('id', $review->current_version_id)
                        ->update($this->versionValues($ratings, $score, $text) + [
                            'updated_at' => $now,
                        ]);

                    return [
                        'review_id' => (int) $review->id,
                        'version_id' => (int) $review->current_version_id,
                        'is_correction' => true,
                        'is_first' => ((int) DB::table('place_review_versions')->where('review_id', $review->id)->count()) === 1,
                    ];
                }
            }

            if ($review && $review->current_published_at) {
                $nextAllowed = Carbon::parse($review->current_published_at)->addDays(self::UPDATE_COOLDOWN_DAYS);

                if ($now->lt($nextAllowed)) {
                    throw new RuntimeException(__('reviews.errors.update_available', [
                        'date' => LocalTime::translatedFormat($nextAllowed, __('reviews.date_time_format')),
                    ]));
                }
            }

            if ($review->current_version_id) {
                $oldVersion = DB::table('place_review_versions')
                    ->where('id', $review->current_version_id)
                    ->first(['id', 'valid_until']);

                if ($oldVersion && Carbon::parse($oldVersion->valid_until)->gt($now)) {
                    DB::table('place_review_versions')
                        ->where('id', $oldVersion->id)
                        ->update([
                            'valid_until' => $now,
                            'updated_at' => $now,
                        ]);
                }
            }

            $versionNumber = ((int) DB::table('place_review_versions')
                ->where('review_id', $review->id)
                ->max('version_number')) + 1;

            $expiresAt = $now->copy()->addMonthsNoOverflow(self::VALID_MONTHS);

            $versionId = (int) DB::table('place_review_versions')->insertGetId(
                $this->versionValues($ratings, $score, $text) + [
                    'review_id' => $review->id,
                    'version_number' => $versionNumber,
                    'is_public' => true,
                    'valid_from' => $now,
                    'valid_until' => $expiresAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            DB::table('place_reviews')
                ->where('id', $review->id)
                ->update([
                    'current_version_id' => $versionId,
                    'status' => 'active',
                    'current_published_at' => $now,
                    'current_expires_at' => $expiresAt,
                    'updated_at' => $now,
                ]);

            return [
                'review_id' => (int) $review->id,
                'version_id' => $versionId,
                'is_correction' => false,
                'is_first' => $versionNumber === 1,
            ];
        });

        return $result;
    }

    public function deleteCurrent(User $user, int $placeId): bool
    {
        return DB::transaction(function () use ($user, $placeId): bool {
            $review = DB::table('place_reviews')
                ->where('place_id', $placeId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $review || $review->status !== 'active' || ! $review->current_version_id) {
                return false;
            }

            $now = now();
            $version = DB::table('place_review_versions')
                ->where('id', $review->current_version_id)
                ->first(['id', 'valid_until']);

            if ($version && Carbon::parse($version->valid_until)->gt($now)) {
                DB::table('place_review_versions')
                    ->where('id', $version->id)
                    ->update([
                        'valid_until' => $now,
                        'is_public' => false,
                        'updated_at' => $now,
                    ]);
            } elseif ($version) {
                DB::table('place_review_versions')
                    ->where('id', $version->id)
                    ->update([
                        'is_public' => false,
                        'updated_at' => $now,
                    ]);
            }

            DB::table('place_reviews')
                ->where('id', $review->id)
                ->update([
                    'status' => 'deleted',
                    'current_expires_at' => $now,
                    'updated_at' => $now,
                ]);

            app(PlacePhotoService::class)->deactivateForReview((int) $review->id);

            return true;
        });
    }

    public function hideHistoricalVersion(User $user, int $reviewId, int $versionId): bool
    {
        $review = DB::table('place_reviews')
            ->where('id', $reviewId)
            ->where('user_id', $user->id)
            ->first(['id', 'current_version_id']);

        if (! $review || (int) $review->current_version_id === $versionId) {
            return false;
        }

        return DB::table('place_review_versions')
            ->where('id', $versionId)
            ->where('review_id', $reviewId)
            ->update([
                'is_public' => false,
                'updated_at' => now(),
            ]) > 0;
    }

    public function summaryForPlace(int $placeId): array
    {
        $row = $this->currentQuery($placeId)
            ->selectRaw('COUNT(*) as review_count')
            ->selectRaw('AVG(prv.overall_score) as overall_score')
            ->selectRaw('AVG(prv.rating_cleanliness) as cleanliness')
            ->selectRaw('AVG(prv.rating_functionality) as functionality')
            ->selectRaw('AVG(prv.rating_condition) as condition_score')
            ->selectRaw('AVG(prv.rating_safety) as safety')
            ->selectRaw('AVG(prv.rating_usability) as usability')
            ->first();

        return [
            'count' => (int) ($row->review_count ?? 0),
            'overall' => $row?->overall_score !== null ? round((float) $row->overall_score, 1) : null,
            'dimensions' => [
                'cleanliness' => $row?->cleanliness !== null ? round((float) $row->cleanliness, 1) : null,
                'functionality' => $row?->functionality !== null ? round((float) $row->functionality, 1) : null,
                'condition' => $row?->condition_score !== null ? round((float) $row->condition_score, 1) : null,
                'safety' => $row?->safety !== null ? round((float) $row->safety, 1) : null,
                'usability' => $row?->usability !== null ? round((float) $row->usability, 1) : null,
            ],
        ];
    }

    public function reviewsForPlace(int $placeId, string $sort = 'newest', int $perPage = 5): CursorPaginator
    {
        $query = $this->currentQuery($placeId)
            ->select([
                'pr.id',
                'pr.id as review_id',
                'pr.user_id',
                'pr.verified_visit',
                'pr.current_published_at',
                'prv.id as version_id',
                'prv.version_number',
                'prv.rating_cleanliness',
                'prv.rating_functionality',
                'prv.rating_condition',
                'prv.rating_safety',
                'prv.rating_usability',
                'prv.overall_score',
                'prv.review_text',
                'prv.valid_from',
                'prv.valid_until',
            ])
            ->selectSub(function ($sub): void {
                $sub->from('place_review_versions as older')
                    ->whereColumn('older.review_id', 'pr.id')
                    ->whereColumn('older.id', '!=', 'pr.current_version_id')
                    ->where('older.is_public', true)
                    ->selectRaw('COUNT(*)');
            }, 'history_count');

        match ($sort) {
            'oldest' => $query->orderBy('pr.current_published_at')->orderBy('pr.id'),
            'best' => $query->orderByDesc('prv.overall_score')->orderByDesc('pr.current_published_at')->orderByDesc('pr.id'),
            'worst' => $query->orderBy('prv.overall_score')->orderByDesc('pr.current_published_at')->orderByDesc('pr.id'),
            default => $query->orderByDesc('pr.current_published_at')->orderByDesc('pr.id'),
        };

        return $query->cursorPaginate(max(1, min(25, $perPage)), ['*'], 'review_cursor')->withQueryString();
    }

    public function presentersFor(Collection $reviews, ?User $viewer): array
    {
        // Favon never exposes contributor identities on public reviews.
        return [];
    }

    public function reviewHistory(int $reviewId): array
    {
        $review = DB::table('place_reviews as pr')
            ->join('places as p', 'p.id', '=', 'pr.place_id')
            ->where('pr.id', $reviewId)
            ->first([
                'pr.id',
                'pr.place_id',
                'pr.user_id',
                'pr.current_version_id',
                'p.name as place_name',
                'p.slug as place_slug',
            ]);

        if (! $review) {
            return [null, collect()];
        }

        $versions = DB::table('place_review_versions')
            ->where('review_id', $reviewId)
            ->where('is_public', true)
            ->orderByDesc('valid_from')
            ->get();

        return [$review, $versions];
    }

    public function monthlyHistory(int $placeId, int $months = 24): array
    {
        $months = max(1, min($months, 60));
        $lastCompletedMonth = now()->startOfMonth()->subMonth();
        $firstMonth = $lastCompletedMonth->copy()->subMonths($months - 1)->startOfMonth();
        $lastMonthEnd = $lastCompletedMonth->copy()->endOfMonth();

        $historicalPlaceIds = DB::table('place_merges')
            ->where('target_place_id', $placeId)
            ->where('status', 'completed')
            ->pluck('source_place_id')
            ->push($placeId)
            ->unique()
            ->values();

        $versions = DB::table('place_review_versions as prv')
            ->join('place_reviews as pr', 'pr.id', '=', 'prv.review_id')
            ->whereIn('pr.place_id', $historicalPlaceIds)
            ->where('prv.valid_from', '<=', $lastMonthEnd)
            ->where('prv.valid_until', '>=', $firstMonth)
            ->get([
                'prv.rating_cleanliness',
                'prv.rating_functionality',
                'prv.rating_condition',
                'prv.rating_safety',
                'prv.rating_usability',
                'prv.overall_score',
                'prv.valid_from',
                'prv.valid_until',
            ]);

        $result = [];

        // Deliberately use a bounded numeric loop here instead of advancing a
        // mutable Carbon instance. This guarantees that history generation can
        // never run indefinitely, even with a frozen test clock.
        for ($offset = 0; $offset < $months; $offset++) {
            $cursor = $firstMonth->copy()->addMonthsNoOverflow($offset);
            $point = $cursor->copy()->endOfMonth();

            if ($point->gt($lastMonthEnd)) {
                break;
            }

            $active = $versions->filter(fn ($version) => Carbon::parse($version->valid_from)->lte($point)
                && Carbon::parse($version->valid_until)->gt($point)
            );

            $avg = static fn (string $field) => $active->isEmpty()
                ? null
                : round((float) $active->avg($field), 1);

            $result[] = [
                'month' => $cursor->format('Y-m'),
                'count' => $active->count(),
                'overall' => $avg('overall_score'),
                'cleanliness' => $avg('rating_cleanliness'),
                'functionality' => $avg('rating_functionality'),
                'condition' => $avg('rating_condition'),
                'safety' => $avg('rating_safety'),
                'usability' => $avg('rating_usability'),
            ];
        }

        return $result;
    }

    public function currentForUser(int $placeId, int $userId): ?object
    {
        return $this->currentQuery($placeId)
            ->where('pr.user_id', $userId)
            ->first([
                'pr.id as review_id',
                'pr.current_published_at',
                'pr.current_expires_at',
                'prv.*',
            ]);
    }

    public function report(User $reporter, int $reviewId, string $reason, ?string $comment): bool
    {
        $review = DB::table('place_reviews as pr')
            ->join('place_review_versions as prv', 'prv.id', '=', 'pr.current_version_id')
            ->where('pr.id', $reviewId)
            ->where('pr.status', 'active')
            ->where('prv.is_public', true)
            ->where('prv.valid_from', '<=', now())
            ->where('prv.valid_until', '>', now())
            ->first(['pr.id', 'pr.user_id', 'pr.current_version_id']);

        if (! $review
            || (int) $review->user_id === (int) $reporter->id
        ) {
            return false;
        }

        return DB::table('place_review_reports')->insertOrIgnore([
            'review_id' => $reviewId,
            'review_version_id' => $review->current_version_id,
            'reported_by' => $reporter->id,
            'reason' => $reason,
            'comment' => $comment ? trim($comment) : null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]) > 0;
    }

    private function currentQuery(int $placeId)
    {
        return DB::table('place_reviews as pr')
            ->join('place_review_versions as prv', 'prv.id', '=', 'pr.current_version_id')
            ->where('pr.place_id', $placeId)
            ->where('pr.status', 'active')
            ->where('prv.is_public', true)
            ->where('prv.valid_from', '<=', now())
            ->where('prv.valid_until', '>', now());
    }

    private function validatedRatings(array $ratings): array
    {
        $expected = array_keys(self::DIMENSIONS);
        $actual = array_keys($ratings);
        sort($expected);
        sort($actual);

        if ($actual !== $expected) {
            throw new InvalidArgumentException(__('reviews.errors.exact_dimensions'));
        }

        foreach ($ratings as $key => $rating) {
            if (! is_int($rating) || $rating < 1 || $rating > 5) {
                throw new InvalidArgumentException(__('reviews.errors.invalid_rating', ['dimension' => $key]));
            }
        }

        return $ratings;
    }

    private function versionValues(array $ratings, float $score, ?string $text): array
    {
        return [
            'rating_cleanliness' => $ratings['cleanliness'],
            'rating_functionality' => $ratings['functionality'],
            'rating_condition' => $ratings['condition'],
            'rating_safety' => $ratings['safety'],
            'rating_usability' => $ratings['usability'],
            'overall_score' => $score,
            'review_text' => $text,
        ];
    }
}
