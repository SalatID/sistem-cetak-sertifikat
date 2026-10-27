<#
.SYNOPSIS
    Menjalankan server Laravel (mode produksi) + Cloudflare Named Tunnel
    untuk domain tetap (default: https://sbcc.my.id).

.DESCRIPTION
    - Memakai konfigurasi tools/cloudflared-named.yml dan tunnel `sertifikat`.
    - Menjalankan `php artisan serve` dengan env produksi hanya untuk proses ini.
    - Menyimpan PID + URL ke storage/app/p5-named.json.
    - Hentikan dengan tools/p5-stop.ps1.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tools/p5-start-named.ps1
    powershell -ExecutionPolicy Bypass -File tools/p5-start-named.ps1 -Hostname sbcc.my.id
#>
param(
    [int]$Port = 8000,
    [string]$BindHost = '127.0.0.1',
    [string]$Hostname = 'sbcc.my.id',
    [int]$TimeoutSeconds = 60
)

$ErrorActionPreference = 'Stop'

$toolsDir = $PSScriptRoot
$root = Split-Path -Parent $toolsDir
$cloudflared = Join-Path $toolsDir 'cloudflared.exe'
$config = Join-Path $toolsDir 'cloudflared-named.yml'
$stateFile = Join-Path $root 'storage\app\p5-named.json'
$logDir = Join-Path $root 'storage\logs'

if (-not (Test-Path $cloudflared)) {
    throw "cloudflared.exe tidak ditemukan di $cloudflared."
}
if (-not (Test-Path $config)) {
    throw "Konfigurasi Named Tunnel tidak ditemukan di $config."
}

New-Item -ItemType Directory -Force $logDir | Out-Null

# Bersihkan cache config/route/view agar env produksi benar-benar terbaca.
& php artisan optimize:clear 2>&1 | Out-Null

# Hentikan instance P5 sebelumnya (Quick maupun Named).
& (Join-Path $toolsDir 'p5-stop.ps1') -Quiet
Start-Sleep -Milliseconds 500

# Pastikan port tidak dipakai proses PHP lain (mis. artisan serve manual).
$listener = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue | Select-Object -First 1
if ($listener) {
    $proc = Get-Process -Id $listener.OwningProcess -ErrorAction SilentlyContinue
    if ($proc -and $proc.ProcessName -eq 'php') {
        Stop-Process -Id $proc.Id -Force
        Start-Sleep -Milliseconds 500
    } else {
        throw "Port $Port sedang dipakai proses '$($proc.ProcessName)' (PID $($listener.OwningProcess))."
    }
}

Write-Host "Menjalankan Laravel (production) di port $Port ..." -ForegroundColor Cyan
$env:APP_ENV = 'production'
$env:APP_DEBUG = 'false'
$env:SESSION_SECURE_COOKIE = 'true'
$env:APP_URL = "https://$Hostname"
$env:LOG_LEVEL = 'warning'

$serveOut = Join-Path $logDir 'serve.out.log'
$serveErr = Join-Path $logDir 'serve.err.log'
Remove-Item $serveOut, $serveErr -ErrorAction SilentlyContinue

$server = Start-Process -FilePath 'php' `
    -ArgumentList @('artisan', 'serve', "--host=$BindHost", "--port=$Port") `
    -WorkingDirectory $root `
    -RedirectStandardOutput $serveOut `
    -RedirectStandardError $serveErr `
    -PassThru -WindowStyle Hidden

Write-Host "Menjalankan Cloudflare Named Tunnel untuk https://$Hostname ..." -ForegroundColor Cyan
$tunnelOut = Join-Path $logDir 'cloudflared-named.out.log'
$tunnelErr = Join-Path $logDir 'cloudflared-named.err.log'
Remove-Item $tunnelOut, $tunnelErr -ErrorAction SilentlyContinue

$tunnel = Start-Process -FilePath $cloudflared `
    -ArgumentList @('--no-autoupdate', 'tunnel', '--config', $config, 'run', 'sertifikat') `
    -WorkingDirectory $root `
    -RedirectStandardOutput $tunnelOut `
    -RedirectStandardError $tunnelErr `
    -PassThru -WindowStyle Hidden

# Tunggu server lokal siap.
$localUp = $false
$deadline = (Get-Date).AddSeconds(20)
while ((Get-Date) -lt $deadline) {
    Start-Sleep -Milliseconds 500
    try {
        $resp = Invoke-WebRequest -Uri "http://127.0.0.1:$Port/up" -UseBasicParsing -TimeoutSec 3
        if ($resp.StatusCode -eq 200) { $localUp = $true; break }
    } catch { }
}

# Tunggu URL publik merespons (DNS + tunnel + sertifikat Cloudflare).
$publicUp = $false
$deadline = (Get-Date).AddSeconds($TimeoutSeconds)
while ((Get-Date) -lt $deadline) {
    Start-Sleep -Seconds 1
    try {
        $resp = Invoke-WebRequest -Uri "https://$Hostname/up" -UseBasicParsing -TimeoutSec 8
        if ($resp.StatusCode -eq 200) { $publicUp = $true; break }
    } catch { }
}

[pscustomobject]@{
    mode      = 'named'
    url       = "https://$Hostname"
    hostname  = $Hostname
    port      = $Port
    tunnelPid = $tunnel.Id
    serverPid = $server.Id
    startedAt = (Get-Date).ToString('o')
} | ConvertTo-Json | Set-Content -Path $stateFile -Encoding UTF8

if (-not $localUp) {
    Write-Host "PERINGATAN: server lokal http://127.0.0.1:$Port/up belum 200. Cek $serveErr." -ForegroundColor Yellow
}
if (-not $publicUp) {
    Write-Host "PERINGATAN: https://$Hostname/up belum 200. Cek $tunnelErr dan status DNS/SSL Cloudflare." -ForegroundColor Yellow
}

Write-Host ''
Write-Host "URL publik : https://$Hostname" -ForegroundColor Green
Write-Host "Login      : https://$Hostname/login" -ForegroundColor Green
Write-Host "Hentikan   : powershell -ExecutionPolicy Bypass -File tools/p5-stop.ps1" -ForegroundColor DarkGray
Write-Host ''
Write-Host 'Named Tunnel memakai domain tetap; aman untuk hari-H.' -ForegroundColor Yellow
