<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AbuseProtectionService;
use App\Services\ChangeRequestModerationService;
use App\Services\PlaceTombstoneService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ChangeRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        if (! in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $status = 'pending';
        }

        $queue = $request->string('queue')->toString() === 'quarantine' && $status === 'pending'
            ? 'quarantine'
            : 'normal';

        $rows = DB::table('change_requests as cr')
            ->join('suggestable_fields as sf', 'sf.id', '=', 'cr.suggestable_field_id')
            ->join('places as p', 'p.id', '=', 'cr.place_id')
            ->leftJoin('users as submitter', 'submitter.id', '=', 'cr.submitted_by')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'cr.reviewed_by')
            ->where('cr.status', $status)
            ->when(
                $status === 'pending' && $queue === 'quarantine',
                fn ($query) => $query->whereExists(function ($flagQuery): void {
                    $flagQuery->selectRaw('1')
                        ->from('abuse_flags as af')
                        ->whereColumn('af.entity_id', 'cr.place_id')
                        ->where('af.entity_type', 'place')
                        ->where('af.status', 'open');
                }),
            )
            ->when(
                $status === 'pending' && $queue === 'normal',
                fn ($query) => $query->whereNotExists(function ($flagQuery): void {
                    $flagQuery->selectRaw('1')
                        ->from('abuse_flags as af')
                        ->whereColumn('af.entity_id', 'cr.place_id')
                        ->where('af.entity_type', 'place')
                        ->where('af.status', 'open');
                }),
            )
            ->orderByDesc('cr.submitted_at')
            ->orderByDesc('cr.id')
            ->get([
                'cr.id',
                'cr.group_uuid',
                'cr.place_id',
                'cr.target_record_id',
                'cr.operation',
                'cr.status',
                'cr.submitted_at',
                'cr.reviewed_at',
                'sf.target_table',
                'sf.target_field',
                'p.name as place_name',
                'p.place_type_id',
                'p.latitude',
                'p.longitude',
                'submitter.name as submitter_name',
                'submitter.email as submitter_email',
                'reviewer.name as reviewer_name',
            ]);

        $groups = $rows
            ->groupBy(fn ($row) => $row->place_id.'|'.($row->group_uuid ?: 'single-'.$row->id))
            ->map(function ($items) {
                $anchor = $items->sortBy('id')->first();

                return (object) [
                    'anchor_id' => (int) $anchor->id,
                    'place_id' => (int) $anchor->place_id,
                    'place_name' => $anchor->place_name,
                    'status' => $anchor->status,
                    'submitted_at' => $items->max('submitted_at'),
                    'reviewed_at' => $items->max('reviewed_at'),
                    'submitter_name' => $anchor->submitter_name,
                    'submitter_email' => $anchor->submitter_email,
                    'reviewer_name' => $anchor->reviewer_name,
                    'field_count' => $items->count(),
                    'place_type_id' => (int) $anchor->place_type_id,
                    'latitude' => (float) $anchor->latitude,
                    'longitude' => (float) $anchor->longitude,
                    'operations' => $items->pluck('operation')->unique()->values(),
                    'fields' => $items->map(fn ($row) => (object) [
                        'target_table' => $row->target_table,
                        'target_field' => $row->target_field,
                        'target_record_id' => $row->target_record_id,
                    ])->values(),
                    'is_new_place_submission' => $items->contains(
                        fn ($row) => $row->target_table === 'places' && $row->target_field === 'publication_status'
                    ),
                ];
            })
            ->sortByDesc('submitted_at')
            ->values();

        $places = $groups
            ->groupBy('place_id')
            ->sortByDesc(fn ($placeGroups) => $placeGroups->max('submitted_at'));

        $perPage = 20;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pagePlaces = $places->forPage($page, $perPage);
        $pageItems = $pagePlaces->flatten(1)->values();
        $tombstones = app(PlaceTombstoneService::class);
        $pageItems->each(function ($item) use ($tombstones): void {
            if (! $item->is_new_place_submission || $item->status !== 'pending') {
                $item->tombstone_warning = null;
                return;
            }

            $check = $tombstones->check(
                (float) $item->latitude,
                (float) $item->longitude,
                (int) $item->place_type_id,
            );
            $item->tombstone_warning = $check['status'] === PlaceTombstoneService::NONE ? null : $check;
        });

        $requests = new LengthAwarePaginator(
            $pageItems,
            $places->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return view('admin.change-requests.index', [
            'requests' => $requests,
            'status' => $status,
            'queue' => $queue,
        ]);
    }

    public function show(int $changeRequest): View
    {
        $anchor = $this->requestDetailsQuery()
            ->where('cr.id', $changeRequest)
            ->first();

        abort_unless($anchor, 404);

        $groupQuery = $this->requestDetailsQuery()
            ->where('cr.place_id', $anchor->place_id);

        if ($anchor->group_uuid) {
            $groupQuery->where('cr.group_uuid', $anchor->group_uuid);
        } else {
            $groupQuery->where('cr.id', $anchor->id);
        }

        $group = $groupQuery->orderBy('cr.id')->get();

        $isNewPlaceSubmission = $group->contains(
            fn ($item) => $item->target_table === 'places' && $item->target_field === 'publication_status'
        );
        $tombstoneWarning = null;

        if ($anchor->status === 'pending' && $isNewPlaceSubmission) {
            $check = app(PlaceTombstoneService::class)->check(
                (float) $anchor->latitude,
                (float) $anchor->longitude,
                (int) $anchor->place_type_id,
            );
            $tombstoneWarning = $check['status'] === PlaceTombstoneService::NONE ? null : $check;
        }

        $abuseFlags = DB::table('abuse_flags')
            ->where('entity_type', 'place')
            ->where('entity_id', $anchor->place_id)
            ->where('status', 'open')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.change-requests.show', [
            'anchor' => $anchor,
            'group' => $group,
            'abuseFlags' => $abuseFlags,
            'tombstoneWarning' => $tombstoneWarning,
            'allPending' => $group->every(fn ($item) => $item->status === 'pending'),
        ]);
    }

    public function approve(
        Request $request,
        int $changeRequest,
        ChangeRequestModerationService $moderation,
        AbuseProtectionService $abuse,
    ): RedirectResponse
    {
        $data = $request->validate([
            'moderator_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $moderation->approve(
                $request->user(),
                $changeRequest,
                $data['moderator_comment'] ?? null,
            );
        } catch (RuntimeException $e) {
            report($e);

            return back()->withErrors(['moderation' => __('admin.change_requests.processing_failed')]);
        }

        $this->resolvePlaceSubmissionFlag($request, $changeRequest, $abuse);

        return redirect()
            ->route('admin.change-requests.show', $changeRequest)
            ->with('ui_toast', __('admin.change_requests.status_approved'));
    }

    public function reject(
        Request $request,
        int $changeRequest,
        ChangeRequestModerationService $moderation,
        AbuseProtectionService $abuse,
    ): RedirectResponse
    {
        $data = $request->validate([
            'moderator_comment' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $moderation->reject(
                $request->user(),
                $changeRequest,
                $data['moderator_comment'],
            );
        } catch (RuntimeException $e) {
            report($e);

            return back()->withErrors(['moderation' => __('admin.change_requests.processing_failed')]);
        }

        $this->resolvePlaceSubmissionFlag($request, $changeRequest, $abuse);

        return redirect()
            ->route('admin.change-requests.show', $changeRequest)
            ->with('ui_toast', __('admin.change_requests.status_rejected'));
    }

    private function resolvePlaceSubmissionFlag(
        Request $request,
        int $changeRequest,
        AbuseProtectionService $abuse,
    ): void {
        $row = DB::table('change_requests as cr')
            ->join('suggestable_fields as sf', 'sf.id', '=', 'cr.suggestable_field_id')
            ->where('cr.id', $changeRequest)
            ->first(['cr.place_id', 'sf.target_table', 'sf.target_field']);

        if ($row && $row->target_table === 'places' && $row->target_field === 'publication_status') {
            $abuse->resolveForEntity($request->user(), 'place', (int) $row->place_id);
        }
    }

    private function requestDetailsQuery()
    {
        return DB::table('change_requests as cr')
            ->join('suggestable_fields as sf', 'sf.id', '=', 'cr.suggestable_field_id')
            ->join('places as p', 'p.id', '=', 'cr.place_id')
            ->leftJoin('users as submitter', 'submitter.id', '=', 'cr.submitted_by')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'cr.reviewed_by')
            ->select([
                'cr.*',
                'sf.target_table',
                'sf.target_field',
                'sf.is_suggestable',
                'sf.is_active as suggestable_field_active',
                'p.name as place_name',
                'p.place_type_id',
                'p.latitude',
                'p.longitude',
                'submitter.name as submitter_name',
                'submitter.email as submitter_email',
                'reviewer.name as reviewer_name',
                'reviewer.email as reviewer_email',
            ]);
    }
}
