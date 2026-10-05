<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserProfilePhotoController extends Controller
{
    public function __invoke(Request $request, string $handle): StreamedResponse
    {
        $record = DB::table('user_profiles as up')
            ->join('users as u', 'u.id', '=', 'up.user_id')
            ->where('u.account_status', 'active')
            ->leftJoin('user_settings as us', 'us.user_id', '=', 'u.id')
            ->leftJoin('photos as p', 'p.id', '=', 'u.profile_photo_id')
            ->where(function ($query) use ($handle) {
                $query->where('up.public_handle', $handle)
                    ->orWhere('up.public_alias', $handle);
            })
            ->where('p.is_active', true)
            ->first([
                'u.id as user_id',
                'us.profile_photo_visibility',
                'p.storage_path',
                'p.mime_type',
            ]);

        abort_unless($record?->storage_path, 404);

        $isOwner = $request->user()?->id === $record->user_id;
        $isRegistered = $request->user() !== null;
        $visibility = $record->profile_photo_visibility ?? 'public';

        $allowed = $isOwner || match ($visibility) {
            'public' => true,
            'registered' => $isRegistered,
            default => false,
        };

        abort_unless($allowed, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($record->storage_path), 404);

        return $disk->response(
            $record->storage_path,
            null,
            [
                'Content-Type' => $record->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, max-age=300',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
