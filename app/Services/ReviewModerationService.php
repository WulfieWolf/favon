<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReviewModerationService
{
    public function remove(User $moderator, int $reportId, ?string $comment = null): void
    {
        DB::transaction(function () use ($moderator, $reportId, $comment): void {
            $report = DB::table('place_review_reports')
                ->where('id', $reportId)
                ->lockForUpdate()
                ->first();

            if (! $report || $report->status !== 'pending') {
                throw new RuntimeException(__('admin.review_reports.errors.already_processed'));
            }

            $review = DB::table('place_reviews')
                ->where('id', $report->review_id)
                ->lockForUpdate()
                ->first();

            if (! $review) {
                throw new RuntimeException(__('admin.review_reports.errors.review_missing'));
            }

            $version = DB::table('place_review_versions')
                ->where('id', $report->review_version_id)
                ->where('review_id', $review->id)
                ->first(['id', 'valid_until']);

            if (! $version) {
                throw new RuntimeException(__('admin.review_reports.errors.version_missing'));
            }

            $now = now();

            DB::table('place_review_versions')
                ->where('id', $version->id)
                ->update([
                    'valid_until' => Carbon::parse($version->valid_until)->gt($now) ? $now : $version->valid_until,
                    'is_public' => false,
                    'updated_at' => $now,
                ]);

            $isCurrentVersion = (int) $review->current_version_id === (int) $version->id;

            if ($isCurrentVersion) {
                DB::table('place_reviews')
                    ->where('id', $review->id)
                    ->update([
                        'status' => 'moderated',
                        'current_expires_at' => $now,
                        'updated_at' => $now,
                    ]);
            }

            $pendingReports = DB::table('place_review_reports')
                ->where('review_version_id', $version->id)
                ->where('status', 'pending')
                ->get(['id', 'reported_by']);

            DB::table('place_review_reports')
                ->whereIn('id', $pendingReports->pluck('id'))
                ->update([
                    'status' => 'removed',
                    'moderated_by' => $moderator->id,
                    'moderator_comment' => $comment,
                    'moderated_at' => $now,
                    'updated_at' => $now,
                ]);

            $place = DB::table('places')->where('id', $review->place_id)->first(['name', 'slug']);
            $notifications = app(UserNotificationService::class);

            foreach ($pendingReports->pluck('reported_by')->unique() as $reporterId) {
                $locale = $notifications->userLocale((int) $reporterId);
                $notifications->createImmediate(
                    (int) $reporterId,
                    'review_report_result',
                    __('notifications.review_report_removed_title', [], $locale),
                    __('notifications.review_report_removed_message', ['place' => $place->name], $locale),
                    route('places.show', $place->slug).'#reviews',
                    'normal',
                    'bell',
                    ['review_id' => $review->id, 'review_version_id' => $version->id, 'decision' => 'removed'],
                    (int) $moderator->id,
                );
            }

            $authorLocale = $notifications->userLocale((int) $review->user_id);
            $notifications->createImmediate(
                (int) $review->user_id,
                'review_moderated',
                __('notifications.review_removed_title', [], $authorLocale),
                __('notifications.review_removed_message', ['place' => $place->name], $authorLocale),
                route('places.show', $place->slug).'#reviews',
                'normal',
                'bell',
                ['review_id' => $review->id, 'review_version_id' => $version->id, 'decision' => 'removed'],
                (int) $moderator->id,
            );
        });
    }

    public function dismiss(User $moderator, int $reportId, ?string $comment = null): void
    {
        DB::transaction(function () use ($moderator, $reportId, $comment): void {
            $report = DB::table('place_review_reports')
                ->where('id', $reportId)
                ->lockForUpdate()
                ->first();

            if (! $report || $report->status !== 'pending') {
                throw new RuntimeException(__('admin.review_reports.errors.already_processed'));
            }

            DB::table('place_review_reports')
                ->where('id', $report->id)
                ->update([
                    'status' => 'dismissed',
                    'moderated_by' => $moderator->id,
                    'moderator_comment' => $comment,
                    'moderated_at' => now(),
                    'updated_at' => now(),
                ]);

            $review = DB::table('place_reviews as pr')
                ->join('places as p', 'p.id', '=', 'pr.place_id')
                ->where('pr.id', $report->review_id)
                ->first(['pr.id', 'p.name as place_name', 'p.slug as place_slug']);

            if ($review) {
                $notifications = app(UserNotificationService::class);
                $locale = $notifications->userLocale((int) $report->reported_by);
                $notifications->createImmediate(
                    (int) $report->reported_by,
                    'review_report_result',
                    __('notifications.review_report_dismissed_title', [], $locale),
                    __('notifications.review_report_dismissed_message', ['place' => $review->place_name], $locale),
                    route('places.show', $review->place_slug).'#reviews',
                    'normal',
                    'bell',
                    ['review_id' => $review->id, 'decision' => 'dismissed'],
                    (int) $moderator->id,
                );
            }
        });
    }
}
