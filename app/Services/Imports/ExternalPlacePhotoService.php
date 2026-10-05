<?php

namespace App\Services\Imports;

use App\Jobs\ProcessPhotoUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ExternalPlacePhotoService
{
    public const AUTO_APPROVE_EXTERNAL_COMMENT = 'auto_approve_external_source';

    private const ALLOWED_LICENSES = ['CC0', 'CC-BY', 'CC-BY-SA'];

    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    private const MAX_SOURCE_BYTES = 50 * 1024 * 1024;

    public function inventoryLinkedSource(int $sourceId): array
    {
        $stats = ['records' => 0, 'media' => 0, 'supported' => 0, 'imported' => 0, 'pending' => 0, 'ignored' => 0, 'unsupported' => 0];

        DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active')
            ->whereNotNull('place_id')
            ->orderBy('id')
            ->chunkById(100, function ($records) use ($sourceId, &$stats): void {
                foreach ($records as $record) {
                    $stats['records']++;
                    $normalized = json_decode((string) $record->normalized_data, true);
                    $media = is_array($normalized['media'] ?? null) ? $normalized['media'] : [];

                    foreach ($media as $item) {
                        if (! is_array($item)) {
                            continue;
                        }

                        $stats['media']++;
                        $license = strtoupper(trim((string) ($item['license'] ?? '')));
                        $mediaId = trim((string) ($item['id'] ?? ''));
                        $url = trim((string) ($item['url'] ?? ''));

                        if (! $this->isImportableMedia($item, $mediaId, $url, $license)) {
                            $stats['unsupported']++;
                            continue;
                        }

                        $stats['supported']++;

                        $exists = DB::table('photos')
                            ->where('external_source_id', $sourceId)
                            ->where('external_media_id', $mediaId)
                            ->exists();

                        if ($exists) {
                            $stats['imported']++;
                        } elseif ($this->isTerminalMedia((int) $sourceId, $mediaId)) {
                            $stats['ignored']++;
                        } else {
                            $stats['pending']++;
                        }
                    }
                }
            });

        return $stats;
    }

    public function pendingMedia(?string $sourceSlug = null, int $limit = 60): array
    {
        $limit = max(1, min(200, $limit));
        $sourceSlug = trim((string) $sourceSlug);
        $rows = [];

        $query = DB::table('external_records as er')
            ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
            ->join('places as p', 'p.id', '=', 'er.place_id')
            ->where('er.status', 'active')
            ->whereNotNull('er.place_id')
            ->when($sourceSlug !== '', fn ($builder) => $builder->where('es.slug', $sourceSlug))
            ->select([
                'er.id as record_id',
                'er.external_source_id',
                'er.normalized_data',
                'es.name as source_name',
                'es.slug as source_slug',
                'p.id as place_id',
                'p.name as place_name',
                'p.slug as place_slug',
            ])
            ->orderBy('er.id');

        foreach ($query->cursor() as $record) {
            $normalized = json_decode((string) $record->normalized_data, true);
            $media = is_array($normalized['media'] ?? null) ? $normalized['media'] : [];

            foreach ($media as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $license = strtoupper(trim((string) ($item['license'] ?? '')));
                $mediaId = trim((string) ($item['id'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));

                if (! $this->isImportableMedia($item, $mediaId, $url, $license)) {
                    continue;
                }

                $exists = DB::table('photos')
                    ->where('external_source_id', $record->external_source_id)
                    ->where('external_media_id', $mediaId)
                    ->exists();

                if ($exists || $this->isTerminalMedia((int) $record->external_source_id, $mediaId)) {
                    continue;
                }

                $rows[] = [
                    'key' => $record->record_id.':'.$mediaId,
                    'record_id' => (int) $record->record_id,
                    'media_id' => $mediaId,
                    'url' => $url,
                    'license' => $license,
                    'author' => $item['author'] ?? $item['source'] ?? null,
                    'copyright' => $item['copyright'] ?? null,
                    'width' => $item['width'] ?? null,
                    'height' => $item['height'] ?? null,
                    'place_id' => (int) $record->place_id,
                    'place_name' => (string) $record->place_name,
                    'place_slug' => (string) $record->place_slug,
                    'source_name' => (string) $record->source_name,
                    'source_slug' => (string) $record->source_slug,
                ];

                if (count($rows) >= $limit) {
                    return $rows;
                }
            }
        }

        return $rows;
    }

    public function pendingCount(?string $sourceSlug = null): int
    {
        $sourceSlug = trim((string) $sourceSlug);
        $count = 0;

        $query = DB::table('external_records as er')
            ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
            ->where('er.status', 'active')
            ->whereNotNull('er.place_id')
            ->when($sourceSlug !== '', fn ($builder) => $builder->where('es.slug', $sourceSlug))
            ->select(['er.id', 'er.external_source_id', 'er.normalized_data'])
            ->orderBy('er.id');

        foreach ($query->cursor() as $record) {
            $normalized = json_decode((string) $record->normalized_data, true);
            $media = is_array($normalized['media'] ?? null) ? $normalized['media'] : [];

            foreach ($media as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $license = strtoupper(trim((string) ($item['license'] ?? '')));
                $mediaId = trim((string) ($item['id'] ?? ''));
                $url = trim((string) ($item['url'] ?? ''));

                if (! $this->isImportableMedia($item, $mediaId, $url, $license)) {
                    continue;
                }

                if (! DB::table('photos')
                    ->where('external_source_id', $record->external_source_id)
                    ->where('external_media_id', $mediaId)
                    ->exists()
                    && ! $this->isTerminalMedia((int) $record->external_source_id, $mediaId)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function importSelected(array $keys): array
    {
        $keys = array_values(array_unique(array_filter(array_map('strval', $keys))));
        $stats = ['selected' => count($keys), 'queued' => 0, 'existing' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        foreach ($keys as $key) {
            [$recordId, $mediaId] = array_pad(explode(':', $key, 2), 2, null);

            if (! ctype_digit((string) $recordId) || ! is_string($mediaId) || $mediaId === '') {
                $stats['skipped']++;
                continue;
            }

            $result = $this->syncRecord((int) $recordId, $mediaId);
            foreach (['queued', 'existing', 'skipped', 'failed'] as $name) {
                $stats[$name] += $result[$name];
            }
            $stats['errors'] = array_merge($stats['errors'], $result['errors'] ?? []);
        }

        return $stats;
    }

    public function importNextPendingBatch(?string $sourceSlug = null, int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $items = $this->pendingMedia($sourceSlug, $limit);

        if ($items === []) {
            return ['queued' => 0, 'existing' => 0, 'skipped' => 0, 'failed' => 0, 'remaining' => 0];
        }

        $result = $this->importSelected(array_column($items, 'key'));
        $result['remaining'] = $this->pendingCount($sourceSlug);
        $result['blocked'] = $result['remaining'] > 0
            && (int) ($result['queued'] ?? 0) === 0
            && (int) ($result['existing'] ?? 0) === 0;

        return $result;
    }

    public function syncLinkedSource(int $sourceId): array
    {
        $stats = ['records' => 0, 'queued' => 0, 'existing' => 0, 'skipped' => 0, 'failed' => 0];

        DB::table('external_records')
            ->where('external_source_id', $sourceId)
            ->where('status', 'active')
            ->whereNotNull('place_id')
            ->orderBy('id')
            ->chunkById(50, function ($records) use (&$stats): void {
                foreach ($records as $record) {
                    $stats['records']++;
                    $result = $this->syncRecord((int) $record->id);

                    foreach (['queued', 'existing', 'skipped', 'failed'] as $key) {
                        $stats[$key] += $result[$key];
                    }
                }
            });

        return $stats;
    }

    public function syncRecord(int $recordId, ?string $onlyMediaId = null): array
    {
        $stats = ['queued' => 0, 'existing' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        $record = DB::table('external_records as er')
            ->join('external_sources as es', 'es.id', '=', 'er.external_source_id')
            ->where('er.id', $recordId)
            ->where('er.status', 'active')
            ->whereNotNull('er.place_id')
            ->first([
                'er.id', 'er.place_id', 'er.external_source_id', 'er.normalized_data',
                'es.name as source_name',
            ]);

        if (! $record) {
            return $stats;
        }

        $normalized = json_decode((string) $record->normalized_data, true);
        $media = is_array($normalized['media'] ?? null) ? $normalized['media'] : [];

        foreach ($media as $item) {
            if (! is_array($item)) {
                continue;
            }

            $license = strtoupper(trim((string) ($item['license'] ?? '')));
            $mediaId = trim((string) ($item['id'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));

            if ($onlyMediaId !== null && $mediaId !== $onlyMediaId) {
                continue;
            }

            if (! $this->isImportableMedia($item, $mediaId, $url, $license)) {
                $stats['skipped']++;
                continue;
            }

            $exists = DB::table('photos')
                ->where('external_source_id', $record->external_source_id)
                ->where('external_media_id', $mediaId)
                ->exists();

            if ($exists) {
                $stats['existing']++;
                continue;
            }

            if ($this->isTerminalMedia((int) $record->external_source_id, $mediaId)) {
                $stats['skipped']++;
                continue;
            }

            try {
                $response = Http::timeout(30)->retry(1, 500)->get($url);
                if (! $response->successful()) {
                    if (in_array($response->status(), [404, 410], true)) {
                        $this->markTerminalMedia(
                            (int) $record->external_source_id,
                            (int) $record->id,
                            $mediaId,
                            'unavailable',
                            'HTTP '.$response->status(),
                        );
                        $stats['skipped']++;
                    } else {
                        $stats['failed']++;
                    }

                    $stats['errors'][] = ['media_id' => $mediaId, 'reason' => 'HTTP '.$response->status()];
                    continue;
                }

                $body = $response->body();
                if ($body === '') {
                    $stats['skipped']++;
                    $stats['errors'][] = ['media_id' => $mediaId, 'reason' => __('admin_imports.errors.external_photo_empty')];
                    continue;
                }

                if (strlen($body) > self::MAX_SOURCE_BYTES) {
                    $this->markTerminalMedia(
                        (int) $record->external_source_id,
                        (int) $record->id,
                        $mediaId,
                        'too_large',
                        __('admin_imports.errors.external_photo_too_large'),
                    );
                    $stats['skipped']++;
                    $stats['errors'][] = ['media_id' => $mediaId, 'reason' => __('admin_imports.errors.external_photo_too_large')];
                    continue;
                }

                $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
                if (! in_array($contentType, self::ALLOWED_MIME_TYPES, true)) {
                    $stats['skipped']++;
                    $stats['errors'][] = ['media_id' => $mediaId, 'reason' => __('admin_imports.errors.external_photo_content_type', ['type' => $contentType ?: __('admin_imports.errors.unknown_value')])];
                    continue;
                }

                $uuid = (string) Str::uuid();
                $sourcePath = 'photo-uploads/'.$uuid.'.source';

                if (! Storage::disk('local')->put($sourcePath, $body)) {
                    $stats['failed']++;
                    continue;
                }

                $now = now();
                $photoId = (int) DB::table('photos')->insertGetId([
                    'uuid' => $uuid,
                    'user_id' => null,
                    'external_source_id' => $record->external_source_id,
                    'external_record_id' => $record->id,
                    'external_media_id' => $mediaId,
                    'source_url' => $url,
                    'source_author' => $item['author'] ?? $item['source'] ?? null,
                    'source_copyright' => $item['copyright'] ?? null,
                    'source_license_code' => $license,
                    'source_license_url' => $this->licenseUrl($license),
                    'source_provider' => $record->source_name,
                    'source_retrieved_at' => $now,
                    'storage_path' => 'photos/'.$uuid.'/detail.webp',
                    'source_path' => $sourcePath,
                    'preview_path' => null,
                    'original_filename' => null,
                    'mime_type' => $contentType,
                    'file_size' => strlen($body),
                    'preview_file_size' => null,
                    'width' => $item['width'] ?? null,
                    'height' => $item['height'] ?? null,
                    'status' => 'processing',
                    'moderated_by' => null,
                    'moderated_at' => null,
                    'moderation_reason' => null,
                    'processing_error' => null,
                    'is_active' => true,
                    'internal_comment' => self::AUTO_APPROVE_EXTERNAL_COMMENT,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('place_photos')->insert([
                    'place_id' => $record->place_id,
                    'place_review_id' => null,
                    'photo_id' => $photoId,
                    'photo_type' => 'external',
                    'sort_order' => 1000,
                    'is_thumbnail_eligible' => true,
                    'thumbnail_excluded_by' => null,
                    'thumbnail_excluded_at' => null,
                    'thumbnail_exclusion_reason' => null,
                    'is_active' => true,
                    'internal_comment' => 'Aus lizenzierter externer Quelle übernommen.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                ProcessPhotoUpload::dispatch($photoId)->afterCommit();
                $stats['queued']++;
            } catch (Throwable $e) {
                report($e);
                $stats['failed']++;
                $stats['errors'][] = ['media_id' => $mediaId, 'reason' => mb_substr($e->getMessage(), 0, 300)];
            }
        }

        return $stats;
    }

    private function isTerminalMedia(int $sourceId, string $mediaId): bool
    {
        return DB::table('external_media_states')
            ->where('external_source_id', $sourceId)
            ->where('external_media_id', $mediaId)
            ->whereIn('status', ['unavailable', 'too_large'])
            ->exists();
    }

    private function markTerminalMedia(int $sourceId, int $recordId, string $mediaId, string $status, string $reason): void
    {
        DB::table('external_media_states')->upsert([
            [
                'external_source_id' => $sourceId,
                'external_record_id' => $recordId,
                'external_media_id' => $mediaId,
                'status' => $status,
                'reason' => mb_substr($reason, 0, 255),
                'last_checked_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['external_source_id', 'external_media_id'], [
            'external_record_id',
            'status',
            'reason',
            'last_checked_at',
            'updated_at',
        ]);
    }

    private function isImportableMedia(array $item, string $mediaId, string $url, string $license): bool
    {
        if ($mediaId === '' || $url === '' || ! in_array($license, self::ALLOWED_LICENSES, true)) {
            return false;
        }

        $mimeType = strtolower(trim((string) ($item['mime_type'] ?? '')));
        if ($mimeType !== '' && ! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return false;
        }

        return true;
    }

    private function licenseUrl(string $license): ?string
    {
        return match ($license) {
            'CC0' => 'https://creativecommons.org/publicdomain/zero/1.0/',
            'CC-BY' => 'https://creativecommons.org/licenses/by/4.0/',
            'CC-BY-SA' => 'https://creativecommons.org/licenses/by-sa/4.0/',
            default => null,
        };
    }
}
