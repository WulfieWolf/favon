<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupportLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_and_support_interface_is_available_in_german_and_english(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['locale' => 'de'])
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Hilfe &amp; Support', false)
            ->assertSee('Meldung erstellen');

        $this->actingAs($user)->withSession(['locale' => 'en'])
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Help &amp; support', false)
            ->assertSee('Submit a report');

        $this->withSession(['locale' => 'en'])
            ->get(route('support.report'))
            ->assertOk()
            ->assertSee('Submit a report')
            ->assertSee('Report a bug');
    }

    public function test_help_article_can_link_to_another_help_article_by_slug(): void
    {
        DB::table('support_articles')->insert([
            [
                'slug' => 'source-article',
                'title' => 'Source article',
                'summary' => null,
                'body' => 'See [[target-article|the related guide]].',
                'context_key' => null,
                'sort_order' => 1,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'slug' => 'target-article',
                'title' => 'Target article',
                'summary' => null,
                'body' => 'Target body',
                'context_key' => null,
                'sort_order' => 2,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs(User::factory()->create())->get(route('help.show', 'source-article'))
            ->assertOk()
            ->assertSee('the related guide')
            ->assertSee(route('help.show', 'target-article'), false)
            ->assertDontSee('[[target-article', false);
    }

    public function test_help_article_uses_the_requested_translation_and_keeps_its_slug(): void
    {
        $articleId = DB::table('support_articles')->insertGetId([
            'slug' => 'translation-test',
            'title' => 'Deutscher Hilfetitel',
            'summary' => 'Deutsche Zusammenfassung',
            'body' => 'Deutscher Hilfetext',
            'context_key' => null,
            'sort_order' => 1,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('support_article_translations')->insert([
            [
                'support_article_id' => $articleId,
                'locale' => 'de',
                'title' => 'Deutscher Hilfetitel',
                'summary' => 'Deutsche Zusammenfassung',
                'body' => 'Deutscher Hilfetext',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'support_article_id' => $articleId,
                'locale' => 'en',
                'title' => 'English help title',
                'summary' => 'English summary',
                'body' => 'English help text',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['locale' => 'de'])
            ->get(route('help.show', 'translation-test'))
            ->assertOk()
            ->assertSee('Deutscher Hilfetitel')
            ->assertSee('Deutscher Hilfetext');

        $this->actingAs($user)->withSession(['locale' => 'en'])
            ->get(route('help.show', 'translation-test'))
            ->assertOk()
            ->assertSee('English help title')
            ->assertSee('English help text')
            ->assertDontSee('Deutscher Hilfetext');
    }
}
