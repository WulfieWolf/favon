<?php

return [
    'eyebrow' => 'Error :status',
    'photo_placeholder' => 'Merlin will provide the appropriately sad look here later.',
    'home' => 'Back to home',
    'report' => 'Report a bug',
    'reload' => 'Reload page',
    '4xx' => [
        'title' => 'This request did not work.',
        'message' => 'The requested page or action could not be completed in this form.',
        'technical' => 'Request could not be processed',
    ],
    '5xx' => [
        'title' => 'Oh oh, something went wrong.',
        'message' => 'Camperwolf could not process the request correctly just now.',
        'technical' => 'Internal server error',
    ],
    '404' => [
        'title' => 'There is nothing here.',
        'message' => 'The page could not be found. It may have moved, been removed or the link may no longer be current.',
        'technical' => '404 - Page not found',
    ],
    '403' => [
        'title' => 'You cannot access this page.',
        'message' => 'You do not have the required permission for this page or action.',
        'technical' => '403 - Access denied',
    ],
    '419' => [
        'title' => 'Your session has expired.',
        'message' => 'Please reload the page and try again.',
        'technical' => '419 - Session expired',
    ],
    '500' => [
        'title' => 'Oh oh, something went wrong.',
        'message' => 'Camperwolf could not process the request correctly just now.',
        'technical' => '500 - Internal error',
    ],
    '503' => [
        'title' => 'Camperwolf is taking a short break.',
        'message' => 'The site is temporarily unavailable. Please try again shortly.',
        'technical' => '503 - Temporarily unavailable',
    ],
];
