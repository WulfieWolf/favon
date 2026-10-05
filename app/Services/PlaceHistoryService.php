<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlaceHistoryService
{
    public function addUser(int $placeId, User $user, string $action, string $summary, array $metadata = [], bool $public = true): int
    {
        return $this->insert($placeId, 'user', $action, $summary, $metadata, $public, $user->id, null);
    }

    public function addSystem(int $placeId, string $action, string $summary, array $metadata = [], bool $public = true): int
    {
        return $this->insert($placeId, 'system', $action, $summary, $metadata, $public, null, null);
    }

    public function addExternalSource(int $placeId, int $sourceId, string $action, string $summary, array $metadata = [], bool $public = true): int
    {
        return $this->insert($placeId, 'external_source', $action, $summary, $metadata, $public, null, $sourceId);
    }

    private function insert(
        int $placeId,
        string $actorType,
        string $action,
        string $summary,
        array $metadata,
        bool $public,
        ?int $userId,
        ?int $sourceId,
    ): int {
        $historyId = (int) DB::table('place_history')->insertGetId([
            'place_id' => $placeId,
            'user_id' => $userId,
            'external_source_id' => $sourceId,
            'actor_type' => $actorType,
            'action' => $action,
            'summary' => $summary,
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_public' => $public,
            'created_at' => now(),
        ]);

        app(PlaceDataScoreService::class)->markDirty($placeId);

        return $historyId;
    }
}
