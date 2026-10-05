<?php

use Database\Seeders\RestAreaCatalogSeeder;
use Database\Seeders\SuggestableFieldSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('place_vehicle_types', 'capacity')) {
            Schema::table('place_vehicle_types', function (Blueprint $table) {
                $table->unsignedInteger('capacity')->nullable()->after('vehicle_type_id');
            });
        }

        if (! Schema::hasColumn('place_details', 'pitch_count_source')) {
            Schema::table('place_details', function (Blueprint $table) {
                $table->string('pitch_count_source', 32)->nullable()->after('pitch_count');
            });
        }

        (new RestAreaCatalogSeeder())->run();
        (new SuggestableFieldSeeder())->run();

        $this->backfillCreatedExternalPlaces();
    }

    private function backfillCreatedExternalPlaces(): void
    {
        $vehicleMap = [
            'car' => 'car',
            'carWithTrailer' => 'car-with-trailer',
            'lorry' => 'truck',
            'bus' => 'coach',
        ];

        $vehicleIds = DB::table('vehicle_types')
            ->whereIn('slug', array_values($vehicleMap))
            ->pluck('id', 'slug');

        DB::table('external_records')
            ->whereNotNull('place_id')
            ->where('classification', 'created')
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(200, function ($records) use ($vehicleMap, $vehicleIds): void {
                foreach ($records as $record) {
                    $normalized = json_decode((string) $record->normalized_data, true);
                    if (! is_array($normalized)) {
                        continue;
                    }

                    $sourceCapacities = is_array($normalized['vehicle_capacities'] ?? null)
                        ? $normalized['vehicle_capacities']
                        : [];

                    $mappedCapacities = [];
                    foreach ($sourceCapacities as $sourceType => $capacity) {
                        $vehicleSlug = $vehicleMap[$sourceType] ?? null;
                        if (! $vehicleSlug || ! is_int($capacity) || $capacity <= 0) {
                            continue;
                        }

                        $mappedCapacities[$vehicleSlug] = $capacity;
                    }

                    foreach ($mappedCapacities as $vehicleSlug => $capacity) {
                        $vehicleTypeId = $vehicleIds[$vehicleSlug] ?? null;
                        if (! $vehicleTypeId) {
                            continue;
                        }

                        $current = DB::table('place_vehicle_types')
                            ->where('place_id', $record->place_id)
                            ->where('vehicle_type_id', $vehicleTypeId)
                            ->where('is_active', true)
                            ->whereNull('version_valid_until')
                            ->first();

                        if ($current) {
                            continue;
                        }

                        DB::table('place_vehicle_types')->insert([
                            'place_id' => $record->place_id,
                            'vehicle_type_id' => $vehicleTypeId,
                            'capacity' => $capacity,
                            'is_active' => true,
                            'version_valid_from' => now(),
                            'version_valid_until' => null,
                            'internal_comment' => 'Backfill aus verknüpfter externer Quelle.',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $placeData = is_array($normalized['place'] ?? null) ? $normalized['place'] : [];
                    $directTotal = $placeData['parking_spaces_total'] ?? null;
                    if (is_int($directTotal) && $directTotal > 0) {
                        continue;
                    }

                    $derivedTotal = array_sum($mappedCapacities);
                    if ($derivedTotal <= 0) {
                        continue;
                    }

                    $details = DB::table('place_details')
                        ->where('place_id', $record->place_id)
                        ->where('is_active', true)
                        ->whereNull('version_valid_until')
                        ->orderByDesc('id')
                        ->first();

                    if ($details && $details->pitch_count === null) {
                        DB::table('place_details')->where('id', $details->id)->update([
                            'pitch_count' => $derivedTotal,
                            'pitch_count_source' => 'summed_vehicle_capacities',
                            'updated_at' => now(),
                        ]);
                    } elseif (! $details) {
                        DB::table('place_details')->insert([
                            'place_id' => $record->place_id,
                            'operator_name' => null,
                            'pitch_count' => $derivedTotal,
                            'pitch_count_source' => 'summed_vehicle_capacities',
                            'operating_mode' => 'unclear',
                            'is_active' => true,
                            'version_valid_from' => now(),
                            'version_valid_until' => null,
                            'internal_comment' => 'Gesamtzahl aus positiven Fahrzeugkapazitäten der externen Quelle abgeleitet.',
                            'created_by' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('place_vehicle_types', function (Blueprint $table) {
            if (Schema::hasColumn('place_vehicle_types', 'capacity')) {
                $table->dropColumn('capacity');
            }
        });

        Schema::table('place_details', function (Blueprint $table) {
            if (Schema::hasColumn('place_details', 'pitch_count_source')) {
                $table->dropColumn('pitch_count_source');
            }
        });
    }
};
