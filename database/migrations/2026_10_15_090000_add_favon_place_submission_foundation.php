<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table): void {
            $table->text('description')->nullable()->after('slug');
            $table->string('environment', 16)->nullable()->after('description');
            $table->boolean('has_gloryhole')->nullable()->after('environment');
        });

        Schema::create('place_place_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('place_type_id')->constrained('place_types')->restrictOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['place_id', 'place_type_id']);
            $table->index(['place_type_id', 'is_primary']);
        });

        DB::table('places')
            ->select(['id', 'place_type_id', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->get()
            ->each(function ($place): void {
                DB::table('place_place_types')->updateOrInsert(
                    [
                        'place_id' => $place->id,
                        'place_type_id' => $place->place_type_id,
                    ],
                    [
                        'is_primary' => true,
                        'created_at' => $place->created_at ?? now(),
                        'updated_at' => $place->updated_at ?? now(),
                    ],
                );
            });

        $now = now();
        $placeTypes = [
            ['slug' => 'gay-sauna', 'sort_order' => 10],
            ['slug' => 'darkroom-sexclub', 'sort_order' => 20],
            ['slug' => 'sex-cinema-video-booth', 'sort_order' => 30],
            ['slug' => 'fetish-leather-bar', 'sort_order' => 40],
            ['slug' => 'park-forest', 'sort_order' => 50],
            ['slug' => 'rest-area', 'sort_order' => 60],
            ['slug' => 'public-toilet', 'sort_order' => 70],
            ['slug' => 'nudist-beach-lake', 'sort_order' => 80],
            ['slug' => 'parking-garage-shopping-center', 'sort_order' => 90],
        ];

        foreach ($placeTypes as $placeType) {
            DB::table('place_types')->updateOrInsert(
                ['slug' => $placeType['slug']],
                [
                    'sort_order' => $placeType['sort_order'],
                    'is_active' => true,
                    'is_searchable' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => 'places.submit'],
                [
                    'name' => 'Plätze vorschlagen',
                    'category' => 'places',
                    'description' => 'Neue Plätze zur redaktionellen Freigabe einreichen.',
                    'sort_order' => 15,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            $permissionId = DB::table('permissions')->where('slug', 'places.submit')->value('id');

            if ($permissionId) {
                $roleIds = DB::table('roles')
                    ->whereIn('slug', ['user', 'mod', 'admin'])
                    ->pluck('id');

                foreach ($roleIds as $roleId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $permissionId],
                        ['updated_at' => $now, 'created_at' => $now],
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            $permissionId = DB::table('permissions')->where('slug', 'places.submit')->value('id');

            if ($permissionId && Schema::hasTable('role_permissions')) {
                DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            }

            DB::table('permissions')->where('slug', 'places.submit')->delete();
        }

        Schema::dropIfExists('place_place_types');

        Schema::table('places', function (Blueprint $table): void {
            $table->dropColumn(['description', 'environment', 'has_gloryhole']);
        });
    }
};
