<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
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

    public function test_login_gateway_links_to_terms_and_privacy(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('legal.terms'), false)
            ->assertSee(route('legal.privacy'), false);
    }

    public function test_fixed_footer_is_available_on_app_and_auth_pages(): void
    {
        foreach ([route('legal.imprint'), route('home')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('fixed inset-x-0 bottom-0', false)
                ->assertSee(route('legal.imprint'), false)
                ->assertSee(route('legal.privacy'), false)
                ->assertSee(route('legal.terms'), false);
        }
    }
}
