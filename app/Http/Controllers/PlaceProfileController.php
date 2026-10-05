<?php

namespace App\Http\Controllers;

use App\Services\AdminDebugService;
use App\Services\CurrentOpeningStateService;
use App\Services\FeatureWorkflowService;
use App\Services\Imports\ExternalFeatureOverlayService;
use App\Services\OpeningHoursPeriodService;
use App\Services\PermissionService;
use App\Services\PlacePhotoService;
use App\Services\PlaceHistoryPresenter;
use App\Services\PlaceDataScoreService;
use App\Services\PlaceReviewService;
use App\Services\PlaceTypeFeatureService;
use App\Support\LocaleConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlaceProfileController extends Controller
{
    public function __invoke(
        Request $request,
        string $slug,
        FeatureWorkflowService $workflows,
        PermissionService $permissions,
        PlaceReviewService $reviewService,
        PlacePhotoService $photoService,
        OpeningHoursPeriodService $openingHoursPeriods,
        PlaceTypeFeatureService $placeTypeFeatures,
        ExternalFeatureOverlayService $externalFeatures,
        AdminDebugService $debug,
        CurrentOpeningStateService $openingStateService,
        PlaceHistoryPresenter $historyPresenter,
        PlaceDataScoreService $dataScores,
    ): View|RedirectResponse {
        $profileEnabled = $debug->enabled($request->user());
        $profile = [];
        $profileStarted = microtime(true);
        $profileLast = $profileStarted;
        $profileMark = function (string $label) use (&$profile, &$profileLast, $profileEnabled): void {
            if (! $profileEnabled) {
                return;
            }

            $now = microtime(true);
            $profile[$label] = round(($now - $profileLast) * 1000, 2);
            $profileLast = $now;
        };

        $mergedTarget = DB::table('place_merges as pm')
            ->join('places as target', 'target.id', '=', 'pm.target_place_id')
            ->where('pm.status', 'completed')
            ->where('pm.source_place_id', DB::table('places')->where('slug', $slug)->value('id'))
            ->value('target.slug');
        if ($mergedTarget) {
            return redirect()->route('places.show', $mergedTarget, 301);
        }

        $place = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->leftJoin('place_addresses as pa', function ($join) {
                $join->on('pa.place_id', '=', 'p.id')
                    ->where('pa.is_active', true)
                    ->whereNull('pa.version_valid_until');
            })
            ->where('p.slug', $slug)
            ->where('p.is_active', true)
            ->where('p.publication_status', 'published')
            ->select([
                'p.id',
                'p.name',
                'p.slug',
                'p.latitude',
                'p.longitude',
                'p.legal_status',
                'p.opening_status',
                'pt.id as place_type_id',
                'pt.slug as place_type_slug',
                'pa.country_code',
                'pa.postal_code',
                'pa.city',
                'pa.street',
                'pa.house_number',
                'pa.address_addition',
            ])
            ->first();

        abort_unless($place, 404);
        $profileMark('Place lookup');

        $locale = app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        $translation = DB::table('place_translations')
            ->where('place_id', $place->id)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderByRaw('CASE WHEN locale = ? THEN 0 WHEN locale = ? THEN 1 ELSE 2 END', [$locale, $fallback])
            ->first(['description', 'directions', 'access_information']);

        $details = DB::table('place_details')
            ->where('place_id', $place->id)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first(['id', 'operator_name', 'pitch_count', 'minimum_stay_nights', 'pitch_area_min_m2']);

        $featureGroups = $externalFeatures
            ->apply((int) $place->id, $workflows->groupedForPlace((int) $place->id))
            ->map(function ($group) use ($workflows) {
                $group->features = $group->features
                    ->map(fn ($feature) => $workflows->present($feature))
                    ->values();
                $group->is_standard = $group->features
                    ->contains(fn ($feature) => ($feature->visibility ?? 'extended') === 'standard');
                $group->has_known_features = $group->features
                    ->contains(fn ($feature) => $feature->tone !== 'unknown');

                return $group;
            });
        $profileMark('Translations, details, features');

        if ($request->user()) {
            $activeFeatureIds = DB::table('place_features')
                ->where('place_id', $place->id)
                ->where('is_active', true)
                ->whereNull('valid_until')
                ->pluck('feature_id', 'id');

            $pendingRows = DB::table('change_requests as cr')
                ->join('suggestable_fields as sf', 'sf.id', '=', 'cr.suggestable_field_id')
                ->where('cr.place_id', $place->id)
                ->where('cr.submitted_by', $request->user()->id)
                ->where('cr.status', 'pending')
                ->where('sf.target_table', 'place_features')
                ->orderBy('cr.id')
                ->get([
                    'cr.id',
                    'cr.group_uuid',
                    'cr.target_record_id',
                    'cr.proposed_value',
                    'sf.target_field',
                ]);

            $pendingByFeature = $pendingRows
                ->groupBy(fn ($row) => $row->group_uuid ?: 'request-'.$row->id)
                ->mapWithKeys(function ($rows) use ($activeFeatureIds) {
                    $featureRequest = $rows->firstWhere('target_field', 'feature_id');
                    $featureId = $featureRequest
                        ? (int) json_decode($featureRequest->proposed_value, true)
                        : (int) ($activeFeatureIds[(int) ($rows->first()->target_record_id ?? 0)] ?? 0);

                    return $featureId > 0 ? [$featureId => $rows] : [];
                });

            $featureGroups->each(function ($group) use ($pendingByFeature, $workflows) {
                $group->features = $group->features
                    ->map(function ($feature) use ($pendingByFeature, $workflows) {
                        $pending = $pendingByFeature->get((int) $feature->feature_id);
                        $feature->is_pending = (bool) $pending;

                        if (! $pending) {
                            return $feature;
                        }

                        foreach ($pending as $item) {
                            $proposed = json_decode($item->proposed_value, true);

                            if ($item->target_field === 'status') {
                                $feature->status = (string) $proposed;
                            } elseif ($item->target_field === 'metadata') {
                                $feature->metadata = is_string($proposed)
                                    ? (json_decode($proposed, true) ?: [])
                                    : (is_array($proposed) ? $proposed : []);
                            } elseif ($item->target_field === 'comment') {
                                $feature->comment = is_array($proposed)
                                    ? ($proposed['note'] ?? null)
                                    : null;
                            }
                        }

                        // Pending user changes must be presented as the user's
                        // proposed value, never as an external fallback.
                        $feature->display_status = $feature->status;
                        $effectiveExternal = collect($feature->external_feature_sources ?? [])->first();
                        $feature->external_feature_conflicts = [];
                        if (
                            $feature->status !== 'unknown'
                            && is_array($effectiveExternal)
                            && ($effectiveExternal['status'] ?? null) !== $feature->status
                        ) {
                            $feature->external_feature_conflicts = [$effectiveExternal];
                        }

                        return $workflows->present($feature);
                    })
                    ->values();

                $group->has_known_features = $group->features
                    ->contains(fn ($feature) => $feature->tone !== 'unknown');
            });
        }

        $profileMark('Pending feature suggestions');

        $vehicleTypes = DB::table('place_vehicle_types as pvt')
            ->join('vehicle_types as vt', 'vt.id', '=', 'pvt.vehicle_type_id')
            ->where('pvt.place_id', $place->id)
            ->where('pvt.is_active', true)
            ->whereNull('pvt.version_valid_until')
            ->where('vt.is_active', true)
            ->orderBy('vt.sort_order')
            ->get(['vt.id', 'vt.slug', 'pvt.capacity']);

        $vehicleLabels = $this->translationLabels(['vehicle_type', 'vehicle_types'], $vehicleTypes->pluck('id'));
        $vehicleTypes->each(function ($vehicle) use ($vehicleLabels) {
            $vehicle->label = $vehicleLabels[(int) $vehicle->id]
                ?? Str::headline(str_replace('-', ' ', $vehicle->slug));
        });

        $contacts = DB::table('place_contacts')
            ->where('place_id', $place->id)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderBy('sort_order')
            ->get(['id', 'contact_type', 'value']);

        $website = $contacts->first(fn ($contact) => in_array($contact->contact_type, ['website', 'url'], true));

        $openingPeriods = $openingHoursPeriods->currentPeriods((int) $place->id);
        $currentOpeningState = $openingStateService->forPlaces(collect([(int) $place->id]))->get((int) $place->id);
        $openingClosureHint = $openingHoursPeriods->currentClosureHint((int) $place->id);
        $profileMark('Vehicle, contacts, opening hours');

        $typeLabels = $this->translationLabels(['place_type', 'place_types'], collect([$place->place_type_id]));
        $place->place_type_label = $typeLabels[(int) $place->place_type_id]
            ?? Str::headline(str_replace('-', ' ', $place->place_type_slug));
        $profileMark('Place type labels');

        $canFavorite = $permissions->can(auth()->user(), 'favorites.manage_own');
        $isFavorite = $canFavorite
            ? DB::table('place_favorites')
                ->where('user_id', auth()->id())
                ->where('place_id', $place->id)
                ->exists()
            : false;

        $reviewSort = $request->string('sort')->toString();
        if (! in_array($reviewSort, ['newest', 'oldest', 'best', 'worst'], true)) {
            $reviewSort = 'newest';
        }

        $countedFeatureGroups = $featureGroups
            ->filter(fn ($group) => ($group->is_standard ?? false) || ($group->has_known_features ?? false))
            ->values();

        $knownFeatureCount = $countedFeatureGroups->sum(
            fn ($group) => $group->features->filter(fn ($feature) => $feature->tone !== 'unknown')->count()
        );
        $featureTotal = $countedFeatureGroups->sum(fn ($group) => $group->features->count());

        $basicInfoChecks = [
            filled($place->place_type_label),
            filled($details?->operator_name),
            $details?->pitch_count !== null,
            $details?->minimum_stay_nights !== null,
            $details?->pitch_area_min_m2 !== null,
            filled($place->city) || filled($place->street),
            filled($website?->value),
            $vehicleTypes->isNotEmpty(),
            $place->opening_status !== 'unclear',
        ];

        $descriptionChecks = [
            filled($translation?->description),
            filled($translation?->directions),
            filled($translation?->access_information),
        ];

        $reviewSummary = $reviewService->summaryForPlace((int) $place->id);

        $placeReviews = $reviewService->reviewsForPlace((int) $place->id, $reviewSort, 5);
        $reviewHistory = $reviewService->monthlyHistory((int) $place->id, 24);
        $placeHistory = $historyPresenter->forPlace((int) $place->id);
        $lastContentUpdateAt = $placeHistory->max(fn ($history) => $history->approved_at ?: $history->submitted_at);
        $lastContentUpdateAt = $lastContentUpdateAt
            ? \Illuminate\Support\Carbon::parse($lastContentUpdateAt)
            : null;
        $placeInformationMayBeOutdated = $lastContentUpdateAt?->lt(now()->subYear()) ?? false;
        $dataScore = $dataScores->scoreForPlace((int) $place->id);
        $profileMark('Place history and DataScore');

        $profileMark('Review summary, list, history');
        $currentUserReview = auth()->check()
            ? $reviewService->currentForUser((int) $place->id, (int) auth()->id())
            : null;
        $reviewPhotos = $photoService->publicPhotosForReviews(
            $placeReviews->getCollection()->pluck('review_id'),
            auth()->id(),
        );
        $currentUserPhotos = $currentUserReview
            ? $photoService->photosForOwnerReview((int) $currentUserReview->review_id, (int) auth()->id())
            : collect();
        $placeThumbnail = $photoService->thumbnailsForPlaces(collect([(int) $place->id]))->get((int) $place->id);
        $placeGalleryPhotos = $photoService->publicPhotosForPlace((int) $place->id, auth()->id(), 12);
        $placePhotoSetting = null;

        $reviewPresenters = $reviewService->presentersFor($placeReviews->getCollection(), $request->user());
        $profileMark('Photos and review presentation');

        if ($profileEnabled) {
            $profile['Controller total'] = round((microtime(true) - $profileStarted) * 1000, 2);
        }

        return view('places.show', [
            'place' => $place,
            'translation' => $translation,
            'details' => $details,
            'featureGroups' => $featureGroups,
            'featureCount' => $featureTotal,
            'knownFeatureCount' => $knownFeatureCount,
            'basicInfoCount' => collect($basicInfoChecks)->filter()->count(),
            'basicInfoTotal' => count($basicInfoChecks),
            'descriptionInfoCount' => collect($descriptionChecks)->filter()->count(),
            'descriptionInfoTotal' => count($descriptionChecks),
            'vehicleTypes' => $vehicleTypes,
            'contacts' => $contacts,
            'website' => $website,
            'openingPeriods' => $openingPeriods,
            'currentOpeningState' => $currentOpeningState,
            'openingClosureHint' => $openingClosureHint,
            'canDirectEdit' => $permissions->can(auth()->user(), 'places.edit'),
            'canSuggest' => $permissions->can(auth()->user(), 'places.suggest'),
            'canManagePhotoCovers' => $permissions->can(auth()->user(), 'photos.set_cover'),
            'canPermanentlyDelete' => $permissions->can(auth()->user(), 'places.delete_permanently'),
            'canFavorite' => $canFavorite,
            'isFavorite' => $isFavorite,
            'reviewSummary' => $reviewSummary,
            'placeReviews' => $placeReviews,
            'reviewSort' => $reviewSort,
            'reviewHistory' => $reviewHistory,
            'currentUserReview' => $currentUserReview,
            'reviewPhotos' => $reviewPhotos,
            'currentUserPhotos' => $currentUserPhotos,
            'placeThumbnail' => $placeThumbnail,
            'placeGalleryPhotos' => $placeGalleryPhotos,
            'placePhotoSetting' => $placePhotoSetting,
            'reviewPresenters' => $reviewPresenters,
            'reviewDimensions' => $reviewService->dimensions(),
            'placeHistory' => $placeHistory,
            'lastContentUpdateAt' => $lastContentUpdateAt,
            'placeInformationMayBeOutdated' => $placeInformationMayBeOutdated,
            'dataScore' => $dataScore,
            'profileEnabled' => $profileEnabled,
            'profile' => $profile,
        ]);
    }

    private function translationLabels(array $entityTypes, $entityIds): array
    {
        $entityIds = collect($entityIds)->filter()->unique()->values();

        if ($entityIds->isEmpty()) {
            return [];
        }

        $locale = app()->getLocale();
        $fallback = LocaleConfiguration::fallback();

        $rows = DB::table('translations')
            ->whereIn('entity_type', $entityTypes)
            ->whereIn('entity_id', $entityIds)
            ->where('field', 'name')
            ->where('is_active', true)
            ->whereIn('locale', array_values(array_unique([$locale, $fallback])))
            ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$locale])
            ->get(['entity_id', 'value']);

        $labels = [];
        foreach ($rows as $row) {
            $id = (int) $row->entity_id;
            if (! array_key_exists($id, $labels)) {
                $labels[$id] = $row->value;
            }
        }

        return $labels;
    }
}
