<?php

namespace App\Http\Controllers;

use App\Services\PhotoMergeConflictService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserPhotoLibraryController extends Controller
{
    public function __invoke(Request $request, PhotoMergeConflictService $conflicts): View
    {
        $photos = DB::table('photos as p')
            ->leftJoin('place_photos as pp', 'pp.photo_id', '=', 'p.id')
            ->leftJoin('places as pl', 'pl.id', '=', 'pp.place_id')
            ->where('p.user_id', $request->user()->id)
            ->orderBy('pl.name')->orderByDesc('p.created_at')
            ->paginate(24, [
                'p.id', 'p.uuid', 'p.status', 'p.is_active', 'p.width', 'p.height', 'p.file_size', 'p.created_at',
                'p.moderation_reason', 'pp.place_id', 'pp.place_review_id', 'pl.name as place_name', 'pl.slug as place_slug',
                DB::raw('(SELECT COUNT(*) FROM photo_helpful_votes phv WHERE phv.photo_id = p.id) as helpful_count'),
            ])->withQueryString();

        return view('photos.mine', [
            'conflicts' => $conflicts->forUser($request->user(), $request->integer('conflict') ?: null),
            'photos' => $photos,
        ]);
    }
}
