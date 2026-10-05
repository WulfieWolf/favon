<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoAssetController extends Controller
{
    public function show(string $uuid, string $variant): StreamedResponse
    {
        abort_unless(in_array($variant, ['preview', 'detail'], true), 404);

        $photo = DB::table('photos as p')
            ->join('place_photos as pp', 'pp.photo_id', '=', 'p.id')
            ->leftJoin('place_reviews as pr', 'pr.id', '=', 'pp.place_review_id')
            ->where('p.uuid', $uuid)
            ->where('p.status', 'approved')
            ->where('p.is_active', true)
            ->where('pp.is_active', true)
            ->where(function ($query): void {
                $query->whereNull('pp.place_review_id')
                    ->orWhere('pr.status', 'active');
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('photo_merge_conflict_items as pmci')
                    ->join('photo_merge_conflicts as pmc', 'pmc.id', '=', 'pmci.photo_merge_conflict_id')
                    ->whereColumn('pmci.photo_id', 'p.id')
                    ->where('pmc.status', 'selection_required');
            })
            ->first(['p.storage_path', 'p.preview_path']);

        abort_unless($photo, 404);

        $path = $variant === 'preview' ? $photo->preview_path : $photo->storage_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $uuid.'.webp', [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=3600, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function moderation(string $uuid, string $variant): StreamedResponse
    {
        abort_unless(in_array($variant, ['preview', 'detail'], true), 404);

        $photo = DB::table('photos')->where('uuid', $uuid)->where('is_active', true)->first(['storage_path', 'preview_path']);
        abort_unless($photo, 404);

        $path = $variant === 'preview' ? $photo->preview_path : $photo->storage_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $uuid.'.webp', [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function owner(Request $request, string $uuid, string $variant): StreamedResponse
    {
        abort_unless(in_array($variant, ['preview', 'detail'], true), 404);

        $photo = DB::table('photos')
            ->where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->first(['storage_path', 'preview_path']);
        abort_unless($photo, 404);

        $path = $variant === 'preview' ? $photo->preview_path : $photo->storage_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $uuid.'.webp', [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
