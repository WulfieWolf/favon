<?php

namespace App\Http\Controllers;

use App\Support\LocalTime;

use App\Services\AdminDebugService;
use App\Services\BadgeService;
use App\Services\CountryService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function __invoke(
        Request $request,
        string $handle,
        CountryService $countryService,
        XpService $xpService,
        BadgeService $badgeService,
        AdminDebugService $debug,
    ): View {
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

        $record = DB::table('user_profiles as up')
            ->join('users as u', 'u.id', '=', 'up.user_id')
            ->where('u.account_status', 'active')
            ->where(function ($query) use ($handle) {
                $query->where('up.public_handle', $handle)
                    ->orWhere('up.public_alias', $handle);
            })
            ->first([
                'u.id as user_id',
                'u.created_at as joined_at',
                'u.profile_photo_id',
                'up.public_handle',
                'up.public_alias',
                'up.alias_finalized_at',
                'up.handle_finalized_at',
                'up.bio',
                'up.hometown_city',
                'up.hometown_country_code',
                'up.birth_date',
                'up.gender',
                'up.gender_custom',
                'up.vehicle_type',
                'up.vehicle_details',
            ]);

        abort_unless($record, 404);
        $profileMark('Profile lookup');

        $settings = DB::table('user_settings')
            ->where('user_id', $record->user_id)
            ->first();

        $isOwner = $request->user()?->id === $record->user_id;
        $isRegistered = $request->user() !== null;
        $profileMark('Settings and visibility');

        $canSee = static function (?string $visibility) use ($isOwner, $isRegistered): bool {
            if ($isOwner) {
                return true;
            }

            return match ($visibility ?? 'private') {
                'public' => true,
                'registered' => $isRegistered,
                default => false,
            };
        };

        $profilePhotoUrl = null;
        if ($record->profile_photo_id && $canSee($settings?->profile_photo_visibility ?? 'public')) {
            $hasActivePhoto = DB::table('photos')
                ->where('id', $record->profile_photo_id)
                ->where('is_active', true)
                ->exists();

            if ($hasActivePhoto) {
                $profilePhotoUrl = route('users.profile.photo', $record->public_alias ?: $record->public_handle);
            }
        }

        $age = null;
        if ($record->birth_date && $canSee($settings?->age_visibility ?? 'private')) {
            $age = Carbon::parse($record->birth_date)->age;
        }

        $showGamification = (bool) ($settings?->show_gamification ?? true);
        $gamification = null;
        $badgeSummary = null;
        $xpEntries = null;

        if ($showGamification) {
            $badgeSummary = $badgeService->profileSummary((int) $record->user_id, $isOwner);
            $profileMark('Badge summary');
            $gamification = $xpService->summaryForUser((int) $record->user_id);
            $profileMark('XP summary');

            $rawXpEntries = DB::table('xp_ledger as x')
                ->leftJoin('places as p', 'p.id', '=', 'x.place_id')
                ->where('x.user_id', $record->user_id)
                ->orderByDesc('x.created_at')
                ->orderByDesc('x.id')
                ->limit(100)
                ->get([
                    'x.id',
                    'x.event_type',
                    'x.action_key',
                    'x.source_type',
                    'x.xp',
                    'x.description',
                    'x.created_at',
                    'x.place_id',
                    'p.slug as place_slug',
                    'p.name as place_name',
                ]);
            $profileMark('XP ledger');

            $xpEntries = $rawXpEntries
                ->groupBy(function (object $entry): string {
                    $minute = Carbon::parse($entry->created_at)->format('YmdHi');
                    $placeId = $entry->place_id ?? 0;

                    if (str_starts_with((string) $entry->action_key, 'feature:')) {
                        return 'features|'.$placeId.'|'.$minute;
                    }

                    if ($entry->event_type === 'place_info') {
                        return 'place_info|'.$placeId.'|'.$minute;
                    }

                    if (in_array($entry->event_type, [
                        'photo_upload',
                        'rating_dimension',
                        'photo_helpful',
                        'community_moderation',
                    ], true)) {
                        return $entry->event_type.'|'.$placeId.'|'.$minute;
                    }

                    return 'entry|'.$entry->id;
                })
                ->map(function ($group) use ($xpService): object {
                    $first = $group->first();
                    $first->description = $xpService->localizedDescription($first);
                    $count = $group->count();
                    $totalXp = (int) $group->sum('xp');
                    $placeName = $first->place_name ?: __('community_profile.place_fallback');

                    if ($count > 1) {
                        if (str_starts_with((string) $first->action_key, 'feature:')) {
                            $first->description = __('community_profile.xp_group_features', ['count' => $count, 'place' => $placeName]);
                        } elseif ($first->event_type === 'place_info') {
                            $first->description = __('community_profile.xp_group_basic', ['count' => $count, 'place' => $placeName]);
                        } elseif ($first->event_type === 'photo_upload') {
                            $first->description = __('community_profile.xp_group_photos', ['count' => $count, 'place' => $placeName]);
                        } elseif ($first->event_type === 'rating_dimension') {
                            $first->description = __('community_profile.xp_group_ratings', ['count' => $count, 'place' => $placeName]);
                        } elseif ($first->event_type === 'photo_helpful') {
                            $first->description = __('community_profile.xp_group_photo_helpful', ['count' => $count, 'place' => $placeName]);
                        } elseif ($first->event_type === 'community_moderation') {
                            $first->description = __('community_profile.xp_group_moderation', ['count' => $count]);
                        }

                        $first->xp = $totalXp;
                    }

                    $first->created_at_label = LocalTime::translatedFormat($first->created_at, __('community_profile.xp_date_time_format'));

                    return $first;
                })
                ->values();
            $profileMark('XP presentation');
        }

        $hometownCity = null;
        $hometownCountryName = null;
        if ($canSee($settings?->hometown_visibility ?? 'registered')) {
            $hometownCity = $record->hometown_city;
            if ($record->hometown_country_code) {
                $countries = $countryService->all(app()->getLocale());
                $hometownCountryName = $countries[strtoupper($record->hometown_country_code)] ?? strtoupper($record->hometown_country_code);
            }
        }

        $vehicleLabel = null;
        if ($canSee($settings?->vehicle_visibility ?? 'registered') && $record->vehicle_type) {
            $vehicleTypeId = DB::table('vehicle_types')
                ->where('slug', $record->vehicle_type)
                ->value('id');

            if ($vehicleTypeId) {
                $vehicleLabel = DB::table('translations')
                    ->where('entity_type', 'vehicle_type')
                    ->where('entity_id', $vehicleTypeId)
                    ->where('field', 'name')
                    ->where('is_active', true)
                    ->whereIn('locale', [app()->getLocale(), 'en'])
                    ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [app()->getLocale()])
                    ->value('value');
            }
        }

        $profileMark('Country and vehicle labels');
        if ($profileEnabled) {
            $profile['Controller total'] = round((microtime(true) - $profileStarted) * 1000, 2);
        }

        return view('users.profile', [
            'profileUserId' => (int) $record->user_id,
            'handle' => $record->public_alias ?: $record->public_handle,
            'automaticHandle' => $record->public_handle,
            'profilePhotoUrl' => $profilePhotoUrl,
            'bio' => $canSee($settings?->bio_visibility ?? 'registered') ? $record->bio : null,
            'hometownCity' => $hometownCity,
            'hometownCountryName' => $hometownCountryName,
            'age' => $age,
            'gender' => $canSee($settings?->gender_visibility ?? 'private') ? $record->gender : null,
            'genderCustom' => $canSee($settings?->gender_visibility ?? 'private') ? $record->gender_custom : null,
            'vehicleType' => $canSee($settings?->vehicle_visibility ?? 'registered') ? $record->vehicle_type : null,
            'vehicleLabel' => $canSee($settings?->vehicle_visibility ?? 'registered') ? $vehicleLabel : null,
            'vehicleDetails' => $canSee($settings?->vehicle_visibility ?? 'registered') ? $record->vehicle_details : null,
            'joinedAt' => ($settings?->show_join_date ?? true) ? LocalTime::translatedFormat($record->joined_at, 'F Y') : null,
            'isOwner' => $isOwner,
            'showGamification' => $showGamification,
            'gamification' => $gamification,
            'badgeSummary' => $badgeSummary,
            'selectedTitle' => $badgeSummary['selected_title'] ?? null,
            'xpEntries' => $xpEntries,
            'profileEnabled' => $profileEnabled,
            'profile' => $profile,
        ]);
    }
}
