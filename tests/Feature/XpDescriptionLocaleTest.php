<?php

namespace Tests\Feature;

use App\Services\XpService;
use Tests\TestCase;

class XpDescriptionLocaleTest extends TestCase
{
    protected function tearDown(): void
    {
        app()->setLocale((string) config('app.locale', 'en'));

        parent::tearDown();
    }

    public function test_system_generated_xp_descriptions_are_localized_at_display_time(): void
    {
        $service = app(XpService::class);

        app()->setLocale('de');
        $this->assertSame(
            'Foto zu „Testplatz“ hinzugefügt',
            $service->localizedDescription($this->entry('photo_upload', 'photo', 'Testplatz')),
        );
        $this->assertSame(
            'Straße bei „Testplatz“ ergänzt oder aktualisiert',
            $service->localizedDescription($this->entry('place_info', 'place_addresses.street', 'Testplatz', 'change_request')),
        );
        $this->assertSame(
            'Bewertung „Sauberkeit“ für „Testplatz“ abgegeben',
            $service->localizedDescription($this->entry('rating_dimension', 'rating:cleanliness', 'Testplatz')),
        );

        app()->setLocale('en');
        $this->assertSame(
            'Photo added to “Testplatz”',
            $service->localizedDescription($this->entry('photo_upload', 'photo', 'Testplatz')),
        );
        $this->assertSame(
            'Street added or updated for “Testplatz”',
            $service->localizedDescription($this->entry('place_info', 'place_addresses.street', 'Testplatz', 'change_request')),
        );
        $this->assertSame(
            '“Cleanliness” rated for “Testplatz”',
            $service->localizedDescription($this->entry('rating_dimension', 'rating:cleanliness', 'Testplatz')),
        );
        $this->assertSame(
            'Feature added for “Testplatz”',
            $service->localizedDescription($this->entry('place_info', 'feature:123', 'Testplatz', 'place_feature')),
        );

        $this->assertSame(
            'Original manual reason',
            $service->localizedDescription((object) [
                'event_type' => 'manual_award',
                'action_key' => 'manual',
                'source_type' => null,
                'place_name' => null,
                'description' => 'Original manual reason',
            ]),
        );
    }

    private function entry(string $eventType, string $actionKey, string $placeName, ?string $sourceType = null): object
    {
        return (object) [
            'event_type' => $eventType,
            'action_key' => $actionKey,
            'source_type' => $sourceType,
            'place_name' => $placeName,
            'description' => 'Legacy German description',
        ];
    }
}
