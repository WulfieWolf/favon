@php
    $content = match ($verificationModal) {
        'registration_pending' => [
            'title' => __('verification_ui.registration_pending.title'),
            'message' => __('verification_ui.registration_pending.message'),
            'button' => __('verification_ui.registration_pending.button'),
        ],
        'email_change_pending' => [
            'title' => __('verification_ui.email_change_pending.title'),
            'message' => __('verification_ui.email_change_pending.message'),
            'button' => __('verification_ui.email_change_pending.button'),
        ],
        'email_change_verified' => [
            'title' => __('verification_ui.email_change_verified.title'),
            'message' => __('verification_ui.email_change_verified.message'),
            'button' => __('verification_ui.email_change_verified.button'),
        ],
        default => [
            'title' => __('verification_ui.verified.title'),
            'message' => __('verification_ui.verified.message'),
            'button' => __('verification_ui.verified.button'),
        ],
    };
@endphp

<flux:modal name="email-verification-feedback" focusable class="max-w-lg" x-data x-init="$nextTick(() => $flux.modal('email-verification-feedback').show())">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $content['title'] }}</flux:heading>
            <flux:subheading class="mt-2">{{ $content['message'] }}</flux:subheading>
        </div>
        <div class="flex justify-end">
            <flux:modal.close>
                <flux:button variant="primary">{{ $content['button'] }}</flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>
