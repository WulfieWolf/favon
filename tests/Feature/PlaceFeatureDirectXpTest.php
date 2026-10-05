<?php

namespace Tests\Feature;

use App\Http\Controllers\PlaceFeatureController;
use App\Services\BadgeService;
use App\Services\XpService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class PlaceFeatureDirectXpTest extends TestCase
{
    public function test_direct_feature_edit_awards_xp_and_badge_progress(): void
    {
        $xp = Mockery::mock(XpService::class);
        $xp->shouldReceive('awardPlaceField')
            ->once()
            ->with(
                7,
                42,
                'feature:13',
                1,
                Mockery::type('string'),
                'place_feature',
                99,
            )
            ->andReturn(true);

        $badges = Mockery::mock(BadgeService::class);
        $badges->shouldReceive('recordApprovedPlaceChange')
            ->once()
            ->withArgs(function (object $request, int $recordId, string $placeName): bool {
                return $request->submitted_by === 7
                    && $request->place_id === 42
                    && $request->target_table === 'place_features'
                    && $recordId === 99
                    && $placeName === 'Testplatz';
            });

        app()->instance(XpService::class, $xp);
        app()->instance(BadgeService::class, $badges);

        $controller = app(PlaceFeatureController::class);
        $method = new ReflectionMethod($controller, 'recordDirectFeatureRewards');
        $method->setAccessible(true);

        $method->invoke($controller, 42, 'Testplatz', 7, [
            'record_id' => 99,
            'feature_id' => 13,
        ]);
    }
}
