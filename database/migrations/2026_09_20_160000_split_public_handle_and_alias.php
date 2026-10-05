<?php

use App\Services\PublicHandleService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('public_alias', 30)->nullable()->after('public_handle');
            $table->timestamp('alias_finalized_at')->nullable()->after('public_alias');
            $table->unique('public_alias', 'user_profiles_public_alias_unique');
        });

        $profiles = DB::table('user_profiles')
            ->select(['id', 'user_id', 'public_handle', 'handle_finalized_at'])
            ->orderBy('id')
            ->get();

        $service = app(PublicHandleService::class);

        foreach ($profiles as $profile) {
            $automatic = $service->automaticForUserId((int) $profile->user_id);
            $current = (string) $profile->public_handle;
            $isAutomatic = strcasecmp($current, $automatic) === 0;

            DB::table('user_profiles')
                ->where('id', $profile->id)
                ->update([
                    'public_handle' => $automatic,
                    'public_alias' => ! $isAutomatic && $profile->handle_finalized_at ? $current : null,
                    'alias_finalized_at' => ! $isAutomatic && $profile->handle_finalized_at
                        ? $profile->handle_finalized_at
                        : null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $profiles = DB::table('user_profiles')
            ->select(['id', 'public_handle', 'public_alias', 'alias_finalized_at'])
            ->get();

        foreach ($profiles as $profile) {
            if ($profile->public_alias && $profile->alias_finalized_at) {
                DB::table('user_profiles')
                    ->where('id', $profile->id)
                    ->update([
                        'public_handle' => $profile->public_alias,
                        'handle_finalized_at' => $profile->alias_finalized_at,
                        'updated_at' => now(),
                    ]);
            }
        }

        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropUnique('user_profiles_public_alias_unique');
            $table->dropColumn(['public_alias', 'alias_finalized_at']);
        });
    }
};
