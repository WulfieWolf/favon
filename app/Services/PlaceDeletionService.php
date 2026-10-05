<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PlaceDeletionService
{
    public const REASONS = [
        'operator_request',
        'wrong_place',
        'duplicate_or_false_entry',
        'import_false_positive',
        'manual_block',
        'other',
    ];

    public function delete(User $actor, int $placeId, string $reason, ?string $note = null): array
    {
        if (! in_array($reason, self::REASONS, true)) {
            throw new RuntimeException('Invalid place deletion reason.');
        }

        return DB::transaction(function () use ($actor, $placeId, $reason, $note): array {
            $place = DB::table('places')->where('id', $placeId)->lockForUpdate()->first();

            if (! $place) {
                throw new RuntimeException('Place not found.');
            }
            if ($place->deleted_at) {
                throw new RuntimeException('Place is already deleted.');
            }
            $this->deleteNotifications($placeId);
            $this->deleteRelatedAudits($placeId);
            $this->deletePlaceRelations($placeId);

            DB::table('audit_logs')
                ->where('entity_type', 'place')
                ->where('entity_id', $placeId)
                ->delete();

            DB::table('abuse_flags')
                ->where('entity_type', 'place')
                ->where('entity_id', $placeId)
                ->delete();

            DB::table('places')->where('id', $placeId)->update([
                'name' => null,
                'slug' => null,
                'publication_status' => 'deleted',
                'legal_status' => 'unclear',
                'opening_status' => 'unclear',
                'is_active' => false,
                'internal_comment' => null,
                'created_by' => null,
                'approved_by' => null,
                'approved_at' => null,
                'deleted_at' => now(),
                'deleted_by' => $actor->id,
                'deletion_reason' => $reason,
                'deletion_note' => filled($note) ? trim((string) $note) : null,
                'updated_at' => now(),
            ]);

            DB::table('audit_logs')->insert([
                'user_id' => $actor->id,
                'entity_type' => 'place_tombstone',
                'entity_id' => $placeId,
                'action' => 'place_permanently_deleted',
                'source' => 'admin',
                'old_values' => null,
                'new_values' => json_encode([
                    'place_id' => $placeId,
                    'place_type_id' => (int) $place->place_type_id,
                    'latitude' => (float) $place->latitude,
                    'longitude' => (float) $place->longitude,
                    'deletion_reason' => $reason,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'internal_comment' => filled($note) ? trim((string) $note) : null,
                'created_at' => now(),
            ]);

            return ['place_id' => $placeId];
        }, 3);
    }

    private function deleteNotifications(int $placeId): void
    {
        if (! Schema::hasTable('notification_events')) {
            return;
        }

        $notificationIds = DB::table('notification_events')
            ->where('place_id', $placeId)
            ->whereNotNull('notification_id')
            ->pluck('notification_id');

        DB::table('notification_events')->where('place_id', $placeId)->delete();

        if (Schema::hasTable('user_notifications') && $notificationIds->isNotEmpty()) {
            DB::table('user_notifications')->whereIn('id', $notificationIds)->delete();
        }
    }

    private function deleteRelatedAudits(int $placeId): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $scopes = [];

        if (Schema::hasTable('change_requests')) {
            $scopes['change_request'] = DB::table('change_requests')->where('place_id', $placeId)->pluck('id');
        }
        if (Schema::hasTable('place_features')) {
            $scopes['place_feature'] = DB::table('place_features')->where('place_id', $placeId)->pluck('id');
        }
        if (Schema::hasTable('place_merges')) {
            $scopes['place_merge'] = DB::table('place_merges')
                ->where('source_place_id', $placeId)
                ->orWhere('target_place_id', $placeId)
                ->pluck('id');
        }

        foreach ($scopes as $entityType => $ids) {
            if ($ids->isNotEmpty()) {
                DB::table('audit_logs')
                    ->where('entity_type', $entityType)
                    ->whereIn('entity_id', $ids)
                    ->delete();
            }
        }
    }

    private function deletePlaceRelations(int $placeId): void
    {
        if (Schema::hasTable('place_merges')) {
            DB::table('place_merges')
                ->where('source_place_id', $placeId)
                ->orWhere('target_place_id', $placeId)
                ->delete();
        }

        if (Schema::hasTable('external_import_review_items')) {
            DB::table('external_import_review_items')->where('place_id', $placeId)->delete();
        }

        foreach ([
            'place_photo_settings',
            'place_photos',
            'place_favorites',
            'place_history',
            'place_data_sources',
            'change_requests',
            'place_reviews',
            'place_price_offers',
            'place_prices',
            'opening_hours',
            'opening_hour_exceptions',
            'opening_hour_periods',
            'place_notes',
            'place_vehicle_types',
            'place_features',
            'place_contacts',
            'place_details',
            'place_addresses',
            'place_translations',
        ] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'place_id')) {
                DB::table($table)->where('place_id', $placeId)->delete();
            }
        }
    }
}
