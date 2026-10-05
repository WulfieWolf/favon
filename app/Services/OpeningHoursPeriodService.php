<?php

namespace App\Services;

use RuntimeException;

/**
 * Transitional shim. Favon does not use Camperwolf's recurring opening-hours model.
 */
class OpeningHoursPeriodService
{
    public function applyProposal(object $request, int $reviewerId, mixed $now): int
    {
        throw new RuntimeException('Opening-hours change requests are not supported by Favon.');
    }
}
