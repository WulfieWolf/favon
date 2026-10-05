<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_rules_help_article_is_available_in_german(): void
    {
        $this->withSession(['locale' => 'de'])
            ->get(route('help.show', 'regeln-und-empfehlungen-fuer-fotouploads'))
            ->assertOk()
            ->assertSee('Regeln und Empfehlungen für Fotouploads')
            ->assertSee('Keine Nacktheit oder sexualisierten Inhalte')
            ->assertSee('Lade Fotos hoch, die anderen Campern helfen');
    }

    public function test_photo_rules_help_article_is_available_in_english(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get(route('help.show', 'regeln-und-empfehlungen-fuer-fotouploads'))
            ->assertOk()
            ->assertSee('Rules and recommendations for photo uploads')
            ->assertSee('No nudity or sexualised content')
            ->assertSee('Upload photos that help other campers');
    }
}
