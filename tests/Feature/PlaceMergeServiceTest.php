<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PlaceMergeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaceMergeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_comparison_suggests_known_values_and_deletes_real_conflicts(): void
    {
        $user = User::factory()->create();
        $type = $this->placeType();
        $main = $this->place($type, $user, 'Main', '51.0000', 'unclear', 'allowed');
        $duplicate = $this->place($type, $user, 'Duplicate', '51.1000', 'open', 'prohibited');

        $comparison = app(PlaceMergeService::class)->comparison($main, $duplicate);

        $this->assertSame('main', $comparison['fields']['latitude']['suggested']);
        $this->assertSame('duplicate', $comparison['fields']['opening_status']['suggested']);
        $this->assertSame('delete', $comparison['fields']['legal_status']['suggested']);
        $this->assertSame('main', $comparison['fields']['name']['suggested']);
    }

    private function placeType(): int
    {
        return (int) DB::table('place_types')->insertGetId([
            'slug' => 'merge-test-'.Str::lower(Str::random(8)), 'icon_id' => null, 'sort_order' => 10,
            'is_active' => true, 'is_searchable' => true, 'internal_comment' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function place(int $type, User $creator, string $name, string $latitude, string $openingStatus, string $legalStatus = 'unclear'): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $type, 'name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'latitude' => $latitude, 'longitude' => 7.0, 'publication_status' => 'published',
            'legal_status' => $legalStatus, 'opening_status' => $openingStatus, 'is_active' => true,
            'created_by' => $creator->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }


}
