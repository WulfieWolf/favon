<?php

namespace App\Services;

use App\Support\LocaleConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserNotificationService
{
    public function createImmediate(
        ?int $userId,
        string $type,
        string $title,
        string $message,
        ?string $url = null,
        string $priority = 'normal',
        ?string $icon = null,
        ?array $metadata = null,
        ?int $createdBy = null,
        $expiresAt = null,
    ): int {
        return DB::table('user_notifications')->insertGetId([
            'user_id' => $userId,
            'type' => $type,
            'priority' => $priority,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'icon' => $icon,
            'metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'created_by' => $createdBy,
            'available_at' => now(),
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function preferenceEnabled(int $userId, string $preference): bool
    {
        $allowed = ['moderation_decisions', 'favorite_changes', 'general_system'];

        if (! in_array($preference, $allowed, true)) {
            return true;
        }

        $value = DB::table('user_notification_preferences')
            ->where('user_id', $userId)
            ->value($preference);

        return $value === null ? true : (bool) $value;
    }

    public function queueFavoritePlaceChange(
        int $placeId,
        array $excludeUserIds = [],
        string $changeType = 'place_updated',
    ): int {
        $place = DB::table('places')
            ->where('id', $placeId)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->first(['id', 'name', 'slug']);

        if (! $place) {
            return 0;
        }

        $userIds = DB::table('place_favorites')
            ->where('place_id', $placeId)
            ->where('notify_changes', true)
            ->when($excludeUserIds !== [], fn ($query) => $query->whereNotIn('user_id', array_values(array_unique(array_map('intval', $excludeUserIds)))))
            ->pluck('user_id');

        $created = 0;

        foreach ($userIds as $userId) {
            if (! $this->preferenceEnabled((int) $userId, 'favorite_changes')) {
                continue;
            }

            $locale = $this->userLocale((int) $userId);

            $this->queueEvent(
                (int) $userId,
                'favorite_place_changed',
                'favorite:changes',
                __('notifications.favorite_event_title', ['place' => $place->name], $locale),
                __('notifications.favorite_event_message', ['place' => $place->name], $locale),
                route('places.show', $place->slug),
                (int) $place->id,
                [
                    'change_type' => $changeType,
                    'place_name' => $place->name,
                ],
            );
            $created++;
        }

        return $created;
    }

    public function queueEvent(
        int $userId,
        string $type,
        string $clusterKey,
        string $title,
        string $message,
        ?string $url = null,
        ?int $placeId = null,
        ?array $payload = null,
        string $priority = 'normal',
    ): int {
        return DB::table('notification_events')->insertGetId([
            'user_id' => $userId,
            'type' => $type,
            'cluster_key' => $clusterKey,
            'priority' => $priority,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'place_id' => $placeId,
            'payload' => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'occurred_at' => now(),
            'processed_at' => null,
            'notification_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function unreadForUser(int $userId, int $limit = 20): Collection
    {
        return $this->visibleQuery($userId)
            ->whereNotExists(function ($query) use ($userId): void {
                $query->selectRaw('1')
                    ->from('user_notification_reads as unr')
                    ->whereColumn('unr.notification_id', 'un.id')
                    ->where('unr.user_id', $userId);
            })
            ->orderByDesc('un.available_at')
            ->orderByDesc('un.id')
            ->limit($limit)
            ->get($this->selectColumns());
    }

    public function unreadCount(int $userId): int
    {
        return $this->visibleQuery($userId)
            ->whereNotExists(function ($query) use ($userId): void {
                $query->selectRaw('1')
                    ->from('user_notification_reads as unr')
                    ->whereColumn('unr.notification_id', 'un.id')
                    ->where('unr.user_id', $userId);
            })
            ->count();
    }

    public function paginatedForUser(int $userId, int $perPage = 30)
    {
        return $this->visibleQuery($userId)
            ->leftJoin('user_notification_reads as unr', function ($join) use ($userId): void {
                $join->on('unr.notification_id', '=', 'un.id')
                    ->where('unr.user_id', '=', $userId);
            })
            ->orderByDesc('un.available_at')
            ->orderByDesc('un.id')
            ->paginate($perPage, array_merge($this->selectColumns(), ['unr.read_at']))
            ->withQueryString();
    }

    public function findVisible(int $notificationId, int $userId): ?object
    {
        return $this->visibleQuery($userId)
            ->leftJoin('user_notification_reads as unr', function ($join) use ($userId): void {
                $join->on('unr.notification_id', '=', 'un.id')
                    ->where('unr.user_id', '=', $userId);
            })
            ->where('un.id', $notificationId)
            ->first(array_merge($this->selectColumns(), ['unr.read_at']));
    }

    public function eventsForNotification(int $notificationId, int $userId): Collection
    {
        return DB::table('notification_events')
            ->where('notification_id', $notificationId)
            ->where('user_id', $userId)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();
    }

    public function markRead(int $notificationId, int $userId): void
    {
        if (! $this->findVisible($notificationId, $userId)) {
            return;
        }

        DB::table('user_notification_reads')->updateOrInsert(
            ['notification_id' => $notificationId, 'user_id' => $userId],
            ['read_at' => now(), 'updated_at' => now(), 'created_at' => now()],
        );
    }

    public function markAllRead(int $userId): void
    {
        $ids = $this->visibleQuery($userId)
            ->whereNotExists(function ($query) use ($userId): void {
                $query->selectRaw('1')
                    ->from('user_notification_reads as unr')
                    ->whereColumn('unr.notification_id', 'un.id')
                    ->where('unr.user_id', $userId);
            })
            ->pluck('un.id');

        foreach ($ids as $id) {
            DB::table('user_notification_reads')->updateOrInsert(
                ['notification_id' => (int) $id, 'user_id' => $userId],
                ['read_at' => now(), 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    public function clusterDueEvents(): int
    {
        $groups = DB::table('notification_events')
            ->whereNull('processed_at')
            ->select(['user_id', 'cluster_key'])
            ->selectRaw('MIN(occurred_at) as first_occurred_at')
            ->groupBy('user_id', 'cluster_key')
            ->get();

        $created = 0;

        foreach ($groups as $group) {
            $window = str_starts_with($group->cluster_key, 'favorite:')
                ? (int) config('favon_notifications.cluster_minutes.favorite', 30)
                : (int) config('favon_notifications.cluster_minutes.moderation', 10);
            if (now()->diffInMinutes($group->first_occurred_at, false) > -$window) {
                continue;
            }

            DB::transaction(function () use ($group, &$created): void {
                $events = DB::table('notification_events')
                    ->where('user_id', $group->user_id)
                    ->where('cluster_key', $group->cluster_key)
                    ->whereNull('processed_at')
                    ->orderBy('occurred_at')
                    ->lockForUpdate()
                    ->get();

                if ($events->isEmpty()) {
                    return;
                }

                [$title, $message, $type, $icon, $priority] = $this->summarizeCluster(
                    $events,
                    $this->userLocale((int) $group->user_id),
                );

                $directToPlace = str_starts_with($group->cluster_key, 'photo-helpful:')
                    || str_starts_with($group->cluster_key, 'photo-approval:');
                $targetUrl = $directToPlace
                    ? ($events->first()->url ?: route('notifications.index'))
                    : route('notifications.index');

                $notificationId = $this->createImmediate(
                    (int) $group->user_id,
                    $type,
                    $title,
                    $message,
                    $targetUrl,
                    $priority,
                    $icon,
                    ['event_count' => $events->count(), 'cluster_key' => $group->cluster_key],
                );

                DB::table('notification_events')
                    ->whereIn('id', $events->pluck('id'))
                    ->update([
                        'processed_at' => now(),
                        'notification_id' => $notificationId,
                        'updated_at' => now(),
                    ]);

                if (! $directToPlace) {
                    DB::table('user_notifications')
                        ->where('id', $notificationId)
                        ->update(['url' => route('notifications.show', $notificationId)]);
                }

                $created++;
            });
        }

        return $created;
    }

    public function cleanup(): array
    {
        $readIds = DB::table('user_notifications as un')
            ->join('user_notification_reads as unr', 'unr.notification_id', '=', 'un.id')
            ->whereNotNull('un.user_id')
            ->where('un.priority', '!=', 'important')
            ->where('unr.read_at', '<', now()->subDays((int) config('favon_notifications.retention_days.read', 30)))
            ->pluck('un.id')
            ->unique();

        $staleUnreadIds = DB::table('user_notifications as un')
            ->join('users as u', 'u.id', '=', 'un.user_id')
            ->whereNotNull('un.user_id')
            ->where('un.priority', '!=', 'important')
            ->where('un.available_at', '<', now()->subDays((int) config('favon_notifications.retention_days.stale_unread', 180)))
            ->where(function ($query): void {
                $query->whereNull('u.last_seen_at')
                    ->orWhere('u.last_seen_at', '<', now()->subDays((int) config('favon_notifications.retention_days.inactive_user', 90)));
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('user_notification_reads as unr')
                    ->whereColumn('unr.notification_id', 'un.id')
                    ->whereColumn('unr.user_id', 'un.user_id');
            })
            ->pluck('un.id');

        $expiredBroadcastIds = DB::table('user_notifications')
            ->whereNull('user_id')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->pluck('id');

        $ids = $readIds
            ->merge($staleUnreadIds)
            ->merge($expiredBroadcastIds)
            ->unique()
            ->values();

        if ($ids->isNotEmpty()) {
            DB::table('user_notifications')->whereIn('id', $ids)->delete();
        }

        return [
            'read_deleted' => $readIds->count(),
            'stale_unread_deleted' => $staleUnreadIds->count(),
            'expired_broadcast_deleted' => $expiredBroadcastIds->count(),
        ];
    }

    private function summarizeCluster(Collection $events, string $locale): array
    {
        $first = $events->first();

        if ($first->cluster_key === 'moderation:decisions') {
            $approved = 0;
            $rejected = 0;

            foreach ($events as $event) {
                $payload = $event->payload ? json_decode($event->payload, true) : [];
                if (($payload['decision'] ?? null) === 'approved') {
                    $approved++;
                } elseif (($payload['decision'] ?? null) === 'rejected') {
                    $rejected++;
                }
            }

            $parts = [];
            if ($approved > 0) {
                $parts[] = trans_choice('notifications.approved_count', $approved, ['count' => $approved], $locale);
            }
            if ($rejected > 0) {
                $parts[] = trans_choice('notifications.rejected_count', $rejected, ['count' => $rejected], $locale);
            }

            return [
                __('notifications.moderation_digest_title', [], $locale),
                implode(', ', $parts).'. '.__('notifications.digest_details', [], $locale),
                'moderation_digest',
                'bell',
                'normal',
            ];
        }

        if (str_starts_with($first->cluster_key, 'photo-helpful:')) {
            $photoCount = $events
                ->map(fn ($event) => $event->payload ? (json_decode($event->payload, true)['photo_id'] ?? null) : null)
                ->filter()
                ->unique()
                ->count();
            $placeName = (string) ($events
                ->map(fn ($event) => $event->payload ? (json_decode($event->payload, true)['place_name'] ?? null) : null)
                ->filter()
                ->first() ?? '');

            return [
                __('notifications.photo_helpful_title', [], $locale),
                trans_choice('notifications.photo_helpful_grouped_message', max(1, $photoCount), [
                    'count' => max(1, $photoCount),
                    'place' => $placeName,
                ], $locale),
                'photo_helpful_milestone',
                'thumbs-up',
                'normal',
            ];
        }

        if (str_starts_with($first->cluster_key, 'photo-approval:')) {
            $photoCount = $events
                ->map(fn ($event) => $event->payload ? (json_decode($event->payload, true)['photo_id'] ?? null) : null)
                ->filter()
                ->unique()
                ->count();
            $placeName = (string) ($events
                ->map(fn ($event) => $event->payload ? (json_decode($event->payload, true)['place_name'] ?? null) : null)
                ->filter()
                ->first() ?? '');

            return [
                __('notifications.photo_approved_title', [], $locale),
                trans_choice('notifications.photo_approved_grouped_message', max(1, $photoCount), [
                    'count' => max(1, $photoCount),
                    'place' => $placeName,
                ], $locale),
                'photo_moderation',
                'photo',
                'normal',
            ];
        }

        if (str_starts_with($first->cluster_key, 'favorite:')) {
            $placeCount = $events->pluck('place_id')->filter()->unique()->count();
            $eventCount = $events->count();

            $message = $placeCount === 1
                ? ($eventCount === 1
                    ? __('notifications.favorite_digest_one', [], $locale)
                    : __('notifications.favorite_digest_events', ['count' => $eventCount], $locale))
                : __('notifications.favorite_digest_places', ['count' => $placeCount], $locale);

            return [
                __('notifications.favorite_digest_title', [], $locale),
                $message.' '.__('notifications.favorite_digest_details', [], $locale),
                'favorite_digest',
                'bell',
                'normal',
            ];
        }

        return [
            __('notifications.activity_digest_title', [], $locale),
            trans_choice('notifications.activity_digest_message', $events->count(), ['count' => $events->count()], $locale),
            'activity_digest',
            'bell',
            $events->contains(fn ($event) => $event->priority === 'important') ? 'important' : 'normal',
        ];
    }

    public function userLocale(int $userId): string
    {
        $locale = (string) DB::table('users')->where('id', $userId)->value('locale');

        return LocaleConfiguration::supports($locale)
            ? $locale
            : LocaleConfiguration::fallback();
    }

    private function visibleQuery(int $userId)
    {
        return DB::table('user_notifications as un')
            ->where(function ($query) use ($userId): void {
                $query->where('un.user_id', $userId)
                    ->orWhere(function ($broadcast) use ($userId): void {
                        $broadcast->whereNull('un.user_id')
                            ->where(function ($preferenceQuery) use ($userId): void {
                                $preferenceQuery->where('un.priority', 'important')
                                    ->orWhereNotExists(function ($preference) use ($userId): void {
                                        $preference->selectRaw('1')
                                            ->from('user_notification_preferences as unp')
                                            ->where('unp.user_id', $userId)
                                            ->where('unp.general_system', false);
                                    });
                            })
                            ->whereExists(function ($users) use ($userId): void {
                                $users->selectRaw('1')
                                    ->from('users as audience_user')
                                    ->where('audience_user.id', $userId)
                                    ->whereColumn('audience_user.created_at', '<=', 'un.available_at');
                            });
                    });
            })
            ->where('un.available_at', '<=', now())
            ->where(function ($query): void {
                $query->whereNull('un.expires_at')
                    ->orWhere('un.expires_at', '>', now());
            });
    }

    private function selectColumns(): array
    {
        return [
            'un.id',
            'un.user_id',
            'un.type',
            'un.priority',
            'un.title',
            'un.message',
            'un.url',
            'un.icon',
            'un.metadata',
            'un.created_by',
            'un.available_at',
            'un.expires_at',
            'un.created_at',
        ];
    }
}
