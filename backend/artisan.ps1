# Run artisan when `php` is not on PATH (Windows).
# Usage: .\artisan.ps1 serve --host=127.0.0.1 --port=1234

$phpCandidates = @(
    (Get-Command php -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source),
    "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe",
    "$env:LOCALAPPDATA\Programs\PHP\php.exe",
    "C:\php\php.exe"
) | Where-Object { $_ -and (Test-Path $_) }

$php = $phpCandidates | Select-Object -First 1
if (-not $php) {
    Write-Error "PHP not found. Install PHP 8.3+ or add it to PATH."
    exit 1
}

& $php (Join-Path $PSScriptRoot "artisan") @args
