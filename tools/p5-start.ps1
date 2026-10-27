<#
.SYNOPSIS
    Menjalankan server Laravel (mode produksi) + Cloudflare Quick Tunnel untuk P5.

.DESCRIPTION
    - Menjalankan `php artisan serve` dengan APP_ENV=production, APP_DEBUG=false,
      dan SESSION_SECURE_COOKIE=true (hanya untuk proses ini; .env tidak diubah).
    - Menjalankan cloudflared quick tunnel dan menampilkan URL https publik.
    - Menyimpan PID + URL ke storage/app/p5-tunnel.json agar bisa dihentikan
      dengan tools/p5-stop.ps1.
    - URL quick tunnel berubah setiap kali dijalankan ulang.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File tools/p5-start.ps1
    powershell -ExecutionPolicy Bypass -File tools/p5-start.ps1 -Port 8000
#>
param(
    [int]$Port = 8000,
    [string]$BindHost = '127.0.0.1',
    [int]$TimeoutSeconds = 45
)

$ErrorActionPreference = 'Stop'

$toolsDir = $PSScriptRoot
$root = Split-Path -Parent $toolsDir
$cloudflared = Join-Path $toolsDir 'cloudflared.exe'
$stateFile = Join-Path $root 'storage\app\p5-tunnel.json'
$logDir = Join-Path $root 'storage\logs'

if (-not (Test-Path $cloudflared)) {
    throw "cloudflared.exe tidak ditemukan di $cloudflared. Unduh dari https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe"
}

New-Item -ItemType Directory -Force $logDir | Out-Null

# Bersihkan cache config/route/view agar env produksi benar-benar terbaca
# (config:cache yang lama akan mengunci nilai APP_ENV/APP_DEBUG dari .env).
& php artisan optimize:clear 2>&1 | Out-Null

# Hentikan instance P5 sebelumnya (bila ada).
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
        throw "Port $Port sedang dipakai proses '$($proc.ProcessName)' (PID $($listener.OwningProcess)). Hentikan dulu atau pakai -Port lain."
    }
}

Write-Host "Menjalankan Cloudflare Quick Tunnel -> http://${BindHost}:${Port} ..." -ForegroundColor Cyan
$outLog = Join-Path $logDir 'cloudflared.out.log'
$errLog = Join-Path $logDir 'cloudflared.err.log'
Remove-Item $outLog, $errLog -ErrorAction SilentlyContinue

$tunnel = Start-Process -FilePath $cloudflared `
    -ArgumentList @('tunnel', '--url', "http://${BindHost}:${Port}", '--no-autoupdate') `
    -RedirectStandardOutput $outLog `
    -RedirectStandardError $errLog `
    -PassThru -WindowStyle Hidden

$url = $null
$deadline = (Get-Date).AddSeconds($TimeoutSeconds)

while ((Get-Date) -lt $deadline) {
    Start-Sleep -Milliseconds 500
    $log = (Get-Content $errLog -Raw -ErrorAction SilentlyContinue) + (Get-Content $outLog -Raw -ErrorAction SilentlyContinue)
    if ($log -match 'https://[a-z0-9-]+\.trycloudflare\.com') {
        $url = $Matches[0]
        break
    }
}

if (-not $url) {
    Stop-Process -Id $tunnel.Id -Force -ErrorAction SilentlyContinue
    throw "Gagal mendapatkan URL tunnel dalam $TimeoutSeconds detik. Lihat $errLog"
}

# Hostname quick tunnel baru bisa sempat ter-cache sebagai NXDOMAIN oleh resolver
# lokal. Flush cache agar health check dan browser di mesin ini langsung bisa
# membuka URL-nya (di mesin lain biasanya langsung resolve).
ipconfig /flushdns | Out-Null
Start-Sleep -Seconds 2

Write-Host "Menjalankan Laravel (production) di port $Port ..." -ForegroundColor Cyan

$env:APP_ENV = 'production'
$env:APP_DEBUG = 'false'
$env:SESSION_SECURE_COOKIE = 'true'
$env:APP_URL = $url
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

# Tunggu sampai aplikasi merespons lewat tunnel.
$sehat = $false
$deadline = (Get-Date).AddSeconds($TimeoutSeconds)

while ((Get-Date) -lt $deadline) {
    Start-Sleep -Milliseconds 750
    try {
        $resp = Invoke-WebRequest -Uri "$url/up" -UseBasicParsing -TimeoutSec 5
        if ($resp.StatusCode -eq 200) {
            $sehat = $true
            break
        }
    } catch {
        # tunnel/server mungkin belum siap
    }
}

[pscustomobject]@{
    url        = $url
    port       = $Port
    tunnelPid  = $tunnel.Id
    serverPid  = $server.Id
    startedAt  = (Get-Date).ToString('o')
} | ConvertTo-Json | Set-Content -Path $stateFile -Encoding UTF8

if (-not $sehat) {
    Write-Host "PERINGATAN: $url/up belum merespons 200. Cek $serveErr dan $errLog." -ForegroundColor Yellow
}

Write-Host ''
Write-Host "URL publik : $url" -ForegroundColor Green
Write-Host "Login      : $url/login" -ForegroundColor Green
Write-Host "Hentikan   : powershell -ExecutionPolicy Bypass -File tools/p5-stop.ps1" -ForegroundColor DarkGray
Write-Host ''
Write-Host 'Catatan: quick tunnel bersifat sementara dan URL berubah setiap restart.' -ForegroundColor Yellow
