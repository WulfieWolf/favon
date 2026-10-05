<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('mail.password_reset.subject', [], $locale) }}</title>
</head>
<body>
    <p>{{ $context['greeting_name'] !== '' ? __('mail.password_reset.greeting', ['name' => $context['greeting_name']], $locale) : __('mail.password_reset.greeting_neutral', [], $locale) }}</p>
    <p>{{ __('mail.password_reset.intro', [], $locale) }}</p>
    <p><a href="{{ $context['reset_url'] }}">{{ __('mail.password_reset.button', [], $locale) }}</a></p>
    <p>{{ __('mail.password_reset.expires', ['minutes' => $context['expires_in']], $locale) }}</p>
    <p><strong>{{ __('mail.password_reset.account_heading', [], $locale) }}</strong><br>
        {{ $context['account_name'] }}<br>
        {{ $context['email'] }}
        @if($context['cw_id'])<br>{{ $context['cw_id'] }}@endif
    </p>
    <p>{{ __('mail.password_reset.ignore', [], $locale) }}</p>
    <p>{{ __('mail.password_reset.fallback', [], $locale) }}<br>
        <a href="{{ $context['reset_url'] }}">{{ $context['reset_url'] }}</a>
    </p>
    <p>{{ __('mail.password_reset.closing', [], $locale) }}<br>{{ __('mail.password_reset.signature', [], $locale) }}</p>
</body>
</html>
