<?php

return [
    'client_id' => env('TELEGRAM_CLIENT_ID'),
    'client_secret' => env('TELEGRAM_CLIENT_SECRET'),
    'authorization_url' => env('TELEGRAM_AUTHORIZATION_URL', 'https://oauth.telegram.org/auth'),
    'token_url' => env('TELEGRAM_TOKEN_URL', 'https://oauth.telegram.org/token'),
    'jwks_url' => env('TELEGRAM_JWKS_URL', 'https://oauth.telegram.org/.well-known/jwks.json'),
    'issuer' => env('TELEGRAM_ISSUER', 'https://oauth.telegram.org'),
];
