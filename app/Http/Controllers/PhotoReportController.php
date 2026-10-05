<?php

namespace App\Http\Controllers;

use App\Services\PlacePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PhotoReportController extends Controller
{
    public function store(Request $request, int $photo, PlacePhotoService $photos): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', Rule::in(['wrong_place', 'privacy', 'inappropriate', 'spam', 'copyright', 'other'])],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $created = $photos->report($request->user(), $photo, $data['reason'], $data['comment'] ?? null);

        return back()->with('ui_toast', $created
            ? __('photos.flash.reported')
            : __('photos.flash.report_duplicate'));
    }
}
