<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemNotificationController extends Controller
{
    public function create(): View
    {
        return view('admin.notifications.create');
    }

    public function store(Request $request, UserNotificationService $notifications): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'url' => ['nullable', 'string', 'max:2048'],
            'priority' => ['required', 'in:normal,important'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $expiresAt = $data['expires_at'] ?? ($data['priority'] === 'normal'
            ? now()->addDays((int) config('camperwolf_notifications.retention_days.normal_broadcast', 180))
            : null);

        $notificationId = $notifications->createImmediate(
            null,
            'system_announcement',
            $data['title'],
            $data['message'],
            $data['url'] ?: null,
            $data['priority'],
            'bell',
            null,
            (int) $request->user()->id,
            $expiresAt,
        );

        \Illuminate\Support\Facades\DB::table('audit_logs')->insert([
            'user_id' => $request->user()->id,
            'entity_type' => 'user_notification',
            'entity_id' => $notificationId,
            'action' => 'system_notification_published',
            'source' => 'admin',
            'old_values' => null,
            'new_values' => json_encode([
                'title' => $data['title'],
                'priority' => $data['priority'],
                'expires_at' => $expiresAt instanceof \Carbon\CarbonInterface ? $expiresAt->toIso8601String() : $expiresAt,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'internal_comment' => null,
            'created_at' => now(),
        ]);

        return redirect()->route('admin.index')->with('ui_toast', __('admin.system_notifications.status_published'));
    }
}
