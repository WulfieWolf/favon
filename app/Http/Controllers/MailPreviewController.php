<?php

namespace App\Http\Controllers;

use App\Mail\VerifyEmailMail;
use App\Services\MailContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class MailPreviewController extends Controller
{
    public function verifyEmail(Request $request, MailContextService $context): View
    {
        abort_unless(app()->environment('local'), 404);
        abort_unless((bool) config('favon.mail.debug_preview', false), 404);

        $user = $request->user();
        $locale = in_array((string) $request->query('locale'), ['de', 'en'], true)
            ? (string) $request->query('locale')
            : $user->preferredLocale();
        $reason = $request->query('reason') === 'email_change' ? 'email_change' : 'registration';
        app()->setLocale($locale);
        $expires = (int) config('auth.verification.expire', 60);

        $mailContext = [
            ...$context->forUser($user),
            'verification_url' => URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes($expires),
                ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())],
            ),
            'expires_in' => $expires,
            'reason' => $reason,
        ];

        $mail = new VerifyEmailMail($mailContext, $locale);

        return view('mail.preview', [
            'subject' => $mail->envelope()->subject,
            'recipient' => $user->email,
            'locale' => $locale,
            'reason' => $reason,
            'html' => $mail->render(),
        ]);
    }
}
