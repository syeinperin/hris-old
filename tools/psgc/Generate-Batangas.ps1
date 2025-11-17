param(
  [Parameter(Mandatory=$true)]
  [string]$Csv,                   # Path to batangas_barangays.csv
  [Parameter(Mandatory=$true)]
  [string]$OutDir                 # Output folder for JSON per city/municipality
)

# Fail fast on errors
$ErrorActionPreference = "Stop"

# Ensure output directory exists
if (-not (Test-Path $OutDir)) {
  New-Item -ItemType Directory -Force -Path $OutDir | Out-Null
}

# Validate CSV
if (-not (Test-Path $Csv)) {
  throw "CSV not found at: $Csv"
}

Write-Host "Reading CSV: $Csv"
$rows = Import-Csv -Path $Csv

# Expecting columns: Province, CityMun, Barangay
# Filter to Batangas rows only (case-insensitive)
$batangas = $rows | Where-Object { $_.Province -match '^\s*Batangas\s*$' }

if (-not $batangas) {
  throw "No Batangas rows found. Check the CSV and column names (Province, CityMun, Barangay)."
}

# Group by City/Municipality and emit a JSON file per city
$groups = $batangas | Group-Object -Property CityMun

foreach ($g in $groups) {
  $city = $g.Name
  if ([string]::IsNullOrWhiteSpace($city)) { continue }

  # Collect barangay names (distinct, sorted)
  $barangays =
    $g.Group |
    Where-Object { -not [string]::IsNullOrWhiteSpace($_.Barangay) } |
    Select-Object -ExpandProperty Barangay |
    Sort-Object -Unique

  $payload = [pscustomobject]@{
    Province   = "Batangas"
    CityMun    = $city
    Barangays  = $barangays
    Count      = $barangays.Count
    Generated  = (Get-Date).ToString("yyyy-MM-ddTHH:mm:ssK")
  }

  # Safe file name for city
  $safe = ($city -replace '[^\p{L}\p{Nd}\s\-\(\)\.]','').Trim() -replace '\s+',' '
  $safe = $safe -replace '\s','_'

  $outFile = Join-Path $OutDir "$safe.json"
  $payload | ConvertTo-Json -Depth 5 | Out-File -FilePath $outFile -Encoding UTF8 -Force

  Write-Host "✓ $city -> $outFile"
}

Write-Host "Done. Files written to $OutDir"
