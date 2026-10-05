<?php

return [
    'registration' => [
        'notice' => 'When registering, please review our',
        'and' => 'and our',
        'accept_prefix' => 'I accept the',
        'and_acknowledge' => 'and acknowledge the',
        'acceptance_required' => 'Please accept the terms of use and acknowledge the privacy policy.',
    ],

    'labels' => [
        'imprint' => 'Legal notice',
        'privacy' => 'Privacy',
        'terms' => 'Terms of use',
        'photo_rules' => 'Photo rules',
        'review_rules' => 'Review rules',
        'last_updated' => 'Last updated: :date',
    ],

    'imprint' => [
        'title' => 'Legal notice',
        'subtitle' => 'Provider information for Camperwolf.de',
        'version' => config('legal.versions.imprint'),
        'sections' => [
            [
                'title' => 'Provider',
                'paragraphs' => [
                    config('legal.operator.name')."\n".config('legal.operator.street')."\n".config('legal.operator.postal_code').' '.config('legal.operator.city')."\n".config('legal.operator.country'),
                    'Current status: privately operated project',
                ],
            ],
            [
                'title' => 'Contact',
                'paragraphs' => ['Email: '.config('legal.operator.email')],
            ],
            [
                'title' => 'About the project',
                'paragraphs' => [
                    'Camperwolf is currently operated privately and without a registered business. If the legal or organisational form changes, these details will be updated.',
                ],
            ],
            [
                'title' => 'Responsibility for content',
                'paragraphs' => [
                    'Camperwolf provides both its own content and information contributed by users. Community content is moderated under the applicable rules. Potentially unlawful, incorrect or otherwise problematic content can be reported through the reporting and support functions.',
                ],
            ],
            [
                'title' => 'Map and geodata',
                'paragraphs' => [
                    'Map data and map tiles are partly based on OpenStreetMap. The applicable copyright and licence attribution is displayed directly on the map.',
                ],
            ],
        ],
    ],

    'privacy' => [
        'title' => 'Privacy policy',
        'subtitle' => 'Information about how Camperwolf processes personal data.',
        'version' => config('legal.versions.privacy'),
        'sections' => [
            [
                'title' => '1. Controller',
                'paragraphs' => [
                    config('legal.operator.name')."\n".config('legal.operator.street')."\n".config('legal.operator.postal_code').' '.config('legal.operator.city')."\n".config('legal.operator.country')."\nEmail: ".config('legal.operator.email'),
                ],
            ],
            [
                'title' => '2. General principles',
                'paragraphs' => [
                    'Camperwolf processes personal data only where necessary to operate the website and community features, where you provide data yourself, or where another legal basis applies.',
                    'Depending on the processing, the legal basis may in particular be Article 6(1)(b) GDPR for requested account and community functions, Article 6(1)(c) GDPR for legal obligations, or Article 6(1)(f) GDPR for secure, reliable and abuse-resistant operation of the service.',
                ],
            ],
            [
                'title' => '3. Website access and technical logs',
                'paragraphs' => [
                    'When you access Camperwolf, technically necessary connection data may be processed, including IP address, access time, requested address, browser or device information and technical error data. These data are used to deliver the website, maintain security, diagnose errors and prevent abuse.',
                    'Where provided, Camperwolf stores security events using a hashed rather than a raw IP address. Retention periods for production web server and hosting logs will be finalised before public launch.',
                ],
            ],
            [
                'title' => '4. Account and authentication',
                'paragraphs' => [
                    'Registration and account use involve data such as your name, email address, password hash, language setting, verification status and technical session information. Your password itself is not stored in plain text.',
                    'Technically necessary session and security information is used for signed-in sessions. Password reset and email verification process the contact and token information required for those functions.',
                ],
            ],
            [
                'title' => '5. Community profile',
                'paragraphs' => [
                    'You can voluntarily add a profile photo, bio, hometown, date of birth, gender and vehicle information. For many profile fields you can choose whether they are public, visible only to registered users or private.',
                    'Your full date of birth is not displayed to other users. If you choose to show your age, only the calculated age is displayed.',
                ],
            ],
            [
                'title' => '6. Contributions, reviews and photos',
                'paragraphs' => [
                    'When you add places, contribute information, publish ratings or reviews, upload photos or use other community features, Camperwolf processes the respective content together with your user reference and necessary timestamps and status information.',
                    'Reviews are currently published immediately and may be moderated after a report. Photos are processed and moderated before becoming public. Published photo variants have embedded image metadata, including EXIF/GPS metadata, removed during processing.',
                    'Additional content rules apply to photos and reviews and are available in Help & Support.',
                ],
                'links' => [
                    ['label' => 'Photo rules', 'route' => 'help.show', 'parameter' => 'regeln-und-empfehlungen-fuer-fotouploads'],
                    ['label' => 'Review rules', 'route' => 'help.show', 'parameter' => 'regeln-und-empfehlungen-fuer-rezensionen'],
                ],
            ],
            [
                'title' => '7. Favourites, interactions, gamification and notifications',
                'paragraphs' => [
                    'Camperwolf stores account-related interactions such as favourites and helpful votes as well as XP, badge and activity data required for optional community features. Notifications and related events are processed to inform you about activity relevant to your account.',
                ],
            ],
            [
                'title' => '8. Support and reports',
                'paragraphs' => [
                    'Support requests and reports contain the information you submit. Requests by signed-in users may be linked to their account. Guest requests may include name, email address and an optional phone number. Technical page context and browser information may also be submitted for troubleshooting.',
                    'Support information is retained only as long as needed for handling, traceability and, where applicable, legal obligations or the establishment, exercise or defence of legal claims. Specific retention periods will be defined in the operating process.',
                ],
            ],
            [
                'title' => '9. OpenStreetMap maps',
                'paragraphs' => [
                    'Camperwolf loads map tiles directly from servers of the OpenStreetMap Foundation (OSMF). When a map is displayed, your browser therefore connects directly to those servers. This technically transmits information including your IP address, browser information and the referring website.',
                    'The maps are used to provide a clear geographic view of camping and parking places. Camperwolf relies on its legitimate interest in providing this map functionality under Article 6(1)(f) GDPR.',
                    'If you explicitly use a location function such as "My location" or "Places nearby" on the map, your browser asks for permission to access your location. The resulting coordinates are used by Camperwolf only within your browser to show your position and approximate accuracy on the map or to determine matching filtered places by their distance from your location. Your current location is not sent to the Camperwolf server, is not linked to your user account and is not stored by Camperwolf. Moving the map continues to load OpenStreetMap tiles as described above.',
                ],
                'external_links' => [
                    ['label' => 'OpenStreetMap Foundation privacy policy', 'url' => 'https://osmfoundation.org/wiki/Privacy_Policy'],
                ],
            ],
            [
                'title' => '10. Address and place search via Photon',
                'paragraphs' => [
                    'Camperwolf currently uses Photon at photon.komoot.io for address suggestions, reverse lookup and hometown selection. Requests are sent directly from your browser. Search terms or coordinates, as well as technically required IP address and browser information, are transmitted to the service provider.',
                    'Photon is provided by komoot GmbH and is only contacted when you use the relevant address or place search.',
                ],
                'external_links' => [
                    ['label' => 'komoot GmbH privacy policy', 'url' => 'https://www.komoot.com/privacy'],
                ],
            ],
            [
                'title' => '11. Cookies and browser storage',
                'paragraphs' => [
                    'Camperwolf uses technically necessary session and security cookies for sign-in, session handling and protection against abusive requests. Your explicitly selected language may also be stored in a cookie.',
                    'For selected convenience features, your browser stores local settings: the chosen list/map layout, temporary editing drafts for the current session and – only when you explicitly choose “don’t show this again” in the welcome window – the preference not to display that window again. These entries contain no advertising or tracking identifier.',
                    'Camperwolf currently uses no advertising, marketing or external analytics trackers. A general consent or cookie banner is therefore not planned for the browser storage currently in use.',
                    'For internal product statistics, Camperwolf counts page views and selected feature events. Only the event type, functional area, where applicable the affected public content, the broad audience class guest/user/moderator/administrator, a broad technical classification as potentially human or automated traffic, and the timestamp are stored. The full user agent is evaluated only during the request and is not stored in the usage statistics. These statistics do not store user IDs, IP addresses, session IDs, persistent visitor identifiers or fingerprints, and no data are sent to an external analytics service.',
                ],
            ],
            [
                'title' => '12. Recipients and service providers',
                'paragraphs' => [
                    'Personal data are disclosed only where required for a function, a legal obligation applies, or another legal basis exists. Recipients may include technical hosting and email providers and the map and geodata services described above.',
                    'The final production hosting and email providers have not yet been selected and will be added or specified before public launch.',
                ],
            ],
            [
                'title' => '13. Retention',
                'paragraphs' => [
                    'Personal data are generally retained only for as long as needed for the relevant purpose. Security events are currently cleaned up after 90 days. Completed self-service data exports are deleted after 72 hours.',
                    'When an account is deleted, personal profile and account information is removed or anonymised under the defined deletion process. Community factual data may remain without personal attribution. Photos and review text are removed on final account deletion.',
                    'Binding retention periods for some operational data, such as support cases, resolved abuse cases and production server logs, will be defined before public launch.',
                ],
            ],
            [
                'title' => '14. Your rights',
                'paragraphs' => [
                    'Subject to the GDPR, you may have rights including access, rectification, erasure, restriction of processing, data portability and, where the legal requirements are met, objection to certain processing.',
                    'Camperwolf additionally offers an automated export for many account-related data. This convenience feature does not limit your statutory right of access.',
                    'Privacy requests can be sent to '.config('legal.operator.email').'.',
                ],
            ],
            [
                'title' => '15. Right to complain',
                'paragraphs' => [
                    'You have the right to lodge a complaint with a data protection supervisory authority regarding the processing of your personal data.',
                ],
            ],
            [
                'title' => '16. Changes to this policy',
                'paragraphs' => [
                    'This policy will be updated when Camperwolf functions, service providers or legal requirements change. The current version is published on this page.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Terms of use',
        'subtitle' => 'Rules for using Camperwolf and its community features.',
        'version' => config('legal.versions.terms'),
        'sections' => [
            [
                'title' => '1. Scope',
                'paragraphs' => [
                    'These terms apply to Camperwolf.de and its community features. Camperwolf is currently a privately operated community project.',
                    'Public place information can generally be searched and viewed without an account. An account is required for contributions, reviews, photos and other community functions.',
                ],
            ],
            [
                'title' => '2. User accounts',
                'paragraphs' => [
                    'Registration requires accurate contact information and reasonable protection of your account from unauthorised access. Login credentials must not be shared with third parties.',
                    'There is no entitlement to a particular feature set remaining permanently unchanged. Camperwolf may develop, change or discontinue features while appropriately considering legitimate user interests.',
                ],
            ],
            [
                'title' => '3. Community contributions',
                'paragraphs' => [
                    'When contributing content or data, you must take reasonable care that it is accurate to the best of your knowledge, relevant and lawful. You must not publish content that infringes third-party rights, exposes confidential personal information, threatens or insults others, or abuses the service.',
                    'Place data contributions may be reviewed, corrected, merged, supplemented or rejected. Factual community data may remain without personal attribution after account deletion so that the shared dataset is not destroyed.',
                ],
            ],
            [
                'title' => '4. Ratings and reviews',
                'paragraphs' => [
                    'Ratings and reviews should reflect your own actual experience or observations you reasonably believe to be accurate and should help other campers assess a place.',
                    'Factual negative criticism is permitted. Fabricated experiences, knowingly false statements of fact, unsupported serious allegations, revenge reviews, advertising, spam or content unrelated to the place are not permitted.',
                    'Reviews are currently published immediately and can be reported, moderated or removed afterwards.',
                ],
                'links' => [
                    ['label' => 'Full review rules', 'route' => 'help.show', 'parameter' => 'regeln-und-empfehlungen-fuer-rezensionen'],
                ],
            ],
            [
                'title' => '5. Photos',
                'paragraphs' => [
                    'You may only upload photos for which you have the necessary publication rights. Rights of identifiable persons, house rules and photography restrictions must be respected.',
                    'Place photos must have a meaningful connection to the place. Prohibited content includes unlawful or sexualised material, confidential personal information, misleading manipulation, advertising and third-party images without sufficient rights.',
                    'Photos are moderated before becoming public.',
                ],
                'links' => [
                    ['label' => 'Full photo rules', 'route' => 'help.show', 'parameter' => 'regeln-und-empfehlungen-fuer-fotouploads'],
                ],
            ],
            [
                'title' => '6. Rights in your content',
                'paragraphs' => [
                    'You generally retain the rights to your own content. To the extent necessary to operate Camperwolf, you grant Camperwolf a non-exclusive right for the period of availability to technically store, reproduce, display and make your contributions available within the service.',
                    'This licence is limited to what is necessary to provide, moderate and technically process the respective content and to maintain a traceable history of place data.',
                ],
            ],
            [
                'title' => '7. Moderation and measures',
                'paragraphs' => [
                    'Camperwolf may review, label, hide, reject or remove content where there are concrete indications of a rule violation, infringement, abuse or serious quality problem.',
                    'Repeated or serious abuse may result in temporary or permanent restrictions on features or accounts. Where appropriate, the type and severity of the violation, previous violations and effects on other users are considered.',
                    'Users can report problematic content through the available reporting functions or Help & Support.',
                ],
            ],
            [
                'title' => '8. No guarantee of place information',
                'paragraphs' => [
                    'Camperwolf collects information from community contributions and may later also use open or official data sources. Despite checks, information may be outdated, incomplete or incorrect.',
                    'Opening hours, prices, access, restrictions, availability and facilities can change at short notice. Important information should be confirmed with the operator or an official source where necessary before travelling.',
                ],
            ],
            [
                'title' => '9. Availability and changes',
                'paragraphs' => [
                    'Camperwolf aims to provide a reliable service but cannot guarantee uninterrupted availability. Maintenance, security measures, technical failures or changes to external services may temporarily restrict functionality.',
                ],
            ],
            [
                'title' => '10. Liability',
                'paragraphs' => [
                    'Camperwolf is liable in accordance with mandatory statutory provisions, including for intent and gross negligence. Otherwise, liability is governed by the applicable statutory rules.',
                    'Camperwolf does not independently guarantee that place information provided by users or external sources is complete and up to date at all times.',
                ],
            ],
            [
                'title' => '11. Account deletion',
                'paragraphs' => [
                    'You can start account deletion in your settings and choose between a recovery period and immediate final deletion. Before confirmation, the deletion page explains which personal data will be removed and which anonymised factual community data may remain.',
                ],
            ],
            [
                'title' => '12. Changes to these terms',
                'paragraphs' => [
                    'These terms may be updated if Camperwolf develops materially or legal requirements change. Registered users will be appropriately informed before material changes affecting them take effect.',
                ],
            ],
            [
                'title' => '13. Contact',
                'paragraphs' => [
                    'Questions about these terms can be sent to '.config('legal.operator.email').' or submitted through Help & Support.',
                ],
            ],
        ],
    ],
];
