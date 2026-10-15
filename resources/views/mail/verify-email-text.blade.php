{{ $context['greeting_name'] !== '' ? __('mail.verify.greeting', ['name' => $context['greeting_name']], $locale) : __('mail.verify.greeting_neutral', [], $locale) }}

{{ __('mail.verify.'.($context['reason'] === 'email_change' ? 'change_intro' : 'registration_intro'), [], $locale) }}

{{ __('mail.verify.'.($context['reason'] === 'email_change' ? 'change_action' : 'registration_action'), [], $locale) }}

{{ __('mail.verify.button', [], $locale) }}:
{{ $context['verification_url'] }}

{{ __('mail.verify.expires', ['minutes' => $context['expires_in']], $locale) }}

{{ __('mail.verify.account_heading', [], $locale) }}
{{ __('mail.verify.account_name', ['name' => $context['account_name']], $locale) }}
{{ __('mail.verify.account_email', ['email' => $context['email']], $locale) }}
@if ($context['favon_id'])
{{ __('mail.verify.account_id', ['favon_id' => $context['favon_id']], $locale) }}
@endif

{{ __('mail.verify.'.($context['reason'] === 'email_change' ? 'change_ignore' : 'registration_ignore'), [], $locale) }}

{{ __('mail.verify.fallback', [], $locale) }}
{{ $context['verification_url'] }}

{{ __('mail.verify.closing', [], $locale) }}
{{ __('mail.verify.signature', [], $locale) }}
