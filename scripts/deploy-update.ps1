# Event Hosting — production zero-downtime update deployment (Windows / Docker Desktop).
# Builds backend/frontend, backs up PostgreSQL, migrates, and rolling-restarts app services.
param(
    [switch]$SkipPull,
    [switch]$Pull,
    [switch]$Optimize,
    [switch]$Help
)

$ErrorActionPreference = "Stop"

function Show-Usage {
    @"
Usage: .\scripts\deploy-update.ps1 [OPTIONS]

Deploy an Event Hosting production update with database backup and health verification.

Options:
  -SkipPull     Skip git pull (deploy local changes only)
  -Pull         Pass --pull to docker compose build (refresh base images)
  -Optimize     Run php artisan config:cache and route:cache after migrate
  -Help         Show this help

Environment:
  `$env:COMPOSE_FILE   Path to compose file (default: docker/production/docker-compose.yml)
  `$env:HEALTH_URL     Override health check URL
"@
}

function Write-Log([string]$Message) {
    Write-Host "[Event Hosting deploy] $Message"
}

function Write-Err([string]$Message) {
    Write-Host "[Event Hosting deploy] ERROR: $Message" -ForegroundColor Red
}

function Invoke-Compose {
    param([Parameter(ValueFromRemainingArguments = $true)][string[]]$Args)
    & docker compose -f $script:ComposePath --project-directory $script:ComposeDir @Args
    if ($LASTEXITCODE -ne 0) {
        throw "docker compose failed: $($Args -join ' ')"
    }
}

function Get-EnvValue([string]$Name, [string]$Default = "") {
    if ($script:EnvVars.ContainsKey($Name) -and $script:EnvVars[$Name]) {
        return $script:EnvVars[$Name]
    }
    return $Default
}

function Import-DotEnv([string]$Path) {
    $vars = @{}
    if (-not (Test-Path $Path)) {
        return $vars
    }

    Get-Content $Path | ForEach-Object {
        $line = $_.Trim()
        if (-not $line -or $line.StartsWith("#")) { return }
        $idx = $line.IndexOf("=")
        if ($idx -lt 1) { return }
        $key = $line.Substring(0, $idx).Trim()
        $value = $line.Substring($idx + 1).Trim()
        if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
            $value = $value.Substring(1, $value.Length - 2)
        }
        $vars[$key] = $value
    }
    return $vars
}

function Wait-ForComposeHealth {
    param(
        [string]$Service,
        [int]$TimeoutSeconds = 180
    )

    Write-Log "Waiting for $Service to become healthy (timeout ${TimeoutSeconds}s)..."

    $elapsed = 0
    while ($elapsed -lt $TimeoutSeconds) {
        $containerId = (& docker compose -f $script:ComposePath --project-directory $script:ComposeDir ps -q $Service 2>$null | Select-Object -First 1)
        if ($containerId) {
            $status = (& docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' $containerId 2>$null)
            if ($status -eq "healthy" -or $status -eq "running") {
                Write-Log "$Service is $status"
                return
            }
        }
        Start-Sleep -Seconds 5
        $elapsed += 5
    }

    throw "$Service did not become healthy within ${TimeoutSeconds}s"
}

function Test-HttpHealth {
    param(
        [string]$Url,
        [int]$TimeoutSeconds = 120
    )

    Write-Log "Verifying health at $Url..."

    $elapsed = 0
    while ($elapsed -lt $TimeoutSeconds) {
        try {
            $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 10
            if ($response.StatusCode -eq 200 -and $response.Content -match '"status"\s*:\s*"ok"') {
                Write-Log "Health check passed (HTTP 200, status=ok)"
                return
            }
            Write-Log "Health check not ready yet (HTTP $($response.StatusCode)); retrying..."
        }
        catch {
            Write-Log "Health check not ready yet ($($_.Exception.Message)); retrying..."
        }

        Start-Sleep -Seconds 5
        $elapsed += 5
    }

    throw "Health check failed at $Url — expected HTTP 200 with `"status`":`"ok`""
}

if ($Help) {
    Show-Usage
    exit 0
}

$RepoRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$ComposeFile = if ($env:COMPOSE_FILE) { $env:COMPOSE_FILE } else { "docker/production/docker-compose.yml" }
$ComposePath = Join-Path $RepoRoot ($ComposeFile -replace '/', '\')
$ComposeDir = Split-Path -Parent $ComposePath
$BackupDir = Join-Path $RepoRoot "backups"

if (-not (Test-Path $ComposePath)) {
    Write-Err "Compose file not found: $ComposePath"
    exit 1
}

$EnvVars = Import-DotEnv (Join-Path $ComposeDir ".env")
if (-not $EnvVars.Count) {
    Write-Log "Warning: .env not found in $ComposeDir; using defaults for backup and health URLs"
}

$PostgresUser = Get-EnvValue "POSTGRES_USER" "hievents"
$PostgresDb = Get-EnvValue "POSTGRES_DB" "hievents"
$NginxHttpPort = Get-EnvValue "NGINX_HTTP_PORT" "80"

New-Item -ItemType Directory -Force -Path $BackupDir | Out-Null

if (-not $SkipPull) {
    Write-Log "Pulling latest git changes..."
    git -C $RepoRoot pull --ff-only
    if ($LASTEXITCODE -ne 0) { throw "git pull failed" }
}
else {
    Write-Log "Skipping git pull (-SkipPull)"
}

$buildArgs = @("build", "--no-cache", "backend", "frontend")
if ($Pull) {
    $buildArgs += "--pull"
    Write-Log "Building backend and frontend (--no-cache, --pull)..."
}
else {
    Write-Log "Building backend and frontend (--no-cache)..."
}
Invoke-Compose @buildArgs

$Timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$BackupFile = Join-Path $BackupDir "pre_migrate_$Timestamp.sql"

Write-Log "Creating PostgreSQL backup: $BackupFile"
Invoke-Compose exec -T postgres pg_dump -U $PostgresUser -d $PostgresDb --no-owner --no-acl |
    Out-File -FilePath $BackupFile -Encoding ascii

if (-not (Test-Path $BackupFile) -or (Get-Item $BackupFile).Length -eq 0) {
    throw "Backup file is empty: $BackupFile"
}

Write-Log "Backup complete ($((Get-Item $BackupFile).Length) bytes)"

Write-Log "Running database migrations..."
Invoke-Compose run --rm --no-deps backend php artisan migrate --force

if ($Optimize) {
    Write-Log "Caching configuration and routes..."
    Invoke-Compose run --rm --no-deps backend php artisan config:cache
    Invoke-Compose run --rm --no-deps backend php artisan route:cache
}
else {
    Write-Log "Skipping config/route cache (pass -Optimize to enable)"
}

Write-Log "Rolling restart: backend → frontend → queue_worker → scheduler"

Invoke-Compose up -d --no-deps backend
Wait-ForComposeHealth -Service backend -TimeoutSeconds 180

Invoke-Compose up -d --no-deps frontend
Wait-ForComposeHealth -Service frontend -TimeoutSeconds 120

Invoke-Compose up -d --no-deps queue_worker
Write-Log "queue_worker recreated"

Invoke-Compose up -d --no-deps scheduler
Write-Log "scheduler recreated"

if ($env:HEALTH_URL) {
    $HealthUrl = $env:HEALTH_URL
}
elseif ($EnvVars["APP_URL"]) {
    $HealthUrl = ($EnvVars["APP_URL"].TrimEnd("/")) + "/api/health"
}
else {
    $HealthUrl = "http://localhost:$NginxHttpPort/api/health"
}

Test-HttpHealth -Url $HealthUrl -TimeoutSeconds 120

Write-Log "Deployment complete."
