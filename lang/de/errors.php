<?php

return [
    'eyebrow' => 'Fehler :status',
    'photo_placeholder' => 'Merlin zieht hier später den passenden traurigen Blick.',
    'home' => 'Zur Startseite',
    'report' => 'Fehler melden',
    'reload' => 'Seite neu laden',
    '4xx' => [
        'title' => 'Diese Anfrage hat leider nicht funktioniert.',
        'message' => 'Die angeforderte Seite oder Aktion konnte so nicht ausgeführt werden.',
        'technical' => 'Anfrage konnte nicht verarbeitet werden',
    ],
    '5xx' => [
        'title' => 'Oh oh, da ist etwas schiefgelaufen.',
        'message' => 'Camperwolf konnte die Anfrage gerade nicht korrekt verarbeiten.',
        'technical' => 'Interner Serverfehler',
    ],
    '404' => [
        'title' => 'Hier gibt es leider nichts.',
        'message' => 'Die Seite wurde nicht gefunden. Vielleicht wurde sie verschoben, gelöscht oder der Link ist nicht mehr aktuell.',
        'technical' => '404 - Seite nicht gefunden',
    ],
    '403' => [
        'title' => 'Hier darfst du leider nicht rein.',
        'message' => 'Für diese Seite oder Aktion fehlen dir die nötigen Berechtigungen.',
        'technical' => '403 - Zugriff nicht erlaubt',
    ],
    '419' => [
        'title' => 'Deine Sitzung ist abgelaufen.',
        'message' => 'Bitte lade die Seite neu und versuche es noch einmal.',
        'technical' => '419 - Sitzung abgelaufen',
    ],
    '500' => [
        'title' => 'Oh oh, da ist etwas schiefgelaufen.',
        'message' => 'Camperwolf konnte die Anfrage gerade nicht korrekt verarbeiten.',
        'technical' => '500 - Interner Fehler',
    ],
    '503' => [
        'title' => 'Camperwolf macht gerade eine kurze Pause.',
        'message' => 'Die Seite ist vorübergehend nicht verfügbar. Bitte versuche es in Kürze noch einmal.',
        'technical' => '503 - Vorübergehend nicht verfügbar',
    ],
];
