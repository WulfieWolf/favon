<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateUserDataExport;
use App\Services\UserDataExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserDataExportController extends Controller
{
    public function show(Request $request, UserDataExportService $exports): View
    {
        $latest = $exports->latestForUser((int) $request->user()->id);
        $nextAvailableAt = $exports->nextAvailableAt((int) $request->user()->id);

        return view('settings.data-export', [
            'latest' => $latest,
            'nextAvailableAt' => $nextAvailableAt,
            'cooldownDays' => UserDataExportService::COOLDOWN_DAYS,
            'downloadHours' => UserDataExportService::DOWNLOAD_HOURS,
        ]);
    }

    public function request(Request $request, UserDataExportService $exports): RedirectResponse
    {
        try {
            $requestId = $exports->request($request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['export' => $e->getMessage()]);
        }

        GenerateUserDataExport::dispatch($requestId);

        return back()->with('ui_toast', __('data_export.requested'));
    }

    public function download(Request $request, string $token): BinaryFileResponse
    {
        $export = DB::table('data_export_requests')
            ->where('token', $token)
            ->where('user_id', $request->user()->id)
            ->where('status', 'ready')
            ->where('expires_at', '>', now())
            ->first();

        abort_unless($export && $export->storage_path && Storage::disk('local')->exists($export->storage_path), 404);

        return response()->download(
            Storage::disk('local')->path($export->storage_path),
            'camperwolf-data-export-'.now()->format('Y-m-d').'.zip',
            [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
