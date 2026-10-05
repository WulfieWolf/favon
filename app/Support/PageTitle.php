<?php

namespace App\Support;

class PageTitle
{
    public static function resolve(?string $title): ?string
    {
        if (! filled($title)) {
            return null;
        }

        $translated = __($title);

        return is_string($translated) ? $translated : $title;
    }
}
