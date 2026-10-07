<#
.SYNOPSIS
  Installs (or updates) Scholar ERP on Windows with IIS, as a real always-on server.

.DESCRIPTION
  Run it again at any time to apply a new version: it detects the existing
  install and upgrades it in place, keeping all data.

  What it does:
    1. Puts a private copy of PHP in <InstallDir>\php with a production php.ini
    2. Gets Composer (into <InstallDir>\tools)
    3. Builds the app with setup.sh (production mode, SQLite) into <InstallDir>\app
    4. Configures IIS: URL Rewrite, PHP FastCGI, an app pool and a site on -Port
    5. Grants the site write access to storage + databases only
    6. Opens the firewall port, schedules a nightly backup, checks /api/health

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File windows\install.ps1
.EXAMPLE
  powershell -ExecutionPolicy Bypass -File windows\install.ps1 -Url https://erp.example.com -WithDemo
#>
param(
    [string]$InstallDir = "C:\ScholarERP",
    [string]$Url = "http://localhost",
    [int]$Port = 80,
    [string]$OwnerEmail = "owner@chenthur.tech",
    [switch]$WithDemo,
    [switch]$NoFirewall
)

$ErrorActionPreference = "Stop"
$Repo = Split-Path -Parent $PSScriptRoot
$SiteName = "ScholarERP"

# ---- Re-launch elevated if needed (IIS and services require Administrator) ----
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    $argList = @("-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "`"$PSCommandPath`"", "-InstallDir", "`"$InstallDir`"", "-Url", $Url, "-Port", $Port, "-OwnerEmail", $OwnerEmail)
    if ($WithDemo) { $argList += "-WithDemo" }
    if ($NoFirewall) { $argList += "-NoFirewall" }
    Write-Host "Asking Windows for Administrator rights (click Yes)..."
    $p = Start-Process powershell -Verb RunAs -ArgumentList $argList -Wait -PassThru
    exit $p.ExitCode
}

# Some shells start with a trimmed PATH; make sure Windows' own tools (icacls, msiexec…) resolve.
$sys = "$env:windir\System32"
$env:PATH = "$sys;$env:windir;$sys\Wbem;$sys\WindowsPowerShell\v1.0;$env:PATH"

New-Item -ItemType Directory -Force -Path $InstallDir, "$InstallDir\tools", "$InstallDir\backups", "$InstallDir\logs" | Out-Null
Start-Transcript -Path "$InstallDir\logs\install-$(Get-Date -Format yyyyMMdd-HHmmss).log" | Out-Null

function Step($msg) { Write-Host "`n==> $msg" -ForegroundColor Cyan }

try {
    # ---------------------------------------------------------------- PHP
    Step "PHP"
    $php = "$InstallDir\php"
    if (-not (Test-Path "$php\php-cgi.exe")) {
        $src = $null
        $cmd = Get-Command php -ErrorAction SilentlyContinue
        if ($cmd) { $src = Split-Path $cmd.Source }
        if (-not $src -or -not (Test-Path "$src\php-cgi.exe")) {
            throw "PHP 8.2+ not found. Install it first:  winget install PHP.PHP.8.3   (then run this again)"
        }
        # A private copy: IIS cannot read PHP inside a user profile, and the
        # server's php.ini then can't be changed by other tools by accident.
        Copy-Item $src $php -Recurse
        Write-Host "Copied PHP from $src"
    }
    $ini = Get-Content "$php\php.ini-production" -Raw
    foreach ($ext in "curl", "fileinfo", "intl", "mbstring", "openssl", "pdo_sqlite", "sqlite3", "zip", "sodium", "bcmath") {
        $ini = $ini -replace "(?m)^;extension=$ext\s*$", "extension=$ext"
    }
    $ini += @"

; ---- Scholar ERP ----
extension_dir = "$php\ext"
zend_extension = opcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.validate_timestamps = 1
opcache.revalidate_freq = 2
cgi.fix_pathinfo = 1
fastcgi.impersonate = 1
memory_limit = 256M
max_execution_time = 120
upload_max_filesize = 10M
post_max_size = 12M
date.timezone = Asia/Kolkata
expose_php = Off
error_log = "$InstallDir\logs\php-errors.log"
"@
    Set-Content -Path "$php\php.ini" -Value $ini -Encoding ASCII
    $env:PHPRC = $php
    $env:PATH = "$php;$InstallDir\tools;$env:PATH"
    $mods = & "$php\php.exe" -m
    foreach ($m in "pdo_sqlite", "mbstring", "openssl", "curl", "Zend OPcache") {
        if ($mods -notcontains $m) { throw "PHP extension '$m' did not load. Check $php\php.ini" }
    }
    Write-Host (& "$php\php.exe" -v | Select-Object -First 1)

    # ----------------------------------------------------------- Composer
    Step "Composer"
    if (-not (Test-Path "$InstallDir\tools\composer.phar")) {
        Invoke-WebRequest "https://getcomposer.org/download/latest-stable/composer.phar" -OutFile "$InstallDir\tools\composer.phar" -UseBasicParsing
    }
    Set-Content "$InstallDir\tools\composer.bat" "@`"$php\php.exe`" `"$InstallDir\tools\composer.phar`" %*" -Encoding ASCII
    Set-Content "$InstallDir\tools\composer" "#!/usr/bin/env bash`nexec php `"$(($InstallDir -replace '\\','/'))/tools/composer.phar`" `"`$@`"`n" -Encoding ASCII -NoNewline
    & "$InstallDir\tools\composer.bat" --version --no-ansi

    # -------------------------------------------------------------- Build
    $bash = "$env:ProgramFiles\Git\bin\bash.exe"
    if (-not (Test-Path $bash)) { throw "Git for Windows not found. Install it: winget install Git.Git" }
    $app = "$InstallDir\app"
    $appUnix = $app -replace '\\', '/'
    $ownerPassFile = "$InstallDir\FIRST-LOGIN.txt"
    $setupSh = ($Repo -replace '\\', '/') + "/setup.sh"
    $env:SCHOLAR_APP_DIR = $appUnix

    if (Test-Path "$app\artisan") {
        Step "Updating existing install (data is kept)"
        Copy-Item "$app\database" "$InstallDir\backups\pre-update-$(Get-Date -Format yyyyMMdd-HHmmss)" -Recurse
        & $bash $setupSh --update
        if ($LASTEXITCODE -ne 0) { throw "setup.sh --update failed" }
    } else {
        Step "Building the app (a few minutes)"
        $env:SCHOLAR_PRODUCTION = "1"
        $env:SCHOLAR_URL = $Url
        $env:SCHOLAR_DEMO = $(if ($WithDemo) { "1" } else { "0" })
        $env:CENTRAL_ADMIN_EMAIL = $OwnerEmail
        $bytes = New-Object byte[] 18; [Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
        $env:CENTRAL_ADMIN_PASSWORD = ([Convert]::ToBase64String($bytes) -replace '[+/=]', 'x')
        & $bash $setupSh
        if ($LASTEXITCODE -ne 0) { throw "setup.sh failed" }
        Set-Content $ownerPassFile @"
Scholar ERP — owner console first login
Address:  $Url   (choose the "Owner console" tab)
Email:    $OwnerEmail
Password: $($env:CENTRAL_ADMIN_PASSWORD)

Sign in, change the password (account menu > Change password), then DELETE this file.
"@
        Remove-Item Env:CENTRAL_ADMIN_PASSWORD
    }
    # Only Administrators may read the password file (re-applied on every run).
    if (Test-Path $ownerPassFile) {
        & "$sys\icacls.exe" $ownerPassFile /inheritance:r /grant:r "*S-1-5-32-544:F" | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "Could not restrict access to $ownerPassFile" }
    }
    Push-Location $app
    & "$php\php.exe" artisan config:cache
    & "$php\php.exe" artisan route:cache
    Pop-Location

    # ---------------------------------------------------------------- IIS
    Step "IIS"
    $features = @("IIS-WebServerRole", "IIS-WebServer", "IIS-CGI", "IIS-DefaultDocument", "IIS-StaticContent", "IIS-HttpErrors", "IIS-RequestFiltering", "IIS-ManagementConsole")
    $missing = $features | Where-Object { (Get-WindowsOptionalFeature -Online -FeatureName $_).State -ne "Enabled" }
    if ($missing) { Enable-WindowsOptionalFeature -Online -FeatureName $missing -All -NoRestart | Out-Null; Write-Host "Enabled: $($missing -join ', ')" }

    if (-not (Test-Path "$env:windir\System32\inetsrv\rewrite.dll")) {
        Write-Host "Installing IIS URL Rewrite 2.1 (Microsoft)..."
        $msi = "$env:TEMP\rewrite_amd64_en-US.msi"
        Invoke-WebRequest "https://download.microsoft.com/download/1/2/8/128E2E22-C1B9-44A4-BE2A-5859ED1D4592/rewrite_amd64_en-US.msi" -OutFile $msi -UseBasicParsing
        $r = Start-Process msiexec.exe -ArgumentList "/i `"$msi`" /quiet /norestart" -Wait -PassThru
        if ($r.ExitCode -ne 0 -and $r.ExitCode -ne 3010) { throw "URL Rewrite install failed (msiexec $($r.ExitCode))" }
    }
    Import-Module WebAdministration

    # PHP as a FastCGI application (a pool of php-cgi processes)
    $cgi = "$php\php-cgi.exe"
    if (-not (Get-WebConfiguration "/system.webServer/fastCgi/application[@fullPath='$cgi']")) {
        Add-WebConfiguration "/system.webServer/fastCgi" -Value @{ fullPath = $cgi; maxInstances = 8; instanceMaxRequests = 10000; activityTimeout = 300; requestTimeout = 300 }
        Add-WebConfiguration "/system.webServer/fastCgi/application[@fullPath='$cgi']/environmentVariables" -Value @{ name = "PHP_FCGI_MAX_REQUESTS"; value = "10000" }
        Add-WebConfiguration "/system.webServer/fastCgi/application[@fullPath='$cgi']/environmentVariables" -Value @{ name = "PHPRC"; value = $php }
    }

    # The default IIS welcome site also wants port 80: stop it (nothing else uses it).
    $default = Get-Website -Name "Default Web Site" -ErrorAction SilentlyContinue
    if ($default -and $Port -eq 80 -and $default.State -eq "Started") {
        Stop-Website "Default Web Site"
        Set-ItemProperty "IIS:\Sites\Default Web Site" serverAutoStart False
        Write-Host "Stopped the IIS 'Default Web Site' (welcome page) to free port 80."
    }

    if (-not (Test-Path "IIS:\AppPools\$SiteName")) { New-WebAppPool $SiteName | Out-Null }
    Set-ItemProperty "IIS:\AppPools\$SiteName" managedRuntimeVersion ""
    Set-ItemProperty "IIS:\AppPools\$SiteName" startMode AlwaysRunning
    Set-ItemProperty "IIS:\AppPools\$SiteName" processModel.idleTimeout ([TimeSpan]::Zero)

    if (-not (Get-Website -Name $SiteName -ErrorAction SilentlyContinue)) {
        New-Website -Name $SiteName -PhysicalPath "$app\public" -Port $Port -ApplicationPool $SiteName | Out-Null
    } else {
        Set-ItemProperty "IIS:\Sites\$SiteName" physicalPath "$app\public"
    }

    # Site config: .php -> PHP FastCGI, Laravel front controller, security headers.
    # The PHP handler lives in this same file on purpose: handler cmdlets write to
    # web.config too, so writing this file afterwards would silently erase them
    # (every .php request then fails with IIS 404.3).
    Set-Content "$app\public\web.config" -Encoding UTF8 -Value @"
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <handlers>
      <remove name="PHP-FastCGI" />
      <add name="PHP-FastCGI" path="*.php" verb="*" modules="FastCgiModule" scriptProcessor="$cgi" resourceType="Either" requireAccess="Script" />
    </handlers>
    <defaultDocument enabled="true">
      <files><clear /><add value="index.php" /></files>
    </defaultDocument>
    <rewrite>
      <rules>
        <rule name="Laravel" stopProcessing="true">
          <match url="^" ignoreCase="false" />
          <conditions logicalGrouping="MatchAll">
            <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
          </conditions>
          <action type="Rewrite" url="index.php" appendQueryString="true" />
        </rule>
      </rules>
    </rewrite>
    <httpErrors existingResponse="PassThrough" />
    <security><requestFiltering><requestLimits maxAllowedContentLength="12582912" /></requestFiltering></security>
    <httpProtocol>
      <customHeaders>
        <add name="X-Content-Type-Options" value="nosniff" />
        <add name="Referrer-Policy" value="strict-origin-when-cross-origin" />
        <add name="X-Frame-Options" value="SAMEORIGIN" />
      </customHeaders>
    </httpProtocol>
  </system.webServer>
</configuration>
"@

    # ------------------------------------------------------- Permissions
    Step "Permissions"
    $pool = "IIS AppPool\$SiteName"
    icacls $InstallDir /grant "${pool}:(OI)(CI)RX" /T /Q | Out-Null
    foreach ($d in "$app\storage", "$app\bootstrap\cache", "$app\database", "$InstallDir\logs") {
        icacls $d /grant "${pool}:(OI)(CI)M" /T /Q | Out-Null
    }

    Restart-WebAppPool $SiteName -ErrorAction SilentlyContinue
    Start-Website $SiteName -ErrorAction SilentlyContinue

    # ------------------------------------------------- Firewall + backups
    if (-not $NoFirewall) {
        Step "Firewall"
        if (-not (Get-NetFirewallRule -DisplayName "Scholar ERP (HTTP $Port)" -ErrorAction SilentlyContinue)) {
            New-NetFirewallRule -DisplayName "Scholar ERP (HTTP $Port)" -Direction Inbound -Protocol TCP -LocalPort $Port -Action Allow -Profile Domain, Private | Out-Null
        }
        Write-Host "Port $Port open for local network (Private/Domain). Use Cloudflare Tunnel for internet access."
    }

    Step "Nightly backup (02:00)"
    $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$Repo\windows\backup.ps1`" -InstallDir `"$InstallDir`""
    $trigger = New-ScheduledTaskTrigger -Daily -At 2am
    Register-ScheduledTask -TaskName "Scholar ERP backup" -Action $action -Trigger $trigger -User "SYSTEM" -RunLevel Highest -Force | Out-Null

    # --------------------------------------------------------- Health
    Step "Health check"
    Start-Sleep -Seconds 2
    $h = Invoke-WebRequest "http://localhost:$Port/api/health" -UseBasicParsing -TimeoutSec 30
    Write-Host "GET /api/health -> $($h.StatusCode) $($h.Content)" -ForegroundColor Green

    Write-Host "`n=================================================================="
    Write-Host " Scholar ERP is running:  http://localhost:$Port   (and http://<this-PC-IP>:$Port on your network)"
    if (Test-Path $ownerPassFile) { Write-Host " Owner console login:     see $ownerPassFile (admins only)" }
    Write-Host " Update later:            run this script again"
    Write-Host " Internet + HTTPS:        windows\connect-domain.ps1 (see docs\WINDOWS.md)"
    Write-Host "=================================================================="
    Stop-Transcript | Out-Null
    exit 0
}
catch {
    Write-Host "`nINSTALL FAILED: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Full log: $InstallDir\logs"
    Stop-Transcript | Out-Null
    Read-Host "Press Enter to close"
    exit 1
}
