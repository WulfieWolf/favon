<?php

return [
    'registration' => [
        'notice' => 'Bitte beachte bei der Registrierung unsere',
        'and' => 'und unsere',
        'accept_prefix' => 'Ich akzeptiere die',
        'and_acknowledge' => 'und habe die',
        'acceptance_required' => 'Bitte bestätige die Nutzungsbedingungen und die Kenntnisnahme der Datenschutzerklärung.',
    ],

    'labels' => [
        'imprint' => 'Impressum',
        'privacy' => 'Datenschutz',
        'terms' => 'Nutzungsbedingungen',
        'photo_rules' => 'Fotoregeln',
        'review_rules' => 'Rezensionsregeln',
        'last_updated' => 'Stand: :date',
    ],

    'imprint' => [
        'title' => 'Impressum',
        'subtitle' => 'Anbieterinformationen zu Camperwolf.de',
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
                'paragraphs' => [
                    'E-Mail: '.config('legal.operator.email'),
                ],
            ],
            [
                'title' => 'Hinweis zum Projekt',
                'paragraphs' => [
                    'Camperwolf wird derzeit privat und ohne Gewerbebetrieb geführt. Sollte sich die rechtliche oder organisatorische Form des Projekts ändern, werden diese Angaben entsprechend aktualisiert.',
                ],
            ],
            [
                'title' => 'Verantwortung für Inhalte',
                'paragraphs' => [
                    'Camperwolf stellt eigene Inhalte sowie von Nutzern beigetragene Informationen bereit. Community-Inhalte werden nach den geltenden Regeln moderiert. Hinweise auf rechtswidrige, unzutreffende oder sonst problematische Inhalte können über die Melde- und Supportfunktionen eingereicht werden.',
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
        'subtitle' => 'Informationen darüber, wie Camperwolf personenbezogene Daten verarbeitet.',
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
                    'Camperwolf verarbeitet personenbezogene Daten nur, soweit dies für den Betrieb der Website und der Community-Funktionen erforderlich ist, du Daten selbst bereitstellst oder eine andere gesetzliche Grundlage besteht.',
                    'Je nach Verarbeitung beruht dies insbesondere auf Art. 6 Abs. 1 lit. b DSGVO zur Bereitstellung angeforderter Konto- und Community-Funktionen, Art. 6 Abs. 1 lit. c DSGVO zur Erfüllung gesetzlicher Pflichten oder Art. 6 Abs. 1 lit. f DSGVO zur sicheren, zuverlässigen und missbrauchsarmen Bereitstellung des Dienstes.',
                ],
            ],
            [
                'title' => '3. Aufruf der Website und technische Protokolldaten',
                'paragraphs' => [
                    'Beim Aufruf von Camperwolf fallen technisch notwendige Verbindungsdaten an. Dazu können insbesondere IP-Adresse, Zeitpunkt des Zugriffs, aufgerufene Adresse, Browser- bzw. Geräteinformationen und technische Fehlerdaten gehören. Diese Daten dienen der Auslieferung der Website, der Systemsicherheit, Fehleranalyse und dem Schutz vor Missbrauch.',
                    'Sicherheitsereignisse werden innerhalb von Camperwolf soweit vorgesehen mit gehashter statt roher IP-Adresse gespeichert. Für produktive Webserver- und Hosting-Protokolle werden Speicherfristen und die konkrete Hostingkonfiguration vor dem öffentlichen Go-live abschließend festgelegt.',
                ],
            ],
            [
                'title' => '4. Benutzerkonto und Anmeldung',
                'paragraphs' => [
                    'Bei der Registrierung und Kontonutzung verarbeitet Camperwolf insbesondere Namen, E-Mail-Adresse, Passwort-Hash, Spracheinstellung, Verifizierungsstatus und technische Sitzungsdaten. Das Passwort selbst wird nicht im Klartext gespeichert.',
                    'Für angemeldete Sitzungen verwendet Camperwolf technisch erforderliche Session- und Sicherheitsinformationen. Passwort-Reset und E-Mail-Verifizierung verarbeiten die dafür notwendigen Kontakt- und Tokeninformationen.',
                ],
            ],
            [
                'title' => '5. Community-Profil',
                'paragraphs' => [
                    'Du kannst freiwillige Profilangaben wie Profilbild, Bio, Heimatort, Geburtsdatum, Geschlecht und Fahrzeugdaten hinterlegen. Für viele Profilbereiche kannst du selbst festlegen, ob sie öffentlich, nur für registrierte Nutzer oder privat sichtbar sind.',
                    'Das vollständige Geburtsdatum wird nicht anderen Nutzern angezeigt. Soweit du die Anzeige deines Alters erlaubst, wird daraus lediglich das Alter berechnet.',
                ],
            ],
            [
                'title' => '6. Beiträge, Bewertungen und Fotos',
                'paragraphs' => [
                    'Wenn du Plätze anlegst, Informationen ergänzt, Bewertungen oder Rezensionen veröffentlichst, Fotos hochlädst oder andere Community-Funktionen nutzt, verarbeitet Camperwolf die jeweiligen Inhalte zusammen mit deinem Nutzerbezug und den erforderlichen Zeit- und Statusinformationen.',
                    'Rezensionen werden derzeit unmittelbar veröffentlicht und können nach Meldung moderiert werden. Fotos werden vor ihrer öffentlichen Anzeige verarbeitet und moderiert. Bei der Bildverarbeitung werden vorhandene Bildmetadaten einschließlich EXIF-/GPS-Metadaten aus den veröffentlichten Varianten entfernt.',
                    'Für Fotos und Rezensionen gelten ergänzende Inhaltsregeln, die im Hilfe-Bereich abrufbar sind.',
                ],
                'links' => [
                    ['label' => 'Fotoregeln', 'route' => 'help.show', 'parameter' => 'regeln-und-empfehlungen-fuer-fotouploads'],
                    ['label' => 'Rezensionsregeln', 'route' => 'help.show', 'parameter' => 'regeln-und-empfehlungen-fuer-rezensionen'],
                ],
            ],
            [
                'title' => '7. Favoriten, Interaktionen, Gamification und Benachrichtigungen',
                'paragraphs' => [
                    'Camperwolf speichert nutzerbezogene Interaktionen wie Favoriten, Helpful-Stimmen sowie für die freiwilligen Community-Funktionen erforderliche XP-, Badge- und Aktivitätsdaten. Benachrichtigungen und zugehörige Ereignisse werden verarbeitet, um dich über für dein Konto relevante Vorgänge zu informieren.',
                ],
            ],
            [
                'title' => '8. Support und Meldungen',
                'paragraphs' => [
                    'Bei Supportanfragen oder Meldungen werden die von dir übermittelten Angaben verarbeitet. Bei angemeldeten Nutzern kann die Anfrage dem Konto zugeordnet werden. Bei Gastanfragen können Name, E-Mail-Adresse und optional eine Telefonnummer verarbeitet werden. Zusätzlich können technischer Seitenkontext und Browserinformationen zur Fehleranalyse übermittelt werden.',
                    'Supportdaten werden nur so lange aufbewahrt, wie sie für Bearbeitung, Nachvollziehbarkeit sowie gegebenenfalls zur Erfüllung gesetzlicher Pflichten oder zur Geltendmachung, Ausübung oder Verteidigung von Rechtsansprüchen benötigt werden. Konkrete Aufbewahrungsfristen werden im Betriebsprozess festgelegt.',
                ],
            ],
            [
                'title' => '9. Karten von OpenStreetMap',
                'paragraphs' => [
                    'Camperwolf bindet Kartenkacheln unmittelbar von Servern der OpenStreetMap Foundation (OSMF) ein. Beim Anzeigen einer Karte stellt dein Browser daher eine direkte Verbindung zu diesen Servern her. Dabei werden technisch insbesondere deine IP-Adresse, Browserinformationen und die aufrufende Website übermittelt.',
                    'Die Einbindung dient der Darstellung von Stell- und Campingplätzen auf einer Karte. Camperwolf stützt diese Verarbeitung auf das berechtigte Interesse an einer verständlichen geografischen Darstellung des Angebots gemäß Art. 6 Abs. 1 lit. f DSGVO.',
                    'Wenn du auf der Karte ausdrücklich eine Standortfunktion wie "Mein Standort" oder "Plätze in der Umgebung" verwendest, fragt dein Browser nach deiner Standortfreigabe. Die dabei ermittelten Koordinaten werden von Camperwolf ausschließlich im Browser verwendet, um deine Position und die ungefähre Genauigkeit auf der Karte darzustellen beziehungsweise passende gefilterte Plätze nach ihrer Entfernung zu deinem Standort zu bestimmen. Der aktuelle Standort wird nicht an den Camperwolf-Server übertragen, nicht deinem Benutzerkonto zugeordnet und von Camperwolf nicht gespeichert. Beim Verschieben der Karte werden weiterhin Kartenkacheln von OpenStreetMap geladen, wie oben beschrieben.',
                ],
                'external_links' => [
                    ['label' => 'Datenschutzinformationen der OpenStreetMap Foundation', 'url' => 'https://osmfoundation.org/wiki/Privacy_Policy'],
                ],
            ],
            [
                'title' => '10. Adress- und Ortssuche über Photon',
                'paragraphs' => [
                    'Für Adressvorschläge, Rückwärtssuche und die Auswahl eines Heimatorts nutzt Camperwolf derzeit den Dienst Photon unter photon.komoot.io. Die Anfrage erfolgt unmittelbar aus deinem Browser. Dabei werden die von dir eingegebenen Suchbegriffe bzw. Koordinaten sowie technisch bedingt deine IP-Adresse und Browserinformationen an den Betreiber des Dienstes übermittelt.',
                    'Photon wird von der komoot GmbH bereitgestellt. Die Funktion wird nur verwendet, wenn du eine entsprechende Orts- oder Adresssuche ausführst.',
                ],
                'external_links' => [
                    ['label' => 'Datenschutzerklärung der komoot GmbH', 'url' => 'https://www.komoot.com/de-de/privacy'],
                ],
            ],
            [
                'title' => '11. Cookies und Speicherungen im Browser',
                'paragraphs' => [
                    'Camperwolf verwendet technisch erforderliche Session- und Sicherheits-Cookies für Anmeldung, Sitzungsverwaltung und Schutz vor missbräuchlichen Anfragen. Außerdem kann die von dir ausdrücklich gewählte Sprache in einem Cookie gespeichert werden.',
                    'Für einzelne von dir genutzte Komfortfunktionen speichert dein Browser lokale Einstellungen: die gewählte Aufteilung von Liste und Karte, temporäre Bearbeitungsentwürfe während einer Sitzung und – nur wenn du im Willkommensfenster ausdrücklich „nicht erneut anzeigen“ auswählst – den Hinweis, dass dieses Fenster nicht noch einmal erscheinen soll. Diese Einträge enthalten keine Werbe- oder Trackingkennung.',
                    'Camperwolf setzt derzeit keine Werbe-, Marketing- oder externen Analyse-Tracker ein. Für die aktuell eingesetzten Browser-Speicherungen ist daher kein allgemeiner Einwilligungs- oder Cookie-Banner vorgesehen.',
                    'Zur rein internen Produktstatistik zählt Camperwolf Seitenaufrufe und ausgewählte Funktionsereignisse. Dabei werden nur Ereignistyp, Funktionsbereich, gegebenenfalls der betroffene öffentliche Inhalt, die grobe Nutzerklasse Gast/User/Moderator/Administrator, eine grobe technische Einordnung als potenziell menschlicher oder automatisierter Zugriff und der Zeitpunkt gespeichert. Der vollständige User-Agent wird dafür nur während der Anfrage ausgewertet und nicht in der Nutzungsstatistik gespeichert. In dieser Statistik werden keine User-ID, IP-Adresse, Session-ID, dauerhafte Besucherkennung oder Fingerprints gespeichert und keine Daten an einen externen Analysedienst übertragen.',
                ],
            ],
            [
                'title' => '12. Empfänger und Dienstleister',
                'paragraphs' => [
                    'Personenbezogene Daten werden nur an Empfänger weitergegeben, soweit dies für die jeweilige Funktion erforderlich ist, eine gesetzliche Verpflichtung besteht oder eine andere Rechtsgrundlage vorliegt. Hierzu können insbesondere technische Hosting- und E-Mail-Dienstleister sowie die genannten Karten- und Geodienste gehören.',
                    'Der konkrete Produktions-Hosting- und E-Mail-Anbieter steht für den späteren öffentlichen Betrieb noch nicht endgültig fest und wird vor dem Go-live in dieser Erklärung ergänzt bzw. konkretisiert.',
                ],
            ],
            [
                'title' => '13. Speicherdauer',
                'paragraphs' => [
                    'Camperwolf speichert personenbezogene Daten grundsätzlich nur so lange, wie sie für den jeweiligen Zweck benötigt werden. Sicherheitsereignisse werden derzeit nach 90 Tagen automatisch bereinigt. Fertige Self-Service-Datenexporte werden nach 72 Stunden gelöscht.',
                    'Bei einer Accountlöschung werden persönliche Profil- und Kontodaten nach dem festgelegten Löschverfahren entfernt oder anonymisiert. Community-Sachdaten können ohne persönlichen Bezug erhalten bleiben. Fotos und Rezensionstexte werden bei der endgültigen Accountlöschung entfernt.',
                    'Für einzelne Betriebsdaten wie Supportfälle, erledigte Missbrauchsfälle und produktive Serverprotokolle werden vor dem öffentlichen Go-live noch verbindliche Aufbewahrungsfristen festgelegt.',
                ],
            ],
            [
                'title' => '14. Deine Rechte',
                'paragraphs' => [
                    'Nach Maßgabe der DSGVO hast du insbesondere das Recht auf Auskunft über deine personenbezogenen Daten, Berichtigung unrichtiger Daten, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit sowie – bei Vorliegen der gesetzlichen Voraussetzungen – Widerspruch gegen bestimmte Verarbeitungen.',
                    'Für viele Daten stellt Camperwolf zusätzlich einen automatischen Datenexport bereit. Diese Komfortfunktion schränkt dein gesetzliches Auskunftsrecht nicht ein.',
                    'Anfragen zu deinen Datenschutzrechten kannst du an '.config('legal.operator.email').' richten.',
                ],
            ],
            [
                'title' => '15. Beschwerderecht',
                'paragraphs' => [
                    'Du hast das Recht, dich bei einer Datenschutz-Aufsichtsbehörde über die Verarbeitung deiner personenbezogenen Daten zu beschweren. Du kannst dich insbesondere an die für deinen Wohnort oder den Sitz des Verantwortlichen zuständige Aufsichtsbehörde wenden.',
                ],
            ],
            [
                'title' => '16. Änderungen dieser Datenschutzerklärung',
                'paragraphs' => [
                    'Camperwolf wird diese Datenschutzerklärung anpassen, wenn sich Funktionen, eingesetzte Dienste oder rechtliche Rahmenbedingungen ändern. Der jeweils aktuelle Stand wird auf dieser Seite veröffentlicht.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Nutzungsbedingungen',
        'subtitle' => 'Regeln für die Nutzung von Camperwolf und die Community.',
        'version' => config('legal.versions.terms'),
        'sections' => [
            [
                'title' => '1. Geltungsbereich',
                'paragraphs' => [
                    'Diese Nutzungsbedingungen gelten für die Nutzung von Camperwolf.de und der dort angebotenen Community-Funktionen. Camperwolf ist derzeit ein privat betriebenes Community-Projekt.',
                    'Das bloße Suchen und Anzeigen öffentlich zugänglicher Platzinformationen ist grundsätzlich ohne Benutzerkonto möglich. Für Beiträge, Bewertungen, Fotos und weitere Community-Funktionen ist ein Benutzerkonto erforderlich.',
                ],
            ],
            [
                'title' => '2. Benutzerkonto',
                'paragraphs' => [
                    'Bei der Registrierung musst du zutreffende Kontaktdaten angeben und dein Konto vor unbefugtem Zugriff schützen. Zugangsdaten dürfen nicht an Dritte weitergegeben oder gemeinsam genutzt werden.',
                    'Ein Anspruch auf die Bereitstellung bestimmter Funktionen oder auf eine dauerhafte unveränderte Ausgestaltung des Dienstes besteht nicht. Camperwolf darf Funktionen weiterentwickeln, ändern oder einstellen, soweit berechtigte Nutzerinteressen angemessen berücksichtigt werden.',
                ],
            ],
            [
                'title' => '3. Community-Beiträge',
                'paragraphs' => [
                    'Wenn du Inhalte oder Daten beiträgst, musst du nach bestem Wissen darauf achten, dass sie zutreffend, sachbezogen und rechtmäßig sind. Du darfst insbesondere keine Inhalte veröffentlichen, die Rechte Dritter verletzen, vertrauliche personenbezogene Daten offenlegen, andere Personen bedrohen oder beleidigen oder den Dienst missbräuchlich nutzen.',
                    'Beiträge zu Platzdaten können geprüft, korrigiert, zusammengeführt, ergänzt oder abgelehnt werden. Sachliche Community-Daten können auch nach einer späteren Accountlöschung ohne persönlichen Bezug erhalten bleiben, damit der gemeinsame Datenbestand nicht zerstört wird.',
                ],
            ],
            [
                'title' => '4. Bewertungen und Rezensionen',
                'paragraphs' => [
                    'Bewertungen und Rezensionen sollen eigene tatsächliche Erfahrungen oder nach bestem Wissen zutreffende Beobachtungen wiedergeben und anderen Campern bei der Einschätzung eines Platzes helfen.',
                    'Sachliche negative Kritik ist ausdrücklich zulässig. Unzulässig sind insbesondere erfundene Erfahrungen, wissentlich falsche Tatsachenangaben, unbelegte schwere Anschuldigungen, persönliche Rachebewertungen, Werbung, Spam oder Inhalte ohne ausreichenden Bezug zum Platz.',
                    'Rezensionen werden derzeit grundsätzlich unmittelbar veröffentlicht. Sie können gemeldet und nachträglich moderiert oder entfernt werden.',
                ],
                'links' => [
                    ['label' => 'Vollständige Rezensionsregeln', 'route' => 'help.show', 'parameter' => 'regeln-und-empfehlungen-fuer-rezensionen'],
                ],
            ],
            [
                'title' => '5. Fotos',
                'paragraphs' => [
                    'Du darfst nur Fotos hochladen, für deren Veröffentlichung du die erforderlichen Rechte besitzt. Persönlichkeitsrechte erkennbarer Personen, Hausregeln und Fotografierverbote sind zu beachten.',
                    'Fotos zu Plätzen müssen einen sinnvollen Bezug zum jeweiligen Platz haben. Unzulässig sind insbesondere rechtswidrige oder sexualisierte Inhalte, vertrauliche persönliche Informationen, irreführende Manipulationen, Werbung oder fremde Bilder ohne ausreichende Nutzungsrechte.',
                    'Fotos werden vor der öffentlichen Freigabe moderiert.',
                ],
                'links' => [
                    ['label' => 'Vollständige Fotoregeln', 'route' => 'help.show', 'parameter' => 'regeln-und-empfehlungen-fuer-fotouploads'],
                ],
            ],
            [
                'title' => '6. Rechte an deinen Inhalten',
                'paragraphs' => [
                    'Die Rechte an deinen eigenen Inhalten verbleiben grundsätzlich bei dir. Soweit es für den Betrieb von Camperwolf erforderlich ist, räumst du Camperwolf für die Dauer der Bereitstellung das nicht ausschließliche Recht ein, deine Beiträge technisch zu speichern, zu vervielfältigen, darzustellen und innerhalb des Dienstes zugänglich zu machen.',
                    'Dieses Nutzungsrecht umfasst nur das, was erforderlich ist, um den jeweiligen Community-Inhalt innerhalb von Camperwolf bereitzustellen, zu moderieren, technisch zu verarbeiten und die nachvollziehbare Entwicklung von Platzdaten abzubilden.',
                ],
            ],
            [
                'title' => '7. Moderation und Maßnahmen',
                'paragraphs' => [
                    'Camperwolf kann Inhalte prüfen, kennzeichnen, ausblenden, ablehnen oder entfernen, wenn konkrete Anhaltspunkte für einen Regelverstoß, eine Rechtsverletzung, Missbrauch oder erhebliche Qualitätsprobleme bestehen.',
                    'Bei wiederholtem oder schwerwiegendem Missbrauch können einzelne Funktionen eingeschränkt oder Benutzerkonten vorübergehend oder dauerhaft gesperrt werden. Soweit angemessen, werden Art und Schwere des Verstoßes, frühere Verstöße und die Auswirkungen auf andere Nutzer berücksichtigt.',
                    'Nutzer können problematische Inhalte über die vorgesehenen Meldefunktionen oder den Support melden.',
                ],
            ],
            [
                'title' => '8. Keine Garantie für Platzinformationen',
                'paragraphs' => [
                    'Camperwolf sammelt Informationen aus Community-Beiträgen und gegebenenfalls künftig aus offenen oder offiziellen Datenquellen. Trotz Prüfungen können Angaben veraltet, unvollständig oder fehlerhaft sein.',
                    'Insbesondere Öffnungszeiten, Preise, Zufahrten, Verbote, Verfügbarkeit und Ausstattung können sich kurzfristig ändern. Vor einer Anreise solltest du wichtige Angaben bei Bedarf direkt beim Betreiber oder einer offiziellen Quelle prüfen.',
                ],
            ],
            [
                'title' => '9. Verfügbarkeit und Änderungen',
                'paragraphs' => [
                    'Camperwolf bemüht sich um einen zuverlässigen Betrieb, kann jedoch keine jederzeitige unterbrechungsfreie Verfügbarkeit zusagen. Wartung, Sicherheitsmaßnahmen, technische Störungen oder Änderungen externer Dienste können Funktionen zeitweise einschränken.',
                ],
            ],
            [
                'title' => '10. Haftung',
                'paragraphs' => [
                    'Für Vorsatz und grobe Fahrlässigkeit sowie in den gesetzlich zwingend vorgesehenen Fällen haftet Camperwolf nach den gesetzlichen Vorschriften. Im Übrigen richtet sich die Haftung nach den jeweils anwendbaren gesetzlichen Regelungen.',
                    'Camperwolf übernimmt keine eigenständige Garantie dafür, dass von Nutzern oder externen Quellen bereitgestellte Platzinformationen jederzeit vollständig und aktuell sind.',
                ],
            ],
            [
                'title' => '11. Accountlöschung',
                'paragraphs' => [
                    'Du kannst die Löschung deines Accounts in den Einstellungen anstoßen. Dabei kannst du zwischen einer Wiederherstellungsfrist und einer sofortigen endgültigen Löschung wählen. Die Löschseite erklärt vor der Bestätigung, welche persönlichen Daten entfernt werden und welche anonymisierten Community-Sachdaten erhalten bleiben.',
                ],
            ],
            [
                'title' => '12. Änderungen der Nutzungsbedingungen',
                'paragraphs' => [
                    'Wenn sich Camperwolf wesentlich weiterentwickelt oder rechtliche Anforderungen ändern, können diese Nutzungsbedingungen angepasst werden. Für wesentliche Änderungen, die registrierte Nutzer betreffen, wird vor dem Wirksamwerden eine angemessene Information vorgesehen.',
                ],
            ],
            [
                'title' => '13. Kontakt',
                'paragraphs' => [
                    'Fragen zu diesen Nutzungsbedingungen kannst du an '.config('legal.operator.email').' oder über den Hilfe- und Supportbereich richten.',
                ],
            ],
        ],
    ],
];
