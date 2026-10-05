<?php

namespace App\Services;

use RuntimeException;

/**
 * Transitional shim. Favon does not use Camperwolf's pricing model.
 */
class PricePeriodService
{
    public function applyProposal(object $request, int $reviewerId, mixed $now): int
    {
        throw new RuntimeException('Price change requests are not supported by Favon.');
    }
}
