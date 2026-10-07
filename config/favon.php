<?php

return [
    'owner_email' => env('FAVON_OWNER_EMAIL'),

    'mail' => [
        // Fail closed: production mail must be explicitly enabled in the environment.
        // Provider limits are only a last line of defence.
        'enabled' => env('FAVON_MAIL_ENABLED', false),
        'global_per_minute' => env('FAVON_MAIL_GLOBAL_PER_MINUTE', 20),
        'global_per_hour' => env('FAVON_MAIL_GLOBAL_PER_HOUR', 100),
        'global_per_day' => env('FAVON_MAIL_GLOBAL_PER_DAY', 50),
        'recipient_per_hour' => env('FAVON_MAIL_RECIPIENT_PER_HOUR', 10),
        'type_per_hour' => [
            'email_verification' => env('FAVON_MAIL_VERIFICATION_PER_HOUR', 5),
            'password_reset' => env('FAVON_MAIL_PASSWORD_RESET_PER_HOUR', 5),
            'default' => env('FAVON_MAIL_TYPE_DEFAULT_PER_HOUR', 20),
        ],
        'duplicate_window_seconds' => env('FAVON_MAIL_DUPLICATE_WINDOW_SECONDS', 60),
        'debug_preview' => env('FAVON_MAIL_DEBUG_PREVIEW', false),
    ],

    'security' => [
        // Keep public GET throttling disabled while developing and during the
        // initial launch. Edge protection should handle verified crawlers later;
        // this is only an emergency server-side fallback.
        'public_read_limit_enabled' => env('FAVON_PUBLIC_READ_LIMIT_ENABLED', false),
        'public_read_per_minute' => env('FAVON_PUBLIC_READ_PER_MINUTE', 180),
        'filtered_browse_per_minute' => env('FAVON_FILTERED_BROWSE_PER_MINUTE', 60),
    ],
];
