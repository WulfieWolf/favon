<?php

namespace App\Services\Imports;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BayernAtkisCatalogAnalysisService
{
    public const BASE_URL = 'https://geoservices.bayern.de/wfs/v1/ogc_atkis_basisdlm.cgi';

    public function analyze(): array
    {
        $capabilities = $this->request([
            'service' => 'WFS',
            'request' => 'GetCapabilities',
            'version' => '2.0.0',
        ]);

        $types = $this->featureTypes($capabilities);
        $relevant = array_values(array_filter(
            $types,
            fn (array $type): bool => str_contains(mb_strtolower($type['name'].' '.$type['title']), 'platz')
                || str_contains(mb_strtolower($type['name'].' '.$type['title']), 'sportfreizeitunderholungsflaeche')
                || str_contains(mb_strtolower($type['name'].' '.$type['title']), 'freizeitunderholung'),
        ));

        $schemas = [];
        foreach ($relevant as $type) {
            $schemas[$type['name']] = $this->describe($type['name']);
        }

        $profiles = [];
        foreach ([
            'adv:AX_Platz',
            'adv:AX_SportFreizeitUndErholungsflaeche',
        ] as $typeName) {
            if (collect($types)->contains(fn (array $type): bool => $type['name'] === $typeName)) {
                $profiles[$typeName] = $this->profile($typeName);
            }
        }

        return [
            'base_url' => self::BASE_URL,
            'feature_types' => $types,
            'relevant_feature_types' => $relevant,
            'schemas' => $schemas,
            'profiles' => $profiles,
            'relevant_conditions' => $this->relevantConditionMatches(),
        ];
    }

    private function relevantConditionMatches(): array
    {
        $targets = [
            'adv:AX_Platz' => ['5310', '5320', '5330', '5370'],
            'adv:AX_SportFreizeitUndErholungsflaeche' => ['4330'],
        ];

        $result = [];

        foreach ($targets as $featureType => $functions) {
            $pageSize = 5000;
            $startIndex = 0;
            $localType = explode(':', $featureType, 2)[1];

            while (true) {
                $xml = $this->request([
                    'service' => 'WFS',
                    'request' => 'GetFeature',
                    'version' => '2.0.0',
                    'typeNames' => $featureType,
                    'count' => $pageSize,
                    'startIndex' => $startIndex,
                ]);

                $dom = $this->xml($xml);
                $xpath = new DOMXPath($dom);
                $nodes = $xpath->query('//*[local-name()="'.$localType.'"]') ?: [];
                $pageCount = $nodes->length;

                if ($pageCount === 0) {
                    break;
                }

                foreach ($nodes as $node) {
                    $function = trim((string) ($xpath->query('./*[local-name()="funktion"]', $node)?->item(0)?->textContent ?? ''));

                    if (! in_array($function, $functions, true)) {
                        continue;
                    }

                    $condition = trim((string) ($xpath->query('./*[local-name()="zustand"]', $node)?->item(0)?->textContent ?? ''));

                    if ($condition === '') {
                        continue;
                    }

                    $key = $featureType.'|'.$function;
                    $result[$key] ??= [
                        'feature_type' => $featureType,
                        'function' => $function,
                        'counts' => [],
                        'examples' => [],
                    ];

                    $result[$key]['counts'][$condition] = ($result[$key]['counts'][$condition] ?? 0) + 1;

                    if (count($result[$key]['examples']) < 20) {
                        $result[$key]['examples'][] = [
                            'id' => $node->attributes?->getNamedItemNS('http://www.opengis.net/gml/3.2', 'id')?->nodeValue,
                            'name' => $this->featureName($xpath, $node),
                            'condition' => $condition,
                        ];
                    }
                }

                $startIndex += $pageCount;

                if ($pageCount < $pageSize) {
                    break;
                }
            }
        }

        foreach ($result as &$group) {
            arsort($group['counts']);
        }
        unset($group);

        ksort($result);

        return $result;
    }

    private function profile(string $typeName): array
    {
        $hitsXml = $this->request([
            'service' => 'WFS',
            'request' => 'GetFeature',
            'version' => '2.0.0',
            'typeNames' => $typeName,
            'resultType' => 'hits',
        ]);

        $hitsDom = $this->xml($hitsXml);
        $numberMatched = $hitsDom->documentElement?->getAttribute('numberMatched');
        $numberMatched = is_numeric($numberMatched) ? (int) $numberMatched : null;

        $pageSize = 5000;
        $startIndex = 0;
        $fetched = 0;
        $functions = [];
        $conditions = [];
        $designations = [];
        $withName = 0;
        $withoutName = 0;
        $examples = [];
        $designationExamples = [];
        $conditionExamples = [];
        $localType = str_contains($typeName, ':') ? explode(':', $typeName, 2)[1] : $typeName;

        while ($numberMatched === null || $startIndex < $numberMatched) {
            $xml = $this->request([
                'service' => 'WFS',
                'request' => 'GetFeature',
                'version' => '2.0.0',
                'typeNames' => $typeName,
                'count' => $pageSize,
                'startIndex' => $startIndex,
            ]);

            $dom = $this->xml($xml);
            $xpath = new DOMXPath($dom);
            $nodes = $xpath->query('//*[local-name()="'.$localType.'"]') ?: [];
            $pageCount = $nodes->length;

            if ($pageCount === 0) {
                break;
            }

            foreach ($nodes as $node) {
                $functionNode = $xpath->query('./*[local-name()="funktion"]', $node)?->item(0);
                $function = trim((string) $functionNode?->textContent);
                $function = $function !== '' ? $function : '(leer)';
                $functions[$function] = ($functions[$function] ?? 0) + 1;

                $name = $this->featureName($xpath, $node);

                $condition = trim((string) ($xpath->query('./*[local-name()="zustand"]', $node)?->item(0)?->textContent ?? ''));
                if ($condition !== '') {
                    $conditions[$condition] = ($conditions[$condition] ?? 0) + 1;

                    if (count($conditionExamples[$condition] ?? []) < 5) {
                        $conditionExamples[$condition][] = [
                            'id' => $node->attributes?->getNamedItemNS('http://www.opengis.net/gml/3.2', 'id')?->nodeValue,
                            'function' => $function,
                            'name' => $name,
                        ];
                    }
                }

                foreach ($xpath->query('./*[local-name()="bezeichnung"]', $node) ?: [] as $designationNode) {
                    $designation = trim((string) $designationNode->textContent);

                    if ($designation === '') {
                        continue;
                    }

                    $designations[$designation] = ($designations[$designation] ?? 0) + 1;

                    if (count($designationExamples) < 40) {
                        $designationExamples[] = [
                            'id' => $node->attributes?->getNamedItemNS('http://www.opengis.net/gml/3.2', 'id')?->nodeValue,
                            'function' => $function,
                            'name' => $name,
                            'designation' => $designation,
                        ];
                    }
                }

                if ($name !== null) {
                    $withName++;

                    if (count($examples) < 20) {
                        $examples[] = [
                            'id' => $node->attributes?->getNamedItemNS('http://www.opengis.net/gml/3.2', 'id')?->nodeValue,
                            'function' => $function,
                            'name' => $name,
                        ];
                    }
                } else {
                    $withoutName++;
                }
            }

            $fetched += $pageCount;
            $startIndex += $pageCount;

            if ($pageCount < $pageSize) {
                break;
            }
        }

        arsort($functions);
        arsort($conditions);
        arsort($designations);

        return [
            'number_matched' => $numberMatched,
            'fetched' => $fetched,
            'truncated' => $numberMatched !== null && $fetched < $numberMatched,
            'with_name' => $withName,
            'without_name' => $withoutName,
            'function_values' => $functions,
            'condition_values' => $conditions,
            'designation_values' => $designations,
            'named_examples' => $examples,
            'designation_examples' => $designationExamples,
            'condition_examples' => $conditionExamples,
        ];
    }

    private function featureName(DOMXPath $xpath, \DOMNode $node): ?string
    {
        $nameNode = $xpath->query('./*[local-name()="name"]', $node)?->item(0);

        if ($nameNode) {
            $plain = $xpath->query('.//*[local-name()="unverschluesselt"]', $nameNode)?->item(0);
            $value = trim((string) ($plain?->textContent ?: $nameNode->textContent));
            if ($value !== '') {
                return $value;
            }
        }

        $secondName = $xpath->query('./*[local-name()="zweitname"]', $node)?->item(0);
        $value = trim((string) $secondName?->textContent);

        return $value !== '' ? $value : null;
    }

    private function describe(string $typeName): array
    {
        $xml = $this->request([
            'service' => 'WFS',
            'request' => 'DescribeFeatureType',
            'version' => '2.0.0',
            'typeNames' => $typeName,
        ]);

        $dom = $this->xml($xml);
        $xpath = new DOMXPath($dom);
        $elements = [];

        foreach ($xpath->query('//*[local-name()="element"]') ?: [] as $element) {
            $name = trim((string) $element->attributes?->getNamedItem('name')?->nodeValue);
            if ($name === '') {
                continue;
            }

            $elements[] = [
                'name' => $name,
                'type' => trim((string) $element->attributes?->getNamedItem('type')?->nodeValue) ?: null,
                'min_occurs' => trim((string) $element->attributes?->getNamedItem('minOccurs')?->nodeValue) ?: null,
                'max_occurs' => trim((string) $element->attributes?->getNamedItem('maxOccurs')?->nodeValue) ?: null,
            ];
        }

        return $elements;
    }

    private function featureTypes(string $xml): array
    {
        $dom = $this->xml($xml);
        $xpath = new DOMXPath($dom);
        $result = [];

        foreach ($xpath->query('//*[local-name()="FeatureType"]') ?: [] as $featureType) {
            $nameNode = $xpath->query('./*[local-name()="Name"]', $featureType)?->item(0);
            $titleNode = $xpath->query('./*[local-name()="Title"]', $featureType)?->item(0);
            $name = trim((string) $nameNode?->textContent);

            if ($name === '') {
                continue;
            }

            $result[] = [
                'name' => $name,
                'title' => trim((string) $titleNode?->textContent),
            ];
        }

        return $result;
    }

    public function sampleRelevant(int $perFunction = 10): array
    {
        $targets = [
            'adv:AX_Platz' => ['5310', '5320', '5330', '5370'],
            'adv:AX_SportFreizeitUndErholungsflaeche' => ['4330'],
        ];

        $result = [];

        foreach ($targets as $featureType => $functions) {
            $xml = $this->request([
                'service' => 'WFS',
                'request' => 'GetFeature',
                'version' => '2.0.0',
                'typeNames' => $featureType,
                'count' => 5000,
                'srsName' => 'EPSG:4326',
            ]);

            $dom = $this->xml($xml);
            $xpath = new DOMXPath($dom);
            $localType = explode(':', $featureType, 2)[1];
            $nodes = $xpath->query('//*[local-name()="'.$localType.'"]') ?: [];

            foreach ($nodes as $node) {
                $functionNode = $xpath->query('./*[local-name()="funktion"]', $node)?->item(0);
                $function = trim((string) $functionNode?->textContent);

                if (! in_array($function, $functions, true)) {
                    continue;
                }

                $result[$function] ??= [];

                if (count($result[$function]) >= $perFunction) {
                    continue;
                }

                $id = trim((string) $node->attributes?->getNamedItemNS('http://www.opengis.net/gml/3.2', 'id')?->nodeValue);
                $name = $this->featureName($xpath, $node);
                [$lat, $lon] = $this->sampleRepresentativePoint($xpath, $node);

                $result[$function][] = [
                    'id' => $id,
                    'feature_type' => $featureType,
                    'name' => $name,
                    'latitude' => $lat,
                    'longitude' => $lon,
                ];
            }
        }

        return $result;
    }

    private function sampleRepresentativePoint(DOMXPath $xpath, \DOMNode $node): array
    {
        $pairs = [];

        foreach ($xpath->query('.//*[local-name()="posList"]', $node) ?: [] as $posList) {
            $values = preg_split('/\\s+/', trim((string) $posList->textContent)) ?: [];

            for ($i = 0; $i + 1 < count($values); $i += 2) {
                if (! is_numeric($values[$i]) || ! is_numeric($values[$i + 1])) {
                    continue;
                }

                $first = (float) $values[$i];
                $second = (float) $values[$i + 1];

                if ($first >= 47.0 && $first <= 51.0 && $second >= 8.0 && $second <= 14.5) {
                    $pairs[] = [$first, $second];
                } elseif ($second >= 47.0 && $second <= 51.0 && $first >= 8.0 && $first <= 14.5) {
                    $pairs[] = [$second, $first];
                }
            }
        }

        if ($pairs === []) {
            return [null, null];
        }

        return [
            array_sum(array_column($pairs, 0)) / count($pairs),
            array_sum(array_column($pairs, 1)) / count($pairs),
        ];
    }

    private function request(array $query): string
    {
        $response = Http::timeout(60)
            ->retry(2, 750)
            ->get(self::BASE_URL, $query);

        if (! $response->successful()) {
            throw new RuntimeException('Bayern ATKIS WFS HTTP '.$response->status().'.');
        }

        $body = $response->body();
        if (trim($body) === '') {
            throw new RuntimeException('Bayern ATKIS WFS lieferte eine leere Antwort.');
        }

        return $body;
    }

    private function xml(string $xml): DOMDocument
    {
        $dom = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            if (! $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                $error = collect(libxml_get_errors())->first();
                throw new RuntimeException('Bayern ATKIS WFS XML konnte nicht gelesen werden: '.trim((string) ($error?->message ?? 'unbekannter XML-Fehler')));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $dom;
    }
}
