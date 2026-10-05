<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReviewModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ReviewReportController extends Controller
{
    private const MODERATION_REASONS = [
        'illegal_inappropriate',
        'abuse_threat_discrimination',
        'personal_data',
        'false_invented',
        'serious_accusation',
        'personal_dispute',
        'unrelated',
        'advertising_spam',
        'manipulated_conflict',
        'copyright',
        'other',
    ];
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        if (! in_array($status, ['pending', 'removed', 'dismissed'], true)) {
            $status = 'pending';
        }

        $reports = DB::table('place_review_reports as rr')
            ->join('place_reviews as pr', 'pr.id', '=', 'rr.review_id')
            ->join('places as p', 'p.id', '=', 'pr.place_id')
            ->join('users as reporter', 'reporter.id', '=', 'rr.reported_by')
            ->join('users as author', 'author.id', '=', 'pr.user_id')
            ->leftJoin('place_review_versions as prv', 'prv.id', '=', 'rr.review_version_id')
            ->leftJoin('users as moderator', 'moderator.id', '=', 'rr.moderated_by')
            ->where('rr.status', $status)
            ->orderByDesc('rr.created_at')
            ->select([
                'rr.id',
                'rr.review_id',
                'rr.review_version_id',
                'rr.reason',
                'rr.comment',
                'rr.status',
                'rr.moderator_comment',
                'rr.created_at',
                'rr.moderated_at',
                'p.name as place_name',
                'p.slug as place_slug',
                'reporter.name as reporter_name',
                'author.name as author_name',
                'moderator.name as moderator_name',
                'prv.review_text',
                'prv.overall_score',
            ])
            ->paginate(30)
            ->withQueryString();

        return view('admin.review-reports.index', [
            'reports' => $reports,
            'status' => $status,
        ]);
    }

    public function remove(
        Request $request,
        int $report,
        ReviewModerationService $moderation,
    ): RedirectResponse {
        $data = $request->validate([
            'reason_code' => ['required', Rule::in(self::MODERATION_REASONS)],
            'reason_details' => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = __('admin.review_reports.moderation_reasons.'.$data['reason_code']);
        $details = trim((string) ($data['reason_details'] ?? ''));
        $comment = $details !== '' ? $reason.' — '.$details : $reason;

        try {
            $moderation->remove($request->user(), $report, $comment);
        } catch (RuntimeException $e) {
            return back()->withErrors(['moderation' => $e->getMessage()]);
        }

        return back()->with('ui_toast', __('admin.review_reports.status_removed'));
    }

    public function dismiss(
        Request $request,
        int $report,
        ReviewModerationService $moderation,
    ): RedirectResponse {
        $data = $request->validate([
            'moderator_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $moderation->dismiss($request->user(), $report, $data['moderator_comment'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['moderation' => $e->getMessage()]);
        }

        return back()->with('ui_toast', __('admin.review_reports.status_dismissed'));
    }
}
