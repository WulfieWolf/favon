<?php

namespace App\Services\Imports;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class NiedersachsenHubCatalogAnalysisService
{
    private const ENDPOINT = 'https://meta.et4.de/rest.ashx/search/';

    private const CATEGORIES = [
        'Wohnmobilstellplatz',
        'Campingplatz',
    ];

    public function analyze(int $limitPerCategory = 100): array
    {
        $limitPerCategory = max(1, min(250, $limitPerCategory));

        $key = (string) config('services.niedersachsen_hub.key');
        $experience = (string) config('services.niedersachsen_hub.experience');

        if ($key === '' || $experience === '') {
            throw new RuntimeException('Niedersachsen-Hub-Konfiguration fehlt. Prüfe NIEDERSACHSEN_HUB_API_KEY und NIEDERSACHSEN_HUB_EXPERIENCE.');
        }

        $categories = [];
        $combined = $this->emptyDistribution();

        foreach (self::CATEGORIES as $category) {
            $response = Http::timeout(30)
                ->retry(2, 500)
                ->get(self::ENDPOINT, [
                    'experience' => $experience,
                    'licensekey' => $key,
                    'type' => 'Hotel',
                    'q' => 'category:"'.$category.'"',
                    'template' => 'ET2014A.json',
                    'limit' => $limitPerCategory,
                    'facets' => 'false',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Niedersachsen Hub HTTP '.$response->status().' für Kategorie '.$category.'.');
            }

            $payload = $response->json();
            if (! is_array($payload) || ($payload['status'] ?? null) !== 'OK') {
                throw new RuntimeException('Ungültige Niedersachsen-Hub-Antwort für Kategorie '.$category.'.');
            }

            $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
            $distribution = $this->distribution($items);

            $categories[$category] = [
                'returned' => count($items),
                'overallcount' => (int) ($payload['overallcount'] ?? 0),
                'distribution' => $distribution,
            ];

            $combined = $this->mergeDistributions($combined, $distribution);
        }

        return [
            'limit_per_category' => $limitPerCategory,
            'categories' => $categories,
            'combined' => $combined,
        ];
    }

    private function distribution(array $items): array
    {
        $result = $this->emptyDistribution();

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            foreach ($item['features'] ?? [] as $feature) {
                if (is_string($feature) && trim($feature) !== '') {
                    $this->increment($result['features'], trim($feature));
                }
            }

            foreach ($item['payment'] ?? [] as $payment) {
                if (is_string($payment) && trim($payment) !== '') {
                    $this->increment($result['payments'], trim($payment));
                }
            }

            foreach ($item['numbers'] ?? [] as $number) {
                if (! is_array($number)) {
                    continue;
                }

                $type = trim((string) ($number['type'] ?? ''));
                if ($type === '') {
                    continue;
                }

                $this->increment($result['number_types'], $type);

                if (array_key_exists('value', $number) && is_numeric($number['value'])) {
                    $value = (float) $number['value'];
                    $result['number_values'][$type] ??= [
                        'count' => 0,
                        'min' => $value,
                        'max' => $value,
                    ];
                    $result['number_values'][$type]['count']++;
                    $result['number_values'][$type]['min'] = min($result['number_values'][$type]['min'], $value);
                    $result['number_values'][$type]['max'] = max($result['number_values'][$type]['max'], $value);
                }
            }

            foreach ($item['attributes'] ?? [] as $attribute) {
                if (! is_array($attribute)) {
                    continue;
                }

                $key = trim((string) ($attribute['key'] ?? ''));
                if ($key !== '') {
                    $this->increment($result['attribute_keys'], $key);
                }
            }
        }

        foreach (['features', 'payments', 'number_types', 'attribute_keys'] as $key) {
            arsort($result[$key]);
        }

        ksort($result['number_values']);

        return $result;
    }

    private function emptyDistribution(): array
    {
        return [
            'features' => [],
            'payments' => [],
            'number_types' => [],
            'number_values' => [],
            'attribute_keys' => [],
        ];
    }

    private function mergeDistributions(array $left, array $right): array
    {
        foreach (['features', 'payments', 'number_types', 'attribute_keys'] as $key) {
            foreach ($right[$key] as $value => $count) {
                $left[$key][$value] = ($left[$key][$value] ?? 0) + $count;
            }
            arsort($left[$key]);
        }

        foreach ($right['number_values'] as $type => $stats) {
            if (! isset($left['number_values'][$type])) {
                $left['number_values'][$type] = $stats;
                continue;
            }

            $left['number_values'][$type]['count'] += $stats['count'];
            $left['number_values'][$type]['min'] = min($left['number_values'][$type]['min'], $stats['min']);
            $left['number_values'][$type]['max'] = max($left['number_values'][$type]['max'], $stats['max']);
        }

        ksort($left['number_values']);

        return $left;
    }

    private function increment(array &$distribution, string $value): void
    {
        $distribution[$value] = ($distribution[$value] ?? 0) + 1;
    }
}
