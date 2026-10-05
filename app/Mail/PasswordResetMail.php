<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{user_id:int,locale:string,greeting_name:string,account_name:string,email:string,cw_id:?string,reset_url:string,expires_in:int}  $context
     */
    public function __construct(
        public array $context,
        public string $mailLocale,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('mail.password_reset.subject', [], $this->mailLocale));
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.password-reset',
            text: 'mail.password-reset-text',
            with: ['context' => $this->context, 'locale' => $this->mailLocale],
        );
    }
}
