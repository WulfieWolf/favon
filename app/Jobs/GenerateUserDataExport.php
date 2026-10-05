<?php

namespace App\Jobs;

use App\Services\UserDataExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateUserDataExport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(public int $requestId)
    {
    }

    public function handle(UserDataExportService $exports): void
    {
        $exports->generate($this->requestId);
    }
}
