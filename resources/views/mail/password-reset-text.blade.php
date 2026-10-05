{{ $context['greeting_name'] !== '' ? __('mail.password_reset.greeting', ['name' => $context['greeting_name']], $locale) : __('mail.password_reset.greeting_neutral', [], $locale) }}

{{ __('mail.password_reset.intro', [], $locale) }}

{{ __('mail.password_reset.button', [], $locale) }}:
{{ $context['reset_url'] }}

{{ __('mail.password_reset.expires', ['minutes' => $context['expires_in']], $locale) }}

{{ __('mail.password_reset.account_heading', [], $locale) }}
{{ $context['account_name'] }}
{{ $context['email'] }}
@if($context['cw_id']){{ $context['cw_id'] }}@endif

{{ __('mail.password_reset.ignore', [], $locale) }}

{{ __('mail.password_reset.fallback', [], $locale) }}
{{ $context['reset_url'] }}

{{ __('mail.password_reset.closing', [], $locale) }}
{{ __('mail.password_reset.signature', [], $locale) }}
