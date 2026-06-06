# Start Event Hosting dev servers on Windows (no Docker).
# Handles PHP path detection, PostgreSQL via pg_ctl, and port reuse.
#
# Usage:
#   .\scripts\dev.ps1           # CSR (Vite only)
#   .\scripts\dev.ps1 -Ssr      # SSR (node server.js + Vite middleware)
# Or from repo root:
#   .\dev.cmd

param(
    [switch]$Ssr
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$backendDir = Join-Path $root "backend"
$frontendDir = Join-Path $root "frontend"
$lib = Join-Path $root "scripts\windows-dev-lib.ps1"

. $lib

$php = Find-Php
if (-not $php) {
    Write-Host "PHP not found. Install PHP 8.3+ or run: winget install PHP.PHP.8.3" -ForegroundColor Red
    exit 1
}

Ensure-DevServers -BackendDir $backendDir -FrontendDir $frontendDir -PhpExe $php -Ssr:$Ssr
