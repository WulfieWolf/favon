<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_rules_help_article_is_available_in_german(): void
    {
        $this->withSession(['locale' => 'de'])
            ->get(route('help.show', 'regeln-und-empfehlungen-fuer-rezensionen'))
            ->assertOk()
            ->assertSee('Regeln und Empfehlungen für Rezensionen')
            ->assertSee('Keine erfundenen Erfahrungen')
            ->assertSee('Schreibe eine Rezension, die anderen Campern hilft');
    }

    public function test_review_rules_help_article_is_available_in_english(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get(route('help.show', 'regeln-und-empfehlungen-fuer-rezensionen'))
            ->assertOk()
            ->assertSee('Rules and recommendations for reviews')
            ->assertSee('No invented experiences')
            ->assertSee('Write a review that helps other campers');
    }
}
