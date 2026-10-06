<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_german_legal_pages_are_public_and_show_operator_details(): void
    {
        $this->withSession(['locale' => 'de'])
            ->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Impressum')
            ->assertSee('Sascha Schwarz')
            ->assertSee('Giesebrechstr. 59')
            ->assertSee('45144 Essen')
            ->assertSee('schwarz.sascha@gmx.de');

        $this->withSession(['locale' => 'de'])
            ->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Datenschutzerklärung')
            ->assertSee('OpenStreetMap')
            ->assertSee('Mein Standort')
            ->assertSee('Plätze in der Umgebung')
            ->assertSee('passende gefilterte Plätze nach ihrer Entfernung')
            ->assertSee('nicht an den Camperwolf-Server übertragen')
            ->assertSee('Photon');

        $this->withSession(['locale' => 'de'])
            ->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Nutzungsbedingungen')
            ->assertSee('Rezensionen')
            ->assertSee('Fotos');
    }

    public function test_english_legal_pages_are_available(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('Legal notice');

        $this->withSession(['locale' => 'en'])
            ->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('Privacy policy')
            ->assertSee('My location')
            ->assertSee('Places nearby')
            ->assertSee('matching filtered places by their distance')
            ->assertSee('is not sent to the Camperwolf server');

        $this->withSession(['locale' => 'en'])
            ->get(route('legal.terms'))
            ->assertOk()
            ->assertSee('Terms of use');
    }

    public function test_login_gateway_stays_minimal_without_legal_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('legal.imprint'), false)
            ->assertDontSee(route('legal.terms'), false)
            ->assertDontSee(route('legal.privacy'), false);
    }

    public function test_fixed_footer_remains_on_public_legal_pages_but_not_login_gateway(): void
    {
        $this->get(route('legal.imprint'))
            ->assertOk()
            ->assertSee('fixed inset-x-0 bottom-0', false)
            ->assertSee(route('legal.imprint'), false)
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('fixed inset-x-0 bottom-0', false);
    }
}
