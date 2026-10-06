<?php

return [
    'bot_username' => env('TELEGRAM_BOT_USERNAME', 'favonde_bot'),
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'auth_max_age_seconds' => (int) env('TELEGRAM_AUTH_MAX_AGE_SECONDS', 300),
];
