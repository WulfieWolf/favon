<?php

namespace App\Support;

class PlaceTypeIconMap
{
    /**
     * @return list<string>
     */
    public static function iconsFor(?string $placeTypeSlug): array
    {
        return match ($placeTypeSlug) {
            'campground' => ['caravan', 'camper', 'tent'],
            'motorhome-pitch' => ['camper'],
            'tent-site' => ['tent'],
            'parking' => ['parking'],
            'hiking-parking' => ['parking'],
            'rest-area' => ['gas-station', 'parking', 'tools-kitchen-2'],
            'free-pitch' => ['current-location'],
            'service-station' => ['hammer', 'trash', 'droplet'],
            'camping-outdoor' => ['shopping-cart'],
            default => ['map-pin'],
        };
    }
}
