# Build a distributable plugin zip.
# Reads the version from the main plugin header, stages the shipping files into a
# slug-named folder, and produces dist/shuffles-social-services-jobs-<version>.zip
# Entries are written with FORWARD-SLASH separators (WordPress/Linux-safe) regardless
# of the PowerShell edition used to run this.
# Usage:  powershell.exe -NonInteractive -File ".\dist\build.ps1"

$ErrorActionPreference = 'Stop'
$slug = 'shuffles-social-services-jobs'
$root = Split-Path -Parent $PSScriptRoot          # plugin root (dist/..)
$main = Join-Path $root "$slug.php"

# --- version from the plugin header ---
$verLine = Select-String -Path $main -Pattern '^\s*\*\s*Version:\s*(.+)$' | Select-Object -First 1
if (-not $verLine) { throw "Could not read Version from $main" }
$version = $verLine.Matches[0].Groups[1].Value.Trim()
Write-Host "Building $slug v$version"

$distDir = Join-Path $root 'dist'
$stage   = Join-Path $env:TEMP "$slug-build"
$payload = Join-Path $stage $slug
if (Test-Path $stage) { Remove-Item -Recurse -Force $stage }
New-Item -ItemType Directory -Force -Path $payload | Out-Null

# --- stage the files/folders to ship ---
$folders = @('includes','admin','public','templates','blocks','data','languages')
foreach ($f in $folders) {
  $src = Join-Path $root $f
  if (Test-Path $src) {
    robocopy $src (Join-Path $payload $f) /E /Z /R:5 /W:2 | Out-Null
  }
}
foreach ($file in @("$slug.php",'uninstall.php','CLAUDE.md','README.md')) {
  $src = Join-Path $root $file
  if (Test-Path $src) { Copy-Item $src (Join-Path $payload $file) -Force }
}

# --- zip with forward-slash entry paths (manual, edition-independent) ---
$zip = Join-Path $distDir "$slug-$version.zip"
if (Test-Path $zip) { Remove-Item -Force $zip }
Add-Type -AssemblyName System.IO.Compression | Out-Null
Add-Type -AssemblyName System.IO.Compression.FileSystem | Out-Null
$archive = [System.IO.Compression.ZipFile]::Open($zip, [System.IO.Compression.ZipArchiveMode]::Create)
try {
  Get-ChildItem -Recurse -File -LiteralPath $stage | ForEach-Object {
    $rel = $_.FullName.Substring($stage.Length + 1) -replace '\\', '/'
    [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $_.FullName, $rel, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
  }
} finally {
  $archive.Dispose()
}
Remove-Item -Recurse -Force $stage

Write-Host "Created $zip"
