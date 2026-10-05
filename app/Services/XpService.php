<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class XpService
{
    public function totalForUser(int $userId): int
    {
        return (int) DB::table('xp_ledger')
            ->where('user_id', $userId)
            ->sum('xp');
    }

    public function summaryForUser(int $userId): array
    {
        return app(LevelService::class)->summary($this->totalForUser($userId));
    }

    public function awardUnique(
        int $userId,
        string $eventType,
        string $dedupeKey,
        int $xp,
        string $description,
        ?int $placeId = null,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $actionKey = null,
        array $metadata = [],
        ?int $awardedBy = null,
    ): bool {
        if ($xp === 0) {
            return false;
        }

        $beforeTotal = $this->totalForUser($userId);
        $inserted = DB::table('xp_ledger')->insertOrIgnore([
            'user_id' => $userId,
            'event_type' => $eventType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'place_id' => $placeId,
            'action_key' => $actionKey,
            'dedupe_key' => $dedupeKey,
            'xp' => $xp,
            'description' => $description,
            'rule_version' => (string) config('xp.rule_version', 'v1'),
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'awarded_by' => $awardedBy,
            'created_at' => now(),
        ]) > 0;

        if ($inserted && $xp > 0) {
            $this->notifyLevelUpIfNeeded($userId, $beforeTotal, $beforeTotal + $xp);
        }

        return $inserted;
    }

    public function awardPlaceField(
        int $userId,
        int $placeId,
        string $fieldKey,
        int $xp,
        string $description,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): bool {
        return $this->awardUnique(
            $userId,
            'place_info',
            "place-info:{$userId}:{$placeId}:{$fieldKey}",
            $xp,
            $description,
            $placeId,
            $sourceType,
            $sourceId,
            $fieldKey,
        );
    }

    public function awardPhoto(int $userId, int $placeId, int $photoId, string $placeName): bool
    {
        $alreadyRewarded = DB::table('xp_ledger')
            ->where('user_id', $userId)
            ->where('place_id', $placeId)
            ->where('event_type', 'photo_upload')
            ->where('xp', '>', 0)
            ->count();

        if ($alreadyRewarded >= (int) config('xp.photos.max_rewarded_per_user_place', 5)) {
            return false;
        }

        return $this->awardUnique(
            $userId,
            'photo_upload',
            "photo-upload:{$userId}:{$placeId}:{$photoId}",
            (int) config('xp.photos.xp', 1),
            $this->storedDescription($userId, 'photo_upload', ['place' => $placeName]),
            $placeId,
            'photo',
            $photoId,
            'photo',
        );
    }

    public function awardReview(int $userId, int $placeId, int $reviewId, string $placeName, bool $detailed): void
    {
        $this->awardUnique(
            $userId,
            'review',
            "review-base:{$userId}:{$placeId}",
            (int) config('xp.reviews.base_xp', 2),
            $this->storedDescription($userId, 'review', ['place' => $placeName]),
            $placeId,
            'review',
            $reviewId,
            'review',
        );

        if ($detailed) {
            $this->awardUnique(
                $userId,
                'review_detailed_bonus',
                "review-detailed:{$userId}:{$placeId}",
                max(0, (int) config('xp.reviews.detailed_total_xp', 4) - (int) config('xp.reviews.base_xp', 2)),
                $this->storedDescription($userId, 'review_detailed', ['place' => $placeName]),
                $placeId,
                'review',
                $reviewId,
                'review_detailed',
            );
        }
    }

    public function awardRatingDimension(
        int $userId,
        int $placeId,
        string $dimensionKey,
        string $dimensionLabel,
        string $placeName,
    ): bool {
        return $this->awardUnique(
            $userId,
            'rating_dimension',
            "rating:{$userId}:{$placeId}:{$dimensionKey}",
            (int) config('xp.ratings.xp_per_dimension', 1),
            $this->storedRatingDescription($userId, $dimensionKey, $dimensionLabel, $placeName),
            $placeId,
            'place',
            $placeId,
            "rating:{$dimensionKey}",
        );
    }

    public function awardPhotoHelpful(
        int $photoOwnerId,
        int $voterId,
        int $photoId,
        int $placeId,
        string $placeName,
    ): bool {
        if ($photoOwnerId === $voterId) {
            return false;
        }

        $rewarded = DB::table('xp_ledger')
            ->where('user_id', $photoOwnerId)
            ->where('source_type', 'photo')
            ->where('source_id', $photoId)
            ->where('event_type', 'photo_helpful')
            ->where('xp', '>', 0)
            ->count();

        if ($rewarded >= (int) config('xp.photos.max_helpful_xp_per_photo', 10)) {
            return false;
        }

        return $this->awardUnique(
            $photoOwnerId,
            'photo_helpful',
            "photo-helpful:{$photoId}:{$voterId}",
            (int) config('xp.photos.helpful_xp', 1),
            $this->storedDescription($photoOwnerId, 'photo_helpful', ['place' => $placeName]),
            $placeId,
            'photo',
            $photoId,
            'helpful',
            ['voter_id' => $voterId],
        );
    }

    public function awardModerationAction(
        int $userId,
        string $actionId,
        int $xp,
        string $description,
        ?int $placeId = null,
        array $metadata = [],
    ): bool {
        return $this->awardUnique(
            $userId,
            'community_moderation',
            "moderation:{$userId}:{$actionId}",
            $xp,
            $description,
            $placeId,
            'moderation',
            null,
            $actionId,
            $metadata,
        );
    }

    public function awardManual(User $recipient, int $xp, string $reason, ?User $awardedBy = null, array $metadata = []): int
    {
        $beforeTotal = $this->totalForUser((int) $recipient->id);
        $id = (int) DB::table('xp_ledger')->insertGetId([
            'user_id' => $recipient->id,
            'event_type' => 'manual_award',
            'source_type' => null,
            'source_id' => null,
            'place_id' => null,
            'action_key' => 'manual',
            'dedupe_key' => null,
            'xp' => $xp,
            'description' => $reason,
            'rule_version' => (string) config('xp.rule_version', 'v1'),
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'awarded_by' => $awardedBy?->id,
            'created_at' => now(),
        ]);

        if ($xp > 0) {
            $this->notifyLevelUpIfNeeded((int) $recipient->id, $beforeTotal, $beforeTotal + $xp);
        }

        return $id;
    }

    public function awardApprovedPlaceChange(object $request, int $resultRecordId, string $placeName): void
    {
        $userId = (int) ($request->submitted_by ?? 0);
        $placeId = (int) ($request->place_id ?? 0);

        if ($userId < 1 || $placeId < 1) {
            return;
        }

        if ($request->target_table === 'places' && $request->target_field === 'publication_status') {
            $proposed = json_decode((string) $request->proposed_value, true);

            if ($proposed === 'published') {
                $this->awardUnique(
                    $userId,
                    'place_created',
                    "place-created:{$userId}:{$placeId}",
                    (int) config('xp.places.new_place_xp', 5),
                    $this->storedDescription($userId, 'place_created', ['place' => $placeName]),
                    $placeId,
                    'place',
                    $placeId,
                    'place_created',
                );

                $this->awardInitialPlaceSnapshot($userId, $placeId, $placeName);
            }

            return;
        }

        if (
            $request->target_table === 'opening_hours'
            && $request->target_field === 'period_schedule'
            && ! $this->openingHoursProposalContainsInformation($request->proposed_value)
        ) {
            return;
        }

        if (
            $request->target_table === 'place_price_offers'
            && $request->target_field === 'period_pricing'
        ) {
            $this->awardPlaceField(
                $userId,
                $placeId,
                'price-offer:'.$resultRecordId,
                (int) config('xp.place_info.default_xp', 1),
                $this->storedDescription($userId, 'price_updated', ['place' => $placeName]),
                'change_request',
                (int) $request->id,
            );

            return;
        }

        if ($request->target_table === 'place_features') {
            $featureId = DB::table('place_features')
                ->where('id', $resultRecordId)
                ->value('feature_id');

            if ($featureId) {
                $this->awardPlaceField(
                    $userId,
                    $placeId,
                    'feature:'.$featureId,
                    (int) config('xp.place_info.default_xp', 1),
                    $this->storedDescription($userId, 'feature_updated', ['place' => $placeName]),
                    'change_request',
                    (int) $request->id,
                );
            }

            return;
        }

        $fieldKey = $request->target_table.'.'.$request->target_field;
        $longTextFields = (array) config('xp.place_info.long_text_fields', []);
        $xp = in_array($fieldKey, $longTextFields, true)
            ? (int) config('xp.place_info.long_text_xp', 2)
            : (int) config('xp.place_info.default_xp', 1);

        $label = $this->storedFieldLabel($userId, $fieldKey);

        $this->awardPlaceField(
            $userId,
            $placeId,
            $fieldKey,
            $xp,
            $this->storedDescription($userId, 'field_updated', ['field' => $label, 'place' => $placeName]),
            'change_request',
            (int) $request->id,
        );
    }

    public function awardInitialPlaceSnapshot(int $userId, int $placeId, string $placeName): void
    {
        $address = DB::table('place_addresses')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first();

        if ($address) {
            foreach (['postal_code', 'city', 'street', 'house_number', 'address_addition'] as $field) {
                if (filled($address->{$field} ?? null)) {
                    $key = 'place_addresses.'.$field;
                    $this->awardPlaceField(
                        $userId,
                        $placeId,
                        $key,
                        (int) config('xp.place_info.default_xp', 1),
                        $this->storedDescription($userId, 'field_added', ['field' => $this->storedFieldLabel($userId, $key), 'place' => $placeName]),
                        'place',
                        $placeId,
                    );
                }
            }
        }

        $translation = DB::table('place_translations')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderBy('id')
            ->first();

        if ($translation) {
            foreach (['description', 'directions', 'access_information'] as $field) {
                if (filled($translation->{$field} ?? null)) {
                    $key = 'place_translations.'.$field;
                    $this->awardPlaceField(
                        $userId,
                        $placeId,
                        $key,
                        (int) config('xp.place_info.long_text_xp', 2),
                        $this->storedDescription($userId, 'field_added', ['field' => $this->storedFieldLabel($userId, $key), 'place' => $placeName]),
                        'place',
                        $placeId,
                    );
                }
            }
        }

        $details = DB::table('place_details')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first();

        if ($details) {
            $detailFields = [
                'operator_name' => filled($details->operator_name ?? null),
                'pitch_count' => ($details->pitch_count ?? null) !== null,
            ];

            foreach ($detailFields as $field => $hasValue) {
                if ($hasValue) {
                    $key = 'place_details.'.$field;
                    $this->awardPlaceField(
                        $userId,
                        $placeId,
                        $key,
                        1,
                        $this->storedDescription($userId, 'field_added', ['field' => $this->storedFieldLabel($userId, $key), 'place' => $placeName]),
                        'place',
                        $placeId,
                    );
                }
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

            $decodedMetadata = $feature->metadata ? json_decode((string) $feature->metadata, true) : null;
            $hasMetadata = is_array($decodedMetadata) ? $decodedMetadata !== [] : filled($feature->metadata);
            $hasStatus = filled($feature->status) && $feature->status !== 'unknown';

            if ($hasStatus || $hasMetadata || $hasNote) {
                $this->awardPlaceField(
                    $userId,
                    $placeId,
                    'feature:'.$feature->feature_id,
                    (int) config('xp.place_info.default_xp', 1),
                    $this->storedDescription($userId, 'feature_added', ['place' => $placeName]),
                    'place_feature',
                    (int) $feature->id,
                );
            }
        }
    }

    public function localizedDescription(object $entry): string
    {
        $placeName = filled($entry->place_name ?? null)
            ? (string) $entry->place_name
            : __('community_profile.place_fallback');
        $actionKey = (string) ($entry->action_key ?? '');
        $eventType = (string) ($entry->event_type ?? '');

        if ($eventType === 'rating_dimension' && str_starts_with($actionKey, 'rating:')) {
            $dimension = Str::after($actionKey, 'rating:');
            $translationKey = 'reviews.dimensions.'.$dimension.'.label';
            $dimensionLabel = Lang::has($translationKey)
                ? __($translationKey)
                : Str::headline($dimension);

            return __('community_profile.xp_entries.rating_dimension', [
                'dimension' => $dimensionLabel,
                'place' => $placeName,
            ]);
        }

        $translationKey = match ($eventType) {
            'photo_upload' => 'photo_upload',
            'review' => 'review',
            'review_detailed_bonus' => 'review_detailed',
            'photo_helpful' => 'photo_helpful',
            'place_created' => 'place_created',
            default => null,
        };

        if ($translationKey) {
            return __('community_profile.xp_entries.'.$translationKey, ['place' => $placeName]);
        }

        if ($eventType === 'place_info') {
            $isInitial = in_array((string) ($entry->source_type ?? ''), ['place', 'place_feature'], true);

            if (str_starts_with($actionKey, 'feature:')) {
                return __('community_profile.xp_entries.'.($isInitial ? 'feature_added' : 'feature_updated'), [
                    'place' => $placeName,
                ]);
            }

            if (str_starts_with($actionKey, 'price-offer:')) {
                return __('community_profile.xp_entries.price_updated', ['place' => $placeName]);
            }

            $fieldKey = 'community_profile.xp_fields.'.str_replace('.', '_', $actionKey);
            $field = Lang::has($fieldKey) ? __($fieldKey) : __('community_profile.xp_fields.information');

            return __('community_profile.xp_entries.'.($isInitial ? 'field_added' : 'field_updated'), [
                'field' => $field,
                'place' => $placeName,
            ]);
        }

        return (string) ($entry->description ?? '');
    }

    private function notifyLevelUpIfNeeded(int $userId, int $beforeTotal, int $afterTotal): void
    {
        $levels = app(LevelService::class);
        $beforeLevel = (int) ($levels->summary($beforeTotal)['level'] ?? 1);
        $afterLevel = (int) ($levels->summary($afterTotal)['level'] ?? 1);

        if ($afterLevel <= $beforeLevel) {
            return;
        }

        $showGamification = DB::table('user_settings')
            ->where('user_id', $userId)
            ->value('show_gamification');

        if ($showGamification !== null && ! (bool) $showGamification) {
            return;
        }

        $notifications = app(UserNotificationService::class);
        $locale = $notifications->userLocale($userId);
        $phrases = __('notifications.level_up_phrases', [], $locale);
        $phrases = is_array($phrases) && $phrases !== []
            ? array_values($phrases)
            : [__('notifications.level_up_fallback', [], $locale)];
        $phrase = $phrases[array_rand($phrases)];
        $message = __('notifications.level_up_message', [
            'level' => $afterLevel,
            'phrase' => $phrase,
        ], $locale);

        $profile = DB::table('user_profiles')
            ->where('user_id', $userId)
            ->first(['public_alias', 'public_handle']);
        $handle = $profile?->public_alias ?: $profile?->public_handle;
        $url = $handle ? route('users.profile', $handle) : route('notifications.index');

        $recent = DB::table('user_notifications')
            ->where('user_id', $userId)
            ->where('type', 'level_up')
            ->where('created_at', '>=', now()->subSeconds(10))
            ->orderByDesc('id')
            ->first(['id']);

        if ($recent) {
            DB::table('user_notifications')->where('id', $recent->id)->update([
                'title' => __('notifications.level_up_title', ['level' => $afterLevel], $locale),
                'message' => $message,
                'url' => $url,
                'metadata' => json_encode(['level' => $afterLevel], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'available_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        $notifications->createImmediate(
            $userId,
            'level_up',
            __('notifications.level_up_title', ['level' => $afterLevel], $locale),
            $message,
            $url,
            'normal',
            'sparkles',
            ['level' => $afterLevel],
        );
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

    private function storedDescription(int $userId, string $entryKey, array $replace = []): string
    {
        return Lang::get(
            'community_profile.xp_entries.'.$entryKey,
            $replace,
            $this->userLocale($userId),
        );
    }

    private function storedRatingDescription(
        int $userId,
        string $dimensionKey,
        string $fallbackLabel,
        string $placeName,
    ): string {
        $locale = $this->userLocale($userId);
        $dimensionTranslationKey = 'reviews.dimensions.'.$dimensionKey.'.label';
        $dimensionLabel = Lang::has($dimensionTranslationKey, $locale)
            ? Lang::get($dimensionTranslationKey, [], $locale)
            : $fallbackLabel;

        return Lang::get('community_profile.xp_entries.rating_dimension', [
            'dimension' => $dimensionLabel,
            'place' => $placeName,
        ], $locale);
    }

    private function storedFieldLabel(int $userId, string $fieldKey): string
    {
        $translationKey = 'community_profile.xp_fields.'.str_replace('.', '_', $fieldKey);
        $locale = $this->userLocale($userId);

        return Lang::has($translationKey, $locale)
            ? Lang::get($translationKey, [], $locale)
            : Lang::get('community_profile.xp_fields.information', [], $locale);
    }

    private function userLocale(int $userId): string
    {
        $locale = DB::table('users')->where('id', $userId)->value('locale');

        return is_string($locale) && $locale !== ''
            ? $locale
            : (string) config('app.fallback_locale', 'de');
    }
}
