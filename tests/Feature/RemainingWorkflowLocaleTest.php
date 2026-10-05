<?php

namespace Tests\Feature;

use Tests\TestCase;

class RemainingWorkflowLocaleTest extends TestCase
{
    protected function tearDown(): void
    {
        app()->setLocale((string) config('app.locale', 'en'));

        parent::tearDown();
    }

    public function test_remaining_user_visible_workflow_errors_are_available_in_german_and_english(): void
    {
        $messages = [
            'de' => [
                'place_editing.opening_hours.errors.workflow_unavailable' => 'Der Workflow für Öffnungszeiten ist derzeit nicht verfügbar.',
                'place_editing.price.errors.offer_missing' => 'Das Preisangebot existiert nicht mehr.',
                'places.suggest.workflow_unavailable' => 'Der Vorschlags-Workflow ist derzeit nicht verfügbar. Bitte versuche es später erneut.',
                'feature_workflow.errors.unavailable' => 'Der Merkmals-Workflow ist derzeit nicht verfügbar. Bitte versuche es später erneut.',
                'admin.change_requests.processing_failed' => 'Der Änderungsvorschlag konnte nicht verarbeitet werden. Bitte prüfe den aktuellen Datenstand und versuche es erneut.',
            ],
            'en' => [
                'place_editing.opening_hours.errors.workflow_unavailable' => 'The opening-hours workflow is currently unavailable.',
                'place_editing.price.errors.offer_missing' => 'The price offer no longer exists.',
                'places.suggest.workflow_unavailable' => 'The suggestion workflow is currently unavailable. Please try again later.',
                'feature_workflow.errors.unavailable' => 'The feature workflow is currently unavailable. Please try again later.',
                'admin.change_requests.processing_failed' => 'The change suggestion could not be processed. Please review the current data and try again.',
            ],
        ];

        foreach ($messages as $locale => $translations) {
            app()->setLocale($locale);

            foreach ($translations as $key => $expected) {
                $this->assertSame($expected, __($key), "{$locale}: {$key}");
            }
        }
    }
}
