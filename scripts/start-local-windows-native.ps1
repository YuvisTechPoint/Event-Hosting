# Start Event Hosting locally on Windows WITHOUT Docker.
# Requires: PostgreSQL 16+, PHP 8.3+, Composer, Node.js, Yarn
#
# Usage:
#   .\scripts\start-local-windows-native.ps1              # First-time setup + start servers
#   .\scripts\start-local-windows-native.ps1 -SetupOnly   # Run migrations/setup only
#   .\scripts\start-local-windows-native.ps1 -SkipSetup   # Start servers only

param(
    [switch]$SetupOnly,
    [switch]$SkipSetup
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$backendDir = Join-Path $root "backend"
$frontendDir = Join-Path $root "frontend"

function Find-Php {
    $candidates = @(
        (Get-Command php -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source),
        "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe",
        "$env:LOCALAPPDATA\Programs\PHP\php.exe",
        "C:\php\php.exe",
        "C:\tools\php83\php.exe",
        "C:\laragon\bin\php\php-8.3.12-Win32-vs16-x64\php.exe"
    ) | Where-Object { $_ -and (Test-Path $_) }

    foreach ($laragon in (Get-ChildItem "C:\laragon\bin\php" -Filter php.exe -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1)) {
        $candidates += $laragon.FullName
    }

    return $candidates | Select-Object -First 1
}

function Test-Postgres {
    try {
        $pg = Get-Command psql -ErrorAction SilentlyContinue
        if ($pg) {
            & psql -h 127.0.0.1 -p 5432 -U postgres -c "SELECT 1" 2>$null | Out-Null
            return $LASTEXITCODE -eq 0
        }
    } catch {}

    try {
        $tcp = New-Object System.Net.Sockets.TcpClient
        $tcp.Connect("127.0.0.1", 5432)
        $tcp.Close()
        return $true
    } catch {
        return $false
    }
}

$php = Find-Php
if (-not $php) {
    Write-Host "PHP not found. Install PHP 8.3+ and add it to PATH, or install via Laragon." -ForegroundColor Red
    Write-Host "Required extensions: gd, pdo_pgsql, sodium, curl, intl, mbstring, xml, zip, bcmath" -ForegroundColor Yellow
    exit 1
}

Write-Host "Using PHP: $php" -ForegroundColor Cyan

if (-not (Test-Postgres)) {
    Write-Host "PostgreSQL is not reachable on 127.0.0.1:5432. Start PostgreSQL before continuing." -ForegroundColor Red
    exit 1
}

if (-not $SkipSetup) {
    Write-Host "Setting up backend..." -ForegroundColor Cyan
    Set-Location $backendDir

    if (-not (Test-Path ".env")) {
        Copy-Item ".env.example" ".env"
        Write-Host "Created backend/.env from .env.example — update DB credentials if needed." -ForegroundColor Yellow
    }

    if (-not (Test-Path "vendor\autoload.php")) {
        if (Test-Path "composer.phar") {
            & $php composer.phar install --no-interaction --prefer-dist
        } elseif (Get-Command composer -ErrorAction SilentlyContinue) {
            composer install --no-interaction --prefer-dist
        } else {
            Write-Host "Composer not found. Install Composer or place composer.phar in backend/." -ForegroundColor Red
            exit 1
        }
    }

    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "APP_KEY=\s*$" -or $envContent -match "APP_KEY=$") {
        & $php artisan key:generate --force
    }

    & $php artisan migrate --force
    & $php artisan storage:link 2>$null

    Write-Host "Setting up frontend..." -ForegroundColor Cyan
    Set-Location $frontendDir

    if (-not (Test-Path ".env")) {
        @"
VITE_API_URL_CLIENT=/api
VITE_API_URL_SERVER=http://127.0.0.1:1234
VITE_FRONTEND_URL=http://localhost:5678
VITE_STRIPE_PUBLISHABLE_KEY=
VITE_APP_NAME=Event Hosting
"@ | Set-Content ".env" -Encoding UTF8
        Write-Host "Created frontend/.env for native dev (API proxied via Vite /api -> :1234)." -ForegroundColor Yellow
    }

    if (-not (Test-Path "node_modules")) {
        yarn install --frozen-lockfile
    }
}

if ($SetupOnly) {
    Write-Host "Setup complete." -ForegroundColor Green
    exit 0
}

Write-Host ""
Write-Host "Starting Event Hosting (native Windows)..." -ForegroundColor Green
Write-Host "  Backend:  http://localhost:1234  (health: /health)" -ForegroundColor White
Write-Host "  Frontend: http://localhost:5678  (API proxy: /api -> :1234)" -ForegroundColor White
Write-Host ""
Write-Host "Queue note: QUEUE_CONNECTION=sync runs jobs inline. For async email/webhooks," -ForegroundColor Yellow
Write-Host "  set QUEUE_CONNECTION=redis, run Redis, and start: php artisan queue:work --queue=default,webhook-queue" -ForegroundColor Yellow
Write-Host ""
Write-Host "Press Ctrl+C in each window to stop servers." -ForegroundColor DarkGray

$backendCmd = "Set-Location '$backendDir'; & '$php' artisan serve --host=127.0.0.1 --port=1234"
$frontendCmd = "Set-Location '$frontendDir'; yarn dev:csr"

Start-Process powershell -ArgumentList "-NoExit", "-Command", $backendCmd
Start-Sleep -Seconds 2
Start-Process powershell -ArgumentList "-NoExit", "-Command", $frontendCmd

Write-Host "Servers launched in separate windows." -ForegroundColor Green
