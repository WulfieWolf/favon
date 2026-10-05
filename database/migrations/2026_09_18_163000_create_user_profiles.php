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
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('public_handle', 50)->unique();
            $table->timestamp('handle_finalized_at')->nullable();
            $table->string('bio', 500)->nullable();
            $table->string('hometown_city', 120)->nullable();
            $table->string('hometown_country_code', 2)->nullable();
            $table->decimal('hometown_latitude', 10, 7)->nullable();
            $table->decimal('hometown_longitude', 10, 7)->nullable();
            $table->string('hometown_source_id', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 40)->nullable();
            $table->string('gender_custom', 80)->nullable();
            $table->string('vehicle_type', 50)->nullable();
            $table->string('vehicle_details', 160)->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['hometown_country_code', 'hometown_city']);
        });

        Schema::create('user_profile_social_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('url', 500);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'sort_order']);
        });

        Schema::table('user_settings', function (Blueprint $table) {
            $table->string('profile_photo_visibility', 20)->default('public');
            $table->string('bio_visibility', 20)->default('registered');
            $table->string('hometown_visibility', 20)->default('registered');
            $table->string('age_visibility', 20)->default('private');
            $table->string('gender_visibility', 20)->default('private');
            $table->string('vehicle_visibility', 20)->default('registered');
            $table->string('social_links_visibility', 20)->default('registered');
        });

        $now = now();

        DB::table('users')
            ->orderBy('id')
            ->select(['id'])
            ->get()
            ->each(function ($user) use ($now): void {
                DB::table('user_profiles')->insertOrIgnore([
                    'user_id' => $user->id,
                    'public_handle' => app(PublicHandleService::class)->automaticForUserId((int) $user->id),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('user_settings')->insertOrIgnore([
                    'user_id' => $user->id,
                    'show_real_name' => false,
                    'show_reviews_in_profile' => true,
                    'show_photos_in_profile' => true,
                    'show_join_date' => true,
                    'show_activity_counts' => true,
                    'allow_email_notifications' => true,
                    'profile_photo_visibility' => 'public',
                    'bio_visibility' => 'registered',
                    'hometown_visibility' => 'registered',
                    'age_visibility' => 'private',
                    'gender_visibility' => 'private',
                    'vehicle_visibility' => 'registered',
                    'social_links_visibility' => 'registered',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo_visibility',
                'bio_visibility',
                'hometown_visibility',
                'age_visibility',
                'gender_visibility',
                'vehicle_visibility',
                'social_links_visibility',
            ]);
        });

        Schema::dropIfExists('user_profile_social_links');
        Schema::dropIfExists('user_profiles');
    }
};
