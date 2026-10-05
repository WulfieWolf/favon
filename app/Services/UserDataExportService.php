<?php

namespace App\Services;

use App\Support\LocalTime;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class UserDataExportService
{
    public const COOLDOWN_DAYS = 7;
    public const DOWNLOAD_HOURS = 72;

    public function latestForUser(int $userId): ?object
    {
        return DB::table('data_export_requests')
            ->where('user_id', $userId)
            ->orderByDesc('requested_at')
            ->first();
    }

    public function hasActiveExport(int $userId): bool
    {
        return DB::table('data_export_requests')
            ->where('user_id', $userId)
            ->whereIn('status', ['queued', 'processing'])
            ->exists();
    }

    public function nextAvailableAt(int $userId): ?\Illuminate\Support\Carbon
    {
        $latest = DB::table('data_export_requests')
            ->where('user_id', $userId)
            ->whereIn('status', ['queued', 'processing', 'ready', 'expired'])
            ->orderByDesc('requested_at')
            ->first(['requested_at']);

        if (! $latest) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($latest->requested_at)->addDays(self::COOLDOWN_DAYS);
    }

    public function request(User $user): int
    {
        return DB::transaction(function () use ($user): int {
            DB::table('users')->where('id', $user->id)->lockForUpdate()->first(['id']);

            if ($this->hasActiveExport((int) $user->id)) {
                throw new RuntimeException(__('data_export.errors.active'));
            }

            $next = $this->nextAvailableAt((int) $user->id);
            if ($next && now()->lt($next)) {
                throw new RuntimeException(__('data_export.errors.cooldown', [
                    'date' => LocalTime::translatedFormat($next, __('data_export.date_time_format')),
                ]));
            }

            return (int) DB::table('data_export_requests')->insertGetId([
                'user_id' => $user->id,
                'token' => (string) Str::uuid(),
                'status' => 'queued',
                'requested_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function generate(int $requestId): void
    {
        $request = DB::table('data_export_requests')->where('id', $requestId)->first();

        if (! $request || ! in_array($request->status, ['queued', 'processing'], true)) {
            return;
        }

        $user = User::query()->find($request->user_id);
        if (! $user) {
            $this->fail($requestId, 'user_missing');

            return;
        }

        DB::table('data_export_requests')->where('id', $requestId)->update([
            'status' => 'processing',
            'updated_at' => now(),
        ]);

        if (! class_exists(ZipArchive::class)) {
            $this->fail($requestId, 'zip_extension_missing');

            return;
        }

        $relativePath = 'data-exports/'.$user->id.'/'.$request->token.'.zip';
        $absolutePath = Storage::disk('local')->path($relativePath);
        $directory = dirname($absolutePath);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            $this->fail($requestId, 'export_directory_failed');

            return;
        }

        $zip = new ZipArchive;
        if ($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->fail($requestId, 'zip_open_failed');

            return;
        }

        try {
            $this->addJson($zip, 'account.json', $this->account($user));
            $this->addJson($zip, 'settings.json', $this->settings((int) $user->id));
            $this->addJson($zip, 'reviews.json', $this->reviews((int) $user->id));
            $this->addJson($zip, 'contributions.json', $this->contributions((int) $user->id));
            $this->addJson($zip, 'interactions.json', $this->interactions((int) $user->id));
            $this->addJson($zip, 'notifications.json', $this->notifications((int) $user->id));
            $this->addJson($zip, 'support.json', $this->support((int) $user->id));


            $zip->addFromString('README.txt', $this->readme($user));
        } catch (\Throwable $e) {
            $zip->close();
            Storage::disk('local')->delete($relativePath);
            $this->fail($requestId, 'generation_failed');

            throw $e;
        }

        $zip->close();

        DB::table('data_export_requests')->where('id', $requestId)->update([
            'status' => 'ready',
            'storage_path' => $relativePath,
            'file_size' => is_file($absolutePath) ? filesize($absolutePath) : null,
            'completed_at' => now(),
            'expires_at' => now()->addHours(self::DOWNLOAD_HOURS),
            'failure_reason' => null,
            'failed_at' => null,
            'updated_at' => now(),
        ]);
    }

    public function cleanupExpired(): int
    {
        $rows = DB::table('data_export_requests')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get(['id', 'storage_path']);

        foreach ($rows as $row) {
            if ($row->storage_path) {
                Storage::disk('local')->delete($row->storage_path);
            }
        }

        return DB::table('data_export_requests')
            ->whereIn('id', $rows->pluck('id'))
            ->update([
                'status' => 'expired',
                'storage_path' => null,
                'file_size' => null,
                'updated_at' => now(),
            ]);
    }

    private function fail(int $requestId, string $reason): void
    {
        DB::table('data_export_requests')->where('id', $requestId)->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'failed_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function addJson(ZipArchive $zip, string $name, mixed $data): void
    {
        $zip->addFromString($name, json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));
    }

    private function account(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'locale' => $user->locale,
            'last_seen_at' => $user->last_seen_at,
            'account_created_at' => $user->created_at,
            'account_updated_at' => $user->updated_at,
        ];
    }

    private function settings(int $userId): array
    {
        return [
            'consents' => DB::table('user_consents')
                ->where('user_id', $userId)
                ->orderBy('accepted_at')
                ->get(['consent_type', 'version', 'accepted_at', 'revoked_at', 'created_at', 'updated_at']),
        ];
    }

    private function reviews(int $userId): array
    {
        $reviews = DB::table('place_reviews as pr')
            ->join('places as p', 'p.id', '=', 'pr.place_id')
            ->where('pr.user_id', $userId)
            ->orderBy('pr.created_at')
            ->get([
                'pr.id', 'p.name as place_name', 'p.slug as place_slug', 'pr.status',
                'pr.verified_visit', 'pr.current_published_at', 'pr.current_expires_at',
                'pr.created_at', 'pr.updated_at',
            ]);

        return $reviews->map(function ($review): array {
            return [
                'review_reference' => $review->id,
                'place' => ['name' => $review->place_name, 'slug' => $review->place_slug],
                'status' => $review->status,
                'verified_visit' => (bool) $review->verified_visit,
                'current_published_at' => $review->current_published_at,
                'current_expires_at' => $review->current_expires_at,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
                'versions' => DB::table('place_review_versions')
                    ->where('review_id', $review->id)
                    ->orderBy('version_number')
                    ->get([
                        'version_number', 'rating_cleanliness', 'rating_functionality',
                        'rating_condition', 'rating_safety', 'rating_usability',
                        'overall_score', 'review_text', 'is_public',
                        'valid_from', 'valid_until', 'created_at', 'updated_at',
                    ]),
            ];
        })->all();
    }

    private function contributions(int $userId): array
    {
        return [
            'places_created' => DB::table('places')
                ->where('created_by', $userId)
                ->orderBy('created_at')
                ->get([
                    'name', 'slug', 'latitude', 'longitude', 'publication_status',
                    'legal_status', 'opening_status', 'created_at', 'updated_at',
                ]),
            'change_requests' => DB::table('change_requests as cr')
                ->join('places as p', 'p.id', '=', 'cr.place_id')
                ->leftJoin('suggestable_fields as sf', 'sf.id', '=', 'cr.suggestable_field_id')
                ->where('cr.submitted_by', $userId)
                ->orderBy('cr.submitted_at')
                ->get([
                    'p.name as place_name', 'p.slug as place_slug',
                    'sf.target_table as target_table', 'sf.target_field as target_field', 'cr.operation', 'cr.original_value',
                    'cr.proposed_value', 'cr.status', 'cr.submitted_at',
                    'cr.user_comment', 'cr.reviewed_at', 'cr.applied_at',
                ]),
        ];
    }

    private function interactions(int $userId): array
    {
        return [
            'favorites' => DB::table('place_favorites as pf')
                ->join('places as p', 'p.id', '=', 'pf.place_id')
                ->where('pf.user_id', $userId)
                ->orderBy('pf.created_at')
                ->get([
                    'p.name as place_name', 'p.slug as place_slug',
                    'pf.notify_changes', 'pf.created_at', 'pf.updated_at',
                ]),
            'review_reports' => DB::table('place_review_reports as rr')
                ->join('place_reviews as pr', 'pr.id', '=', 'rr.review_id')
                ->join('places as p', 'p.id', '=', 'pr.place_id')
                ->where('rr.reported_by', $userId)
                ->orderBy('rr.created_at')
                ->get([
                    'p.name as place_name', 'p.slug as place_slug',
                    'rr.reason', 'rr.comment', 'rr.status',
                    'rr.created_at', 'rr.moderated_at',
                ]),
        ];
    }

    private function notifications(int $userId): array
    {
        return [
            'notifications' => DB::table('user_notifications')
                ->where('user_id', $userId)
                ->orderBy('created_at')
                ->get([
                    'type', 'priority', 'title', 'message', 'url',
                    'available_at', 'expires_at', 'created_at', 'updated_at',
                ]),
            'events' => DB::table('notification_events')
                ->where('user_id', $userId)
                ->orderBy('occurred_at')
                ->get([
                    'type', 'priority', 'title', 'message', 'url',
                    'occurred_at', 'processed_at', 'created_at', 'updated_at',
                ]),
        ];
    }

    private function support(int $userId): array
    {
        $tickets = DB::table('support_tickets')
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->get([
                'id', 'type', 'status', 'priority', 'subject', 'description',
                'module', 'route_name', 'first_response_at', 'closed_at',
                'created_at', 'updated_at',
            ]);

        return $tickets->map(function ($ticket) use ($userId): array {
            return [
                'ticket_reference' => $ticket->id,
                'type' => $ticket->type,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'module' => $ticket->module,
                'route_name' => $ticket->route_name,
                'first_response_at' => $ticket->first_response_at,
                'closed_at' => $ticket->closed_at,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
                'messages_written_by_you' => DB::table('support_ticket_messages')
                    ->where('ticket_id', $ticket->id)
                    ->where('user_id', $userId)
                    ->orderBy('created_at')
                    ->get(['message_type', 'message', 'created_at', 'updated_at']),
            ];
        })->all();
    }

    private function readme(User $user): string
    {
        $locale = in_array((string) $user->locale, ['de', 'en'], true)
            ? (string) $user->locale
            : config('app.fallback_locale', 'de');

        $line = fn (string $key, array $replace = []): string => Lang::get(
            'data_export.readme.'.$key,
            $replace,
            $locale,
        );

        return implode(PHP_EOL, [
            $line('title'),
            '',
            $line('created_for', ['email' => $user->email]),
            $line('created_at', ['date' => now()->toIso8601String()]),
            '',
            $line('intro_1'),
            $line('intro_2'),
            $line('intro_3'),
            '',
            $line('excluded_1'),
            $line('excluded_2'),
            $line('excluded_3'),
            '',
            $line('rights_1'),
            $line('rights_2'),
            $line('rights_3'),
        ]);
    }
}
