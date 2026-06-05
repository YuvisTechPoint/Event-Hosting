# Start Event Hosting locally on Windows via Docker Desktop.
# For native Windows (no Docker), use: .\scripts\start-local-windows-native.ps1
$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$dockerDir = Join-Path $root "docker\development"

Write-Host "Starting Event Hosting (Docker)..." -ForegroundColor Cyan
Write-Host "No Docker? Run: .\scripts\start-local-windows-native.ps1" -ForegroundColor Yellow
Set-Location $dockerDir

docker info *> $null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Docker Desktop is not running. Start Docker Desktop first, then re-run this script." -ForegroundColor Red
    Write-Host "Alternatively, use native setup: .\scripts\start-local-windows-native.ps1" -ForegroundColor Yellow
    exit 1
}

docker compose -f docker-compose.dev.yml up -d
docker compose -f docker-compose.dev.yml exec -T backend composer install --ignore-platform-reqs --no-interaction --optimize-autoloader --prefer-dist
docker compose -f docker-compose.dev.yml exec -T backend php artisan migrate --force
docker compose -f docker-compose.dev.yml exec -T backend php artisan storage:link

Write-Host ""
Write-Host "Event Hosting is ready:" -ForegroundColor Green
Write-Host "  Full app:  https://localhost:8443"
Write-Host "  Frontend:  http://localhost:5678 (run 'yarn dev:csr' in frontend/)"
Write-Host "  Mailpit:   http://localhost:8025"
Write-Host ""
Write-Host "Async jobs (email, webhooks): set QUEUE_CONNECTION=redis in backend/.env, then:" -ForegroundColor Yellow
Write-Host "  docker compose -f docker-compose.dev.yml --profile worker up -d queue-worker"
