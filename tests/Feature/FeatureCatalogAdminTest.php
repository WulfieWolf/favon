<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeatureCatalogAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_catalog_is_clean_and_complete(): void
    {
        $dogsCategory = DB::table('feature_categories')->where('slug', 'dogs')->first();

        $this->assertNotNull($dogsCategory);
        $this->assertTrue((bool) $dogsCategory->is_active);
        $this->assertDatabaseHas('features', ['slug' => 'dog-water-station', 'category_id' => $dogsCategory->id, 'is_active' => true]);
        $this->assertDatabaseHas('features', ['slug' => 'level-pitch', 'is_active' => true]);
        $this->assertDatabaseHas('features', ['slug' => 'waste-bins', 'is_active' => true]);
        $this->assertDatabaseHas('features', ['slug' => 'electricity-price', 'is_active' => false]);
        $this->assertDatabaseHas('feature_categories', ['slug' => 'costs', 'is_active' => false]);
        $this->assertDatabaseHas('features', ['slug' => 'dogs-allowed', 'filter_priority' => 30]);

        $maxStayConfig = json_decode(
            DB::table('feature_workflows as fw')
                ->join('features as f', 'f.id', '=', 'fw.feature_id')
                ->where('f.slug', 'max-stay-duration')
                ->value('fw.config'),
            true,
        );

        $this->assertContains('unlimited', collect($maxStayConfig['status_options'])->pluck('value'));
        $this->assertSame('number_unit', $maxStayConfig['details'][0]['type']);

        $evCharging = DB::table('features as f')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->where('f.slug', 'ev-charging')
            ->value('fc.slug');

        $this->assertSame('utilities', $evCharging);
        $this->assertSame(
            DB::table('features')->where('is_active', true)->count(),
            DB::table('feature_workflows')->where('is_active', true)->count(),
        );
    }

    public function test_owner_can_create_and_edit_features_with_audit_log(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $admin->email]);
        $category = DB::table('feature_categories')->where('slug', 'dogs')->first();
        $placeTypes = DB::table('place_types')->pluck('id');
        $visibility = $placeTypes->mapWithKeys(fn ($id) => [$id => 'extended'])->all();
        $priorityPlaceTypeId = (int) $placeTypes->first();

        $this->actingAs($admin)
            ->get(route('admin.features.index', ['search' => 'Hundetränke']))
            ->assertOk()
            ->assertSee('Hundetränke');

        $response = $this->actingAs($admin)->post(route('admin.features.store'), [
            'name_de' => 'Pfotenwaschplatz',
            'name_en' => 'Paw washing station',
            'slug' => 'paw-washing-station',
            'category_id' => $category->id,
            'value_type' => 'boolean',
            'unit_type' => null,
            'sort_order' => 90,
            'filter_priority' => 40,
            'place_type_filter_priority' => [$priorityPlaceTypeId => 15],
            'is_active' => '1',
            'is_searchable' => '1',
            'status_mode' => 'availability',
            'statuses' => [
                ['value' => 'unknown', 'de' => 'Unbekannt', 'en' => 'Unknown'],
                ['value' => 'available', 'de' => 'Vorhanden', 'en' => 'Available'],
                ['value' => 'unavailable', 'de' => 'Nicht vorhanden', 'en' => 'Not available'],
            ],
            'details' => [],
            'visibility' => $visibility,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $featureId = DB::table('features')->where('slug', 'paw-washing-station')->value('id');
        $this->assertNotNull($featureId);
        $this->assertDatabaseHas('feature_workflows', ['feature_id' => $featureId, 'is_active' => true]);
        $this->assertDatabaseHas('features', ['id' => $featureId, 'filter_priority' => 40]);
        $this->assertDatabaseHas('feature_place_types', [
            'feature_id' => $featureId,
            'place_type_id' => $priorityPlaceTypeId,
            'filter_priority' => 15,
        ]);
        $this->assertDatabaseHas('audit_logs', ['entity_type' => 'feature', 'entity_id' => $featureId, 'action' => 'feature_catalog_created']);

        $this->actingAs($admin)->put(route('admin.features.update', $featureId), [
            'name_de' => 'Pfotenwaschplatz am Platz',
            'name_en' => 'On-site paw washing station',
            'slug' => 'paw-washing-station',
            'category_id' => $category->id,
            'value_type' => 'boolean',
            'sort_order' => 95,
            'filter_priority' => 35,
            'place_type_filter_priority' => [$priorityPlaceTypeId => 12],
            'is_active' => '1',
            'status_mode' => 'availability',
            'statuses' => [
                ['value' => 'unknown', 'de' => 'Unbekannt', 'en' => 'Unknown'],
                ['value' => 'available', 'de' => 'Vorhanden', 'en' => 'Available'],
                ['value' => 'unavailable', 'de' => 'Nicht vorhanden', 'en' => 'Not available'],
            ],
            'visibility' => $visibility,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('features', ['id' => $featureId, 'filter_priority' => 35]);
        $this->assertDatabaseHas('feature_place_types', [
            'feature_id' => $featureId,
            'place_type_id' => $priorityPlaceTypeId,
            'filter_priority' => 12,
        ]);
        $this->assertDatabaseHas('translations', [
            'entity_type' => 'feature',
            'entity_id' => $featureId,
            'locale' => 'de',
            'field' => 'name',
            'value' => 'Pfotenwaschplatz am Platz',
        ]);
    }

    public function test_non_admin_cannot_open_catalog_management(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('admin.features.index'))->assertNotFound();
    }
}
