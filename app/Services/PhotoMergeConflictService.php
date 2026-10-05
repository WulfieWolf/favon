<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PhotoMergeConflictService
{
    public function forUser(User $user, ?int $conflictId = null)
    {
        $conflicts = DB::table('photo_merge_conflicts as pmc')
            ->join('places as pl', 'pl.id', '=', 'pmc.place_id')
            ->where('pmc.user_id', $user->id)
            ->where('pmc.status', 'selection_required')
            ->when($conflictId, fn ($query) => $query->where('pmc.id', $conflictId))
            ->orderByDesc('pmc.created_at')
            ->get(['pmc.id', 'pmc.place_id', 'pmc.photo_limit', 'pmc.created_at', 'pl.name as place_name', 'pl.slug as place_slug']);

        $ids = $conflicts->pluck('id');
        $photos = $ids->isEmpty() ? collect() : DB::table('photo_merge_conflict_items as pmci')
            ->join('photo_merge_conflicts as pmc', 'pmc.id', '=', 'pmci.photo_merge_conflict_id')
            ->join('photos as p', 'p.id', '=', 'pmci.photo_id')
            ->leftJoin('place_photos as pp', 'pp.photo_id', '=', 'p.id')
            ->whereIn('pmci.photo_merge_conflict_id', $ids)
            ->where('p.user_id', $user->id)
            ->where('p.is_active', true)
            ->where('pp.is_active', true)
            ->orderBy('p.created_at')
            ->get([
                'pmci.photo_merge_conflict_id as conflict_id', 'p.id', 'p.uuid', 'p.status',
                'p.width', 'p.height', 'p.file_size', 'p.created_at',
                DB::raw('(SELECT COUNT(*) FROM photo_helpful_votes phv WHERE phv.photo_id = p.id) as helpful_count'),
            ])->groupBy('conflict_id');

        return $conflicts->map(function ($conflict) use ($photos) {
            $conflict->photos = $photos->get($conflict->id, collect());
            $conflict->remaining_to_remove = max(0, $conflict->photos->count() - (int) $conflict->photo_limit);

            return $conflict;
        });
    }

    public function assertUploadsAllowed(int $userId, int $placeId): void
    {
        if (DB::table('photo_merge_conflicts')->where('user_id', $userId)->where('place_id', $placeId)->where('status', 'selection_required')->exists()) {
            throw new RuntimeException(__('place_profile.photo_upload_blocked_by_merge'));
        }
    }

    public function resolveEligibleForPhoto(int $photoId): void
    {
        $conflictIds = DB::table('photo_merge_conflict_items')->where('photo_id', $photoId)->pluck('photo_merge_conflict_id');
        foreach ($conflictIds as $conflictId) {
            $this->resolveIfEligible((int) $conflictId);
        }
    }

    public function resolveIfEligible(int $conflictId): bool
    {
        $conflict = DB::table('photo_merge_conflicts')->where('id', $conflictId)->lockForUpdate()->first();
        if (! $conflict || $conflict->status !== 'selection_required') {
            return false;
        }
        $activeCount = DB::table('photo_merge_conflict_items as pmci')
            ->join('photos as p', 'p.id', '=', 'pmci.photo_id')
            ->join('place_photos as pp', 'pp.photo_id', '=', 'p.id')
            ->where('pmci.photo_merge_conflict_id', $conflictId)
            ->where('p.is_active', true)->where('pp.is_active', true)
            ->whereIn('p.status', ['processing', 'pending', 'approved'])
            ->count();
        if ($activeCount > (int) $conflict->photo_limit) {
            return false;
        }
        DB::table('photo_merge_conflicts')->where('id', $conflictId)->update([
            'status' => 'resolved', 'resolved_at' => now(), 'updated_at' => now(),
        ]);

        return true;
    }
}
