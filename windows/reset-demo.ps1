<#
.SYNOPSIS
  Creates or refreshes the sales-demo school (School ID "demo") with fresh data.
  Run it the morning of a school visit so the dashboard shows this week's attendance.
  Logins are written to <InstallDir>\DEMO-LOGINS.txt. Real schools are never touched.
.EXAMPLE
  powershell -ExecutionPolicy Bypass -File windows\reset-demo.ps1
#>
param([string]$InstallDir = "C:\ScholarERP")
$ErrorActionPreference = "Stop"
$env:PHPRC = "$InstallDir\php"
$out = & "$InstallDir\php\php.exe" "$InstallDir\app\artisan" scholar:demo --no-ansi 2>&1
if ($LASTEXITCODE -ne 0) { $out; throw "Demo reset failed" }
$file = "$InstallDir\DEMO-LOGINS.txt"
Set-Content $file -Encoding UTF8 -Value (@(
    "Scholar ERP - sales demo school   (refreshed $(Get-Date -Format 'dd MMM yyyy HH:mm'))",
    "Open the app, School ID: demo",
    "") + ($out | Select-String -Pattern '@greenfield|Password for all' | ForEach-Object { $_.Line.Trim(' |') }))
Write-Host "Demo school refreshed. Logins saved to $file" -ForegroundColor Green
