# Stop dev servers listening on backend (1234) and frontend (5678) ports.
# Usage: .\scripts\stop-dev.ps1

$ErrorActionPreference = "Continue"
$ports = @(1234, 5678)

foreach ($port in $ports) {
    $conns = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
    if (-not $conns) {
        Write-Host "Port ${port}: nothing listening" -ForegroundColor DarkGray
        continue
    }

    $procIds = $conns | Select-Object -ExpandProperty OwningProcess -Unique
    foreach ($procId in $procIds) {
        try {
            $proc = Get-Process -Id $procId -ErrorAction Stop
            Write-Host "Stopping $($proc.ProcessName) (PID $procId) on port $port" -ForegroundColor Yellow
            Stop-Process -Id $procId -Force -ErrorAction Stop
        } catch {
            Write-Host "Could not stop PID $procId on port ${port}: $_" -ForegroundColor Red
        }
    }
}

Write-Host "Done. Run .\dev.cmd or .\scripts\dev.ps1 to start again." -ForegroundColor Green
