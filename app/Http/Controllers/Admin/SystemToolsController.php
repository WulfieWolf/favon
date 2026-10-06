<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDebugService;
use App\Services\SiteAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SystemToolsController extends Controller
{
    public function index(Request $request, AdminDebugService $debug): View
    {
        abort_unless($debug->canManage($request->user()), 403);

        $since = now()->subDay();

        return view('admin.system.index', [
            'debugEnabled' => $debug->enabled($request->user()),
            'accessMode' => app(SiteAccessService::class)->mode(),
            'accessMessage' => app(SiteAccessService::class)->message(),
            'securityMetrics' => [
                'quarantined' => DB::table('abuse_flags')
                    ->where('entity_type', 'place')
                    ->where('status', 'open')
                    ->distinct()
                    ->count('entity_id'),
                'abuse_flags_24h' => DB::table('abuse_flags')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'rate_limited_24h' => DB::table('security_events')
                    ->where('event_type', 'rate_limited')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'browse_rejected_24h' => DB::table('security_events')
                    ->where('event_type', 'browse_query_rejected')
                    ->where('created_at', '>=', $since)
                    ->count(),
                'registration_limited_24h' => DB::table('security_events')
                    ->where('event_type', 'registration_rate_limited')
                    ->where('created_at', '>=', $since)
                    ->count(),
            ],
            'securityEvents' => DB::table('security_events')
                ->leftJoin('users', 'users.id', '=', 'security_events.user_id')
                ->orderByDesc('security_events.created_at')
                ->limit(20)
                ->get([
                    'security_events.id',
                    'security_events.event_type',
                    'security_events.route_name',
                    'security_events.source_ip_hash',
                    'security_events.context',
                    'security_events.created_at',
                    'users.name as user_name',
                ]),
        ]);
    }

    public function updateAccess(Request $request, AdminDebugService $debug, SiteAccessService $access): RedirectResponse
    {
        abort_unless($debug->canManage($request->user()), 403);

        $data = $request->validate([
            'mode' => ['required', 'in:normal,registration_closed,lockdown'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $access->set($data['mode'], $data['message'] ?? null);

        return back()->with('ui_toast', __('admin.system.access_updated'));
    }

    public function updateDebug(Request $request, AdminDebugService $debug): RedirectResponse
    {
        abort_unless($debug->canManage($request->user()), 403);

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $debug->setEnabled($request->user(), (bool) $data['enabled']);

        return back()->with('ui_toast', (bool) $data['enabled']
            ? __('admin.system.debug_enabled')
            : __('admin.system.debug_disabled'));
    }
}
