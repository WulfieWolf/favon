<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeatureCatalogAdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_catalog_administration_is_available_in_german_and_english(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.features.index'))
            ->assertOk()
            ->assertSee('Merkmale, Eingabeoptionen, Details und Platztyp-Zuordnungen zentral pflegen.')
            ->assertSee('Merkmal hinzufügen')
            ->assertSee('Alle Kategorien');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.features.index'))
            ->assertOk()
            ->assertSee('Manage features, input options, details and place-type assignments centrally.')
            ->assertSee('Add feature')
            ->assertSee('All categories')
            ->assertSee('German name')
            ->assertSee('English name');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.features.index', ['section' => 'categories']))
            ->assertOk()
            ->assertSee('Add category')
            ->assertSee('Internal note');

        $category = DB::table('feature_categories')->where('is_active', true)->first();
        $visibility = DB::table('place_types')->where('is_active', true)->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => 'extended'])->all();

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->post(route('admin.features.store'), [
                'name_de' => 'Unübersetzter Testinhalt',
                'name_en' => 'Untranslated test content',
                'slug' => 'locale-test-feature',
                'category_id' => $category->id,
                'value_type' => 'boolean',
                'unit_type' => null,
                'sort_order' => 999,
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
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('ui_toast', 'Feature created.');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.features.index', ['search' => 'locale-test-feature']))
            ->assertOk()
            ->assertSee('Untranslated test content')
            ->assertSee('Unübersetzter Testinhalt');
    }
}
