<?php

return [
    'registration' => [
        'notice' => 'When signing in, please review our',
        'and' => 'and our',
        'accept_prefix' => 'I accept the',
        'and_acknowledge' => 'and acknowledge the',
        'acceptance_required' => 'Please accept the terms of use and acknowledge the privacy policy.',
    ],

    'labels' => [
        'imprint' => 'Legal notice',
        'privacy' => 'Privacy',
        'terms' => 'Terms of use',
        'photo_rules' => 'Content rules',
        'review_rules' => 'Rating rules',
        'last_updated' => 'Last updated: :date',
    ],

    'imprint' => [
        'title' => 'Legal notice',
        'subtitle' => 'Provider information for Favon',
        'version' => config('legal.versions.imprint'),
        'sections' => [
            [
                'title' => 'Provider',
                'paragraphs' => [
                    config('legal.operator.name')."\n".config('legal.operator.street')."\n".config('legal.operator.postal_code').' '.config('legal.operator.city')."\n".config('legal.operator.country'),
                    config('legal.operator.status'),
                ],
            ],
            [
                'title' => 'Contact',
                'paragraphs' => ['Email: '.config('legal.operator.email')],
            ],
            [
                'title' => 'About the project',
                'paragraphs' => [
                    'Favon is currently operated as a private project. These details will be updated if its legal or organisational form changes.',
                ],
            ],
            [
                'title' => 'Responsibility for content',
                'paragraphs' => [
                    'Favon provides its own content and factual place information contributed by users. Potentially unlawful, incorrect or otherwise problematic content can be reported through the available reporting and support channels.',
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
        'subtitle' => 'Information about how Favon processes personal data.',
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
                    'Favon processes personal data only where necessary to operate the service, provide functions you use, protect the system or comply with legal obligations.',
                    'Favon follows the principle “Places, not people”. Public user profiles, visitor lists, movement profiles and public attribution of contributions to individual people are not intended features.',
                ],
            ],
            [
                'title' => '3. Website access and technical logs',
                'paragraphs' => [
                    'When Favon is accessed, technically necessary connection data may be processed, including IP address, access time, requested address, browser or device information and technical error data. These data are used for delivery, system security, troubleshooting and abuse prevention.',
                    'Where implemented, application security events use a hash derived from the source IP rather than storing the raw IP address. Technically necessary server and hosting logs may exist independently of these application events.',
                ],
            ],
            [
                'title' => '4. Community accounts and Telegram sign-in',
                'paragraphs' => [
                    'Community users sign in through Telegram using OpenID Connect (OIDC). For account mapping, Favon stores only the stable numeric Telegram ID together with the necessary internal account, role, status and timestamp information.',
                    'Favon does not store the Telegram display name, username, profile photo or phone number as part of community sign-in. When Telegram sign-in is opened, your browser is redirected to Telegram, which processes technically necessary connection and authentication data under its own responsibility.',
                    'Administrators and the system owner may additionally use a classic email/password login. Passwords are stored only as hashes.',
                ],
                'external_links' => [
                    ['label' => 'Telegram privacy policy', 'url' => 'https://telegram.org/privacy'],
                ],
            ],
            [
                'title' => '5. Contributions and interactions',
                'paragraphs' => [
                    'When you contribute place data, submit structured ratings, use favourites, file reports or use other available community functions, Favon processes the records required for those functions with an internal account reference.',
                    'Favon does not publicly show who created, edited, rated, favourited or reported a place. Favon does not provide free-text reviews or community photo uploads as place or user features.',
                ],
            ],
            [
                'title' => '6. Location functions',
                'paragraphs' => [
                    'If you explicitly use a location function, your browser asks for permission to access your location. Favon is designed to use raw coordinates only for the requested location or proximity check and not to build a persistent movement or visit history.',
                    'Where the current map calculates distance to places directly in the browser, your current location is not stored as an account field.',
                ],
            ],
            [
                'title' => '7. OpenStreetMap maps',
                'paragraphs' => [
                    'Favon uses OpenStreetMap-based maps. If map tiles are loaded directly from servers of the OpenStreetMap Foundation or another map provider, your browser connects directly to that provider and technically necessary connection data such as your IP address are transmitted.',
                ],
                'external_links' => [
                    ['label' => 'OpenStreetMap Foundation privacy policy', 'url' => 'https://osmfoundation.org/wiki/Privacy_Policy'],
                ],
            ],
            [
                'title' => '8. Place and address search',
                'paragraphs' => [
                    'Where Favon uses an external geocoding service such as Photon for place or address search, search terms or coordinates and technically necessary connection data are transmitted to that service. Such requests are made only in connection with the relevant search function.',
                ],
                'external_links' => [
                    ['label' => 'komoot GmbH privacy policy', 'url' => 'https://www.komoot.com/privacy'],
                ],
            ],
            [
                'title' => '9. Cookies, sessions and internal statistics',
                'paragraphs' => [
                    'Favon uses technically necessary session and security cookies for sign-in, session handling and protection against abusive requests. Your selected language may also be stored.',
                    'Favon currently uses no advertising, marketing or external analytics trackers.',
                    'For internal usage statistics, page views and selected feature events may be stored without user ID, raw IP address, session ID, persistent visitor identifier or fingerprint.',
                ],
            ],
            [
                'title' => '10. Support and reports',
                'paragraphs' => [
                    'Support requests and reports contain the information you submit and the context needed to process them. For signed-in users, a request may be linked internally to the account.',
                    'Support and report data are retained only as long as needed for handling, traceability, abuse prevention or legal obligations and claims.',
                ],
            ],
            [
                'title' => '11. Retention and account deletion',
                'paragraphs' => [
                    'Favon generally retains personal data only for as long as needed for the relevant purpose. Security events are cleaned up according to the configured technical retention period; completed self-service data exports are made available for a limited period and then deleted.',
                    'On final account deletion, personal account data are removed or irreversibly anonymised under the implemented deletion process. Factual place data may remain without public attribution to a person.',
                ],
            ],
            [
                'title' => '12. Your rights',
                'paragraphs' => [
                    'Subject to the GDPR, you may have rights including access, rectification, erasure, restriction of processing, data portability and, where the requirements are met, objection.',
                    'Favon additionally provides a self-service data export. This convenience function does not limit your statutory right of access.',
                    'Privacy requests can be sent to '.config('legal.operator.email').'.',
                ],
            ],
            [
                'title' => '13. Right to complain',
                'paragraphs' => [
                    'You have the right to lodge a complaint with a data protection supervisory authority regarding the processing of your personal data.',
                ],
            ],
            [
                'title' => '14. Changes to this policy',
                'paragraphs' => [
                    'Favon will update this policy when functions, service providers or legal requirements change. The current version is published on this page.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Terms of use',
        'subtitle' => 'Rules for using Favon.',
        'version' => config('legal.versions.terms'),
        'sections' => [
            [
                'title' => '1. Scope',
                'paragraphs' => [
                    'These terms apply to Favon and its available functions. The actual place directory is accessible only after sign-in.',
                    'Favon is a place-based community directory. Its principle is “Places, not people”: Favon is not a dating, personal-ad, people-discovery or escort service.',
                ],
            ],
            [
                'title' => '2. User accounts',
                'paragraphs' => [
                    'Community accounts sign in through Telegram. Accounts must not be abused or shared with third parties for use.',
                    'There is no entitlement to a specific permanent feature set or uninterrupted availability.',
                ],
            ],
            [
                'title' => '3. Place data and community contributions',
                'paragraphs' => [
                    'Contributed information must, to the best of your knowledge, be relevant, accurate and lawful. Personal information about visitors or other third parties does not belong in place data.',
                    'Favon may review, correct, merge, reject, hide or remove entries and changes that are inaccurate, inappropriate, unlawful or abusive.',
                ],
            ],
            [
                'title' => '4. Prohibited content and uses',
                'paragraphs' => [
                    'Prohibited content includes private home addresses used as personal or meetup ads, doxxing, personal ads, identifying descriptions of visitors, pornographic content, explicit sexual experience reports, advertising or spam, and content that infringes third-party rights.',
                    'Favon does not provide user photos, free-text reviews, chats, direct messages, dating or matching functions.',
                ],
            ],
            [
                'title' => '5. Structured ratings',
                'paragraphs' => [
                    'Where structured ratings are available, they should reflect your own actual observations of the place in a factual manner. Manipulation, abusive repeat submissions and knowingly false information are not permitted.',
                ],
            ],
            [
                'title' => '6. Moderation and measures',
                'paragraphs' => [
                    'In cases of rule violations, abuse or security issues, Favon may reject or remove content and temporarily or permanently restrict functions or accounts.',
                    'Problematic places or content can be reported through the available reporting and support channels.',
                ],
            ],
            [
                'title' => '7. No guarantee of place information',
                'paragraphs' => [
                    'Despite checks, community information may be outdated, incomplete or incorrect. Local rules, accessibility and actual conditions can change at short notice.',
                    'Favon does not guarantee that a place is always accessible, lawful to use, safe or suitable for a particular purpose.',
                ],
            ],
            [
                'title' => '8. Availability and liability',
                'paragraphs' => [
                    'Favon aims to provide a reliable service but cannot guarantee uninterrupted availability.',
                    'Liability follows mandatory statutory rules, including for intent and gross negligence. Otherwise, the applicable statutory provisions apply.',
                ],
            ],
            [
                'title' => '9. Account deletion',
                'paragraphs' => [
                    'You can start account deletion through the available account function. Personal account data are removed or anonymised under the implemented deletion process; factual place data may remain without personal attribution.',
                ],
            ],
            [
                'title' => '10. Changes and contact',
                'paragraphs' => [
                    'These terms may be updated if Favon or the applicable legal framework changes materially.',
                    'Questions can be sent to '.config('legal.operator.email').' or submitted through Help & Support.',
                ],
            ],
        ],
    ],
];
