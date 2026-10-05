<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlaceDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlaceDeletionController extends Controller
{
    public function destroy(Request $request, int $place, PlaceDeletionService $service): RedirectResponse
    {
        $current = DB::table('places')
            ->where('id', $place)
            ->whereNull('deleted_at')
            ->first(['id', 'name']);

        abort_unless($current, 404);

        $data = $request->validate([
            'deletion_reason' => ['required', Rule::in(PlaceDeletionService::REASONS)],
            'deletion_note' => ['nullable', 'string', 'max:2000'],
            'confirmation' => ['required', 'string'],
        ]);

        if (trim((string) $data['confirmation']) !== trim((string) $current->name)) {
            return back()->withErrors(['confirmation' => __('admin.place_deletion.confirmation_mismatch')]);
        }

        $service->delete(
            $request->user(),
            (int) $current->id,
            (string) $data['deletion_reason'],
            $data['deletion_note'] ?? null,
        );

        return redirect()->route('dashboard')->with('ui_toast', __('admin.place_deletion.deleted'));
    }
}
