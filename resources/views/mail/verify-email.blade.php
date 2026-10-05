<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('mail.verify.subject', [], $locale) }}</title>
</head>
<body>
    <p>{{ $context['greeting_name'] !== '' ? __('mail.verify.greeting', ['name' => $context['greeting_name']], $locale) : __('mail.verify.greeting_neutral', [], $locale) }}</p>
    <p>{{ __('mail.verify.'.($context['reason'] === 'email_change' ? 'change_intro' : 'registration_intro'), [], $locale) }}</p>
    <p>{{ __('mail.verify.'.($context['reason'] === 'email_change' ? 'change_action' : 'registration_action'), [], $locale) }}</p>

    <p><a href="{{ $context['verification_url'] }}">{{ __('mail.verify.button', [], $locale) }}</a></p>

    <p>{{ __('mail.verify.expires', ['minutes' => $context['expires_in']], $locale) }}</p>

    <p>
        <strong>{{ __('mail.verify.account_heading', [], $locale) }}</strong><br>
        {{ __('mail.verify.account_name', ['name' => $context['account_name']], $locale) }}<br>
        {{ __('mail.verify.account_email', ['email' => $context['email']], $locale) }}
        @if ($context['cw_id'])
            <br>{{ __('mail.verify.account_id', ['cw_id' => $context['cw_id']], $locale) }}
        @endif
    </p>

    <p>{{ __('mail.verify.'.($context['reason'] === 'email_change' ? 'change_ignore' : 'registration_ignore'), [], $locale) }}</p>

    <p>{{ __('mail.verify.fallback', [], $locale) }}<br>
        <a href="{{ $context['verification_url'] }}">{{ $context['verification_url'] }}</a>
    </p>

    <p>{{ __('mail.verify.closing', [], $locale) }}<br>{{ __('mail.verify.signature', [], $locale) }}</p>
</body>
</html>
