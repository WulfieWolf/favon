<?php

use App\Services\PublicHandleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $generator = app(PublicHandleService::class);

        DB::table('user_profiles')
            ->whereNull('handle_finalized_at')
            ->orderBy('user_id')
            ->select(['id', 'user_id', 'public_handle'])
            ->get()
            ->each(function ($profile) use ($generator): void {
                $newHandle = $generator->automaticForUserId((int) $profile->user_id);

                if ($profile->public_handle === $newHandle) {
                    return;
                }

                $conflict = DB::table('user_profiles')
                    ->where('id', '!=', $profile->id)
                    ->whereRaw('LOWER(public_handle) = ?', [strtolower($newHandle)])
                    ->exists();

                if ($conflict) {
                    throw new RuntimeException('Automatic public handle collision detected for user '.$profile->user_id.'.');
                }

                DB::table('user_profiles')
                    ->where('id', $profile->id)
                    ->update([
                        'public_handle' => $newHandle,
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('user_profiles')
            ->whereNull('handle_finalized_at')
            ->orderBy('user_id')
            ->select(['id', 'user_id'])
            ->get()
            ->each(function ($profile): void {
                DB::table('user_profiles')
                    ->where('id', $profile->id)
                    ->update([
                        'public_handle' => 'CWNutzer'.str_pad((string) $profile->user_id, 8, '0', STR_PAD_LEFT),
                        'updated_at' => now(),
                    ]);
            });
    }
};
