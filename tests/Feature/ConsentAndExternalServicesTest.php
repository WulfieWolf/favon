<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsentAndExternalServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_describes_current_storage_without_tracking_banner(): void
    {
        $this->withSession(['locale' => 'de'])
            ->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('keine Werbe-, Marketing- oder externen Analyse-Tracker')
            ->assertSee('kein allgemeiner Einwilligungs- oder Cookie-Banner');
    }

    public function test_leaflet_is_bundled_locally_instead_of_loaded_from_unpkg(): void
    {
        $package = file_get_contents(base_path('package.json'));
        $javascript = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString('"leaflet": "^1.9.4"', $package);
        $this->assertStringContainsString("import L from 'leaflet';", $javascript);
        $this->assertStringContainsString("import 'leaflet/dist/leaflet.css';", $javascript);
        $this->assertStringContainsString("marker-icon.png", $javascript);
        $this->assertStringContainsString("marker-shadow.png", $javascript);

        foreach ([
            resource_path('views/dashboard.blade.php'),
            resource_path('views/places/show.blade.php'),
        ] as $view) {
            $this->assertStringNotContainsString('unpkg.com', file_get_contents($view));
        }
    }

}
