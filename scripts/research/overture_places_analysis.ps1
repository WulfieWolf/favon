param(
    [string]$DuckDbVersion = "v1.5.6"
)

$ErrorActionPreference = "Stop"

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
$outputDir = Join-Path $repoRoot "storage\app\private\overture-research\places"
$binDir = Join-Path $repoRoot "storage\app\private\overture-research\bin"
$duckDbExe = Join-Path $binDir "duckdb.exe"
$sqlFile = Join-Path $PSScriptRoot "overture_places_analysis.sql"

New-Item -ItemType Directory -Force -Path $outputDir | Out-Null
New-Item -ItemType Directory -Force -Path $binDir | Out-Null

if (-not (Test-Path $duckDbExe)) {
    $zipPath = Join-Path $binDir "duckdb_cli-windows-amd64.zip"
    $downloadUrl = "https://github.com/duckdb/duckdb/releases/download/$DuckDbVersion/duckdb_cli-windows-amd64.zip"

    Write-Host "DuckDB wurde noch nicht gefunden."
    Write-Host "Lade portable DuckDB CLI $DuckDbVersion..."

    Invoke-WebRequest -Uri $downloadUrl -OutFile $zipPath
    Expand-Archive -Path $zipPath -DestinationPath $binDir -Force
    Remove-Item $zipPath -Force

    if (-not (Test-Path $duckDbExe)) {
        throw "duckdb.exe wurde nach dem Entpacken nicht gefunden: $duckDbExe"
    }
}

if (-not (Test-Path $sqlFile)) {
    throw "SQL-Datei fehlt: $sqlFile"
}

Write-Host ""
Write-Host "Overture Places Analyse"
Write-Host "======================="
Write-Host "Release: 2026-09-23.1"
Write-Host "Land: Deutschland"
Write-Host "Kategorien: campground, rv_park, holiday_park"
Write-Host "Ausgabe: $outputDir"
Write-Host ""
Write-Host "Die Abfrage liest die Overture-GeoParquet-Dateien direkt von AWS."
Write-Host "Es werden keine Camperwolf-Datenbanktabellen geaendert."
Write-Host ""

Push-Location $outputDir

try {
    Get-Content -Raw $sqlFile | & $duckDbExe
    if ($LASTEXITCODE -ne 0) {
        throw "DuckDB wurde mit Exitcode $LASTEXITCODE beendet."
    }
}
finally {
    Pop-Location
}

Write-Host ""
Write-Host "Fertig. Erzeugte Dateien:"
Get-ChildItem -Path $outputDir -File | Sort-Object Name | ForEach-Object {
    Write-Host ("  " + $_.Name)
}
Write-Host ""
Write-Host "Fuer die naechste Auswertung sind besonders interessant:"
Write-Host "  01_category_counts.csv"
Write-Host "  03_confidence_distribution.csv"
Write-Host "  04_field_completeness.csv"
Write-Host "  05_sources_and_licenses.csv"
Write-Host "  07_sample_100_per_category.csv"
Write-Host "  08_compact_summary.csv"
