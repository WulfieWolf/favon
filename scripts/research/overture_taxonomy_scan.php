<?php

declare(strict_types=1);

/**
 * Overture Places taxonomy scanner for Camperwolf research.
 *
 * - Downloads the official Overture Places taxonomy CSV.
 * - Keeps the raw CSV untouched.
 * - Searches all columns for camping-related terms.
 * - Writes a compact candidate CSV and readable TXT report.
 *
 * No Camperwolf database or application data is touched.
 */

const TAXONOMY_URL = 'https://docs.overturemaps.org/taxonomy/2026-09-23.0/taxonomy.csv';

$outputDir = dirname(__DIR__, 2)
    . DIRECTORY_SEPARATOR . 'storage'
    . DIRECTORY_SEPARATOR . 'app'
    . DIRECTORY_SEPARATOR . 'private'
    . DIRECTORY_SEPARATOR . 'overture-research'
    . DIRECTORY_SEPARATOR . 'taxonomy';

$searchTerms = [
    'camp',
    'camping',
    'campground',
    'campsite',
    'camp_site',
    'caravan',
    'rv',
    'rv_park',
    'recreational vehicle',
    'recreational_vehicle',
    'motorhome',
    'motor home',
    'camper',
    'tent',
    'trailer',
    'holiday park',
    'holiday_park',
];

if (! is_dir($outputDir) && ! mkdir($outputDir, 0777, true) && ! is_dir($outputDir)) {
    fwrite(STDERR, "Fehler: Ausgabeordner konnte nicht erstellt werden.\n");
    exit(1);
}

$rawPath = $outputDir . DIRECTORY_SEPARATOR . 'taxonomy_raw.csv';
$candidateCsvPath = $outputDir . DIRECTORY_SEPARATOR . 'taxonomy_candidates.csv';
$reportPath = $outputDir . DIRECTORY_SEPARATOR . 'taxonomy_candidates.txt';
$headerPath = $outputDir . DIRECTORY_SEPARATOR . 'taxonomy_headers.txt';

echo "Overture Taxonomy Scan\n";
echo "======================\n";
echo 'Quelle: ' . TAXONOMY_URL . "\n\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: Camperwolf-Research/1.0',
            'Accept: text/csv,*/*;q=0.8',
        ],
        'timeout' => 60,
        'follow_location' => 1,
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

echo "Lade Taxonomie...\n";
$csv = @file_get_contents(TAXONOMY_URL, false, $context);

if ($csv === false || trim($csv) === '') {
    fwrite(STDERR, "Fehler: Taxonomie konnte nicht heruntergeladen werden.\n");
    fwrite(STDERR, 'URL: ' . TAXONOMY_URL . "\n");
    exit(2);
}

if (file_put_contents($rawPath, $csv) === false) {
    fwrite(STDERR, "Fehler: Rohdatei konnte nicht gespeichert werden.\n");
    exit(3);
}

echo "Rohdatei gespeichert: {$rawPath}\n";

$lines = preg_split('/\R/u', $csv);
if (! $lines || trim($lines[0] ?? '') === '') {
    fwrite(STDERR, "Fehler: CSV scheint leer oder unlesbar zu sein.\n");
    exit(4);
}

$firstLine = $lines[0];

$delimiters = [
    ',' => substr_count($firstLine, ','),
    ';' => substr_count($firstLine, ';'),
    "\t" => substr_count($firstLine, "\t"),
];

arsort($delimiters);
$delimiter = array_key_first($delimiters);

if (($delimiters[$delimiter] ?? 0) < 1) {
    fwrite(STDERR, "Fehler: CSV-Trennzeichen konnte nicht erkannt werden.\n");
    exit(5);
}

$handle = fopen($rawPath, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Fehler: Rohdatei konnte nicht geöffnet werden.\n");
    exit(6);
}

$headers = fgetcsv($handle, 0, $delimiter, '"', '');
if (! $headers) {
    fclose($handle);
    fwrite(STDERR, "Fehler: CSV-Header konnte nicht gelesen werden.\n");
    exit(7);
}

$headers = array_map(
    static fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B"),
    $headers
);

file_put_contents(
    $headerPath,
    'Erkannte Spalten (' . count($headers) . '):' . PHP_EOL . '- ' . implode(PHP_EOL . '- ', $headers) . PHP_EOL
);

$candidates = [];
$totalRows = 0;

while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
    if ($row === [null] || $row === []) {
        continue;
    }

    $totalRows++;

    if (count($row) < count($headers)) {
        $row = array_pad($row, count($headers), '');
    } elseif (count($row) > count($headers)) {
        $row = array_slice($row, 0, count($headers));
    }

    $assoc = array_combine($headers, $row);
    if ($assoc === false) {
        continue;
    }

    $haystack = mb_strtolower(
        implode(' | ', array_map(static fn ($value) => (string) $value, $assoc)),
        'UTF-8'
    );

    $matchedTerms = [];

    foreach ($searchTerms as $term) {
        if (str_contains($haystack, mb_strtolower($term, 'UTF-8'))) {
            $matchedTerms[] = $term;
        }
    }

    if ($matchedTerms === []) {
        continue;
    }

    $assoc['_matched_terms'] = implode(', ', array_values(array_unique($matchedTerms)));
    $candidates[] = $assoc;
}

fclose($handle);

$out = fopen($candidateCsvPath, 'wb');
if ($out === false) {
    fwrite(STDERR, "Fehler: Kandidaten-CSV konnte nicht erstellt werden.\n");
    exit(8);
}

$outputHeaders = array_merge($headers, ['_matched_terms']);
fputcsv($out, $outputHeaders, ',', '"', '');

foreach ($candidates as $candidate) {
    $ordered = [];

    foreach ($outputHeaders as $header) {
        $ordered[] = $candidate[$header] ?? '';
    }

    fputcsv($out, $ordered, ',', '"', '');
}

fclose($out);

$interestingHeaderPatterns = [
    'category',
    'name',
    'label',
    'parent',
    'hierarchy',
    'description',
    'basic',
    'id',
];

$reportHeaders = [];

foreach ($headers as $header) {
    $lower = mb_strtolower($header, 'UTF-8');

    foreach ($interestingHeaderPatterns as $pattern) {
        if (str_contains($lower, $pattern)) {
            $reportHeaders[] = $header;
            break;
        }
    }
}

if ($reportHeaders === []) {
    $reportHeaders = array_slice($headers, 0, min(8, count($headers)));
}

$report = [
    'Overture Places Taxonomy - Camping-Kandidaten',
    '================================================',
    '',
    'Quelle: ' . TAXONOMY_URL,
    'Taxonomie-Zeilen gesamt: ' . number_format($totalRows, 0, ',', '.'),
    'Campingnahe Kandidaten: ' . number_format(count($candidates), 0, ',', '.'),
    'Suchbegriffe: ' . implode(', ', $searchTerms),
    '',
    'WICHTIG: Die Treffer sind absichtlich breit. Sie sind noch keine Import-Whitelist.',
    '',
];

foreach ($candidates as $index => $candidate) {
    $report[] = sprintf('#%d', $index + 1);

    foreach ($reportHeaders as $header) {
        $value = trim((string) ($candidate[$header] ?? ''));

        if ($value !== '') {
            $report[] = $header . ': ' . $value;
        }
    }

    $report[] = 'matched_terms: ' . ($candidate['_matched_terms'] ?? '');
    $report[] = str_repeat('-', 72);
}

file_put_contents($reportPath, implode(PHP_EOL, $report) . PHP_EOL);

echo "\nFertig.\n";
echo 'Taxonomie-Zeilen gesamt: ' . number_format($totalRows, 0, ',', '.') . "\n";
echo 'Campingnahe Kandidaten: ' . number_format(count($candidates), 0, ',', '.') . "\n\n";
echo "Ausgaben:\n";
echo "  Rohdaten:        {$rawPath}\n";
echo "  Spalten:         {$headerPath}\n";
echo "  Kandidaten:      {$candidateCsvPath}\n";
echo "  Lesbarer Report: {$reportPath}\n";
