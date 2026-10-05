<?php

return [
    'rule_version' => 'v1',

    'places' => [
        'new_place_xp' => 5,
    ],

    'place_info' => [
        'default_xp' => 1,
        'long_text_xp' => 2,
        'long_text_fields' => [
            'place_translations.description',
            'place_translations.directions',
            'place_translations.access_information',
        ],
    ],

    'photos' => [
        'xp' => 1,
        'max_rewarded_per_user_place' => 5,
        'helpful_xp' => 1,
        'max_helpful_xp_per_photo' => 10,
    ],

    'reviews' => [
        'base_xp' => 2,
        'detailed_total_xp' => 4,
        'detailed_min_chars' => 300,
    ],

    'ratings' => [
        'xp_per_dimension' => 1,
    ],
];
