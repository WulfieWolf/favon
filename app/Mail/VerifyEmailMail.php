<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{user_id:int,locale:string,greeting_name:string,account_name:string,email:string,cw_id:?string,verification_url:string,expires_in:int,reason:string}  $context
     */
    public function __construct(
        public array $context,
        public string $mailLocale,
    ) {
        $this->locale($mailLocale);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.verify.subject', [], $this->mailLocale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.verify-email',
            text: 'mail.verify-email-text',
            with: ['context' => $this->context, 'locale' => $this->mailLocale],
        );
    }
}
