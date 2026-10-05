<?php

namespace App\Services;

use App\Models\User;

/**
 * Transitional no-op shim. Favon has no badges or achievements.
 * Remove this class once inherited contribution workflows no longer reference it.
 */
class BadgeService
{
    public function recordProgress(int $userId, string $badgeSlug, string $contributionKey, ?int $placeId = null, array $metadata = [], bool $recordActivity = true): bool { return false; }
    public function deactivateProgress(int $userId, string $badgeSlug, string $contributionKey): bool { return false; }
    public function recordActivity(int $userId, string $source): void {}
    public function recordApprovedPlaceChange(object $request, int $resultRecordId, string $placeName): void {}
    public function recordReview(int $userId, int $placeId, int $reviewId): void {}
    public function recordPhotoAccepted(int $userId, int $placeId, int $photoId): bool { return false; }
    public function recordPhotoDeleted(int $userId, int $photoId): bool { return false; }
    public function recordCommunityReview(int $userId, string $actionId, ?int $placeId = null): bool { return false; }
    public function unlockAchievement(int $userId, string $slug): bool { return false; }
    public function grantManualBadge(User $recipient, int $badgeId, ?string $comment, User $awardedBy): bool { return false; }
    public function revokeManualBadge(User $recipient, int $badgeId, User $revokedBy): bool { return false; }
    public function selectableTitles(int $userId): array { return []; }
    public function titleForUser(int $userId): ?array { return null; }
    public function profileSummary(int $userId, bool $isOwner): array { return []; }
    public function syncAutomaticAchievements(int $userId): array { return []; }
}
