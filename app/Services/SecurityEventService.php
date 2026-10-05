<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityEventService
{
    public function record(Request $request, string $eventType, array $context = []): void
    {
        $ip = trim((string) $request->ip());

        DB::table('security_events')->insert([
            'event_type' => $eventType,
            'user_id' => $request->user()?->id,
            'route_name' => $request->route()?->getName(),
            'source_ip_hash' => $ip !== '' ? hash('sha256', $ip.'|'.config('app.key')) : null,
            'context' => $context === []
                ? null
                : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
    }
}
