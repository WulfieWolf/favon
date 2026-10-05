<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PlaceTypeFeatureService
{
    /** Legacy category defaults remain as a fallback for old data without a pivot row. */
    private const STANDARD_CATEGORIES = [
        'campground' => ['utilities', 'sanitary', 'facilities', 'access', 'rules', 'dogs', 'services', 'rental', 'accessibility'],
        'motorhome-pitch' => ['utilities', 'sanitary', 'access', 'rules', 'dogs', 'services', 'accessibility'],
        'tent-site' => ['utilities', 'sanitary', 'facilities', 'access', 'rules', 'dogs', 'accessibility'],
        'parking' => ['access', 'rules', 'dogs', 'accessibility'],
        'hiking-parking' => ['access', 'rules', 'dogs', 'accessibility'],
        'rest-area' => ['fuel-rest-area', 'utilities', 'sanitary', 'access', 'dogs', 'services', 'accessibility'],
        'free-pitch' => ['access', 'rules', 'dogs', 'accessibility'],
        'service-station' => ['utilities', 'services', 'fuel-rest-area', 'sanitary', 'accessibility'],
        'camping-outdoor' => ['services', 'facilities', 'utilities', 'rental', 'accessibility'],
        'stay' => ['access', 'rules', 'dogs', 'accessibility'],
        'stay-service' => ['utilities', 'sanitary', 'access', 'rules', 'dogs', 'services', 'accessibility'],
        'service' => ['utilities', 'services', 'sanitary', 'accessibility'],
    ];

    public function standardCategorySlugs(string $placeTypeSlug): array
    {
        return self::STANDARD_CATEGORIES[$placeTypeSlug] ?? [];
    }

    public function isStandardCategory(string $placeTypeSlug, string $categorySlug): bool
    {
        return in_array($categorySlug, $this->standardCategorySlugs($placeTypeSlug), true);
    }

    public function quickGroups(Collection $groups, string $placeTypeSlug): Collection
    {
        return $groups->map(function ($group) use ($placeTypeSlug) {
            $group->features = $group->features->filter(function ($feature) use ($placeTypeSlug, $group) {
                if (isset($feature->visibility)) {
                    return $feature->visibility === 'standard';
                }

                $categorySlug = $feature->category_slug ?? $group->slug ?? null;

                return $categorySlug !== null && $this->isStandardCategory($placeTypeSlug, $categorySlug);
            })->values();

            return $group;
        })->filter(fn ($group) => $group->features->isNotEmpty())->values();
    }
}
