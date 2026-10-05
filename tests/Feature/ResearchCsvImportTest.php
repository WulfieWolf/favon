<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResearchCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_research_csv_stages_new_value_without_changing_place_data(): void
    {
        [$owner, $placeId] = $this->ownerAndPlace();

        $csv = $this->csv([
            [$placeId, 'KI-gestützte Ergänzung', 'Betreiberwebsite', 'https://example.org/kontakt', '2026-09-30 16:00:00', '', '', '+49 201 123456'],
        ], ['name', 'phone']);

        $this->actingAs($owner)
            ->post(route('admin.imports.research-upload'), [
                'research_file' => UploadedFile::fake()->createWithContent('research.csv', $csv),
            ])
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasNoErrors();

        $source = DB::table('external_sources')->where('slug', 'research-import')->first();
        $this->assertNotNull($source);

        $record = DB::table('external_records')
            ->where('external_source_id', $source->id)
            ->where('place_id', $placeId)
            ->first();

        $this->assertNotNull($record);
        $this->assertSame('research_update', $record->classification);

        $this->assertDatabaseHas('external_record_fields', [
            'external_record_id' => $record->id,
            'field_key' => 'phone',
        ]);

        $this->assertDatabaseHas('external_import_review_items', [
            'external_record_id' => $record->id,
            'place_id' => $placeId,
            'type' => 'research_update',
            'status' => 'pending',
        ]);

        $this->assertFalse(
            DB::table('place_contacts')
                ->where('place_id', $placeId)
                ->where('contact_type', 'phone')
                ->exists()
        );
    }

    public function test_identical_research_value_is_staged_without_review(): void
    {
        [$owner, $placeId] = $this->ownerAndPlace();

        $csv = $this->csv([
            [$placeId, 'Sascha manuell recherchiert', 'Vor Ort erhoben', '', '', '', 'Bestehender Platz'],
        ], ['name']);

        $this->actingAs($owner)
            ->post(route('admin.imports.research-upload'), [
                'research_file' => UploadedFile::fake()->createWithContent('research.csv', $csv),
            ])
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasNoErrors();

        $record = DB::table('external_records')
            ->where('place_id', $placeId)
            ->where('classification', 'research_no_change')
            ->first();

        $this->assertNotNull($record);
        $this->assertDatabaseCount('external_import_review_items', 0);
    }

    public function test_different_values_from_two_research_sources_are_marked_as_conflicts(): void
    {
        [$owner, $placeId] = $this->ownerAndPlace();

        $csv = $this->csv([
            [$placeId, 'Recherche A', 'Quelle A', 'https://a.example.test', '', '', '111'],
            [$placeId, 'Recherche B', 'Quelle B', 'https://b.example.test', '', '', '222'],
        ], ['phone']);

        $this->actingAs($owner)
            ->post(route('admin.imports.research-upload'), [
                'research_file' => UploadedFile::fake()->createWithContent('research.csv', $csv),
            ])
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            2,
            DB::table('external_import_review_items')
                ->where('place_id', $placeId)
                ->where('type', 'research_conflict')
                ->where('status', 'pending')
                ->count()
        );
    }

    public function test_research_csv_accepts_tab_delimited_excel_export(): void
    {
        [$owner, $placeId] = $this->ownerAndPlace();

        $csv = "place_id\tauthor\tsource_label\tsource_url\tresearched_at\tnotes\tphone\n"
            .$placeId."\tSascha\tMaps\thttps://www.google.de\t\t\t123456\n";

        $this->actingAs($owner)
            ->post(route('admin.imports.research-upload'), [
                'research_file' => UploadedFile::fake()->createWithContent('research.csv', $csv),
            ])
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('external_import_review_items', [
            'place_id' => $placeId,
            'type' => 'research_update',
            'status' => 'pending',
        ]);
    }

    public function test_research_csv_accepts_decimal_comma_coordinates(): void
    {
        [$owner, $placeId] = $this->ownerAndPlace();

        $csv = $this->csv([
            [$placeId, 'Sascha', 'Maps', 'https://www.google.de', '', '', '51,1234567', '7,7654321'],
        ], ['latitude', 'longitude']);

        $this->actingAs($owner)
            ->post(route('admin.imports.research-upload'), [
                'research_file' => UploadedFile::fake()->createWithContent('research.csv', $csv),
            ])
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasNoErrors();

        $record = DB::table('external_records')
            ->where('place_id', $placeId)
            ->where('classification', 'research_update')
            ->first();

        $this->assertNotNull($record);
        $data = json_decode((string) $record->normalized_data, true);
        $this->assertSame(51.1234567, $data['proposals']['latitude']);
        $this->assertSame(7.7654321, $data['proposals']['longitude']);
    }

    public function test_research_csv_requires_provenance_for_rows_with_proposals(): void
    {
        [$owner, $placeId] = $this->ownerAndPlace();

        $csv = $this->csv([
            [$placeId, '', 'Betreiberwebsite', '', '', '', '111'],
        ], ['phone']);

        $this->actingAs($owner)
            ->post(route('admin.imports.research-upload'), [
                'research_file' => UploadedFile::fake()->createWithContent('research.csv', $csv),
            ])
            ->assertRedirect(route('admin.imports.index'))
            ->assertSessionHasErrors('research_file');

        $this->assertDatabaseCount('external_records', 0);
    }

    private function ownerAndPlace(): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        config(['camperwolf.owner_email' => $owner->email]);

        $placeTypeId = (int) DB::table('place_types')->insertGetId([
            'slug' => 'research-import-test',
            'is_active' => true,
            'is_searchable' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $placeId = (int) DB::table('places')->insertGetId([
            'place_type_id' => $placeTypeId,
            'name' => 'Bestehender Platz',
            'slug' => 'bestehender-platz',
            'latitude' => 51.45,
            'longitude' => 7.01,
            'publication_status' => 'published',
            'legal_status' => 'unclear',
            'opening_status' => 'unclear',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$owner, $placeId];
    }

    private function csv(array $rows, array $proposalColumns): string
    {
        $headers = array_merge([
            'place_id',
            'author',
            'source_label',
            'source_url',
            'researched_at',
            'notes',
        ], $proposalColumns);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $headers, ';', '"', '');

        foreach ($rows as $row) {
            fputcsv($stream, $row, ';', '"', '');
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content === false ? '' : $content;
    }
}
