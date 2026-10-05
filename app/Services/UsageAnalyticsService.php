<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class UsageAnalyticsService
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {
    }

    public function track(
        Request $request,
        string $eventType,
        string $area,
        ?string $contentType = null,
        ?int $contentId = null,
        array $metadata = [],
    ): void {
        try {
            DB::table('usage_events')->insert([
                'event_type' => $eventType,
                'area' => $area,
                'content_type' => $contentType,
                'content_id' => $contentId,
                'audience' => $this->audience($request->user()),
                'traffic_type' => $this->trafficType($request),
                'metadata' => $metadata === []
                    ? null
                    : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function audience(?User $user): string
    {
        if (! $user) {
            return 'guest';
        }

        if ($this->permissions->isOwner($user)) {
            return 'admin';
        }

        $roles = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('user_roles.user_id', $user->id)
            ->where('roles.is_active', true)
            ->whereIn('roles.slug', ['admin', 'mod'])
            ->pluck('roles.slug');

        if ($roles->contains('admin')) {
            return 'admin';
        }

        if ($roles->contains('mod')) {
            return 'moderator';
        }

        return 'user';
    }

    public function trafficType(Request $request): string
    {
        if ($request->user()) {
            return 'human';
        }

        $userAgent = strtolower(trim((string) $request->userAgent()));

        if ($userAgent === '') {
            return 'unknown';
        }

        $botMarkers = [
            'bot',
            'crawler',
            'spider',
            'slurp',
            'bingpreview',
            'facebookexternalhit',
            'meta-externalagent',
            'meta-webindexer',
            'telegrambot',
            'twitterbot',
            'linkedinbot',
            'discordbot',
            'whatsapp',
            'google-inspectiontool',
            'googleother',
            'python-urllib',
            'python-requests',
            'curl/',
            'wget/',
            'scrapy',
            'httpclient',
            'headlesschrome',
            'phantomjs',
        ];

        foreach ($botMarkers as $marker) {
            if (str_contains($userAgent, $marker)) {
                return 'bot';
            }
        }

        return 'human';
    }
}
