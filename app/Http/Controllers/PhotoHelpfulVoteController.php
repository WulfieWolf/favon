<?php

namespace App\Http\Controllers;

use App\Services\PlacePhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PhotoHelpfulVoteController extends Controller
{
    public function store(Request $request, int $photo, PlacePhotoService $photos): RedirectResponse
    {
        abort_unless($photos->toggleHelpful($request->user(), $photo, true), 404);

        return redirect()->to(preg_replace('/#.*$/', '', url()->previous()).'#photo-'.$photo)
            ->with('ui_toast', __('photos.flash.helpful_added'));
    }

    public function destroy(Request $request, int $photo, PlacePhotoService $photos): RedirectResponse
    {
        abort_unless($photos->toggleHelpful($request->user(), $photo, false), 404);

        return redirect()->to(preg_replace('/#.*$/', '', url()->previous()).'#photo-'.$photo)
            ->with('ui_toast', __('photos.flash.helpful_removed'));
    }
}
