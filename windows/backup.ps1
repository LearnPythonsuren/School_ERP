<#
.SYNOPSIS
  Consistent backup of every Scholar ERP database (central + one per school) and the .env.
  install.ps1 schedules this nightly at 02:00. Keeps the last -KeepDays days.
.EXAMPLE
  powershell -ExecutionPolicy Bypass -File windows\backup.ps1
#>
param(
    [string]$InstallDir = "C:\ScholarERP",
    [int]$KeepDays = 14
)
$ErrorActionPreference = "Stop"
$app = "$InstallDir\app"
$php = "$InstallDir\php\php.exe"
$env:PHPRC = "$InstallDir\php"
$stamp = Get-Date -Format "yyyy-MM-dd_HHmm"
$work = "$InstallDir\backups\$stamp"
New-Item -ItemType Directory -Force -Path $work | Out-Null

# Central DB is database.sqlite; each school is database\tenant<id> (no extension).
$dbs = Get-ChildItem "$app\database" -File | Where-Object {
    ($_.Name -eq "database.sqlite" -or $_.Name -like "tenant*") -and $_.Name -notmatch "-(wal|shm|journal)$"
}
foreach ($db in $dbs) {
    # VACUUM INTO takes a consistent snapshot even while the site is writing.
    $out = Join-Path $work (($db.Name -replace '\.sqlite$', '') + ".sqlite")
    & $php -r "`$p = new PDO('sqlite:' . `$argv[1]); `$p->exec('PRAGMA busy_timeout = 10000'); `$p->exec('VACUUM INTO ' . `$p->quote(`$argv[2]));" $db.FullName $out
    if ($LASTEXITCODE -ne 0) { throw "Backup of $($db.Name) failed" }
}
# APP_KEY lives in .env — without it, encrypted data and sessions can't be restored.
Copy-Item "$app\.env" "$work\env.txt"

Compress-Archive -Path "$work\*" -DestinationPath "$InstallDir\backups\scholar-$stamp.zip" -Force
Remove-Item $work -Recurse -Force
Get-ChildItem "$InstallDir\backups" -Filter "scholar-*.zip" |
    Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$KeepDays) } | Remove-Item -Force
Write-Host "Backup written: $InstallDir\backups\scholar-$stamp.zip ($($dbs.Count) databases)"
