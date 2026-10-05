<?php

namespace App\Services;

use App\Jobs\ProcessPhotoUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Imagick;
use RuntimeException;
use Throwable;

class PlacePhotoService
{
    public const AUTO_APPROVE_INTERNAL_COMMENT = 'auto_approve_administrative_upload';

    public function queueUploads(User $user, int $placeId, int $reviewId, array $uploads): int
    {
        app(PhotoMergeConflictService::class)->assertUploadsAllowed((int) $user->id, $placeId);

        $review = DB::table('place_reviews')
            ->where('id', $reviewId)
            ->where('place_id', $placeId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first(['id']);

        if (!$review) {
            throw new RuntimeException(__('photos.errors.own_active_review'));
        }

        $uploads = array_values(array_filter($uploads, fn ($upload) => $upload instanceof UploadedFile));
        if ($uploads === []) {
            return 0;
        }

        $existing = DB::table('place_photos as pp')
            ->join('photos as p', 'p.id', '=', 'pp.photo_id')
            ->where('pp.place_review_id', $reviewId)
            ->where('pp.is_active', true)
            ->where('p.is_active', true)
            ->whereIn('p.status', ['processing', 'pending', 'approved'])
            ->count();

        $maximum = (int) config('photos.max_per_review', 5);
        if ($existing + count($uploads) > $maximum) {
            throw new RuntimeException(__('photos.errors.maximum', ['maximum' => $maximum]));
        }

        $metadata = [];
        foreach ($uploads as $upload) {
            $metadata[] = $this->inspectUpload($upload);
        }

        $storedPaths = [];
        $permissions = app(PermissionService::class);
        $autoApprove = $permissions->can($user, 'places.create_direct')
            && $permissions->can($user, 'photos.moderate');

        try {
            $photoIds = DB::transaction(function () use ($user, $placeId, $reviewId, $uploads, $metadata, $autoApprove, &$storedPaths): array {
                $ids = [];
                $nextSort = ((int) DB::table('place_photos')
                    ->where('place_review_id', $reviewId)
                    ->max('sort_order')) + 10;

                foreach ($uploads as $index => $upload) {
                    $uuid = (string) Str::uuid();
                    $sourcePath = $upload->storeAs('photo-uploads', $uuid.'.source', 'local');
                    if (!is_string($sourcePath) || $sourcePath === '') {
                        throw new RuntimeException(__('photos.errors.temporary_storage'));
                    }

                    $storedPaths[] = $sourcePath;
                    $now = now();
                    $photoId = (int) DB::table('photos')->insertGetId([
                        'uuid' => $uuid,
                        'user_id' => $user->id,
                        'storage_path' => 'photos/'.$uuid.'/detail.webp',
                        'source_path' => $sourcePath,
                        'preview_path' => null,
                        'original_filename' => null,
                        'mime_type' => $metadata[$index]['mime_type'],
                        'file_size' => $metadata[$index]['file_size'],
                        'preview_file_size' => null,
                        'width' => $metadata[$index]['width'],
                        'height' => $metadata[$index]['height'],
                        'status' => 'processing',
                        'moderated_by' => null,
                        'moderated_at' => null,
                        'moderation_reason' => null,
                        'processing_error' => null,
                        'is_active' => true,
                        'internal_comment' => $autoApprove ? self::AUTO_APPROVE_INTERNAL_COMMENT : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('place_photos')->insert([
                        'place_id' => $placeId,
                        'place_review_id' => $reviewId,
                        'photo_id' => $photoId,
                        'photo_type' => 'review',
                        'sort_order' => $nextSort,
                        'is_thumbnail_eligible' => true,
                        'thumbnail_excluded_by' => null,
                        'thumbnail_excluded_at' => null,
                        'thumbnail_exclusion_reason' => null,
                        'is_active' => true,
                        'internal_comment' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $ids[] = $photoId;
                    $nextSort += 10;
                }

                return $ids;
            });

            foreach ($photoIds as $photoId) {
                ProcessPhotoUpload::dispatch($photoId)->afterCommit();
            }

            return count($photoIds);
        } catch (Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    public function publicPhotosForReviews(Collection $reviewIds, ?int $viewerId = null): Collection
    {
        if ($reviewIds->isEmpty()) {
            return collect();
        }

        return $this->publicPhotoQuery()
            ->whereIn('pp.place_review_id', $reviewIds)
            ->orderBy('pp.sort_order')
            ->orderBy('p.id')
            ->get($this->photoSelect($viewerId))
            ->groupBy('place_review_id');
    }

    public function publicPhotosForPlace(int $placeId, ?int $viewerId = null, int $perPage = 12)
    {
        return $this->publicPhotoQuery()
            ->where('pp.place_id', $placeId)
            ->orderByDesc('p.created_at')
            ->orderByDesc('p.id')
            ->paginate(
                max(1, min(48, $perPage)),
                $this->photoSelect($viewerId),
                'photo_page',
            )
            ->withQueryString()
            ->fragment('photos');
    }

    public function administrativeLibrary(array $filters, string $sort, string $direction)
    {
        $sortColumns = [
            'photo' => 'p.id',
            'place' => 'pl.name',
            'review' => 'pp.place_review_id',
            'author' => 'u.name',
            'status' => 'p.status',
            'dimensions' => 'p.width',
            'format' => 'p.mime_type',
            'size' => 'p.file_size',
            'helpful' => 'helpful_count',
            'uploaded' => 'p.created_at',
        ];

        $sort = array_key_exists($sort, $sortColumns) ? $sort : 'uploaded';
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $query = DB::table('photos as p')
            ->leftJoin('place_photos as pp', 'pp.photo_id', '=', 'p.id')
            ->leftJoin('places as pl', 'pl.id', '=', 'pp.place_id')
            ->leftJoin('place_reviews as pr', 'pr.id', '=', 'pp.place_review_id')
            ->leftJoin('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('place_photo_settings as pps', 'pps.place_id', '=', 'pp.place_id')
            ->select([
                'p.id', 'p.uuid', 'p.status', 'p.is_active', 'p.width', 'p.height',
                'p.mime_type', 'p.file_size', 'p.preview_file_size', 'p.created_at',
                'p.moderated_at', 'p.moderation_reason', 'p.processing_error',
                'pp.place_id', 'pp.place_review_id', 'pp.is_active as relation_active',
                'pp.is_thumbnail_eligible', 'pp.thumbnail_exclusion_reason',
                'pl.name as place_name', 'pl.slug as place_slug',
                'pr.status as review_status', 'u.name as author_name', 'u.email as author_email',
                'pps.admin_photo_id',
                DB::raw('(SELECT COUNT(*) FROM photo_helpful_votes phv WHERE phv.photo_id = p.id) as helpful_count'),
            ]);

        if (!empty($filters['status'])) {
            $query->where('p.status', $filters['status']);
        }

        if (($filters['activity'] ?? 'all') === 'active') {
            $query->where('p.is_active', true);
        } elseif (($filters['activity'] ?? 'all') === 'inactive') {
            $query->where('p.is_active', false);
        }

        if (!empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function ($query) use ($search): void {
                $query->where('pl.name', 'like', $search)
                    ->orWhere('u.name', 'like', $search)
                    ->orWhere('u.email', 'like', $search)
                    ->orWhere('p.uuid', 'like', $search);
            });
        }

        return $query
            ->orderBy($sortColumns[$sort], $direction)
            ->orderByDesc('p.id')
            ->paginate(30, ['*'], 'library_page')
            ->withQueryString();
    }

    public function deactivateForReview(int $reviewId): void
    {
        DB::table('place_photos')
            ->where('place_review_id', $reviewId)
            ->where('is_active', true)
            ->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function photosForOwnerReview(int $reviewId, int $userId): Collection
    {
        return DB::table('place_photos as pp')
            ->join('photos as p', 'p.id', '=', 'pp.photo_id')
            ->where('pp.place_review_id', $reviewId)
            ->where('p.user_id', $userId)
            ->where('pp.is_active', true)
            ->where('p.is_active', true)
            ->orderBy('pp.sort_order')
            ->get([
                'p.id', 'p.uuid', 'p.status', 'p.moderation_reason', 'p.processing_error',
                'p.width', 'p.height', 'p.created_at', 'pp.place_id', 'pp.place_review_id',
            ]);
    }

    public function thumbnailsForPlaces(Collection $placeIds): Collection
    {
        if ($placeIds->isEmpty()) {
            return collect();
        }

        $candidates = $this->publicPhotoQuery()
            ->whereIn('pp.place_id', $placeIds)
            ->where('pp.is_thumbnail_eligible', true)
            ->orderBy('p.id')
            ->get([
                'p.id', 'p.uuid', 'p.external_source_id', 'p.source_author', 'p.source_copyright', 'p.source_license_code', 'p.source_license_url', 'p.source_provider', 'p.source_url',
                'pp.place_id', 'pp.place_review_id',
                DB::raw('(SELECT COUNT(*) FROM photo_helpful_votes phv WHERE phv.photo_id = p.id) as helpful_count'),
            ])
            ->groupBy('place_id');

        $settings = DB::table('place_photo_settings')
            ->whereIn('place_id', $placeIds)
            ->get()
            ->keyBy('place_id');

        $result = collect();

        foreach ($placeIds as $placeId) {
            $photos = $candidates->get($placeId, collect());
            if ($photos->isEmpty()) {
                continue;
            }

            $setting = $settings->get($placeId);
            $admin = $setting?->admin_photo_id
                ? $photos->firstWhere('id', (int) $setting->admin_photo_id)
                : null;

            if ($admin) {
                $admin->selection_source = 'admin';
                $result->put((int) $placeId, $admin);
                continue;
            }

            $highestVotes = (int) $photos->max('helpful_count');
            if ($highestVotes > 0) {
                $leaders = $photos->filter(fn ($photo) => (int) $photo->helpful_count === $highestVotes);
                $selected = $setting?->vote_photo_id
                    ? $leaders->firstWhere('id', (int) $setting->vote_photo_id)
                    : null;
                $selected ??= $setting?->fallback_photo_id
                    ? $leaders->firstWhere('id', (int) $setting->fallback_photo_id)
                    : null;
                $selected ??= $leaders->sortBy('id')->first();
                $this->storeVoteSelection((int) $placeId, (int) $selected->id);
                $selected->selection_source = 'votes';
                $result->put((int) $placeId, $selected);
                continue;
            }

            $fallback = $setting?->fallback_photo_id
                ? $photos->firstWhere('id', (int) $setting->fallback_photo_id)
                : null;

            if (!$fallback) {
                $fallback = $photos->random();
                $this->storeFallback((int) $placeId, (int) $fallback->id);
            }

            $fallback->selection_source = 'fallback';
            $this->storeVoteSelection((int) $placeId, null);
            $result->put((int) $placeId, $fallback);
        }

        return $result;
    }

    public function toggleHelpful(User $user, int $photoId, bool $helpful): bool
    {
        $photo = $this->publicPhotoQuery()
            ->where('p.id', $photoId)
            ->whereNull('p.external_source_id')
            ->first(['p.id', 'p.user_id']);

        if (!$photo || (int) $photo->user_id === (int) $user->id) {
            return false;
        }

        if ($helpful) {
            $created = DB::table('photo_helpful_votes')->insertOrIgnore([
                'photo_id' => $photoId,
                'user_id' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]) > 0;

            if ($created) {
                $place = DB::table('place_photos as pp')
                    ->join('places as pl', 'pl.id', '=', 'pp.place_id')
                    ->where('pp.photo_id', $photoId)
                    ->first(['pp.place_id', 'pl.name', 'pl.slug']);

                if ($place && $photo->user_id) {
                    app(XpService::class)->awardPhotoHelpful(
                        (int) $photo->user_id,
                        (int) $user->id,
                        $photoId,
                        (int) $place->place_id,
                        (string) $place->name,
                    );
                    $this->notifyHelpfulMilestone(
                        (int) $photo->user_id,
                        $photoId,
                        (int) $place->place_id,
                        (string) $place->name,
                        (string) $place->slug,
                    );
                }
            }

            return $created;
        }

        return DB::table('photo_helpful_votes')
            ->where('photo_id', $photoId)
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    public function report(User $user, int $photoId, string $reason, ?string $comment): bool
    {
        $photo = $this->publicPhotoQuery()
            ->where('p.id', $photoId)
            ->whereNull('p.external_source_id')
            ->first(['p.id', 'p.user_id']);

        if (!$photo || (int) $photo->user_id === (int) $user->id) {
            return false;
        }

        return DB::table('photo_reports')->insertOrIgnore([
            'photo_id' => $photoId,
            'reported_by' => $user->id,
            'reason' => $reason,
            'comment' => $comment ? trim($comment) : null,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]) > 0;
    }

    public function deleteOwn(User $user, int $photoId): bool
    {
        return DB::transaction(function () use ($user, $photoId): bool {
            $photo = DB::table('photos')->where('id', $photoId)->where('user_id', $user->id)->lockForUpdate()->first();
            if (!$photo || !$photo->is_active) {
                return false;
            }

            $this->deactivatePhoto($photo, $user, 'author_removed', __('photos.moderation.author_removed'));
            return true;
        });
    }

    public function pendingForModeration()
    {
        return DB::table('photos as p')
            ->join('place_photos as pp', 'pp.photo_id', '=', 'p.id')
            ->join('places as pl', 'pl.id', '=', 'pp.place_id')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.status', 'pending')
            ->where('p.is_active', true)
            ->where('pp.is_active', true)
            ->orderBy('p.created_at')
            ->paginate(30, [
                'p.id', 'p.uuid', 'p.width', 'p.height', 'p.created_at',
                'pp.place_id', 'pp.place_review_id', 'pl.name as place_name', 'pl.slug as place_slug',
                'u.name as user_name',
            ]);
    }

    public function reportsForModeration()
    {
        return DB::table('photo_reports as pr')
            ->join('photos as p', 'p.id', '=', 'pr.photo_id')
            ->join('place_photos as pp', 'pp.photo_id', '=', 'p.id')
            ->join('places as pl', 'pl.id', '=', 'pp.place_id')
            ->join('users as reporter', 'reporter.id', '=', 'pr.reported_by')
            ->where('pr.status', 'pending')
            ->orderBy('pr.created_at')
            ->get([
                'pr.id as report_id', 'pr.reason', 'pr.comment', 'pr.created_at',
                'p.id', 'p.uuid', 'pl.name as place_name', 'pl.slug as place_slug',
                'reporter.name as reporter_name',
            ]);
    }

    public function approve(User $moderator, int $photoId, bool $notifyAuthor = true): void
    {
        DB::transaction(function () use ($moderator, $photoId, $notifyAuthor): void {
            $photo = DB::table('photos')->where('id', $photoId)->lockForUpdate()->first();
            if (!$photo || !$photo->is_active || $photo->status !== 'pending') {
                throw new RuntimeException(__('photos.errors.not_pending'));
            }

            DB::table('photos')->where('id', $photoId)->update([
                'status' => 'approved',
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
                'moderation_reason' => null,
                'updated_at' => now(),
            ]);

            $placePhoto = DB::table('place_photos')->where('photo_id', $photoId)->first(['place_id']);
            if ($placePhoto) {
                $this->ensureFallback((int) $placePhoto->place_id);
                $placeName = (string) DB::table('places')->where('id', $placePhoto->place_id)->value('name');
                app(XpService::class)->awardPhoto(
                    (int) $photo->user_id,
                    (int) $placePhoto->place_id,
                    $photoId,
                    $placeName,
                );
                app(BadgeService::class)->recordPhotoAccepted(
                    (int) $photo->user_id,
                    (int) $placePhoto->place_id,
                    $photoId,
                );
            }

            $this->audit($moderator, $photoId, 'photo_approved', null, ['status' => 'approved']);
            if ($notifyAuthor) {
                $locale = $this->userLocale((int) $photo->user_id);
                $this->notifyAuthor(
                    $photo,
                    $moderator,
                    __('photos.notifications.approved_title', [], $locale),
                    __('photos.notifications.approved_message', [], $locale),
                    'approved',
                );
            }
        });
    }

    public function reject(User $moderator, int $photoId, string $reason): void
    {
        DB::transaction(function () use ($moderator, $photoId, $reason): void {
            $photo = DB::table('photos')->where('id', $photoId)->lockForUpdate()->first();
            if (!$photo || !$photo->is_active || !in_array($photo->status, ['pending', 'processing_failed'], true)) {
                throw new RuntimeException(__('photos.errors.cannot_reject'));
            }

            app(PhotoDeletionService::class)->deactivate(
                $photoId,
                'rejected',
                (int) $moderator->id,
                $reason,
            );

            $this->audit($moderator, $photoId, 'photo_rejected', ['status' => $photo->status], ['status' => 'rejected', 'reason' => $reason]);
            $locale = $this->userLocale((int) $photo->user_id);
            $this->notifyAuthor(
                $photo,
                $moderator,
                __('photos.notifications.rejected_title', [], $locale),
                __('photos.notifications.rejected_message', ['reason' => $reason], $locale),
                'rejected',
            );
            app(PhotoMergeConflictService::class)->resolveEligibleForPhoto($photoId);
        });
    }

    public function remove(User $moderator, int $photoId, string $reason): void
    {
        DB::transaction(function () use ($moderator, $photoId, $reason): void {
            $photo = DB::table('photos')->where('id', $photoId)->lockForUpdate()->first();
            if (!$photo || !$photo->is_active) {
                throw new RuntimeException(__('photos.errors.already_removed'));
            }

            $this->deactivatePhoto($photo, $moderator, 'photo_removed', $reason);
            if ($photo->user_id) {
                $locale = $this->userLocale((int) $photo->user_id);
                $this->notifyAuthor(
                    $photo,
                    $moderator,
                    __('photos.notifications.removed_title', [], $locale),
                    __('photos.notifications.removed_message', ['reason' => $reason], $locale),
                    'removed',
                );
            }
        });
    }

    public function dismissReport(User $moderator, int $reportId, ?string $comment): void
    {
        $updated = DB::table('photo_reports')
            ->where('id', $reportId)
            ->where('status', 'pending')
            ->update([
                'status' => 'dismissed',
                'moderated_by' => $moderator->id,
                'moderator_comment' => $comment,
                'moderated_at' => now(),
                'updated_at' => now(),
            ]);

        if (!$updated) {
            throw new RuntimeException(__('photos.errors.report_processed'));
        }
    }

    public function resolveReport(User $moderator, int $reportId, string $comment): void
    {
        $updated = DB::table('photo_reports')
            ->where('id', $reportId)
            ->where('status', 'pending')
            ->update([
                'status' => 'resolved',
                'moderated_by' => $moderator->id,
                'moderator_comment' => $comment,
                'moderated_at' => now(),
                'updated_at' => now(),
            ]);

        if (!$updated) {
            throw new RuntimeException(__('photos.errors.report_processed'));
        }
    }

    public function setAdminThumbnail(User $moderator, int $photoId): void
    {
        $photo = $this->publicPhotoQuery()
            ->where('p.id', $photoId)
            ->where('pp.is_thumbnail_eligible', true)
            ->first(['p.id', 'pp.place_id']);

        if (!$photo) {
            throw new RuntimeException(__('photos.errors.cover_unavailable'));
        }

        DB::table('place_photo_settings')->updateOrInsert(
            ['place_id' => $photo->place_id],
            [
                'admin_photo_id' => $photoId,
                'admin_selected_by' => $moderator->id,
                'admin_selected_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $this->audit($moderator, $photoId, 'photo_thumbnail_admin_selected', null, ['place_id' => $photo->place_id]);
    }

    public function clearAdminThumbnail(User $moderator, int $placeId): void
    {
        DB::table('place_photo_settings')->where('place_id', $placeId)->update([
            'admin_photo_id' => null,
            'admin_selected_by' => null,
            'admin_selected_at' => null,
            'updated_at' => now(),
        ]);

        $this->audit($moderator, $placeId, 'photo_thumbnail_admin_cleared', null, ['place_id' => $placeId]);
    }

    public function setThumbnailEligibility(User $moderator, int $photoId, bool $eligible, ?string $reason): void
    {
        $placePhoto = DB::table('place_photos')->where('photo_id', $photoId)->first();
        if (!$placePhoto) {
            throw new RuntimeException(__('photos.errors.place_missing'));
        }

        DB::transaction(function () use ($moderator, $photoId, $eligible, $reason, $placePhoto): void {
            DB::table('place_photos')->where('photo_id', $photoId)->update([
                'is_thumbnail_eligible' => $eligible,
                'thumbnail_excluded_by' => $eligible ? null : $moderator->id,
                'thumbnail_excluded_at' => $eligible ? null : now(),
                'thumbnail_exclusion_reason' => $eligible ? null : $reason,
                'updated_at' => now(),
            ]);

            if (!$eligible) {
                $this->clearPhotoSelections((int) $placePhoto->place_id, $photoId);
                $this->ensureFallback((int) $placePhoto->place_id);
            }

            $this->audit($moderator, $photoId, $eligible ? 'photo_thumbnail_enabled' : 'photo_thumbnail_excluded', null, ['reason' => $reason]);
        });
    }

    private function inspectUpload(UploadedFile $upload): array
    {
        if (!$upload->isValid()) {
            throw new RuntimeException(__('photos.errors.upload_incomplete'));
        }

        $maximumBytes = (int) config('photos.upload_max_kilobytes', 51200) * 1024;
        if ($upload->getSize() === false || $upload->getSize() > $maximumBytes) {
            throw new RuntimeException(__('photos.errors.too_large', ['maximum' => (int) ($maximumBytes / 1024 / 1024)]));
        }

        $mimeType = (string) $upload->getMimeType();
        if (!in_array($mimeType, config('photos.allowed_mime_types', []), true)) {
            throw new RuntimeException(__('photos.errors.unsupported_format'));
        }

        if (!class_exists(Imagick::class)) {
            throw new RuntimeException(__('photos.errors.processing_unavailable'));
        }

        try {
            $image = new Imagick();
            $image->pingImage($upload->getRealPath());
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            $frames = $image->getNumberImages();
            $image->clear();
            $image->destroy();
        } catch (Throwable) {
            throw new RuntimeException(__('photos.errors.unsafe_file'));
        }

        if ($frames !== 1) {
            throw new RuntimeException(__('photos.errors.animated_unsupported'));
        }

        $pixels = $width * $height;
        if ($pixels > (int) config('photos.max_pixels', 80_000_000)) {
            throw new RuntimeException(__('photos.errors.pixel_limit'));
        }

        if ($pixels < (int) config('photos.min_pixels', 900_000) || min($width, $height) < (int) config('photos.min_side', 600)) {
            throw new RuntimeException(__('photos.errors.too_small'));
        }

        return [
            'mime_type' => $mimeType,
            'file_size' => (int) $upload->getSize(),
            'width' => $width,
            'height' => $height,
        ];
    }

    private function publicPhotoQuery()
    {
        return DB::table('photos as p')
            ->join('place_photos as pp', 'pp.photo_id', '=', 'p.id')
            ->leftJoin('place_reviews as pr_photo', 'pr_photo.id', '=', 'pp.place_review_id')
            ->whereNotNull('p.uuid')
            ->where('p.status', 'approved')
            ->where('p.is_active', true)
            ->where('pp.is_active', true)
            ->where(function ($query): void {
                $query->whereNull('pp.place_review_id')
                    ->orWhere('pr_photo.status', 'active');
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('photo_merge_conflict_items as pmci')
                    ->join('photo_merge_conflicts as pmc', 'pmc.id', '=', 'pmci.photo_merge_conflict_id')
                    ->whereColumn('pmci.photo_id', 'p.id')
                    ->where('pmc.status', 'selection_required');
            });
    }

    private function photoSelect(?int $viewerId): array
    {
        $viewerId = (int) ($viewerId ?? 0);

        return [
            'p.id', 'p.uuid', 'p.user_id', 'p.external_source_id', 'p.width', 'p.height',
            'p.source_author', 'p.source_copyright', 'p.source_license_code', 'p.source_license_url', 'p.source_provider', 'p.source_url',
            'pp.place_id', 'pp.place_review_id', 'pp.sort_order', 'pp.is_thumbnail_eligible',
            DB::raw('(SELECT COUNT(*) FROM photo_helpful_votes phv WHERE phv.photo_id = p.id) as helpful_count'),
            DB::raw('(SELECT COUNT(*) FROM photo_helpful_votes own_vote WHERE own_vote.photo_id = p.id AND own_vote.user_id = '.$viewerId.') as viewer_voted'),
        ];
    }

    private function ensureFallback(int $placeId): void
    {
        $current = DB::table('place_photo_settings')->where('place_id', $placeId)->value('fallback_photo_id');
        if ($current && $this->eligiblePhotoExists($placeId, (int) $current)) {
            return;
        }

        $candidates = $this->publicPhotoQuery()
            ->where('pp.place_id', $placeId)
            ->where('pp.is_thumbnail_eligible', true)
            ->pluck('p.id');

        $this->storeFallback($placeId, $candidates->isEmpty() ? null : (int) $candidates->random());
    }

    private function storeFallback(int $placeId, ?int $photoId): void
    {
        $existing = DB::table('place_photo_settings')->where('place_id', $placeId)->first();
        DB::table('place_photo_settings')->updateOrInsert(
            ['place_id' => $placeId],
            [
                'fallback_photo_id' => $photoId,
                'vote_photo_id' => $existing?->vote_photo_id,
                'admin_photo_id' => $existing?->admin_photo_id,
                'admin_selected_by' => $existing?->admin_selected_by,
                'admin_selected_at' => $existing?->admin_selected_at,
                'updated_at' => now(),
                'created_at' => $existing?->created_at ?? now(),
            ],
        );
    }

    private function storeVoteSelection(int $placeId, ?int $photoId): void
    {
        $existing = DB::table('place_photo_settings')->where('place_id', $placeId)->first();
        DB::table('place_photo_settings')->updateOrInsert(
            ['place_id' => $placeId],
            [
                'fallback_photo_id' => $existing?->fallback_photo_id,
                'vote_photo_id' => $photoId,
                'admin_photo_id' => $existing?->admin_photo_id,
                'admin_selected_by' => $existing?->admin_selected_by,
                'admin_selected_at' => $existing?->admin_selected_at,
                'updated_at' => now(),
                'created_at' => $existing?->created_at ?? now(),
            ],
        );
    }

    private function eligiblePhotoExists(int $placeId, int $photoId): bool
    {
        return $this->publicPhotoQuery()
            ->where('pp.place_id', $placeId)
            ->where('p.id', $photoId)
            ->where('pp.is_thumbnail_eligible', true)
            ->exists();
    }

    private function deactivatePhoto(object $photo, User $actor, string $action, string $reason): void
    {
        $placePhoto = DB::table('place_photos')->where('photo_id', $photo->id)->first(['place_id']);

        app(PhotoDeletionService::class)->deactivate(
            (int) $photo->id,
            'removed',
            (int) $actor->id,
            $reason,
        );

        if ($placePhoto) {
            $this->clearPhotoSelections((int) $placePhoto->place_id, (int) $photo->id);
            $this->ensureFallback((int) $placePhoto->place_id);
        }

        $this->audit($actor, (int) $photo->id, $action, ['status' => $photo->status], ['status' => 'removed', 'reason' => $reason]);
        app(PhotoMergeConflictService::class)->resolveEligibleForPhoto((int) $photo->id);
    }

    private function clearPhotoSelections(int $placeId, int $photoId): void
    {
        $settings = DB::table('place_photo_settings')->where('place_id', $placeId)->first();
        if (!$settings) {
            return;
        }

        $values = ['updated_at' => now()];

        if ((int) ($settings->fallback_photo_id ?? 0) === $photoId) {
            $values['fallback_photo_id'] = null;
        }

        if ((int) ($settings->vote_photo_id ?? 0) === $photoId) {
            $values['vote_photo_id'] = null;
        }

        if ((int) ($settings->admin_photo_id ?? 0) === $photoId) {
            $values['admin_photo_id'] = null;
            $values['admin_selected_by'] = null;
            $values['admin_selected_at'] = null;
        }

        if (count($values) > 1) {
            DB::table('place_photo_settings')->where('place_id', $placeId)->update($values);
        }
    }

    private function notifyHelpfulMilestone(
        int $authorId,
        int $photoId,
        int $placeId,
        string $placeName,
        string $placeSlug,
    ): void {
        $count = (int) DB::table('photo_helpful_votes')->where('photo_id', $photoId)->count();
        if (! in_array($count, [1, 5, 10, 25, 50, 100], true)) {
            return;
        }

        app(UserNotificationService::class)->upsertPlacePhotoActivity(
            $authorId,
            'photo_helpful_milestone',
            $placeId,
            $placeName,
            route('places.show', $placeSlug).'#photos',
            $photoId,
            'notifications.photo_helpful_grouped_title',
            'notifications.photo_helpful_grouped_message',
            'thumbs-up',
        );
    }

    private function notifyAuthor(object $photo, User $moderator, string $title, string $message, string $decision): void
    {
        if (!$photo->user_id) {
            return;
        }

        $place = DB::table('place_photos as pp')
            ->join('places as pl', 'pl.id', '=', 'pp.place_id')
            ->where('pp.photo_id', $photo->id)
            ->first(['pp.place_id', 'pl.name', 'pl.slug']);

        $notifications = app(UserNotificationService::class);

        if ($decision === 'approved' && $place) {
            $notifications->upsertPlacePhotoActivity(
                (int) $photo->user_id,
                'photo_moderation',
                (int) $place->place_id,
                (string) $place->name,
                route('places.show', $place->slug).'#photos',
                (int) $photo->id,
                'notifications.photo_approved_grouped_title',
                'notifications.photo_approved_grouped_message',
                'photo',
                (int) $moderator->id,
            );

            return;
        }

        $notifications->createImmediate(
            (int) $photo->user_id,
            'photo_moderation',
            $title,
            $message,
            $place ? route('places.show', $place->slug).'#photos' : route('notifications.index'),
            'normal',
            'photo',
            [
                'photo_id' => (int) $photo->id,
                'decision' => $decision,
                'place_id' => $place?->place_id,
                'place_name' => $place?->name,
            ],
            (int) $moderator->id,
        );
    }

    private function userLocale(int $userId): string
    {
        return (string) (DB::table('users')->where('id', $userId)->value('locale') ?: config('app.fallback_locale'));
    }

    private function audit(User $actor, int $entityId, string $action, ?array $oldValues, ?array $newValues): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => $actor->id,
            'entity_type' => 'photo',
            'entity_id' => $entityId,
            'action' => $action,
            'source' => $actor->hasPermission('photos.moderate') ? 'admin' : 'user',
            'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'internal_comment' => null,
            'created_at' => now(),
        ]);
    }
}
