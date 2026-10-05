<?php

return [
    'upload_max_kilobytes' => 50 * 1024,
    'max_pixels' => 80_000_000,
    'min_pixels' => 900_000,
    'min_side' => 600,
    'max_per_review' => 5,

    'variants' => [
        'detail' => [
            'max_side' => 1920,
            'quality' => 82,
        ],
        'preview' => [
            'max_side' => 640,
            'quality' => 76,
        ],
    ],

    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/heic',
        'image/heif',
    ],

    'temporary_retention_hours' => 24,
];
