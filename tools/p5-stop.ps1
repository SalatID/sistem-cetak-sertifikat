<#
.SYNOPSIS
    Menghentikan server Laravel + Cloudflare Tunnel (Quick atau Named) yang
    dijalankan oleh tools/p5-start.ps1 atau tools/p5-start-named.ps1.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tools/p5-stop.ps1
#>
param(
    [switch]$Quiet
)

$toolsDir = $PSScriptRoot
$root = Split-Path -Parent $toolsDir
$stateFiles = @(
    (Join-Path $root 'storage\app\p5-tunnel.json'),
    (Join-Path $root 'storage\app\p5-named.json')
)

$found = $false
$stopped = @()
$ports = @()

foreach ($stateFile in $stateFiles) {
    if (-not (Test-Path $stateFile)) { continue }

    $found = $true
    try {
        $state = Get-Content -Raw $stateFile | ConvertFrom-Json
    } catch {
        Remove-Item $stateFile -Force -ErrorAction SilentlyContinue
        continue
    }

    foreach ($id in @($state.tunnelPid, $state.serverPid)) {
        if ($id) {
            $proc = Get-Process -Id $id -ErrorAction SilentlyContinue
            if ($proc -and $proc.ProcessName -in @('cloudflared', 'php')) {
                Stop-Process -Id $id -Force -ErrorAction SilentlyContinue
            }
        }
    }

    if ($state.port) { $ports += [int] $state.port }
    $stopped += "tunnel PID $($state.tunnelPid), server PID $($state.serverPid)"
    Remove-Item $stateFile -Force -ErrorAction SilentlyContinue
}

# Hentikan juga proses php -S yang mungkin masih memegang port.
foreach ($port in ($ports | Select-Object -Unique)) {
    $listener = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($listener) {
        $proc = Get-Process -Id $listener.OwningProcess -ErrorAction SilentlyContinue
        if ($proc -and $proc.ProcessName -eq 'php') {
            Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
        }
    }
}

if (-not $Quiet) {
    if ($found) {
        Write-Host "P5 dihentikan ($($stopped -join '; '))." -ForegroundColor Yellow
    } else {
        Write-Host 'Tidak ada state P5 (p5-tunnel.json / p5-named.json).'
    }
}
