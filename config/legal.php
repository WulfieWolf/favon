<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legal / operator information
    |--------------------------------------------------------------------------
    |
    | Kept in one place so a later change of operator, business form or
    | contact details does not require editing the legal pages themselves.
    |
    */
    'operator' => [
        'name' => env('LEGAL_OPERATOR_NAME', 'Sascha Schwarz'),
        'street' => env('LEGAL_OPERATOR_STREET', 'Giesebrechstr. 59'),
        'postal_code' => env('LEGAL_OPERATOR_POSTAL_CODE', '45144'),
        'city' => env('LEGAL_OPERATOR_CITY', 'Essen'),
        'country' => env('LEGAL_OPERATOR_COUNTRY', 'Deutschland'),
        'email' => env('LEGAL_OPERATOR_EMAIL', 'schwarz.sascha@gmx.de'),
        'status' => env('LEGAL_OPERATOR_STATUS', 'Privat betriebenes Projekt'),
    ],

    'versions' => [
        'privacy' => '2026-10-03',
        'terms' => '2026-09-23',
        'imprint' => '2026-09-23',
    ],
];
