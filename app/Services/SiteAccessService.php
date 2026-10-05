<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiteAccessService
{
    public const NORMAL = 'normal';
    public const REGISTRATION_CLOSED = 'registration_closed';
    public const LOCKDOWN = 'lockdown';

    public const MODES = [
        self::NORMAL,
        self::REGISTRATION_CLOSED,
        self::LOCKDOWN,
    ];

    public function mode(): string
    {
        if (! Schema::hasTable('site_settings')) {
            return self::NORMAL;
        }

        $mode = DB::table('site_settings')->where('key', 'access_mode')->value('value');

        return in_array($mode, self::MODES, true) ? $mode : self::NORMAL;
    }

    public function message(): ?string
    {
        if (! Schema::hasTable('site_settings')) {
            return null;
        }

        $message = trim((string) DB::table('site_settings')->where('key', 'access_message')->value('value'));

        return $message !== '' ? $message : null;
    }

    public function registrationClosed(): bool
    {
        return in_array($this->mode(), [self::REGISTRATION_CLOSED, self::LOCKDOWN], true);
    }

    public function lockdown(): bool
    {
        return $this->mode() === self::LOCKDOWN;
    }

    public function set(string $mode, ?string $message = null): void
    {
        if (! in_array($mode, self::MODES, true)) {
            throw new \InvalidArgumentException('Unknown site access mode.');
        }

        $now = now();

        DB::table('site_settings')->updateOrInsert(
            ['key' => 'access_mode'],
            ['value' => $mode, 'updated_at' => $now, 'created_at' => $now],
        );

        DB::table('site_settings')->updateOrInsert(
            ['key' => 'access_message'],
            ['value' => $message !== null ? trim($message) : null, 'updated_at' => $now, 'created_at' => $now],
        );
    }
}
