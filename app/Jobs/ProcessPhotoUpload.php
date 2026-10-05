<?php

namespace App\Jobs;

use App\Services\PhotoProcessingService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPhotoUpload implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public int $uniqueFor = 600;

    public function __construct(public int $photoId)
    {
        $this->onQueue('photos');
    }

    public function handle(PhotoProcessingService $processor): void
    {
        $processor->process($this->photoId);
    }

    public function uniqueId(): string
    {
        return (string) $this->photoId;
    }
}
