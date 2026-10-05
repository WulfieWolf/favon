<?php

namespace App\Jobs;

use App\Exceptions\MailSafetyException;
use App\Models\User;
use App\Services\PasswordResetMailService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPasswordResetMail implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public int $uniqueFor = 60;

    public function __construct(
        public int $userId,
        public string $token,
    ) {
        $this->onQueue('mail');
    }

    public function uniqueId(): string
    {
        return $this->userId.'|'.hash('sha256', $this->token);
    }

    public function handle(PasswordResetMailService $service): void
    {
        $user = User::query()->find($this->userId);

        if (! $user) {
            return;
        }

        try {
            $service->send($user, $this->token);
        } catch (MailSafetyException) {
            // Expected safety suppression: do not turn a blocked duplicate/rate limit into a failed job.
        }
    }
}
