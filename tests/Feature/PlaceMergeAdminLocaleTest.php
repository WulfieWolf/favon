<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlaceMergeAdminLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_merge_administration_is_available_in_german_and_english(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $typeId = (int) DB::table('place_types')->where('is_active', true)->value('id');
        $mainId = $this->place($typeId, $owner, 'Untranslated Main Place', '51.0000');
        $duplicateId = $this->place($typeId, $owner, 'Untranslated Duplicate Place', '51.1000');

        $this->actingAs($owner)
            ->withSession(['locale' => 'de'])
            ->get(route('admin.place-merges.index'))
            ->assertOk()
            ->assertSee('Wähle Hauptplatz und Duplikat.')
            ->assertSee('Hauptplatz auswählen')
            ->assertSee('Duplikat auswählen');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.place-merges.index', ['main' => $mainId, 'duplicate' => $duplicateId]))
            ->assertOk()
            ->assertSee('Select the main place and the duplicate.')
            ->assertSee('Select main place')
            ->assertSee('Select duplicate')
            ->assertSee('Compare')
            ->assertSee('Untranslated Main Place')
            ->assertSee('Untranslated Duplicate Place');

        $this->actingAs($owner)
            ->withSession(['locale' => 'en'])
            ->get(route('admin.place-merges.index', ['main' => $mainId, 'duplicate' => $mainId]))
            ->assertOk()
            ->assertSee('Select main place')
            ->assertSee('Select duplicate')
            ->assertSee('Compare');
    }

    private function place(int $typeId, User $creator, string $name, string $latitude): int
    {
        return (int) DB::table('places')->insertGetId([
            'place_type_id' => $typeId,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'latitude' => $latitude,
            'longitude' => 7.0,
            'publication_status' => 'published',
            'legal_status' => 'prohibited',
            'opening_status' => 'open',
            'is_active' => true,
            'created_by' => $creator->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
