# Shared helpers for native Windows dev (dev.ps1, start-local-windows-native.ps1).

function Find-Php {
    $candidates = [System.Collections.Generic.List[string]]::new()

    $cmd = Get-Command php -ErrorAction SilentlyContinue
    if ($cmd -and $cmd.Source) {
        $candidates.Add($cmd.Source)
    }

    $wingetRoot = Join-Path $env:LOCALAPPDATA "Microsoft\WinGet\Packages"
    if (Test-Path $wingetRoot) {
        Get-ChildItem $wingetRoot -Directory -Filter "PHP.PHP.*" -ErrorAction SilentlyContinue | ForEach-Object {
            $exe = Join-Path $_.FullName "php.exe"
            if (Test-Path $exe) { $candidates.Add($exe) }
        }
    }

    foreach ($path in @(
            "$env:LOCALAPPDATA\Programs\PHP\php.exe",
            "C:\php\php.exe",
            "C:\tools\php83\php.exe"
        )) {
        if (Test-Path $path) { $candidates.Add($path) }
    }

    foreach ($laragon in (Get-ChildItem "C:\laragon\bin\php" -Filter php.exe -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1)) {
        $candidates.Add($laragon.FullName)
    }

    return ($candidates | Where-Object { $_ } | Select-Object -Unique | Select-Object -First 1)
}

function Test-PortListening([int]$Port) {
    return [bool](Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue)
}

function Test-PostgresReachable {
    try {
        $pg = Get-Command psql -ErrorAction SilentlyContinue
        if ($pg) {
            & psql -h 127.0.0.1 -p 5432 -U postgres -c "SELECT 1" 2>$null | Out-Null
            if ($LASTEXITCODE -eq 0) { return $true }
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

function Start-PostgresIfNeeded {
    if (Test-PostgresReachable) {
        Write-Host "PostgreSQL: listening on :5432" -ForegroundColor Green
        return $true
    }

    $service = Get-Service -Name "postgresql-x64-16" -ErrorAction SilentlyContinue
    if ($service -and $service.Status -ne "Running") {
        try {
            Start-Service $service.Name -ErrorAction Stop
            Start-Sleep -Seconds 3
            if (Test-PostgresReachable) {
                Write-Host "PostgreSQL: started Windows service" -ForegroundColor Green
                return $true
            }
        } catch {
            Write-Host "Could not start PostgreSQL service (admin may be required). Trying pg_ctl..." -ForegroundColor Yellow
        }
    }

    $pgCtlCandidates = @(
        "C:\Program Files\PostgreSQL\16\bin\pg_ctl.exe",
        "C:\Program Files\PostgreSQL\15\bin\pg_ctl.exe"
    )
    $dataCandidates = @(
        "C:\Program Files\PostgreSQL\16\data",
        "C:\Program Files\PostgreSQL\15\data"
    )

    for ($i = 0; $i -lt $pgCtlCandidates.Count; $i++) {
        $pgCtl = $pgCtlCandidates[$i]
        $pgData = $dataCandidates[$i]
        if ((Test-Path $pgCtl) -and (Test-Path $pgData)) {
            & $pgCtl -D $pgData -l (Join-Path $pgData "..\log\dev-start.log") start 2>$null | Out-Null
            Start-Sleep -Seconds 4
            if (Test-PostgresReachable) {
                Write-Host "PostgreSQL: started via pg_ctl" -ForegroundColor Green
                return $true
            }
        }
    }

    Write-Host "PostgreSQL not reachable on :5432. Start the PostgreSQL service, then re-run." -ForegroundColor Red
    return $false
}

function Test-DevHttp([string]$Url) {
    try {
        $response = Invoke-WebRequest -Uri $Url -UseBasicParsing -TimeoutSec 8
        return $response.StatusCode -eq 200
    } catch {
        return $false
    }
}

function Start-DevBackendWindow([string]$BackendDir, [string]$PhpExe) {
    $phpDir = Split-Path -Parent $PhpExe
    $backendCmd = "`$env:Path = '$phpDir;' + `$env:Path; Set-Location '$BackendDir'; & '$PhpExe' artisan serve --host=127.0.0.1 --port=1234"
    Start-Process powershell -ArgumentList "-NoExit", "-Command", $backendCmd
}

function Start-DevFrontendWindow([string]$FrontendDir, [switch]$Ssr) {
    $devScript = if ($Ssr) { "yarn dev:ssr" } else { "yarn dev:csr" }
    $frontendCmd = "Set-Location '$FrontendDir'; $devScript"
    Start-Process powershell -ArgumentList "-NoExit", "-Command", $frontendCmd
}

function Ensure-DevServers([string]$BackendDir, [string]$FrontendDir, [string]$PhpExe, [switch]$Ssr) {
    if (-not (Start-PostgresIfNeeded)) {
        exit 1
    }

    if (Test-PortListening 1234) {
        Write-Host "Backend already running on http://127.0.0.1:1234" -ForegroundColor Yellow
    } else {
        Write-Host "Starting backend on http://127.0.0.1:1234 ..." -ForegroundColor Cyan
        Start-DevBackendWindow -BackendDir $BackendDir -PhpExe $PhpExe
        Start-Sleep -Seconds 3
    }

    if (Test-PortListening 5678) {
        Write-Host "Frontend already running on http://localhost:5678" -ForegroundColor Yellow
    } else {
        $devLabel = if ($Ssr) { "SSR" } else { "CSR" }
        Write-Host "Starting frontend on http://localhost:5678 ($devLabel) ..." -ForegroundColor Cyan
        Start-DevFrontendWindow -FrontendDir $FrontendDir -Ssr:$Ssr
        Start-Sleep -Seconds 2
    }

    Write-Host ""
    if (Test-DevHttp "http://127.0.0.1:1234/health") {
        Write-Host "  API health: OK  http://127.0.0.1:1234/health" -ForegroundColor Green
    } else {
        Write-Host "  API health: not ready yet — check the backend PowerShell window" -ForegroundColor Yellow
    }

    if (Test-DevHttp "http://localhost:5678") {
        Write-Host "  Frontend:   OK  http://localhost:5678" -ForegroundColor Green
    } else {
        Write-Host "  Frontend:   not ready yet — check the Vite window" -ForegroundColor Yellow
    }

    Write-Host ""
    Write-Host "  App:  http://localhost:5678" -ForegroundColor White
    Write-Host "  API:  http://127.0.0.1:1234/health" -ForegroundColor White
    Write-Host "  PHP:  $PhpExe" -ForegroundColor DarkGray
    Write-Host ""
    Write-Host "Stop servers: .\scripts\stop-dev.ps1" -ForegroundColor DarkGray
}
