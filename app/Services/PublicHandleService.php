<?php

namespace App\Services;

use InvalidArgumentException;

class PublicHandleService
{
    private const PREFIX = 'CW-';
    private const SPACE = 60466176; // 36^5
    private const MULTIPLIER = 15485863;
    private const OFFSET = 27182818;

    public function automaticForUserId(int $userId): string
    {
        if ($userId < 1 || $userId > self::SPACE) {
            throw new InvalidArgumentException('User ID is outside the supported 5-character public handle range.');
        }

        $value = ((self::MULTIPLIER * $userId) + self::OFFSET) % self::SPACE;
        $code = strtoupper(base_convert((string) $value, 10, 36));

        return self::PREFIX.str_pad($code, 5, '0', STR_PAD_LEFT);
    }

    public function isReservedAutomaticNamespace(string $handle): bool
    {
        return str_starts_with(strtoupper($handle), self::PREFIX);
    }
}
