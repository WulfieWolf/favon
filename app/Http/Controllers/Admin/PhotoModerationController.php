<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use App\Services\PlacePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PhotoModerationController extends Controller
{
    private const MODERATION_REASONS = [
        'nudity_sexual',
        'illegal_harmful',
        'privacy',
        'copyright',
        'unrelated',
        'misleading',
        'advertising_spam',
        'other',
    ];
    public function index(PlacePhotoService $photos): View
    {
        return view('admin.photos.index', [
            'pendingPhotos' => $photos->pendingForModeration(),
            'photoReports' => $photos->reportsForModeration(),
        ]);
    }

    public function library(Request $request, PlacePhotoService $photos, PermissionService $permissions): View
    {
        $status = $request->string('status')->toString();
        $activity = $request->string('activity')->toString();
        $sort = $request->string('sort')->toString();
        $direction = $request->string('direction')->toString();

        $allowedStatuses = ['processing', 'pending', 'approved', 'rejected', 'removed', 'processing_failed'];
        $allowedSorts = ['photo', 'place', 'review', 'author', 'status', 'dimensions', 'format', 'size', 'helpful', 'uploaded'];

        $filters = [
            'search' => trim($request->string('search')->toString()),
            'status' => in_array($status, $allowedStatuses, true) ? $status : '',
            'activity' => in_array($activity, ['active', 'inactive'], true) ? $activity : 'all',
        ];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'uploaded';
        $direction = $direction === 'asc' ? 'asc' : 'desc';
        $user = $request->user();

        return view('admin.photos.library', [
            'photos' => $photos->administrativeLibrary($filters, $sort, $direction),
            'filters' => $filters,
            'sort' => $sort,
            'direction' => $direction,
            'canModerate' => $permissions->can($user, 'photos.moderate'),
            'canDelete' => $permissions->can($user, 'photos.delete_any'),
            'canSetCover' => $permissions->can($user, 'photos.set_cover'),
        ]);
    }

    public function approve(Request $request, int $photo, PlacePhotoService $photos): RedirectResponse
    {
        return $this->run(fn () => $photos->approve($request->user(), $photo), __('admin.photos.status_approved'));
    }

    public function reject(Request $request, int $photo, PlacePhotoService $photos): RedirectResponse
    {
        $data = $request->validate([
            'reason_code' => ['required', Rule::in(self::MODERATION_REASONS)],
            'reason_details' => ['nullable', 'string', 'max:500'],
        ]);
        $reason = $this->moderationReason($photo, $data);

        return $this->run(fn () => $photos->reject($request->user(), $photo, $reason), __('admin.photos.status_rejected'));
    }

    public function remove(Request $request, int $photo, PlacePhotoService $photos): RedirectResponse
    {
        $data = $request->validate([
            'reason_code' => ['required', Rule::in(self::MODERATION_REASONS)],
            'reason_details' => ['nullable', 'string', 'max:500'],
        ]);
        $reason = $this->moderationReason($photo, $data);
        $reportId = $request->integer('report_id');

        return $this->run(function () use ($photos, $request, $photo, $reason, $reportId): void {
            $photos->remove($request->user(), $photo, $reason);
            if ($reportId) {
                $photos->resolveReport($request->user(), $reportId, __('admin.photos.report_resolution_removed', ['reason' => $reason]));
            }
        }, __('admin.photos.status_removed'));
    }

    public function dismissReport(Request $request, int $report, PlacePhotoService $photos): RedirectResponse
    {
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);

        return $this->run(fn () => $photos->dismissReport($request->user(), $report, $data['comment'] ?? null), __('admin.photos.status_report_dismissed'));
    }

    public function setCover(Request $request, int $photo, PlacePhotoService $photos): RedirectResponse
    {
        return $this->run(fn () => $photos->setAdminThumbnail($request->user(), $photo), __('admin.photos.status_cover_set'));
    }

    public function clearCover(Request $request, int $place, PlacePhotoService $photos): RedirectResponse
    {
        return $this->run(fn () => $photos->clearAdminThumbnail($request->user(), $place), __('admin.photos.status_cover_cleared'));
    }

    public function excludeThumbnail(Request $request, int $photo, PlacePhotoService $photos): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        return $this->run(fn () => $photos->setThumbnailEligibility($request->user(), $photo, false, $data['reason']), __('admin.photos.status_thumbnail_excluded'));
    }

    public function includeThumbnail(Request $request, int $photo, PlacePhotoService $photos): RedirectResponse
    {
        return $this->run(fn () => $photos->setThumbnailEligibility($request->user(), $photo, true, null), __('admin.photos.status_thumbnail_included'));
    }

    private function moderationReason(int $photoId, array $data): string
    {
        $locale = DB::table('photos as p')
            ->join('users as u', 'u.id', '=', 'p.user_id')
            ->where('p.id', $photoId)
            ->value('u.locale') ?: config('app.fallback_locale');

        $reason = __('admin.photos.moderation_reasons.'.$data['reason_code'], [], $locale);
        $details = trim((string) ($data['reason_details'] ?? ''));

        return $details !== '' ? $reason.' — '.$details : $reason;
    }

    private function run(callable $callback, string $message): RedirectResponse
    {
        try {
            $callback();
        } catch (RuntimeException $exception) {
            return back()->withErrors(['photo' => $exception->getMessage()]);
        }

        return back()->with('ui_toast', $message);
    }
}
