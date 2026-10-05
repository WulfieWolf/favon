<?php

namespace App\Http\Controllers;

use App\Services\UserNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, UserNotificationService $notifications): View
    {
        return view('notifications.index', [
            'notifications' => $notifications->paginatedForUser((int) $request->user()->id),
        ]);
    }

    public function show(Request $request, int $notification, UserNotificationService $notifications): View
    {
        $item = $notifications->findVisible($notification, (int) $request->user()->id);
        abort_unless($item, 404);

        $notifications->markRead($notification, (int) $request->user()->id);

        return view('notifications.show', [
            'notification' => $item,
            'events' => $notifications->eventsForNotification($notification, (int) $request->user()->id),
        ]);
    }

    public function markAllRead(Request $request, UserNotificationService $notifications): RedirectResponse
    {
        $notifications->markAllRead((int) $request->user()->id);

        return back()->with('ui_toast', __('notifications.all_marked_read'));
    }
}
