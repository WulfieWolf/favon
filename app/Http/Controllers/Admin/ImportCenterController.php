<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Imports\Datex2ParkingStageService;
use App\Services\Imports\ExternalRecordClassificationService;
use App\Services\Imports\ExternalRecordReviewService;
use App\Services\Imports\ExternalPlacePhotoService;
use App\Services\Imports\ExternalRecordStagingService;
use App\Services\Imports\ManualCsvPlaceParser;
use App\Services\Imports\ManualAtkisDuplicateLinkService;
use App\Services\Imports\ResearchCsvImportService;
use App\Services\Imports\ResearchReviewApplyService;
use App\Services\PlaceMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class ImportCenterController extends Controller
{
    public function index(Request $request, ManualAtkisDuplicateLinkService $manualAtkisDuplicateLink): View
    {
        $reviewStatus = $request->string('review_status')->toString();
        if (! in_array($reviewStatus, ['pending', 'superseded', 'resolved', 'all'], true)) {
            $reviewStatus = 'pending';
        }

        $runs = DB::table('external_import_runs as eir')
            ->join('external_sources as es', 'es.id', '=', 'eir.external_source_id')
            ->select([
                'eir.id',
                'eir.mode',
                'eir.status',
                'eir.source_record_count',
                'eir.mapped_record_count',
                'eir.created_count',
                'eir.updated_count',
                'eir.skipped_count',
                'eir.conflict_count',
                'eir.review_count',
                'eir.error_code',
                'eir.error_message',
                'eir.stats',
                'eir.started_at',
                'eir.completed_at',
                'eir.created_at',
                'es.name as source_name',
                'es.slug as source_slug',
            ])
            ->orderByDesc('eir.id')
            ->paginate(15, ['*'], 'runs_page')
            ->withQueryString();

        $reviews = DB::table('external_import_review_items as ri')
            ->join('external_sources as es', 'es.id', '=', 'ri.external_source_id')
            ->leftJoin('external_records as er', 'er.id', '=', 'ri.external_record_id')
            ->leftJoin('places as p', 'p.id', '=', 'ri.place_id')
            ->select([
                'ri.id',
                'ri.type',
                'ri.severity',
                'ri.status',
                'ri.details',
                'ri.resolution_note',
                'ri.created_at',
                'ri.resolved_at',
                'es.name as source_name',
                'es.slug as source_slug',
                'er.external_id',
                'er.classification',
                'p.id as place_id',
                'p.name as place_name',
                'p.slug as place_slug',
            ])
            ->where('ri.type', '!=', 'external_duplicate_group')
            ->when($reviewStatus !== 'all', fn ($query) => $query->where('ri.status', $reviewStatus))
            ->orderByRaw("CASE ri.severity WHEN 'error' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
            ->orderByDesc('ri.id')
            ->paginate(25, ['*'], 'reviews_page')
            ->withQueryString();

        $reviews->getCollection()->transform(function ($review) {
            $details = json_decode((string) $review->details, true) ?: [];

            if (is_array($details['candidates'] ?? null)) {
                $details['candidates'] = $this->resolveReviewCandidates($details['candidates']);
            }

            $review->details_array = $details;

            return $review;
        });

        $candidateSearch = trim($request->string('candidate_search')->toString());
        $candidateSource = trim($request->string('candidate_source')->toString());
        $externalFeatureLabels = $this->externalFeatureLabels(app()->getLocale());
        $externalPhotoService = app(ExternalPlacePhotoService::class);
        $externalMedia = $externalPhotoService->pendingMedia($candidateSource, 60);
        $externalMediaPending = $externalPhotoService->pendingCount($candidateSource);

        $candidates = DB::table('external_records as er')
            ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
            ->where('er.status', 'active')
            ->where('er.classification', 'new_candidate')
            ->whereNull('er.place_id')
            ->when($candidateSource !== '', fn ($query) => $query->where('es.slug', $candidateSource))
            ->when($candidateSearch !== '', function ($query) use ($candidateSearch) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $candidateSearch).'%';

                $query->where(function ($nested) use ($term) {
                    $nested->where('er.external_id', 'like', $term)
                        ->orWhere('er.normalized_data', 'like', $term);
                });
            })
            ->select([
                'er.id',
                'er.external_id',
                'er.normalized_data',
                'er.source_updated_at',
                'er.first_seen_at',
                'es.name as source_name',
                'es.slug as source_slug',
            ])
            ->orderBy('es.name')
            ->orderBy('er.external_id')
            ->paginate(20, ['*'], 'candidates_page')
            ->withQueryString();

        $candidates->getCollection()->transform(function ($candidate) use ($externalFeatureLabels) {
            $data = json_decode((string) $candidate->normalized_data, true) ?: [];
            $place = is_array($data['place'] ?? null) ? $data['place'] : [];
            $address = is_array($place['address'] ?? null) ? $place['address'] : [];
            $operator = is_array($place['operator'] ?? null) ? $place['operator'] : [];
            $features = is_array($data['features'] ?? null) ? $data['features'] : [];

            $candidate->place_data = $place;
            $candidate->name = trim((string) ($place['name'] ?? '')) ?: $candidate->external_id;
            $candidate->latitude = $place['latitude'] ?? null;
            $candidate->longitude = $place['longitude'] ?? null;
            $candidate->place_type = $place['suggested_place_type'] ?? null;
            $candidate->operator_name = $operator['name'] ?? null;
            $candidate->parking_spaces = $place['parking_spaces_total'] ?? null;
            $candidate->address_text = collect([
                trim(implode(' ', array_filter([$address['street'] ?? null, $address['house_number'] ?? null]))),
                $address['postal_code'] ?? null,
                $address['city'] ?? null,
            ])->filter(fn ($value) => trim((string) $value) !== '')->implode(', ');
            $candidate->available_features = collect($features)
                ->filter(fn ($feature) => is_array($feature)
                    && ($feature['status'] ?? null) === 'available'
                    && ! ($feature['conflict'] ?? false))
                ->keys()
                ->map(fn ($slug) => [
                    'slug' => $slug,
                    'label' => $externalFeatureLabels[$slug] ?? $slug,
                ])
                ->values()
                ->all();
            $candidate->conflicting_features = collect($features)
                ->filter(fn ($feature) => is_array($feature) && ($feature['conflict'] ?? false))
                ->keys()
                ->map(fn ($slug) => [
                    'slug' => $slug,
                    'label' => $externalFeatureLabels[$slug] ?? $slug,
                ])
                ->values()
                ->all();

            return $candidate;
        });

        return view('admin.imports.index', [
            'runs' => $runs,
            'reviews' => $reviews,
            'duplicateGroups' => $this->duplicateGroups(),
            'manualAtkisDuplicatePreview' => $manualAtkisDuplicateLink->preview(),
            'reviewStatus' => $reviewStatus,
            'candidates' => $candidates,
            'candidateSearch' => $candidateSearch,
            'candidateSource' => $candidateSource,
            'practiceCandidates' => $this->practiceCandidates($externalFeatureLabels, $candidateSource),
            'externalMedia' => $externalMedia,
            'externalMediaPending' => $externalMediaPending,
            'sources' => DB::table('external_sources')
                ->orderBy('name')
                ->get(['id', 'slug', 'name', 'source_type', 'last_checked_at', 'last_success_at']),
            'summary' => [
                'sources' => DB::table('external_sources')->count(),
                'active_records' => DB::table('external_records')->where('status', 'active')->count(),
                'new_candidates' => DB::table('external_records')->where('classification', 'new_candidate')->count(),
                'possible_duplicates' => DB::table('external_records')->where('classification', 'possible_duplicate')->count(),
                'needs_review' => DB::table('external_records')->where('classification', 'needs_review')->count(),
                'pending_reviews' => DB::table('external_import_review_items')->where('status', 'pending')->count(),
                'pending_research_updates' => DB::table('external_import_review_items')->where('status', 'pending')->where('type', 'research_update')->count(),
                'pending_research_conflicts' => DB::table('external_import_review_items')->where('status', 'pending')->where('type', 'research_conflict')->count(),
                'pending_other_reviews' => DB::table('external_import_review_items')
                    ->where('status', 'pending')
                    ->whereNotIn('type', ['research_update', 'research_conflict', 'external_duplicate_group', 'source_missing', 'possible_reopen'])
                    ->count(),
            ],
        ]);
    }

    public function upload(
        Request $request,
        Datex2ParkingStageService $datex,
        ManualCsvPlaceParser $csv,
        ExternalRecordStagingService $staging,
        ExternalRecordClassificationService $classifier,
    ): RedirectResponse {
        $storedPath = null;

        try {
            $data = $request->validate([
                'adapter' => ['required', Rule::in(['datex2', 'csv'])],
                'source_name' => ['nullable', 'string', 'max:160'],
                'file' => ['required', 'file', 'max:51200'],
                'complete_snapshot' => ['nullable', 'boolean'],
            ]);

            $adapter = $data['adapter'];
            $file = $request->file('file');
            $extension = mb_strtolower($file->getClientOriginalExtension());

            if ($adapter === 'datex2' && $extension !== 'xml') {
                return redirect()
                    ->route('admin.imports.index')
                    ->withErrors(['file' => __('admin_imports.errors.datex_xml')]);
            }

            if ($adapter === 'csv' && $extension !== 'csv') {
                return redirect()
                    ->route('admin.imports.index')
                    ->withErrors(['file' => __('admin_imports.errors.csv_file')]);
            }

            if ($adapter === 'csv' && trim((string) ($data['source_name'] ?? '')) === '') {
                return redirect()
                    ->route('admin.imports.index')
                    ->withErrors(['source_name' => __('admin_imports.errors.csv_source')]);
            }

            $complete = $request->boolean('complete_snapshot');
            $safeName = now()->format('Ymd_His').'_'.Str::random(8).'.'.$extension;
            $storedPath = $file->storeAs('imports/manual', $safeName, 'local');

            if (! is_string($storedPath) || $storedPath === '') {
                throw new RuntimeException(__('admin_imports.errors.upload_store'));
            }

            $absolutePath = Storage::disk('local')->path($storedPath);

            if ($adapter === 'datex2') {
                // The classifier immediately performs the authoritative
                // duplicate/candidate pass. Skip the diagnostic candidate scan here
                // to avoid doing the same expensive work twice during HTTP uploads.
                $result = $datex->stagePath($absolutePath, $complete, false);
                $sourceId = (int) $result['staging']['source_id'];
                $stageStats = $result['staging'];
                $mappedCount = (int) $result['mapped_records'];
            } else {
                $records = $csv->parseFile($absolutePath);
                $sourceName = trim((string) $data['source_name']);
                $slugBase = Str::slug($sourceName);
                $sourceSlug = 'manual-csv-'.($slugBase !== '' ? $slugBase : substr(hash('sha256', $sourceName), 0, 16));

                $stageStats = $staging->stage($records, [
                    'slug' => $sourceSlug,
                    'name' => $sourceName,
                    'provider' => $sourceName,
                    'source_type' => 'manual_csv',
                    'adapter' => ManualCsvPlaceParser::class,
                    'country_code' => 'DE',
                    'config' => ['manual_upload' => true, 'template' => 'place-csv-v1'],
                ], $complete);

                $sourceId = (int) $stageStats['source_id'];
                $mappedCount = count($records);
            }

            $runId = (int) DB::table('external_import_runs')->insertGetId([
                'external_source_id' => $sourceId,
                'mode' => 'manual_upload',
                'status' => 'completed',
                'started_at' => now(),
                'fetched_at' => now(),
                'validated_at' => now(),
                'completed_at' => now(),
                'source_record_count' => $mappedCount,
                'mapped_record_count' => $mappedCount,
                'created_count' => $stageStats['new'] ?? 0,
                'updated_count' => $stageStats['changed'] ?? 0,
                'skipped_count' => $stageStats['unchanged'] ?? 0,
                'stats' => json_encode([
                    'adapter' => $adapter,
                    'complete_snapshot' => $complete,
                    'missing_marked' => $stageStats['missing_marked'] ?? 0,
                    'original_filename' => $file->getClientOriginalName(),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('external_raw_snapshots')->insert([
                'external_source_id' => $sourceId,
                'external_import_run_id' => $runId,
                'storage_path' => $storedPath,
                'sha256' => hash_file('sha256', $absolutePath),
                'content_type' => $file->getClientMimeType(),
                'byte_size' => $file->getSize(),
                'fetched_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $classification = $classifier->classifySource($sourceId);

            return redirect()
                ->route('admin.imports.index')
                ->with(
                    'ui_toast',
                    __('admin_imports.controller.import_completed', [
                        'mapped' => $mappedCount,
                        'new' => $classification['new_candidate'],
                        'duplicates' => $classification['possible_duplicate'],
                        'reviews' => $classification['needs_review'],
                    ]),
                );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Import upload validation failed.', [
                'errors' => $e->errors(),
                'content_length' => $request->server('CONTENT_LENGTH'),
                'adapter' => $request->input('adapter'),
            ]);

            return redirect()
                ->route('admin.imports.index')
                ->withErrors($e->errors());
        } catch (\Throwable $e) {
            if (is_string($storedPath) && $storedPath !== '') {
                Storage::disk('local')->delete($storedPath);
            }

            Log::error('Import upload failed.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'content_length' => $request->server('CONTENT_LENGTH'),
                'adapter' => $request->input('adapter'),
            ]);

            return redirect()
                ->route('admin.imports.index')
                ->withErrors([
                    'file' => __('admin_imports.controller.import_aborted', ['message' => $e->getMessage()]),
                ]);
        }
    }

    public function createPlaceFromCandidate(Request $request, int $record, ExternalRecordReviewService $service): RedirectResponse
    {
        try {
            $placeId = $service->createPlaceFromCandidate($record, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['candidate' => $e->getMessage()]);
        }

        $slug = DB::table('places')->where('id', $placeId)->value('slug');

        return $slug
            ? redirect()->route('places.show', $slug)->with('ui_toast', __('admin_imports.messages.candidate_taken'))
            : redirect()->route('admin.imports.index')->with('ui_toast', __('admin_imports.messages.candidate_taken_short'));
    }

    public function createPlacesFromCandidates(Request $request, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'candidate_ids' => ['required', 'array', 'min:1', 'max:50'],
            'candidate_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        try {
            $created = $service->createPlacesFromCandidates(
                $data['candidate_ids'],
                $request->user(),
                50,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['candidate_bulk' => $e->getMessage()]);
        }

        return back()->with(
            'ui_toast',
            __('admin_imports.controller.bulk_candidates_taken', ['count' => count($created)]),
        );
    }

    public function createAllCandidateBatch(
        Request $request,
        ExternalRecordReviewService $service,
    ): JsonResponse {
        $data = $request->validate([
            'source_slug' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $result = $service->createNextCandidateBatch(
                $request->user(),
                $data['source_slug'] ?? null,
                50,
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 409);
        }

        return response()->json([
            'created' => $result['created'],
            'remaining' => $result['remaining'],
            'total_before' => $result['total_before'],
            'done' => $result['remaining'] === 0,
        ]);
    }

    public function importExternalMediaSelected(Request $request, ExternalPlacePhotoService $service): RedirectResponse
    {
        $data = $request->validate([
            'media_keys' => ['required', 'array', 'min:1', 'max:100'],
            'media_keys.*' => ['required', 'string', 'max:255', 'distinct'],
        ]);

        $result = $service->importSelected($data['media_keys']);

        $message = __('admin_imports.controller.media_queued', ['count' => ($result['queued'] ?? 0)]);

        if (($result['skipped'] ?? 0) > 0) {
            $message .= ' Übersprungen: '.$result['skipped'].'.';
        }

        if (($result['failed'] ?? 0) > 0) {
            $message .= ' '.__('admin_imports.controller.media_failed', ['count' => $result['failed']]);
        }

        if (! empty($result['errors'])) {
            $message .= ' '.collect($result['errors'])
                ->pluck('reason')
                ->filter()
                ->unique()
                ->take(3)
                ->implode(' | ');
        }

        return back()->with('ui_toast', $message);
    }

    public function importExternalMediaBatch(Request $request, ExternalPlacePhotoService $service): JsonResponse
    {
        $data = $request->validate([
            'source_slug' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $service->importNextPendingBatch($data['source_slug'] ?? null, 20);

        return response()->json([
            'queued' => (int) ($result['queued'] ?? 0),
            'existing' => (int) ($result['existing'] ?? 0),
            'skipped' => (int) ($result['skipped'] ?? 0),
            'failed' => (int) ($result['failed'] ?? 0),
            'remaining' => (int) ($result['remaining'] ?? 0),
            'blocked' => (bool) ($result['blocked'] ?? false),
            'errors' => $result['errors'] ?? [],
            'done' => (int) ($result['remaining'] ?? 0) === 0 || (bool) ($result['blocked'] ?? false),
        ]);
    }

    public function ignoreCandidate(Request $request, int $record, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->ignoreCandidate($record, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['candidate' => $e->getMessage()]);
        }

        return back()->with('ui_toast', __('admin_imports.messages.candidate_ignored'));
    }

    public function linkReview(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'place_id' => ['required', 'integer', 'exists:places,id'],
        ]);

        try {
            $placeId = $service->link($review, (int) $data['place_id'], $request->user());
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')->with('ui_toast', __('admin_imports.controller.linked_record', ['id' => $placeId]));
    }

    public function promoteReviewCandidate(
        Request $request,
        int $review,
        ExternalRecordReviewService $service,
    ): RedirectResponse {
        $data = $request->validate([
            'place_id' => ['required', 'integer', 'exists:places,id'],
        ]);

        try {
            $placeId = $service->createPlaceAsMainAndMerge(
                $review,
                (int) $data['place_id'],
                $request->user(),
            );
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')
                ->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')
            ->with('ui_toast', __('admin_imports.controller.promoted_candidate', ['id' => $placeId]));
    }

    public function reviewMap(int $review): JsonResponse
    {
        $item = DB::table('external_import_review_items as ri')
            ->join('external_records as er', 'er.id', '=', 'ri.external_record_id')
            ->join('external_sources as es', 'es.id', '=', 'ri.external_source_id')
            ->where('ri.id', $review)
            ->where('ri.status', 'pending')
            ->where('ri.type', 'possible_duplicate')
            ->first([
                'ri.id as review_id',
                'ri.details',
                'er.id as record_id',
                'er.external_id',
                'er.normalized_data',
                'es.name as source_name',
            ]);

        if (! $item) {
            abort(404);
        }

        $details = json_decode((string) $item->details, true) ?: [];
        $mapped = json_decode((string) $item->normalized_data, true) ?: [];
        $place = is_array($mapped['place'] ?? null) ? $mapped['place'] : [];
        $candidates = $this->resolveReviewCandidates(
            is_array($details['candidates'] ?? null) ? $details['candidates'] : [],
        );
        $candidateIds = collect($candidates)
            ->pluck('place_id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $candidatePlaces = DB::table('places')
            ->whereIn('id', $candidateIds)
            ->get(['id', 'latitude', 'longitude'])
            ->keyBy('id');

        $candidates = collect($candidates)
            ->map(function (array $candidate) use ($candidatePlaces) {
                $candidatePlace = $candidatePlaces->get((int) ($candidate['place_id'] ?? 0));
                $candidate['latitude'] = $candidatePlace?->latitude;
                $candidate['longitude'] = $candidatePlace?->longitude;

                return $candidate;
            })
            ->values()
            ->all();

        return response()->json([
            'mode' => 'single_review',
            'name' => (string) ($place['name'] ?? $item->external_id),
            'place_type' => (string) ($place['suggested_place_type'] ?? ''),
            'members' => [[
                'review_id' => (int) $item->review_id,
                'record_id' => (int) $item->record_id,
                'external_id' => (string) $item->external_id,
                'source_name' => (string) $item->source_name,
                'latitude' => isset($place['latitude']) && is_numeric($place['latitude']) ? (float) $place['latitude'] : null,
                'longitude' => isset($place['longitude']) && is_numeric($place['longitude']) ? (float) $place['longitude'] : null,
            ]],
            'review_ids' => [(int) $item->review_id],
            'create_place_url' => route('admin.imports.reviews.create-place', $item->review_id),
            'link_url' => route('admin.imports.reviews.link', $item->review_id),
            'promote_url' => route('admin.imports.reviews.promote-candidate', $item->review_id),
            'candidates' => $candidates,
        ]);
    }

    public function duplicateGroupMap(int $review): JsonResponse
    {
        $selected = DB::table('external_import_review_items')
            ->where('id', $review)
            ->where('status', 'pending')
            ->where('type', 'external_duplicate_group')
            ->first(['details']);

        if (! $selected) {
            abort(404);
        }

        $selectedDetails = json_decode((string) $selected->details, true) ?: [];
        $groupKey = trim((string) ($selectedDetails['group_key'] ?? ''));

        if ($groupKey === '') {
            abort(404);
        }

        $members = DB::table('external_import_review_items as ri')
            ->join('external_records as er', 'er.id', '=', 'ri.external_record_id')
            ->join('external_sources as es', 'es.id', '=', 'ri.external_source_id')
            ->where('ri.status', 'pending')
            ->where('ri.type', 'external_duplicate_group')
            ->get([
                'ri.id as review_id',
                'ri.details',
                'er.id as record_id',
                'er.external_id',
                'er.normalized_data',
                'es.name as source_name',
            ])
            ->filter(function ($item) use ($groupKey) {
                $details = json_decode((string) $item->details, true) ?: [];

                return ($details['group_key'] ?? null) === $groupKey;
            })
            ->map(function ($item) {
                $mapped = json_decode((string) $item->normalized_data, true) ?: [];
                $place = is_array($mapped['place'] ?? null) ? $mapped['place'] : [];

                return [
                    'review_id' => (int) $item->review_id,
                    'record_id' => (int) $item->record_id,
                    'external_id' => (string) $item->external_id,
                    'source_name' => (string) $item->source_name,
                    'latitude' => isset($place['latitude']) ? (float) $place['latitude'] : null,
                    'longitude' => isset($place['longitude']) ? (float) $place['longitude'] : null,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'name' => (string) ($selectedDetails['group_name'] ?? __('admin_imports.duplicate_group')),
            'place_type' => (string) ($selectedDetails['group_place_type'] ?? ''),
            'members' => $members,
            'review_ids' => collect($members)->pluck('review_id')->values()->all(),
            'create_place_url' => route('admin.imports.duplicate-groups.create-place'),
            'candidates' => $this->resolveReviewCandidates(
                is_array($selectedDetails['candidates'] ?? null)
                    ? $selectedDetails['candidates']
                    : [],
            ),
        ]);
    }

    public function linkEligibleAtkisDuplicateGroups(
        Request $request,
        ManualAtkisDuplicateLinkService $service,
    ): RedirectResponse {
        $data = $request->validate([
            'confirmed' => ['accepted'],
        ]);

        try {
            $result = $service->execute($request->user());
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'duplicate-groups')->withErrors(['duplicate_group_bulk' => $e->getMessage()]);
        }

        $message = __('admin_imports.controller.atkis_linked', [
            'groups' => $result['linked_groups'],
            'records' => $result['linked_records'],
        ]);

        if ($result['skipped'] !== []) {
            $message .= ' '.__('admin_imports.controller.groups_skipped', ['count' => count($result['skipped'])]);
        }

        return $this->backToImportAnchor($request, 'duplicate-groups')->with('ui_toast', $message);
    }

    public function linkDuplicateGroup(Request $request, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'review_ids' => ['required', 'array', 'min:1', 'max:100'],
            'review_ids.*' => ['required', 'integer', 'distinct'],
            'place_id' => ['required', 'integer', 'exists:places,id'],
        ]);

        try {
            $result = $service->linkDuplicateGroup(
                $data['review_ids'],
                (int) $data['place_id'],
                $request->user(),
            );
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'duplicate-groups')->withErrors(['duplicate_group' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'duplicate-groups')->with(
            'ui_toast',
            __('admin_imports.controller.source_records_linked', ['count' => $result['linked'], 'id' => $result['place_id']]),
        );
    }

    public function createPlaceFromDuplicateGroup(Request $request, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'review_ids' => ['required', 'array', 'min:1', 'max:100'],
            'review_ids.*' => ['required', 'integer', 'distinct'],
            'primary_review_id' => ['required', 'integer'],
        ]);

        try {
            $result = $service->createPlaceFromDuplicateGroup(
                (int) $data['primary_review_id'],
                $data['review_ids'],
                $request->user(),
            );
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'duplicate-groups')->withErrors(['duplicate_group' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'duplicate-groups')->with(
            'ui_toast',
            __('admin_imports.controller.primary_created', ['count' => $result['linked']]),
        );
    }

    public function createSeparateDuplicateGroupMember(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        try {
            $placeId = $service->createSeparatePlaceFromDuplicateGroupMember($review, $request->user());
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'duplicate-groups')->withErrors(['duplicate_group' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'duplicate-groups')->with(
            'ui_toast',
            __('admin_imports.controller.separate_created', ['id' => $placeId]),
        );
    }

    public function createPlaceFromReview(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        try {
            $placeId = $service->createPlace($review, $request->user());
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')
            ->with('ui_toast', __('admin_imports.messages.place_taken'));
    }

    public function deferSelectedReviews(Request $request, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'review_ids' => ['required', 'array', 'min:1', 'max:200'],
            'review_ids.*' => ['integer', 'distinct'],
        ]);

        try {
            $result = $service->deferSelected($data['review_ids'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['review_ids' => $e->getMessage()]);
        }

        $message = __('admin_imports.controller.selected_deferred', [
            'processed' => $result['processed'],
            'attempted' => $result['attempted'],
        ]);

        if ($result['skipped'] !== []) {
            $message .= ' '.__('admin_imports.controller.skipped_processing', ['count' => count($result['skipped'])]);
        }

        return back()->with('ui_toast', $message);
    }

    public function ignoreSelectedReviews(Request $request, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'review_ids' => ['required', 'array', 'min:1', 'max:200'],
            'review_ids.*' => ['integer', 'distinct'],
        ]);

        try {
            $result = $service->ignoreSelected($data['review_ids'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['review_ids' => $e->getMessage()]);
        }

        $message = __('admin_imports.controller.selected_ignored', [
            'processed' => $result['processed'],
            'attempted' => $result['attempted'],
        ]);

        if ($result['skipped'] !== []) {
            $message .= ' '.__('admin_imports.controller.skipped_protected', ['count' => count($result['skipped'])]);
        }

        return back()->with('ui_toast', $message);
    }

    public function deferAllReviews(Request $request, ExternalRecordReviewService $service): RedirectResponse
    {
        $result = $service->deferAllPending($request->user());

        $message = __('admin_imports.controller.all_deferred', [
            'processed' => $result['deferred'],
            'attempted' => $result['attempted'],
        ]);

        if ($result['skipped'] !== []) {
            $message .= ' '.__('admin_imports.controller.skipped_defer', ['count' => count($result['skipped'])]);
        }

        return redirect()
            ->route('admin.imports.index')
            ->with('ui_toast', $message);
    }

    public function ignoreAllReviews(Request $request, ExternalRecordReviewService $service): RedirectResponse
    {
        $result = $service->ignoreAllPending($request->user());

        $message = __('admin_imports.controller.all_ignored', [
            'processed' => $result['ignored'],
            'attempted' => $result['attempted'],
        ]);

        if ($result['skipped'] !== []) {
            $message .= ' '.__('admin_imports.controller.skipped_ignore', ['count' => count($result['skipped'])]);
        }

        return redirect()
            ->route('admin.imports.index')
            ->with('ui_toast', $message);
    }

    public function updateReviewCoordinates(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $result = $service->updateMissingCoordinates(
                $review,
                (float) $data['latitude'],
                (float) $data['longitude'],
                $request->user(),
            );
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        $message = $result['classification'] === 'possible_duplicate'
            ? __('admin_imports.messages.coordinates_duplicate')
            : __('admin_imports.messages.coordinates_candidate');

        return $this->backToImportAnchor($request, 'review-queue')->with('ui_toast', $message);
    }

    public function reopenReviewPlace(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        try {
            $placeId = $service->reopenPossibleReopen($review, $request->user());
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')
            ->with('ui_toast', __('admin_imports.messages.reopened', ['id' => $placeId]));
    }

    public function resolvePossibleReopenReview(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->resolvePossibleReopen($review, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')
            ->with('ui_toast', __('admin_imports.messages.reopen_resolved'));
    }

    public function createPlaceFromDeletedReview(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        try {
            $placeId = $service->createPlaceFromDeletedReview($review, $request->user());
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')
            ->with('ui_toast', __('admin_imports.messages.deleted_place_recreated', ['id' => $placeId]));
    }

    public function ignoreDeletedPlaceReview(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->ignoreDeletedPlaceReview($review, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')
            ->with('ui_toast', __('admin_imports.messages.deleted_place_kept'));
    }

    public function ignoreReview(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->ignore($review, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')->with('ui_toast', __('admin_imports.messages.review_ignored'));
    }

    public function resolveSourceMissingReview(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->resolveSourceMissing($review, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')->with('ui_toast', __('admin_imports.messages.missing_resolved'));
    }

    public function deferReview(Request $request, int $review, ExternalRecordReviewService $service): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->defer($review, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return $this->backToImportAnchor($request, 'review-queue')->withErrors(['review' => $e->getMessage()]);
        }

        return $this->backToImportAnchor($request, 'review-queue')->with('ui_toast', __('admin_imports.messages.review_deferred'));
    }

    public function template(ManualCsvPlaceParser $csv): Response
    {
        $content = "\xEF\xBB\xBF".$csv->template();

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="camperwolf-import-template.csv"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function researchExport(
        Request $request,
        \App\Services\Imports\ResearchPlaceCsvExporter $exporter,
    ): Response {
        $scope = $request->string('scope')->toString();
        $onlyMissing = $scope !== 'all';
        $content = $exporter->export($onlyMissing);
        $suffix = $onlyMissing ? 'recherchebedarf' : 'alle-plaetze';

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="camperwolf-recherche-'.$suffix.'.csv"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function researchUpload(
        Request $request,
        ResearchCsvImportService $service,
    ): RedirectResponse {
        try {
            $data = $request->validate([
                'research_file' => ['required', 'file', 'max:51200'],
            ]);

            $file = $data['research_file'];
            if (mb_strtolower($file->getClientOriginalExtension()) !== 'csv') {
                return back()->withErrors(['research_file' => __('admin_imports.errors.research_csv')]);
            }

            $stats = $service->import($file);

            return redirect()
                ->route('admin.imports.index')
                ->with('ui_toast', __('admin_imports.controller.research_processed', [
                    'rows' => $stats['staged'],
                    'reviews' => $stats['review'],
                    'conflicts' => $stats['conflicts'],
                    'unchanged' => $stats['no_change'],
                ]));
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Research CSV import failed.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.imports.index')
                ->withErrors(['research_file' => __('admin_imports.controller.research_aborted', ['message' => $e->getMessage()])]);
        }
    }

    public function approveAllResearchReviews(
        Request $request,
        ResearchReviewApplyService $service,
    ): RedirectResponse {
        $result = $service->approveAll($request->user());

        $message = __('admin_imports.controller.research_applied_all', [
            'processed' => $result['applied'],
            'attempted' => $result['attempted'],
        ]);

        if ($result['skipped'] !== []) {
            $message .= ' '.__('admin_imports.controller.research_skipped', ['count' => count($result['skipped'])]);
        }

        return redirect()
            ->route('admin.imports.index')
            ->with('ui_toast', $message);
    }

    public function approveResearchReview(
        Request $request,
        int $review,
        ResearchReviewApplyService $service,
    ): RedirectResponse {
        try {
            $placeId = $service->approve($review, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['research_review' => $e->getMessage()]);
        }

        $slug = DB::table('places')->where('id', $placeId)->value('slug');

        return $slug
            ? redirect()->route('places.show', $slug)->with('ui_toast', __('admin_imports.controller.research_applied'))
            : redirect()->route('admin.imports.index')->with('ui_toast', __('admin_imports.controller.research_applied'));
    }

    private function backToImportAnchor(Request $request, string $anchor): RedirectResponse
    {
        $previous = preg_replace('/#.*$/', '', url()->previous()) ?: route('admin.imports.index');

        return redirect()->to($previous.'#'.$anchor);
    }

    private function duplicateGroups(): array
    {
        $items = DB::table('external_import_review_items as ri')
            ->join('external_records as er', 'er.id', '=', 'ri.external_record_id')
            ->join('external_sources as es', 'es.id', '=', 'ri.external_source_id')
            ->where('ri.status', 'pending')
            ->where('ri.type', 'external_duplicate_group')
            ->orderBy('ri.id')
            ->get([
                'ri.id as review_id',
                'ri.details',
                'er.id as record_id',
                'er.external_id',
                'er.normalized_data',
                'es.name as source_name',
                'es.slug as source_slug',
            ]);

        return $items
            ->map(function ($item) {
                $details = json_decode((string) $item->details, true) ?: [];
                $mapped = json_decode((string) $item->normalized_data, true) ?: [];
                $place = is_array($mapped['place'] ?? null) ? $mapped['place'] : [];

                return [
                    'review_id' => (int) $item->review_id,
                    'record_id' => (int) $item->record_id,
                    'external_id' => (string) $item->external_id,
                    'source_name' => (string) $item->source_name,
                    'source_slug' => (string) $item->source_slug,
                    'group_key' => (string) ($details['group_key'] ?? ''),
                    'group_name' => (string) ($details['group_name'] ?? ($place['name'] ?? $item->external_id)),
                    'place_type' => (string) ($details['group_place_type'] ?? ($place['suggested_place_type'] ?? '')),
                    'latitude' => isset($place['latitude']) ? (float) $place['latitude'] : null,
                    'longitude' => isset($place['longitude']) ? (float) $place['longitude'] : null,
                    'candidates' => $this->resolveReviewCandidates(
                        is_array($details['candidates'] ?? null) ? $details['candidates'] : [],
                    ),
                ];
            })
            ->filter(fn (array $item) => $item['group_key'] !== '')
            ->groupBy('group_key')
            ->map(function ($members) {
                $members = $members->values();

                $candidates = $members
                    ->flatMap(fn (array $member) => $member['candidates'])
                    ->filter(fn ($candidate) => is_array($candidate) && isset($candidate['place_id']))
                    ->sortBy([
                        ['distance_m', 'asc'],
                        ['name_similarity', 'desc'],
                    ])
                    ->unique('place_id')
                    ->values()
                    ->all();

                return [
                    'group_key' => $members->first()['group_key'],
                    'name' => $members->first()['group_name'],
                    'place_type' => $members->first()['place_type'],
                    'members' => $members->all(),
                    'review_ids' => $members->pluck('review_id')->all(),
                    'candidates' => $candidates,
                    'source_names' => $members->pluck('source_name')->unique()->values()->all(),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    private function resolveReviewCandidates(array $candidates): array
    {
        $mergeService = app(PlaceMergeService::class);

        return collect($candidates)
            ->filter(fn ($candidate) => is_array($candidate) && isset($candidate['place_id']) && is_numeric($candidate['place_id']))
            ->map(function (array $candidate) use ($mergeService): ?array {
                $originalPlaceId = (int) $candidate['place_id'];
                $activePlaceId = $mergeService->resolveActivePlaceId($originalPlaceId);

                if (! $activePlaceId) {
                    return null;
                }

                $place = DB::table('places')
                    ->where('id', $activePlaceId)
                    ->where('is_active', true)
                    ->first(['id', 'name', 'slug']);

                if (! $place) {
                    return null;
                }

                $candidate['place_id'] = (int) $place->id;
                $candidate['name'] = (string) $place->name;
                $candidate['slug'] = $place->slug;

                if ($activePlaceId !== $originalPlaceId) {
                    $candidate['merged_from_place_id'] = $originalPlaceId;
                }

                return $candidate;
            })
            ->filter()
            ->sortBy([
                ['distance_m', 'asc'],
                ['name_similarity', 'desc'],
            ])
            ->unique('place_id')
            ->values()
            ->all();
    }

    private function externalFeatureLabels(string $locale): array
    {
        $fallback = \App\Support\LocaleConfiguration::fallback();

        return DB::table('features as f')
            ->leftJoin('translations as tr', function ($join) use ($locale) {
                $join->on('tr.entity_id', '=', 'f.id')
                    ->where('tr.entity_type', 'feature')
                    ->where('tr.locale', $locale)
                    ->where('tr.field', 'name')
                    ->where('tr.is_active', true);
            })
            ->leftJoin('translations as tf', function ($join) use ($fallback) {
                $join->on('tf.entity_id', '=', 'f.id')
                    ->where('tf.entity_type', 'feature')
                    ->where('tf.locale', $fallback)
                    ->where('tf.field', 'name')
                    ->where('tf.is_active', true);
            })
            ->where('f.is_active', true)
            ->whereIn('f.slug', [
                'waste-bins',
                'rest-picnic-area',
                'toilet',
                'shower',
                'playground',
                'defibrillator',
                'first-aid-equipment',
                'fresh-water',
                'dumping-station',
            ])
            ->select([
                'f.slug',
                DB::raw('COALESCE(tr.value, tf.value, f.slug) as label'),
            ])
            ->pluck('label', 'slug')
            ->map(fn ($value) => (string) $value)
            ->all();
    }

    private function practiceCandidates(array $externalFeatureLabels, string $sourceSlug = ''): array
    {
        $best = [
            'simple' => null,
            'contact' => null,
            'features' => null,
            'sparse' => null,
        ];

        foreach (
            DB::table('external_records as er')
                ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
                ->where('er.status', 'active')
                ->where('er.classification', 'new_candidate')
                ->whereNull('er.place_id')
                ->when($sourceSlug !== '', fn ($query) => $query->where('es.slug', $sourceSlug))
                ->select([
                    'er.id',
                    'er.external_id',
                    'er.normalized_data',
                    'es.name as source_name',
                    'es.slug as source_slug',
                ])
                ->orderBy('er.id')
                ->cursor() as $record
        ) {
            $candidate = $this->practiceCandidateData($record, $externalFeatureLabels);
            if ($candidate === null) {
                continue;
            }

            if ($best['features'] === null || $candidate['available_feature_count'] > $best['features']['candidate']['available_feature_count']) {
                $best['features'] = [
                    'score' => $candidate['available_feature_count'],
                    'candidate' => $candidate,
                ];
            }

            $contactScore = ($candidate['operator_name'] ? 5 : 0)
                + ($candidate['contact_count'] * 4)
                + ($candidate['address_text'] !== '' ? 1 : 0)
                + (str_contains(mb_strtolower($candidate['name']), 'autohof') ? 5 : 0);

            if ($contactScore > 0 && ($best['contact'] === null || $contactScore > $best['contact']['score'])) {
                $best['contact'] = [
                    'score' => $contactScore,
                    'candidate' => $candidate,
                ];
            }

            $simpleScore = abs($candidate['available_feature_count'] - 2)
                + ($candidate['contact_count'] > 0 ? 5 : 0)
                + ($candidate['operator_name'] ? 2 : 0)
                + ($candidate['address_text'] === '' ? 2 : 0);

            if ($best['simple'] === null || $simpleScore < $best['simple']['score']) {
                $best['simple'] = [
                    'score' => $simpleScore,
                    'candidate' => $candidate,
                ];
            }

            $sparseScore = $candidate['metadata_score'];

            if ($best['sparse'] === null || $sparseScore < $best['sparse']['score']) {
                $best['sparse'] = [
                    'score' => $sparseScore,
                    'candidate' => $candidate,
                ];
            }
        }

        $definitions = [
            'simple' => [__('admin_imports.practice.simple_title'), __('admin_imports.practice.simple_reason')],
            'contact' => [__('admin_imports.practice.contact_title'), __('admin_imports.practice.contact_reason')],
            'features' => [__('admin_imports.practice.features_title'), __('admin_imports.practice.features_reason')],
            'sparse' => [__('admin_imports.practice.sparse_title'), __('admin_imports.practice.sparse_reason')],
        ];

        $used = [];
        $result = [];

        foreach ($definitions as $key => [$title, $reason]) {
            $entry = $best[$key];
            if ($entry === null) {
                continue;
            }

            $candidate = $entry['candidate'];
            if (in_array($candidate['id'], $used, true)) {
                $candidate = $this->nextUnusedPracticeCandidate($used, $externalFeatureLabels, $sourceSlug);
                if ($candidate === null) {
                    continue;
                }
            }

            $used[] = $candidate['id'];
            $result[] = [
                'key' => $key,
                'title' => $title,
                'reason' => $reason,
                'candidate' => $candidate,
            ];
        }

        return $result;
    }

    private function nextUnusedPracticeCandidate(array $used, array $externalFeatureLabels, string $sourceSlug = ''): ?array
    {
        $query = DB::table('external_records as er')
            ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
            ->where('er.status', 'active')
            ->where('er.classification', 'new_candidate')
            ->whereNull('er.place_id')
            ->when($sourceSlug !== '', fn ($query) => $query->where('es.slug', $sourceSlug))
            ->when($used !== [], fn ($builder) => $builder->whereNotIn('er.id', $used))
            ->select([
                'er.id',
                'er.external_id',
                'er.normalized_data',
                'es.name as source_name',
                'es.slug as source_slug',
            ])
            ->orderBy('er.id');

        foreach ($query->cursor() as $record) {
            $candidate = $this->practiceCandidateData($record, $externalFeatureLabels);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    private function practiceCandidateData(object $record, array $externalFeatureLabels): ?array
    {
        $data = json_decode((string) $record->normalized_data, true);
        if (! is_array($data)) {
            return null;
        }

        $place = is_array($data['place'] ?? null) ? $data['place'] : [];
        $name = trim((string) ($place['name'] ?? ''));
        $latitude = $place['latitude'] ?? null;
        $longitude = $place['longitude'] ?? null;

        if ($name === '' || ! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $address = is_array($place['address'] ?? null) ? $place['address'] : [];
        $operator = is_array($place['operator'] ?? null) ? $place['operator'] : [];
        $features = is_array($data['features'] ?? null) ? $data['features'] : [];

        $availableFeatures = collect($features)
            ->filter(fn ($feature) => is_array($feature)
                && ($feature['status'] ?? null) === 'available'
                && ! ($feature['conflict'] ?? false))
            ->keys()
            ->map(fn ($slug) => [
                'slug' => $slug,
                'label' => $externalFeatureLabels[$slug] ?? $slug,
            ])
            ->values()
            ->all();

        $conflictingFeatures = collect($features)
            ->filter(fn ($feature) => is_array($feature) && ($feature['conflict'] ?? false))
            ->keys()
            ->map(fn ($slug) => [
                'slug' => $slug,
                'label' => $externalFeatureLabels[$slug] ?? $slug,
            ])
            ->values()
            ->all();

        $contactCount = collect([
            $operator['phone'] ?? null,
            $operator['email'] ?? null,
            $operator['url'] ?? null,
        ])->filter(fn ($value) => trim((string) $value) !== '')->count();

        $addressText = collect([
            trim(implode(' ', array_filter([$address['street'] ?? null, $address['house_number'] ?? null]))),
            $address['postal_code'] ?? null,
            $address['city'] ?? null,
        ])->filter(fn ($value) => trim((string) $value) !== '')->implode(', ');

        $operatorName = trim((string) ($operator['name'] ?? ''));

        $metadataScore = 2
            + ($addressText !== '' ? 2 : 0)
            + ($operatorName !== '' ? 2 : 0)
            + $contactCount
            + (is_numeric($place['parking_spaces_total'] ?? null) ? 1 : 0)
            + (trim((string) ($place['description'] ?? '')) !== '' ? 1 : 0)
            + count($availableFeatures);

        return [
            'id' => (int) $record->id,
            'external_id' => (string) $record->external_id,
            'source_name' => (string) $record->source_name,
            'source_slug' => (string) $record->source_slug,
            'name' => $name,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude,
            'place_type' => $place['suggested_place_type'] ?? null,
            'operator_name' => $operatorName !== '' ? $operatorName : null,
            'contact_count' => $contactCount,
            'parking_spaces' => $place['parking_spaces_total'] ?? null,
            'address_text' => $addressText,
            'available_features' => $availableFeatures,
            'available_feature_count' => count($availableFeatures),
            'conflicting_features' => $conflictingFeatures,
            'conflicting_feature_count' => count($conflictingFeatures),
            'metadata_score' => $metadataScore,
        ];
    }
}
