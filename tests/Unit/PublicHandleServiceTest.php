<?php

namespace Tests\Unit;

use App\Services\PublicHandleService;
use Tests\TestCase;

class PublicHandleServiceTest extends TestCase
{
    public function test_automatic_handles_are_deterministic_short_and_unique(): void
    {
        $service = app(PublicHandleService::class);
        $seen = [];

        for ($id = 1; $id <= 10000; $id++) {
            $handle = $service->automaticForUserId($id);

            $this->assertMatchesRegularExpression('/^FV-[0-9A-Z]{5}$/', $handle);
            $this->assertSame($handle, $service->automaticForUserId($id));
            $this->assertArrayNotHasKey($handle, $seen);

            $seen[$handle] = true;
        }
    }

    public function test_cw_prefix_is_reserved_for_automatic_handles(): void
    {
        $service = app(PublicHandleService::class);

        $this->assertTrue($service->isReservedAutomaticNamespace('FV-ABCDE'));
        $this->assertTrue($service->isReservedAutomaticNamespace('cw-test'));
        $this->assertFalse($service->isReservedAutomaticNamespace('camperwolf'));
    }
}
