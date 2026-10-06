<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlaceHistoryService
{
    public function addUser(int $placeId, User $user, string $action, string $summary, array $metadata = [], bool $public = true): int
    {
        return $this->insert($placeId, 'user', $action, $summary, $metadata, $public, $user->id);
    }

    public function addSystem(int $placeId, string $action, string $summary, array $metadata = [], bool $public = true): int
    {
        return $this->insert($placeId, 'system', $action, $summary, $metadata, $public, null);
    }

    private function insert(
        int $placeId,
        string $actorType,
        string $action,
        string $summary,
        array $metadata,
        bool $public,
        ?int $userId,
    ): int {
        return (int) DB::table('place_history')->insertGetId([
            'place_id' => $placeId,
            'user_id' => $userId,
            'actor_type' => $actorType,
            'action' => $action,
            'summary' => $summary,
            'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'is_public' => $public,
            'created_at' => now(),
        ]);
    }
}
