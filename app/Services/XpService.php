<?php

namespace App\Services;

use App\Models\User;

/**
 * Transitional no-op shim. Favon has no XP/level system.
 * Remove this class once the inherited contribution workflows are rebuilt.
 */
class XpService
{
    public function totalForUser(int $userId): int { return 0; }
    public function summaryForUser(int $userId): array { return []; }
    public function awardUnique(int $userId, string $eventType, string $dedupeKey, int $xp, string $description, ?int $placeId = null, ?string $sourceType = null, ?int $sourceId = null, array $metadata = []): bool { return false; }
    public function awardPlaceField(int $userId, int $placeId, string $fieldKey, int $xp, string $description, ?string $sourceType = null, ?int $sourceId = null, array $metadata = []): bool { return false; }
    public function awardPhoto(int $userId, int $placeId, int $photoId, string $placeName): bool { return false; }
    public function awardReview(int $userId, int $placeId, int $reviewId, string $placeName, bool $detailed): void {}
    public function awardRatingDimension(int $userId, int $placeId, string $dimensionKey, string $dimensionLabel, string $placeName): bool { return false; }
    public function awardPhotoHelpful(int $photoOwnerId, int $voterId, int $photoId, int $placeId, string $placeName): bool { return false; }
    public function awardModerationAction(int $userId, string $actionId, int $xp, string $description, ?int $placeId = null, array $metadata = []): bool { return false; }
    public function awardManual(User $recipient, int $xp, string $reason, ?User $awardedBy = null, array $metadata = []): int { return 0; }
    public function awardApprovedPlaceChange(object $request, int $resultRecordId, string $placeName): void {}
    public function awardInitialPlaceSnapshot(int $userId, int $placeId, string $placeName): void {}
    public function localizedDescription(object $entry): string { return ''; }
}
