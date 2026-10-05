<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class PhotoDeletionService
{
    public function deactivate(
        int $photoId,
        string $status = 'removed',
        ?int $actorId = null,
        ?string $reason = null,
    ): bool {
        $photo = DB::table('photos')->where('id', $photoId)->lockForUpdate()->first();
        if (! $photo) {
            return false;
        }

        $this->deleteFiles($photo);
        $this->clearReferences($photoId);

        DB::table('place_photos')->where('photo_id', $photoId)->update([
            'is_active' => false,
            'is_thumbnail_eligible' => false,
            'updated_at' => now(),
        ]);

        DB::table('photos')->where('id', $photoId)->update([
            'status' => $status,
            'is_active' => false,
            'moderated_by' => $actorId,
            'moderated_at' => now(),
            'moderation_reason' => $reason,
            'source_path' => null,
            'storage_path' => 'deleted',
            'preview_path' => null,
            'file_size' => null,
            'preview_file_size' => null,
            'width' => null,
            'height' => null,
            'mime_type' => null,
            'original_filename' => null,
            'processing_error' => null,
            'updated_at' => now(),
        ]);

        return true;
    }

    public function purgeUnusedProfilePhoto(int $photoId, int $userId): bool
    {
        $photo = DB::table('photos')
            ->where('id', $photoId)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if (! $photo || DB::table('place_photos')->where('photo_id', $photoId)->exists()) {
            return false;
        }

        $this->deleteFiles($photo);
        DB::table('users')->where('profile_photo_id', $photoId)->update(['profile_photo_id' => null]);
        DB::table('photos')->where('id', $photoId)->delete();

        return true;
    }

    public function deleteForPlace(int $placeId, ?int $actorId, string $reason): array
    {
        $photoIds = DB::table('place_photos')
            ->where('place_id', $placeId)
            ->pluck('photo_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $deleted = 0;
        $shared = 0;

        foreach ($photoIds as $photoId) {
            $usedElsewhere = DB::table('place_photos')
                ->where('photo_id', $photoId)
                ->where('place_id', '!=', $placeId)
                ->where('is_active', true)
                ->exists();

            if ($usedElsewhere) {
                DB::table('place_photos')->where('place_id', $placeId)->where('photo_id', $photoId)->delete();
                $shared++;
                continue;
            }

            if ($this->deactivate($photoId, 'deleted', $actorId, $reason)) {
                $deleted++;
            }
        }

        return ['deleted' => $deleted, 'shared_detached' => $shared, 'photo_ids' => $photoIds->all()];
    }

    private function clearReferences(int $photoId): void
    {
        DB::table('users')->where('profile_photo_id', $photoId)->update(['profile_photo_id' => null]);

        if (! Schema::hasTable('place_photo_settings')) {
            return;
        }

        DB::table('place_photo_settings')->where('fallback_photo_id', $photoId)->update([
            'fallback_photo_id' => null,
            'updated_at' => now(),
        ]);
        DB::table('place_photo_settings')->where('vote_photo_id', $photoId)->update([
            'vote_photo_id' => null,
            'updated_at' => now(),
        ]);
        DB::table('place_photo_settings')->where('admin_photo_id', $photoId)->update([
            'admin_photo_id' => null,
            'admin_selected_by' => null,
            'admin_selected_at' => null,
            'updated_at' => now(),
        ]);
    }

    private function deleteFiles(object $photo): void
    {
        Storage::disk('local')->delete(array_values(array_filter([
            $photo->source_path ?? null,
            ($photo->storage_path ?? null) !== 'deleted' ? ($photo->storage_path ?? null) : null,
            $photo->preview_path ?? null,
        ])));
    }
}
