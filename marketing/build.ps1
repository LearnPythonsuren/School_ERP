<#
.SYNOPSIS
  Builds the marketing material from marketing\config.json:
    dist\website\index.html   the product website (setup.sh serves it at your domain's home page)
    dist\quotation.html       interactive quotation maker (open in a browser, fill in, Print > Save as PDF)
    dist\Scholar-ERP-Brochure.pdf      2-page A4 brochure (English + Tamil)
    dist\Scholar-ERP-Price-Sheet.pdf   1-page A4 price sheet
    dist\Sample-Quotation.pdf          example quotation
  PDFs are printed with Microsoft Edge, which shapes Tamil script correctly.
.EXAMPLE
  powershell -ExecutionPolicy Bypass -File marketing\build.ps1
#>
$ErrorActionPreference = "Stop"
$root = $PSScriptRoot
$src = Join-Path $root "src"
$dist = Join-Path $root "dist"
New-Item -ItemType Directory -Force -Path $dist, (Join-Path $dist "website") | Out-Null

$json = [IO.File]::ReadAllText((Join-Path $root "config.json"), [Text.Encoding]::UTF8)
$null = $json | ConvertFrom-Json   # fail early on a typo in config.json
$inject = "<script>window.CFG = $json;</script>"
$utf8 = New-Object Text.UTF8Encoding $false

function Build($name, $target) {
    $html = [IO.File]::ReadAllText((Join-Path $src $name), [Text.Encoding]::UTF8)
    if ($html -notmatch "<!--CONFIG-->") { throw "$name has no <!--CONFIG--> marker" }
    [IO.File]::WriteAllText($target, $html.Replace("<!--CONFIG-->", $inject), $utf8)
    Write-Host "built $target"
}
Build "website.html"     (Join-Path $dist "website\index.html")
Build "quotation.html"   (Join-Path $dist "quotation.html")
Build "brochure.html"    (Join-Path $dist "brochure.html")
Build "price-sheet.html" (Join-Path $dist "price-sheet.html")

$edge = @("${env:ProgramFiles(x86)}\Microsoft\Edge\Application\msedge.exe", "$env:ProgramFiles\Microsoft\Edge\Application\msedge.exe") |
    Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $edge) { Write-Warning "Microsoft Edge not found - skipped PDFs. Open dist\*.html and use Print > Save as PDF."; exit 0 }

$profileDir = Join-Path $env:TEMP "scholar-pdf-profile"
function Pdf($html, $pdf, $query) {
    $url = ([Uri](Join-Path $dist $html)).AbsoluteUri + $query
    $out = Join-Path $dist $pdf
    if (Test-Path $out) { Remove-Item $out }
    $p = Start-Process $edge -PassThru -Wait -WindowStyle Hidden -ArgumentList @(
        "--headless=new", "--disable-gpu", "--no-first-run", "--user-data-dir=`"$profileDir`"",
        "--no-pdf-header-footer", "--run-all-compositor-stages-before-draw", "--virtual-time-budget=10000",
        "--print-to-pdf=`"$out`"", "`"$url`"")
    if (-not (Test-Path $out)) { throw "Edge did not produce $pdf (exit $($p.ExitCode))" }
    Write-Host "printed $out"
}
Pdf "brochure.html"    "Scholar-ERP-Brochure.pdf"    ""
Pdf "price-sheet.html" "Scholar-ERP-Price-Sheet.pdf" ""
Pdf "quotation.html"   "Sample-Quotation.pdf"        "?print=1"
Remove-Item (Join-Path $dist "brochure.html"), (Join-Path $dist "price-sheet.html")
Write-Host "Done. Files are in $dist" -ForegroundColor Green
