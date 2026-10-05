<?php

namespace Tests\Unit;

use App\Services\PlaceTypeFeatureService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PlaceTypeFeatureServiceTest extends TestCase
{
    public function test_rest_area_uses_rest_specific_categories(): void
    {
        $service = new PlaceTypeFeatureService();

        $categories = $service->standardCategorySlugs('rest-area');

        $this->assertContains('fuel-rest-area', $categories);
        $this->assertContains('sanitary', $categories);
        $this->assertNotContains('rental', $categories);
    }

    public function test_free_pitch_does_not_show_utilities_by_default(): void
    {
        $service = new PlaceTypeFeatureService();

        $this->assertFalse($service->isStandardCategory('free-pitch', 'utilities'));
        $this->assertTrue($service->isStandardCategory('free-pitch', 'access'));
    }

    public function test_quick_groups_keep_all_features_from_relevant_categories(): void
    {
        $service = new PlaceTypeFeatureService();

        $groups = new Collection([
            (object) [
                'slug' => 'utilities',
                'features' => new Collection([
                    (object) ['feature_slug' => 'fresh-water'],
                    (object) ['feature_slug' => 'gas-bottle-exchange'],
                ]),
            ],
            (object) [
                'slug' => 'facilities',
                'features' => new Collection([
                    (object) ['feature_slug' => 'wifi'],
                ]),
            ],
        ]);

        $filtered = $service->quickGroups($groups, 'motorhome-pitch');

        $this->assertCount(1, $filtered);
        $this->assertSame(
            ['fresh-water', 'gas-bottle-exchange'],
            $filtered->first()->features->pluck('feature_slug')->all(),
        );
    }
}
