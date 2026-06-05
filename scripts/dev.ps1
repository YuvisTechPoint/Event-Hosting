# Start Event Hosting dev servers on Windows (no Docker).
# Handles PHP path detection and skips PostgreSQL if already running.
#
# Usage:
#   .\scripts\dev.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$backendDir = Join-Path $root "backend"
$frontendDir = Join-Path $root "frontend"

function Find-Php {
    $candidates = @(
        (Get-Command php -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source),
        "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe",
        "$env:LOCALAPPDATA\Programs\PHP\php.exe",
        "C:\php\php.exe"
    ) | Where-Object { $_ -and (Test-Path $_) }

    foreach ($laragon in (Get-ChildItem "C:\laragon\bin\php" -Filter php.exe -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1)) {
        $candidates += $laragon.FullName
    }

    return $candidates | Select-Object -First 1
}

function Test-PortListening($port) {
    return [bool](Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue)
}

$php = Find-Php
if (-not $php) {
    Write-Host "PHP not found. Install PHP 8.3+ or run: winget install PHP.PHP.8.3" -ForegroundColor Red
    exit 1
}

# PostgreSQL: only warn if not reachable (often already running as a service)
try {
    $tcp = New-Object System.Net.Sockets.TcpClient
    $tcp.Connect("127.0.0.1", 5432)
    $tcp.Close()
    Write-Host "PostgreSQL: running on :5432" -ForegroundColor Green
} catch {
    Write-Host "PostgreSQL not reachable on :5432. Start the PostgreSQL service first." -ForegroundColor Red
    exit 1
}

if (Test-PortListening 1234) {
    Write-Host "Backend already running on http://localhost:1234" -ForegroundColor Yellow
} else {
    Write-Host "Starting backend on http://localhost:1234 ..." -ForegroundColor Cyan
    $backendCmd = "Set-Location '$backendDir'; & '$php' artisan serve --host=127.0.0.1 --port=1234"
    Start-Process powershell -ArgumentList "-NoExit", "-Command", $backendCmd
    Start-Sleep -Seconds 2
}

if (Test-PortListening 5678) {
    Write-Host "Frontend already running on http://localhost:5678" -ForegroundColor Yellow
    Write-Host "Open http://localhost:5678 — do not start a second Vite instance." -ForegroundColor Green
} else {
    Write-Host "Starting frontend on http://localhost:5678 ..." -ForegroundColor Cyan
    $frontendCmd = "Set-Location '$frontendDir'; yarn dev:csr"
    Start-Process powershell -ArgumentList "-NoExit", "-Command", $frontendCmd
}

Write-Host ""
Write-Host "  App:     http://localhost:5678" -ForegroundColor White
Write-Host "  API:     http://localhost:1234/health" -ForegroundColor White
Write-Host "  PHP:     $php" -ForegroundColor DarkGray
