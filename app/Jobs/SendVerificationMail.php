<?php

namespace App\Jobs;

use App\Exceptions\MailSafetyException;
use App\Models\User;
use App\Services\VerificationMailService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendVerificationMail implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public int $uniqueFor = 60;

    public function __construct(
        public int $userId,
        public string $reason = 'registration',
    ) {
        $this->onQueue('mail');
    }

    public function uniqueId(): string
    {
        return $this->userId.'|'.$this->reason;
    }

    public function handle(VerificationMailService $verificationMail): void
    {
        $user = User::query()->find($this->userId);

        if (! $user || $user->hasVerifiedEmail()) {
            return;
        }

        try {
            $verificationMail->send(
                $user,
                in_array($this->reason, ['registration', 'email_change'], true)
                    ? $this->reason
                    : 'registration',
            );
        } catch (MailSafetyException) {
            // Expected safety suppression: do not turn a blocked duplicate/rate limit into a failed job.
        }
    }
}
