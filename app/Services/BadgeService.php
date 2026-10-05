<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    private function tierLabel(string $tier): string
    {
        $key = 'community_profile.tier_labels.'.$tier;
        $translated = __($key);

        return $translated === $key ? ucfirst($tier) : $translated;
    }

    public function recordProgress(
        int $userId,
        string $badgeSlug,
        string $contributionKey,
        ?int $placeId = null,
        array $metadata = [],
        bool $recordActivity = true,
    ): bool {
        $badge = $this->badgeBySlug($badgeSlug);

        if (! $badge || $badge->type !== 'progress') {
            return false;
        }

        $inserted = DB::table('badge_progress_events')->insertOrIgnore([
            'user_id' => $userId,
            'badge_id' => $badge->id,
            'place_id' => $placeId,
            'contribution_key' => $contributionKey,
            'is_active' => true,
            'metadata' => $metadata === [] ? null : $this->json($metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]) > 0;

        if (! $inserted) {
            return false;
        }

        $this->unlockReachedTiers($userId, (int) $badge->id);

        if ($recordActivity) {
            $this->recordActivity($userId, 'badge_progress');
        }

        return true;
    }

    public function deactivateProgress(
        int $userId,
        string $badgeSlug,
        string $contributionKey,
    ): bool {
        $badge = $this->badgeBySlug($badgeSlug);

        if (! $badge || $badge->type !== 'progress') {
            return false;
        }

        return DB::table('badge_progress_events')
            ->where('user_id', $userId)
            ->where('badge_id', $badge->id)
            ->where('contribution_key', $contributionKey)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]) > 0;
    }

    public function recordActivity(int $userId, string $source): void
    {
        DB::table('user_activity_days')->insertOrIgnore([
            'user_id' => $userId,
            'activity_date' => now()->toDateString(),
            'source' => $source,
            'created_at' => now(),
        ]);

        $this->syncAutomaticAchievements($userId);
    }

    public function recordApprovedPlaceChange(object $request, int $resultRecordId, string $placeName): void
    {
        $userId = (int) $request->submitted_by;
        $placeId = (int) $request->place_id;

        if ($request->target_table === 'places' && $request->target_field === 'publication_status') {
            $proposed = $this->decode($request->proposed_value);

            if ($proposed === 'published') {
                $this->recordProgress(
                    $userId,
                    'explorer',
                    'place:'.$placeId,
                    $placeId,
                    ['place_name' => $placeName],
                );
                $this->recordInitialPlaceInformation($userId, $placeId, $placeName);
                $this->unlockAchievement($userId, 'first-find');
                $this->unlockAchievement($userId, 'first-steps');
                $this->syncAutomaticAchievements($userId);
            }

            return;
        }

        $infoKey = $this->informationKey($request, $resultRecordId);
        if ($infoKey === null) {
            return;
        }

        $contributionKey = 'place:'.$placeId.':info:'.$infoKey;

        $pathfinder = $this->badgeBySlug('pathfinder');
        $alreadyContributedAtCreation = $pathfinder
            ? DB::table('badge_progress_events')
                ->where('user_id', $userId)
                ->where('badge_id', $pathfinder->id)
                ->where('contribution_key', $contributionKey)
                ->exists()
            : false;

        if ($alreadyContributedAtCreation) {
            return;
        }

        $inserted = $this->recordProgress(
            $userId,
            'sleuth',
            $contributionKey,
            $placeId,
            [
                'place_name' => $placeName,
                'field' => $infoKey,
            ],
        );

        if ($inserted) {
            $this->unlockAchievement($userId, 'eagle-eye');
            $this->unlockAchievement($userId, 'first-steps');
            $this->syncAutomaticAchievements($userId);
        }
    }

    public function recordReview(int $userId, int $placeId, int $reviewId): void
    {
        if ($this->recordProgress($userId, 'connoisseur', 'place:'.$placeId, $placeId, ['review_id' => $reviewId])) {
            $this->unlockAchievement($userId, 'first-opinion');
            $this->unlockAchievement($userId, 'first-steps');
        }
    }

    public function recordPhotoAccepted(int $userId, int $placeId, int $photoId): bool
    {
        $badge = $this->badgeBySlug('photographer');
        if (! $badge) {
            return false;
        }

        $activeForPlace = DB::table('badge_progress_events')
            ->where('user_id', $userId)
            ->where('badge_id', $badge->id)
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->count();

        if ($activeForPlace >= 5) {
            return false;
        }

        $inserted = $this->recordProgress(
            $userId,
            'photographer',
            'photo:'.$photoId,
            $placeId,
            ['photo_id' => $photoId],
        );

        if ($inserted) {
            $this->unlockAchievement($userId, 'first-photo');
            $this->unlockAchievement($userId, 'first-steps');
        }

        return $inserted;
    }

    public function recordPhotoDeleted(int $userId, int $photoId): bool
    {
        return $this->deactivateProgress($userId, 'photographer', 'photo:'.$photoId);
    }

    public function recordCommunityReview(int $userId, string $actionId, ?int $placeId = null): bool
    {
        return $this->recordProgress(
            $userId,
            'community-helper',
            'moderation:'.$actionId,
            $placeId,
            ['action_id' => $actionId],
        );
    }

    public function unlockAchievement(int $userId, string $slug): bool
    {
        $badge = $this->badgeBySlug($slug);

        if (! $badge || $badge->type !== 'achievement' || ! $badge->is_active) {
            return false;
        }

        $unlockKey = $this->unlockKey($userId, (int) $badge->id, null);

        $inserted = DB::table('user_badge_unlocks')->insertOrIgnore([
            'user_id' => $userId,
            'badge_id' => $badge->id,
            'tier' => null,
            'unlock_key' => $unlockKey,
            'award_comment' => null,
            'awarded_by' => null,
            'unlocked_at' => now(),
            'revoked_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]) > 0;

        if ($inserted) {
            $this->awardBadgeXp($userId, $badge, 'achievement');
            $this->notifyBadgeUnlock($userId, $badge, null, 'achievement');
            $this->forgetRarityCache();
        }

        return $inserted;
    }

    public function grantManualBadge(User $recipient, int $badgeId, ?string $comment, User $awardedBy): bool
    {
        $badge = DB::table('badge_definitions')
            ->where('id', $badgeId)
            ->where('type', 'manual')
            ->where('is_active', true)
            ->first();

        if (! $badge) {
            return false;
        }

        $unlockKey = $this->unlockKey((int) $recipient->id, (int) $badge->id, null);
        $existing = DB::table('user_badge_unlocks')->where('unlock_key', $unlockKey)->first();

        if ($existing && $existing->revoked_at === null) {
            DB::table('user_badge_unlocks')->where('id', $existing->id)->update([
                'award_comment' => filled($comment) ? trim((string) $comment) : null,
                'updated_at' => now(),
            ]);

            return false;
        }

        if ($existing) {
            DB::table('user_badge_unlocks')->where('id', $existing->id)->update([
                'award_comment' => filled($comment) ? trim((string) $comment) : null,
                'awarded_by' => $awardedBy->id,
                'unlocked_at' => now(),
                'revoked_at' => null,
                'updated_at' => now(),
            ]);
            $inserted = true;
        } else {
            $inserted = DB::table('user_badge_unlocks')->insertOrIgnore([
                'user_id' => $recipient->id,
                'badge_id' => $badge->id,
                'tier' => null,
                'unlock_key' => $unlockKey,
                'award_comment' => filled($comment) ? trim((string) $comment) : null,
                'awarded_by' => $awardedBy->id,
                'unlocked_at' => now(),
                'revoked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]) > 0;
        }

        if ($inserted) {
            $this->awardBadgeXp((int) $recipient->id, $badge, 'manual_badge');
            $this->notifyBadgeUnlock((int) $recipient->id, $badge, null, 'manual');
            $this->forgetRarityCache();
        }

        return $inserted;
    }

    public function revokeManualBadge(User $recipient, int $badgeId, User $revokedBy): bool
    {
        $badge = DB::table('badge_definitions')
            ->where('id', $badgeId)
            ->where('type', 'manual')
            ->first();

        if (! $badge) {
            return false;
        }

        $unlock = DB::table('user_badge_unlocks')
            ->where('user_id', $recipient->id)
            ->where('badge_id', $badgeId)
            ->whereNull('revoked_at')
            ->first();

        if (! $unlock) {
            return false;
        }

        DB::transaction(function () use ($recipient, $badgeId, $badge, $unlock, $revokedBy): void {
            DB::table('user_badge_unlocks')->where('id', $unlock->id)->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('user_profiles')
                ->where('user_id', $recipient->id)
                ->where('selected_badge_id', $badgeId)
                ->update(['selected_badge_id' => null]);

            if ((int) $badge->xp_reward !== 0) {
                DB::table('xp_ledger')->insertOrIgnore([
                    'user_id' => $recipient->id,
                    'event_type' => 'badge_reversal',
                    'source_type' => 'badge',
                    'source_id' => $badge->id,
                    'place_id' => null,
                    'action_key' => 'badge:'.$badge->slug,
                    'dedupe_key' => 'badge-reversal:'.$unlock->id,
                    'xp' => -abs((int) $badge->xp_reward),
                    'description' => 'Auszeichnung „'.$badge->name.'“ entzogen',
                    'rule_version' => (string) config('xp.rule_version', 'v1'),
                    'metadata' => $this->json(['revoked_by' => $revokedBy->id]),
                    'awarded_by' => $revokedBy->id,
                    'created_at' => now(),
                ]);
            }
        });

        $this->forgetRarityCache();

        return true;
    }

    public function selectableTitles(int $userId): array
    {
        $badges = DB::table('badge_definitions')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');

        $unlocks = DB::table('user_badge_unlocks')
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->orderBy('unlocked_at')
            ->get()
            ->groupBy('badge_id');

        $options = [];

        foreach ($unlocks as $badgeId => $rows) {
            $badge = $badges->get($badgeId);
            if (! $badge || ($badge->type === 'achievement' && $badge->is_hidden)) {
                continue;
            }

            if ($badge->type === 'progress') {
                $tier = $this->highestTierFromUnlocks($rows);
                if (! $tier) {
                    continue;
                }

                $options[(int) $badgeId] = $badge->name.' '.$this->tierLabel($tier);
            } else {
                $options[(int) $badgeId] = $badge->name;
            }
        }

        return $options;
    }

    public function titleForUser(int $userId): ?array
    {
        $selectedBadgeId = DB::table('user_profiles')
            ->where('user_id', $userId)
            ->value('selected_badge_id');

        if (! $selectedBadgeId) {
            return null;
        }

        $badge = DB::table('badge_definitions')
            ->where('id', $selectedBadgeId)
            ->where('is_active', true)
            ->first();

        if (! $badge || ($badge->type === 'achievement' && $badge->is_hidden)) {
            return null;
        }

        $unlocks = DB::table('user_badge_unlocks')
            ->where('user_id', $userId)
            ->where('badge_id', $badge->id)
            ->whereNull('revoked_at')
            ->get();

        if ($unlocks->isEmpty()) {
            return null;
        }

        $tier = $badge->type === 'progress' ? $this->highestTierFromUnlocks($unlocks) : null;
        $label = $tier
            ? $badge->name.' '.$this->tierLabel($tier)
            : $badge->name;

        return [
            'badge_id' => (int) $badge->id,
            'slug' => $badge->slug,
            'label' => $label,
            'name' => $badge->name,
            'tier' => $tier,
        ];
    }

    public function profileSummary(int $userId, bool $isOwner): array
    {
        $metrics = $this->syncAutomaticAchievements($userId);

        $definitions = DB::table('badge_definitions')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $tiers = DB::table('badge_tiers')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('badge_id');

        $progressCounts = DB::table('badge_progress_events')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->select('badge_id', DB::raw('COUNT(*) as total'))
            ->groupBy('badge_id')
            ->pluck('total', 'badge_id');

        $unlocks = DB::table('user_badge_unlocks')
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->orderBy('unlocked_at')
            ->get()
            ->groupBy('badge_id');

        $rarity = $this->rarityPercentages();

        $progressBadges = [];
        $achievements = [];
        $manualBadges = [];
        $hiddenTotal = 0;
        $hiddenFound = 0;

        foreach ($definitions as $badge) {
            $badgeUnlocks = $unlocks->get($badge->id, collect());

            if ($badge->type === 'progress') {
                $count = (int) ($progressCounts[$badge->id] ?? 0);
                $badgeTiers = $tiers->get($badge->id, collect());
                $highest = $this->highestTierFromUnlocks($badgeUnlocks);
                $nextTier = $badgeTiers->first(fn ($tier) => $tier->threshold > $count);

                $progressBadges[] = [
                    'id' => (int) $badge->id,
                    'slug' => $badge->slug,
                    'name' => $badge->name,
                    'description' => $badge->description,
                    'icon' => $badge->icon,
                    'count' => $count,
                    'tier' => $highest,
                    'tier_label' => $highest ? $this->tierLabel($highest) : null,
                    'next_tier' => $nextTier?->tier,
                    'next_tier_label' => $nextTier ? $this->tierLabel($nextTier->tier) : null,
                    'next_threshold' => $nextTier?->threshold,
                    'unlocked_at' => $highest
                        ? optional($badgeUnlocks->firstWhere('tier', $highest))->unlocked_at
                        : null,
                ];

                continue;
            }

            $activeUnlock = $badgeUnlocks->first();

            if ($badge->is_hidden) {
                $hiddenTotal++;

                if ($activeUnlock) {
                    $hiddenFound++;
                }

                if (! $isOwner || ! $activeUnlock) {
                    continue;
                }
            }

            if ($badge->type === 'achievement') {
                $progress = $this->achievementProgress($badge, $metrics);

                $achievements[] = [
                    'id' => (int) $badge->id,
                    'slug' => $badge->slug,
                    'name' => $badge->name,
                    'description' => $badge->description,
                    'icon' => $badge->icon,
                    'is_hidden' => (bool) $badge->is_hidden,
                    'unlocked' => $activeUnlock !== null,
                    'unlocked_at' => $activeUnlock?->unlocked_at,
                    'xp_reward' => (int) $badge->xp_reward,
                    'progress' => $progress,
                    'target' => $badge->target_value !== null ? (int) $badge->target_value : null,
                    'rarity' => $rarity[$badge->id] ?? 0.0,
                ];

                continue;
            }

            if ($badge->type === 'manual' && $activeUnlock) {
                $manualBadges[] = [
                    'id' => (int) $badge->id,
                    'slug' => $badge->slug,
                    'name' => $badge->name,
                    'description' => $badge->description,
                    'icon' => $badge->icon,
                    'unlocked_at' => $activeUnlock->unlocked_at,
                    'award_comment' => $activeUnlock->award_comment,
                    'xp_reward' => (int) $badge->xp_reward,
                    'rarity' => $rarity[$badge->id] ?? 0.0,
                ];
            }
        }

        return [
            'progress_badges' => $progressBadges,
            'achievements' => $achievements,
            'manual_badges' => $manualBadges,
            'hidden_found' => $hiddenFound,
            'hidden_total' => $hiddenTotal,
            'selected_title' => $this->titleForUser($userId),
        ];
    }

    public function syncAutomaticAchievements(int $userId): array
    {
        $metrics = $this->automaticMetricSnapshot($userId);

        if ($metrics === []) {
            return [];
        }

        foreach ([10, 50, 500, 1000] as $limit) {
            if ($metrics['registration_rank'] <= $limit) {
                $this->unlockAchievement($userId, 'og-'.$limit);
            }
        }

        if ($metrics['account_age_days'] >= 365) {
            $this->unlockAchievement($userId, 'one-year');
        }

        foreach ([10, 25, 50, 100] as $target) {
            if ($metrics['level'] >= $target) {
                $this->unlockAchievement($userId, 'level-'.$target);
            }
        }

        if ($metrics['activity_streak_max'] >= 7) {
            $this->unlockAchievement($userId, 'streak-7');
        }
        if ($metrics['activity_streak_max'] >= 30) {
            $this->unlockAchievement($userId, 'streak-30');
        }

        if ($metrics['active_month_streak_max'] >= 12) {
            $this->unlockAchievement($userId, 'month-loyal');
        }

        if ($metrics['countries_contributed'] >= 3) {
            $this->unlockAchievement($userId, 'border-crosser');
        }
        if ($metrics['countries_contributed'] >= 10) {
            $this->unlockAchievement($userId, 'globetrotter');
        }

        return $metrics;
    }

    private function recordInitialPlaceInformation(int $userId, int $placeId, string $placeName): void
    {
        $keys = [];

        $address = DB::table('place_addresses')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first();

        if ($address) {
            foreach (['postal_code', 'city', 'street', 'house_number', 'address_addition'] as $field) {
                if (filled($address->{$field} ?? null)) {
                    $keys[] = 'place_addresses.'.$field;
                }
            }
        }

        $translation = DB::table('place_translations')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first();

        if ($translation) {
            foreach (['description', 'directions', 'access_information'] as $field) {
                if (filled($translation->{$field} ?? null)) {
                    $keys[] = 'place_translations.'.$field;
                }
            }
        }

        $details = DB::table('place_details')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first();

        if ($details) {
            if (filled($details->operator_name ?? null)) {
                $keys[] = 'place_details.operator_name';
            }
            if (($details->pitch_count ?? null) !== null) {
                $keys[] = 'place_details.pitch_count';
            }
        }

        $features = DB::table('place_features')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('valid_until')
            ->get(['id', 'feature_id', 'status', 'metadata']);

        foreach ($features as $feature) {
            $hasNote = DB::table('place_feature_notes')
                ->where('place_feature_id', $feature->id)
                ->where('is_active', true)
                ->whereNotNull('note')
                ->where('note', '!=', '')
                ->exists();

            $metadata = $feature->metadata ? json_decode((string) $feature->metadata, true) : null;
            $hasMetadata = is_array($metadata) ? $metadata !== [] : filled($feature->metadata);
            $hasStatus = filled($feature->status) && $feature->status !== 'unknown';

            if ($hasStatus || $hasMetadata || $hasNote) {
                $keys[] = 'feature:'.$feature->feature_id;
            }
        }

        foreach (array_unique($keys) as $key) {
            $this->recordProgress(
                $userId,
                'pathfinder',
                'place:'.$placeId.':info:'.$key,
                $placeId,
                [
                    'place_name' => $placeName,
                    'field' => $key,
                ],
                false,
            );
        }

        if ($keys !== []) {
            $this->recordActivity($userId, 'initial_place_information');
        }
    }

    private function informationKey(object $request, int $resultRecordId): ?string
    {
        if (
            $request->target_table === 'opening_hours'
            && $request->target_field === 'period_schedule'
            && ! $this->openingHoursProposalContainsInformation($request->proposed_value)
        ) {
            return null;
        }

        if (
            $request->target_table === 'place_price_offers'
            && $request->target_field === 'period_pricing'
        ) {
            return 'price-offer:'.$resultRecordId;
        }

        if ($request->target_table === 'place_features') {
            $featureId = DB::table('place_features')->where('id', $resultRecordId)->value('feature_id');

            return $featureId ? 'feature:'.$featureId : null;
        }

        if (! in_array($request->target_table, [
            'places',
            'place_addresses',
            'place_translations',
            'place_contacts',
            'place_details',
            'place_vehicle_types',
            'opening_hours',
            'place_price_offers',
        ], true)) {
            return null;
        }

        return $request->target_table.'.'.$request->target_field;
    }

    private function openingHoursProposalContainsInformation(?string $proposedValue): bool
    {
        $payload = json_decode((string) $proposedValue, true);
        $schedule = is_array($payload) ? ($payload['schedule'] ?? null) : null;

        if (! is_array($schedule)) {
            return false;
        }

        foreach ($schedule as $day) {
            if (is_array($day) && ($day['mode'] ?? 'unknown') !== 'unknown') {
                return true;
            }
        }

        return false;
    }

    private function unlockReachedTiers(int $userId, int $badgeId): void
    {
        $count = (int) DB::table('badge_progress_events')
            ->where('user_id', $userId)
            ->where('badge_id', $badgeId)
            ->where('is_active', true)
            ->count();

        $tiers = DB::table('badge_tiers')
            ->where('badge_id', $badgeId)
            ->where('threshold', '<=', $count)
            ->orderBy('sort_order')
            ->get();

        foreach ($tiers as $tier) {
            $inserted = DB::table('user_badge_unlocks')->insertOrIgnore([
                'user_id' => $userId,
                'badge_id' => $badgeId,
                'tier' => $tier->tier,
                'unlock_key' => $this->unlockKey($userId, $badgeId, $tier->tier),
                'award_comment' => null,
                'awarded_by' => null,
                'unlocked_at' => now(),
                'revoked_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]) > 0;

            if ($inserted) {
                $this->notifyBadgeUnlock($userId, $this->badgeById($badgeId), (string) $tier->tier, 'progress');
                $this->forgetRarityCache();
            }
        }
    }

    private function achievementProgress(object $badge, array $metrics): ?int
    {
        if (! $badge->progress_metric || $badge->target_value === null || $metrics === []) {
            return null;
        }

        $value = match ($badge->progress_metric) {
            'activity_streak' => $metrics['activity_streak_current'],
            'active_month_streak' => $metrics['active_month_streak_current'],
            'account_age_days' => $metrics['account_age_days'],
            'countries_contributed' => $metrics['countries_contributed'],
            'level' => $metrics['level'],
            default => null,
        };

        return $value === null ? null : min((int) $badge->target_value, (int) $value);
    }

    private function automaticMetricSnapshot(int $userId): array
    {
        $user = DB::table('users')->where('id', $userId)->first(['id', 'created_at']);
        if (! $user) {
            return [];
        }

        $streaks = $this->activityStreakStats($userId);
        $monthStreaks = $this->activeMonthStreakStats($userId);

        return [
            // The auto-increment user ID is the stable registration order. Unlike a live COUNT,
            // it does not move backwards when older accounts are deleted later.
            'registration_rank' => $userId,
            'account_age_days' => (int) Carbon::parse($user->created_at)->diffInDays(now()),
            'level' => (int) app(XpService::class)->summaryForUser($userId)['level'],
            'activity_streak_current' => (int) $streaks['current'],
            'activity_streak_max' => (int) $streaks['max'],
            'active_month_streak_current' => (int) $monthStreaks['current'],
            'active_month_streak_max' => (int) $monthStreaks['max'],
            'countries_contributed' => $this->contributedCountryCount($userId),
        ];
    }

    private function activityStreakStats(int $userId): array
    {
        $dates = DB::table('user_activity_days')
            ->where('user_id', $userId)
            ->orderBy('activity_date')
            ->pluck('activity_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfDay());

        if ($dates->isEmpty()) {
            return ['current' => 0, 'max' => 0];
        }

        $max = 1;
        $run = 1;
        for ($i = 1; $i < $dates->count(); $i++) {
            if ($dates[$i - 1]->copy()->addDay()->equalTo($dates[$i])) {
                $run++;
                $max = max($max, $run);
            } else {
                $run = 1;
            }
        }

        $last = $dates->last();
        $current = ($last->isToday() || $last->isYesterday()) ? $run : 0;

        return ['current' => $current, 'max' => $max];
    }

    private function activeMonthStreakStats(int $userId): array
    {
        $months = DB::table('user_activity_days')
            ->where('user_id', $userId)
            ->orderBy('activity_date')
            ->pluck('activity_date')
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth()->toDateString())
            ->unique()
            ->values()
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth());

        if ($months->isEmpty()) {
            return ['current' => 0, 'max' => 0];
        }

        $max = 1;
        $run = 1;
        for ($i = 1; $i < $months->count(); $i++) {
            if ($months[$i - 1]->copy()->addMonth()->equalTo($months[$i])) {
                $run++;
                $max = max($max, $run);
            } else {
                $run = 1;
            }
        }

        $last = $months->last();
        $now = now()->startOfMonth();
        $current = ($last->equalTo($now) || $last->copy()->addMonth()->equalTo($now)) ? $run : 0;

        return ['current' => $current, 'max' => $max];
    }

    private function contributedCountryCount(int $userId): int
    {
        return (int) DB::table('badge_progress_events as bpe')
            ->join('place_addresses as pa', function ($join): void {
                $join->on('pa.place_id', '=', 'bpe.place_id')
                    ->where('pa.is_active', true)
                    ->whereNull('pa.version_valid_until');
            })
            ->where('bpe.user_id', $userId)
            ->where('bpe.is_active', true)
            ->whereNotNull('pa.country_code')
            ->distinct()
            ->count('pa.country_code');
    }

    private function rarityPercentages(): array
    {
        return Cache::remember('badge-rarity-percentages-v1', now()->addMinutes(10), function (): array {
            $userCount = max(1, (int) DB::table('users')->count());

            return DB::table('user_badge_unlocks')
                ->whereNull('revoked_at')
                ->select('badge_id', DB::raw('COUNT(DISTINCT user_id) as users_with_badge'))
                ->groupBy('badge_id')
                ->get()
                ->mapWithKeys(fn ($row) => [
                    (int) $row->badge_id => round(((int) $row->users_with_badge / $userCount) * 100, 1),
                ])
                ->all();
        });
    }

    private function awardBadgeXp(int $userId, object $badge, string $eventType): void
    {
        $xp = (int) $badge->xp_reward;
        if ($xp === 0) {
            return;
        }

        DB::table('xp_ledger')->insertOrIgnore([
            'user_id' => $userId,
            'event_type' => $eventType,
            'source_type' => 'badge',
            'source_id' => $badge->id,
            'place_id' => null,
            'action_key' => 'badge:'.$badge->slug,
            'dedupe_key' => 'badge-xp:'.$userId.':'.$badge->id,
            'xp' => $xp,
            'description' => ($badge->type === 'achievement' ? 'Achievement' : 'Auszeichnung').' „'.$badge->name.'“ erhalten',
            'rule_version' => (string) config('xp.rule_version', 'v1'),
            'metadata' => null,
            'awarded_by' => null,
            'created_at' => now(),
        ]);
    }

    private function highestTierFromUnlocks(Collection $unlocks): ?string
    {
        $order = ['bronze' => 10, 'silver' => 20, 'gold' => 30, 'platinum' => 40];

        return $unlocks
            ->whereNotNull('tier')
            ->sortByDesc(fn ($unlock) => $order[$unlock->tier] ?? 0)
            ->first()?->tier;
    }

    private function notifyBadgeUnlock(int $userId, ?object $badge, ?string $tier, string $kind): void
    {
        if (! $badge) {
            return;
        }

        $notifications = app(UserNotificationService::class);
        $locale = $notifications->userLocale($userId);
        $tierLabel = $tier ? $this->tierLabel($tier) : null;
        $titleKey = match ($kind) {
            'achievement' => 'badge_achievement_title',
            'manual' => 'badge_manual_title',
            default => 'badge_tier_title',
        };
        $messageKey = match ($kind) {
            'achievement' => 'badge_achievement_message',
            'manual' => 'badge_manual_message',
            default => 'badge_tier_message',
        };

        $notifications->createImmediate(
            $userId,
            'badge_unlocked',
            __('notifications.'.$titleKey, [], $locale),
            __('notifications.'.$messageKey, ['badge' => $badge->name, 'tier' => $tierLabel], $locale),
            $this->profileUrl($userId),
            'normal',
            (string) ($badge->icon ?: 'award'),
            ['badge_id' => (int) $badge->id, 'badge_slug' => $badge->slug, 'tier' => $tier, 'kind' => $kind],
        );
    }

    private function profileUrl(int $userId): ?string
    {
        $handle = DB::table('user_profiles')->where('user_id', $userId)->value('public_handle');

        return $handle ? route('users.profile', $handle) : null;
    }

    private function badgeById(int $badgeId): ?object
    {
        return DB::table('badge_definitions')->where('id', $badgeId)->where('is_active', true)->first();
    }

    private function badgeBySlug(string $slug): ?object
    {
        return DB::table('badge_definitions')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    private function unlockKey(int $userId, int $badgeId, ?string $tier): string
    {
        return $userId.':'.$badgeId.':'.($tier ?: 'base');
    }

    private function decode(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return json_decode((string) $value, true);
    }

    private function json(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function forgetRarityCache(): void
    {
        Cache::forget('badge-rarity-percentages-v1');
    }
}
