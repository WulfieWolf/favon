<?php

return [
    'registration' => [
        'notice' => 'Bitte beachte bei der Anmeldung unsere',
        'and' => 'und unsere',
        'accept_prefix' => 'Ich akzeptiere die',
        'and_acknowledge' => 'und habe die',
        'acceptance_required' => 'Bitte bestätige die Nutzungsbedingungen und die Kenntnisnahme der Datenschutzerklärung.',
    ],

    'labels' => [
        'imprint' => 'Impressum',
        'privacy' => 'Datenschutz',
        'terms' => 'Nutzungsbedingungen',
        'photo_rules' => 'Inhaltsregeln',
        'review_rules' => 'Bewertungsregeln',
        'last_updated' => 'Stand: :date',
    ],

    'imprint' => [
        'title' => 'Impressum',
        'subtitle' => 'Anbieterinformationen zu Favon',
        'version' => config('legal.versions.imprint'),
        'sections' => [
            [
                'title' => 'Anbieter',
                'paragraphs' => [
                    config('legal.operator.name')."\n".config('legal.operator.street')."\n".config('legal.operator.postal_code').' '.config('legal.operator.city')."\n".config('legal.operator.country'),
                    config('legal.operator.status'),
                ],
            ],
            [
                'title' => 'Kontakt',
                'paragraphs' => ['E-Mail: '.config('legal.operator.email')],
            ],
            [
                'title' => 'Hinweis zum Projekt',
                'paragraphs' => [
                    'Favon wird derzeit privat betrieben. Sollte sich die rechtliche oder organisatorische Form des Projekts ändern, werden diese Angaben entsprechend aktualisiert.',
                ],
            ],
            [
                'title' => 'Verantwortung für Inhalte',
                'paragraphs' => [
                    'Favon stellt eigene Inhalte sowie von Nutzern beigetragene sachliche Informationen über Orte bereit. Problematische, rechtswidrige oder unzutreffende Inhalte können über die vorgesehenen Melde- und Supportwege gemeldet werden.',
                ],
            ],
            [
                'title' => 'Karten- und Geodaten',
                'paragraphs' => [
                    'Kartendaten und Kartenkacheln basieren teilweise auf OpenStreetMap. Die jeweiligen Urheber- und Lizenzhinweise werden unmittelbar an der Karte angezeigt.',
                ],
            ],
        ],
    ],

    'privacy' => [
        'title' => 'Datenschutzerklärung',
        'subtitle' => 'Informationen darüber, wie Favon personenbezogene Daten verarbeitet.',
        'version' => config('legal.versions.privacy'),
        'sections' => [
            [
                'title' => '1. Verantwortlicher',
                'paragraphs' => [
                    config('legal.operator.name')."\n".config('legal.operator.street')."\n".config('legal.operator.postal_code').' '.config('legal.operator.city')."\n".config('legal.operator.country')."\nE-Mail: ".config('legal.operator.email'),
                ],
            ],
            [
                'title' => '2. Grundsätze der Verarbeitung',
                'paragraphs' => [
                    'Favon verarbeitet personenbezogene Daten nur, soweit dies für den Betrieb des Dienstes, die von dir genutzten Funktionen, die Sicherheit des Systems oder gesetzliche Pflichten erforderlich ist.',
                    'Favon ist nach dem Grundsatz „Places, not people“ aufgebaut. Öffentliche Nutzerprofile, Besucherlisten, Bewegungsprofile und eine öffentliche Zuordnung von Beiträgen zu einzelnen Personen sind nicht vorgesehen.',
                ],
            ],
            [
                'title' => '3. Aufruf der Website und technische Protokolldaten',
                'paragraphs' => [
                    'Beim Aufruf von Favon fallen technisch notwendige Verbindungsdaten an. Dazu können insbesondere IP-Adresse, Zeitpunkt des Zugriffs, aufgerufene Adresse, Browser- bzw. Geräteinformationen und technische Fehlerdaten gehören. Sie dienen der Auslieferung, Systemsicherheit, Fehleranalyse und dem Schutz vor Missbrauch.',
                    'Anwendungsseitige Sicherheitsereignisse verwenden soweit vorgesehen einen aus der Quell-IP abgeleiteten Hash statt der rohen IP-Adresse. Unabhängig davon können technisch notwendige Server- und Hosting-Protokolle bestehen.',
                ],
            ],
            [
                'title' => '4. Community-Konto und Telegram-Anmeldung',
                'paragraphs' => [
                    'Community-Nutzer melden sich über Telegram mittels OpenID Connect (OIDC) an. Favon speichert für diese Zuordnung nur die stabile numerische Telegram-ID sowie die notwendigen internen Konto-, Rollen-, Status- und Zeitinformationen.',
                    'Telegram-Anzeigename, Benutzername, Profilfoto und Telefonnummer werden von Favon nicht als Teil des Community-Logins gespeichert. Beim Aufruf der Telegram-Anmeldung wird dein Browser zu Telegram weitergeleitet; dabei verarbeitet Telegram technisch notwendige Verbindungs- und Anmeldedaten in eigener Verantwortung.',
                    'Für Administratoren und den System Owner kann zusätzlich ein klassischer E-Mail-/Passwort-Zugang verwendet werden. Passwörter werden ausschließlich als Hash gespeichert.',
                ],
                'external_links' => [
                    ['label' => 'Datenschutzinformationen von Telegram', 'url' => 'https://telegram.org/privacy'],
                ],
            ],
            [
                'title' => '5. Beiträge und Interaktionen',
                'paragraphs' => [
                    'Wenn du Ortsdaten ergänzt, strukturierte Bewertungen abgibst, Favoriten verwendest, Meldungen einreichst oder andere verfügbare Community-Funktionen nutzt, verarbeitet Favon die dafür erforderlichen Datensätze mit internem Kontobezug.',
                    'Öffentlich wird nicht angezeigt, wer einen Ort angelegt, bearbeitet, bewertet, favorisiert oder gemeldet hat. Favon bietet keine freien Rezensionstexte und keine Nutzer- oder Ortsfotos als Community-Funktion an.',
                ],
            ],
            [
                'title' => '6. Standortfunktionen',
                'paragraphs' => [
                    'Wenn du eine Standortfunktion ausdrücklich verwendest, fragt dein Browser nach deiner Standortfreigabe. Favon ist darauf ausgelegt, Rohkoordinaten nur für die jeweils angeforderte Standort- oder Näheprüfung zu verwenden und keine dauerhafte Bewegungs- oder Besuchshistorie daraus aufzubauen.',
                    'Soweit die aktuelle Kartenfunktion die Entfernung zu Orten direkt im Browser berechnet, wird dein aktueller Standort nicht als Kontodatenfeld gespeichert.',
                ],
            ],
            [
                'title' => '7. Karten von OpenStreetMap',
                'paragraphs' => [
                    'Favon verwendet OpenStreetMap-basierte Karten. Wenn Kartenkacheln direkt von Servern der OpenStreetMap Foundation oder eines anderen Kartenanbieters geladen werden, stellt dein Browser eine direkte Verbindung zu diesem Anbieter her; dabei werden technisch notwendige Verbindungsdaten wie die IP-Adresse übertragen.',
                ],
                'external_links' => [
                    ['label' => 'Datenschutzinformationen der OpenStreetMap Foundation', 'url' => 'https://osmfoundation.org/wiki/Privacy_Policy'],
                ],
            ],
            [
                'title' => '8. Orts- und Adresssuche',
                'paragraphs' => [
                    'Soweit Favon für Orts- oder Adresssuchen einen externen Geodienst wie Photon verwendet, werden Suchbegriffe oder Koordinaten sowie technisch notwendige Verbindungsdaten an den jeweiligen Dienst übertragen. Eine solche Anfrage erfolgt nur im Zusammenhang mit der entsprechenden Suchfunktion.',
                ],
                'external_links' => [
                    ['label' => 'Datenschutzerklärung der komoot GmbH', 'url' => 'https://www.komoot.com/de-de/privacy'],
                ],
            ],
            [
                'title' => '9. Cookies, Sitzungen und interne Statistik',
                'paragraphs' => [
                    'Favon verwendet technisch erforderliche Session- und Sicherheits-Cookies für Anmeldung, Sitzungsverwaltung und Schutz vor missbräuchlichen Anfragen. Eine von dir gewählte Sprache kann ebenfalls gespeichert werden.',
                    'Favon setzt derzeit keine Werbe-, Marketing- oder externen Analyse-Tracker ein. Für die aktuell ausschließlich technisch erforderlichen Browser-Speicherungen ist daher kein allgemeiner Einwilligungs- oder Cookie-Banner vorgesehen.',
                    'Für interne Nutzungsstatistiken können Seitenaufrufe und ausgewählte Funktionsereignisse ohne User-ID, rohe IP-Adresse, Session-ID, dauerhafte Besucherkennung oder Fingerprint gespeichert werden.',
                ],
            ],
            [
                'title' => '10. Support und Meldungen',
                'paragraphs' => [
                    'Bei Supportanfragen oder Meldungen verarbeitet Favon die von dir übermittelten Angaben und den zur Bearbeitung notwendigen Kontext. Bei angemeldeten Nutzern kann eine Anfrage intern dem Konto zugeordnet werden.',
                    'Support- und Meldedaten werden nur so lange aufbewahrt, wie sie für Bearbeitung, Nachvollziehbarkeit, Missbrauchsschutz oder rechtliche Pflichten und Ansprüche erforderlich sind.',
                ],
            ],
            [
                'title' => '11. Speicherdauer und Accountlöschung',
                'paragraphs' => [
                    'Favon speichert personenbezogene Daten grundsätzlich nur so lange, wie sie für den jeweiligen Zweck benötigt werden. Sicherheitsereignisse werden nach der jeweils festgelegten technischen Aufbewahrungsfrist bereinigt; fertige Self-Service-Datenexporte werden zeitlich begrenzt bereitgestellt und anschließend gelöscht.',
                    'Bei einer endgültigen Accountlöschung werden persönliche Kontodaten nach dem implementierten Löschverfahren entfernt oder irreversibel anonymisiert. Sachliche Ortsdaten können ohne öffentlichen Personenbezug erhalten bleiben.',
                ],
            ],
            [
                'title' => '12. Deine Rechte',
                'paragraphs' => [
                    'Nach Maßgabe der DSGVO hast du insbesondere Rechte auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit und – soweit die Voraussetzungen vorliegen – Widerspruch.',
                    'Favon stellt zusätzlich einen Self-Service-Datenexport bereit. Diese Komfortfunktion beschränkt dein gesetzliches Auskunftsrecht nicht.',
                    'Datenschutzanfragen kannst du an '.config('legal.operator.email').' richten.',
                ],
            ],
            [
                'title' => '13. Beschwerderecht',
                'paragraphs' => [
                    'Du hast das Recht, dich bei einer Datenschutz-Aufsichtsbehörde über die Verarbeitung deiner personenbezogenen Daten zu beschweren.',
                ],
            ],
            [
                'title' => '14. Änderungen dieser Datenschutzerklärung',
                'paragraphs' => [
                    'Favon passt diese Datenschutzerklärung an, wenn sich Funktionen, eingesetzte Dienste oder rechtliche Rahmenbedingungen ändern. Der aktuelle Stand wird auf dieser Seite veröffentlicht.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Nutzungsbedingungen',
        'subtitle' => 'Regeln für die Nutzung von Favon.',
        'version' => config('legal.versions.terms'),
        'sections' => [
            [
                'title' => '1. Geltungsbereich',
                'paragraphs' => [
                    'Diese Nutzungsbedingungen gelten für Favon und die dort angebotenen Funktionen. Der eigentliche Ortsbestand ist nur nach Anmeldung zugänglich.',
                    'Favon ist ein ortsbezogenes Community-Verzeichnis. Der Grundsatz lautet „Places, not people“: Favon ist kein Dating-, Kontaktanzeigen-, Personenfinde- oder Escort-Dienst.',
                ],
            ],
            [
                'title' => '2. Benutzerkonto',
                'paragraphs' => [
                    'Community-Konten werden über Telegram angemeldet. Ein Konto darf nicht missbräuchlich verwendet oder Dritten zur Nutzung überlassen werden.',
                    'Ein Anspruch auf eine bestimmte dauerhafte Funktionsausstattung oder jederzeitige Verfügbarkeit besteht nicht.',
                ],
            ],
            [
                'title' => '3. Ortsdaten und Community-Beiträge',
                'paragraphs' => [
                    'Beigetragene Informationen müssen nach bestem Wissen sachbezogen, zutreffend und rechtmäßig sein. Personenbezogene Angaben über Besucher oder andere Dritte gehören nicht in Ortsdaten.',
                    'Favon kann Einträge und Änderungen prüfen, korrigieren, zusammenführen, ablehnen, ausblenden oder entfernen, wenn sie unzutreffend, unpassend, rechtswidrig oder missbräuchlich sind.',
                ],
            ],
            [
                'title' => '4. Unzulässige Inhalte und Nutzungen',
                'paragraphs' => [
                    'Nicht zulässig sind insbesondere private Wohnadressen als Kontakt- oder Treffanzeigen, Doxxing, persönliche Anzeigen, personenbezogene Besucherbeschreibungen, pornografische Inhalte, explizite sexuelle Erlebnisberichte, Werbung oder Spam sowie Inhalte, die Rechte Dritter verletzen.',
                    'Favon bietet keine Nutzerfotos, freien Rezensionstexte, Chats, Direktnachrichten, Dating- oder Matching-Funktionen an.',
                ],
            ],
            [
                'title' => '5. Strukturierte Bewertungen',
                'paragraphs' => [
                    'Soweit strukturierte Bewertungen angeboten werden, sollen sie eigene tatsächliche Beobachtungen zum Ort sachlich wiedergeben. Manipulation, Mehrfachmissbrauch und wissentlich falsche Angaben sind unzulässig.',
                ],
            ],
            [
                'title' => '6. Moderation und Maßnahmen',
                'paragraphs' => [
                    'Bei Regelverstößen, Missbrauch oder Sicherheitsproblemen kann Favon Inhalte ablehnen oder entfernen und Funktionen oder Konten vorübergehend oder dauerhaft einschränken.',
                    'Problematische Orte oder Inhalte können über die vorgesehenen Melde- und Supportwege gemeldet werden.',
                ],
            ],
            [
                'title' => '7. Keine Garantie für Ortsinformationen',
                'paragraphs' => [
                    'Community-Informationen können trotz Prüfungen veraltet, unvollständig oder fehlerhaft sein. Örtliche Regeln, Zugänglichkeit und tatsächliche Bedingungen können sich kurzfristig ändern.',
                    'Favon ist keine Zusicherung dafür, dass ein Ort jederzeit zugänglich, legal nutzbar, sicher oder für einen bestimmten Zweck geeignet ist.',
                ],
            ],
            [
                'title' => '8. Verfügbarkeit und Haftung',
                'paragraphs' => [
                    'Favon bemüht sich um einen zuverlässigen Betrieb, kann aber keine jederzeitige unterbrechungsfreie Verfügbarkeit zusagen.',
                    'Für Vorsatz und grobe Fahrlässigkeit sowie in gesetzlich zwingenden Fällen gilt die gesetzliche Haftung. Im Übrigen richtet sich die Haftung nach den anwendbaren gesetzlichen Vorschriften.',
                ],
            ],
            [
                'title' => '9. Accountlöschung',
                'paragraphs' => [
                    'Du kannst die Löschung deines Accounts über die dafür vorgesehene Funktion anstoßen. Persönliche Kontodaten werden nach dem implementierten Löschverfahren entfernt oder anonymisiert; sachliche Ortsdaten können ohne Personenbezug erhalten bleiben.',
                ],
            ],
            [
                'title' => '10. Änderungen und Kontakt',
                'paragraphs' => [
                    'Diese Nutzungsbedingungen können angepasst werden, wenn sich Favon oder die rechtlichen Rahmenbedingungen wesentlich ändern.',
                    'Fragen kannst du an '.config('legal.operator.email').' oder über den Hilfe- und Supportbereich richten.',
                ],
            ],
        ],
    ],
];
