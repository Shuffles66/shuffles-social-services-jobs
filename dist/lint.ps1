# PHP syntax check (lint) for the whole plugin. Runs `php -l` on every .php file and reports any
# parse errors. Exit 0 = all clean, 1 = at least one syntax error, 2 = no PHP found.
#
# Run:  powershell.exe -NonInteractive -File ".\dist\lint.ps1"
# Use before every deploy so a stray bracket/quote never reaches the live site.

$ErrorActionPreference = 'Stop'

# --- Locate php.exe: PATH first, then the winget install, then common dev installs. ---
$php = (Get-Command php -ErrorAction SilentlyContinue).Source
if (-not $php) {
	$php = Get-ChildItem "$env:LOCALAPPDATA\Microsoft\WinGet\Packages" -Recurse -Filter php.exe -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty FullName
}
if (-not $php) {
	foreach ($c in @("C:\php\php.exe", "C:\xampp\php\php.exe", "C:\laragon\bin\php\php.exe", "C:\tools\php\php.exe")) {
		if (Test-Path $c) { $php = $c; break }
	}
}
if (-not $php) { Write-Output "ERROR: no php.exe found (install with: winget install -e --id PHP.PHP.8.3)"; exit 2 }

# Plugin root = the folder that contains this dist/ directory.
$root = Split-Path $PSScriptRoot -Parent
if (-not $root) { $root = (Resolve-Path "$PWD").Path }

$files = Get-ChildItem -Path $root -Recurse -Filter *.php | Where-Object {
	$_.FullName -notmatch '\\dist\\' -and $_.FullName -notmatch '\\node_modules\\' -and $_.FullName -notmatch '\\vendor\\'
}

$fail = @()
foreach ($f in $files) {
	$out = & $php -l $f.FullName 2>&1
	if ($LASTEXITCODE -ne 0) {
		$fail += [pscustomobject]@{ File = $f.FullName.Substring($root.Length + 1); Msg = (($out | Out-String).Trim()) }
	}
}

Write-Output ("PHP {0}" -f (& $php -r 'echo PHP_VERSION;'))
Write-Output ("Linted {0} PHP files in {1}" -f $files.Count, $root)
if ($fail.Count -eq 0) {
	Write-Output "RESULT: OK - no syntax errors."
	exit 0
}
Write-Output ("RESULT: {0} file(s) with syntax errors:" -f $fail.Count)
foreach ($x in $fail) { Write-Output ("`n  x {0}`n    {1}" -f $x.File, $x.Msg) }
exit 1
