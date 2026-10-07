<#
.SYNOPSIS
  Puts Scholar ERP on the internet at your domain, over HTTPS.

.DESCRIPTION
  Recommended: Cloudflare Tunnel. No router port-forwarding or static IP,
  works on home/office broadband, HTTPS handled by Cloudflare.

  Before running (one time, in the Cloudflare dashboard — free plan):
    1. Add your domain to Cloudflare (its nameservers must point to Cloudflare).
    2. Zero Trust > Networks > Tunnels > Create a tunnel > "Cloudflared".
       Copy the token from the install command it shows (the long eyJ... string).
    3. In the tunnel's "Public hostname" tab add:
         Subdomain: erp   Domain: yourdomain.com   Service: HTTP  localhost:80

  Then run:
    powershell -ExecutionPolicy Bypass -File windows\connect-domain.ps1 -Url https://erp.yourdomain.com -TunnelToken eyJ...

  Without -TunnelToken it only records the public address in the app
  (use that if you forward ports and terminate HTTPS yourself).
#>
param(
    [Parameter(Mandatory = $true)][string]$Url,
    [string]$TunnelToken,
    [string]$InstallDir = "C:\ScholarERP"
)
$ErrorActionPreference = "Stop"

$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    $argList = @("-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "`"$PSCommandPath`"", "-Url", $Url, "-InstallDir", "`"$InstallDir`"")
    if ($TunnelToken) { $argList += @("-TunnelToken", $TunnelToken) }
    Write-Host "Asking Windows for Administrator rights (click Yes)..."
    $p = Start-Process powershell -Verb RunAs -ArgumentList $argList -Wait -PassThru
    exit $p.ExitCode
}

try {
    if ($Url -notmatch '^https?://[^/\s]+$') { throw "Use just the address, e.g. https://erp.yourdomain.com (no path)" }
    $app = "$InstallDir\app"
    if (-not (Test-Path "$app\.env")) { throw "Scholar ERP is not installed in $InstallDir. Run windows\install.ps1 first." }

    if ($TunnelToken) {
        $cf = (Get-Command cloudflared -ErrorAction SilentlyContinue).Source
        if (-not $cf) {
            Write-Host "Installing cloudflared (Cloudflare's official connector)..."
            winget install --id Cloudflare.cloudflared --exact --scope machine --accept-package-agreements --accept-source-agreements --silent
            $cf = @("$env:ProgramFiles\cloudflared\cloudflared.exe", "${env:ProgramFiles(x86)}\cloudflared\cloudflared.exe") | Where-Object { Test-Path $_ } | Select-Object -First 1
            if (-not $cf) { throw "cloudflared did not install. Install it manually: winget install Cloudflare.cloudflared" }
        }
        if (Get-Service cloudflared -ErrorAction SilentlyContinue) {
            & $cf service uninstall | Out-Null
        }
        & $cf service install $TunnelToken
        if ($LASTEXITCODE -ne 0) { throw "cloudflared service install failed (check the token)" }
        Set-Service cloudflared -StartupType Automatic
        Start-Service cloudflared -ErrorAction SilentlyContinue
        Write-Host "Cloudflare Tunnel service installed and running." -ForegroundColor Green
    }

    # Record the public address in the app.
    $env:PHPRC = "$InstallDir\php"
    $envFile = Get-Content "$app\.env"
    if ($envFile -match '^APP_URL=') { $envFile = $envFile -replace '^APP_URL=.*', "APP_URL=$Url" } else { $envFile += "APP_URL=$Url" }
    # No byte-order mark: PowerShell 5's UTF8 would corrupt the first .env line.
    [IO.File]::WriteAllLines("$app\.env", [string[]]$envFile, (New-Object Text.UTF8Encoding $false))
    Push-Location $app
    & "$InstallDir\php\php.exe" artisan config:cache
    Pop-Location
    Import-Module WebAdministration
    Restart-WebAppPool ScholarERP

    Start-Sleep -Seconds 5
    try {
        $h = Invoke-WebRequest "$Url/api/health" -UseBasicParsing -TimeoutSec 30
        Write-Host "$Url/api/health -> $($h.StatusCode)  Scholar ERP is live on the internet." -ForegroundColor Green
    } catch {
        Write-Host "Not reachable at $Url yet ($($_.Exception.Message))." -ForegroundColor Yellow
        Write-Host "If you just created the tunnel or DNS record, wait a few minutes and open $Url in a browser."
    }
    Read-Host "Press Enter to close"
} catch {
    Write-Host "FAILED: $($_.Exception.Message)" -ForegroundColor Red
    Read-Host "Press Enter to close"
    exit 1
}
