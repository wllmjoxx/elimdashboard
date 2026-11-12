<#
Online preview from this laptop through a Cloudflare quick tunnel, with the
whole app running as on a server: built assets (no Vite dev server), queue
worker (emails), scheduler, SSR, and uploads up to 64 MB.

    powershell -ExecutionPolicy Bypass -File deploy\tunnel.ps1

Stop with Ctrl+C: every process stops and .env gets APP_URL back to
http://localhost:8000. The address changes each run, so links in emails
sent during an earlier run no longer open.
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
Set-Location $root

$port = 8000
$envFile = Join-Path $root '.env'
$logDir = Join-Path $root 'storage\logs\preview'
$utf8 = New-Object System.Text.UTF8Encoding $false

function Set-EnvValue([string] $name, [string] $value) {
    $content = [IO.File]::ReadAllText($envFile)
    $line = if ($value) { "$name=$value" } else { '' }
    if ($content -match "(?m)^$name=.*$") {
        $content = [regex]::Replace($content, "(?m)^$name=.*(\r?\n)?", $(if ($line) { "$line`n" } else { '' }))
    } elseif ($line) {
        $content = $content.TrimEnd() + "`n$line`n"
    }
    [IO.File]::WriteAllText($envFile, $content, $utf8)
}

if (Test-Path (Join-Path $root 'public\hot')) {
    throw 'Vite dev server masih jalan (composer dev / npm run dev). Hentikan dulu dengan Ctrl+C di terminalnya, lalu jalankan skrip ini lagi.'
}
if (Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue) {
    throw "Port $port sudah dipakai (php artisan serve / composer dev?). Hentikan dulu."
}
if (-not (Get-Command cloudflared -ErrorAction SilentlyContinue)) {
    throw 'cloudflared belum terpasang: winget install --id Cloudflare.cloudflared'
}

New-Item -ItemType Directory -Force $logDir | Out-Null
$processes = @()

try {
    Write-Host '==> Build front end (client + SSR)'
    npm run build:ssr
    if ($LASTEXITCODE) { throw 'Build gagal.' }

    Write-Host '==> Cloudflare tunnel'
    $tunnelLog = Join-Path $logDir 'cloudflared.log'
    Remove-Item $tunnelLog -ErrorAction SilentlyContinue
    $tunnel = Start-Process cloudflared -PassThru -WindowStyle Hidden `
        -ArgumentList 'tunnel', '--no-autoupdate', '--url', "http://127.0.0.1:$port", '--logfile', "`"$tunnelLog`""
    $processes += $tunnel

    $url = $null
    for ($i = 0; $i -lt 90 -and -not $url; $i++) {
        Start-Sleep -Seconds 1
        if (Test-Path $tunnelLog) {
            $match = Select-String -Path $tunnelLog -Pattern 'https://[a-z0-9-]+\.trycloudflare\.com' | Select-Object -First 1
            if ($match) { $url = $match.Matches[0].Value }
        }
    }
    if (-not $url) { throw 'Alamat tunnel tidak muncul. Cek koneksi internet, lalu coba lagi.' }

    Set-EnvValue 'APP_URL' $url
    Set-EnvValue 'ASSET_URL' $url
    # Build the caches once, before the processes start: four of them
    # writing bootstrap/cache at the same time fail on Windows file locks.
    php artisan optimize:clear | Out-Null
    php artisan migrate --force
    if ($LASTEXITCODE) { throw 'php artisan migrate gagal.' }
    php artisan optimize | Out-Null
    if ($LASTEXITCODE) { throw 'php artisan optimize gagal.' }

    Write-Host '==> App, queue, scheduler, SSR'
    # Uploads up to 64 MB for the PHP server (and the processes it starts).
    $env:PHP_INI_SCAN_DIR = Join-Path $root 'deploy\php'
    $start = {
        param([string] $name, [string] $file, [string[]] $arguments)
        Start-Process $file -PassThru -WindowStyle Hidden -ArgumentList $arguments `
            -RedirectStandardOutput (Join-Path $logDir "$name.log") `
            -RedirectStandardError (Join-Path $logDir "$name.err.log")
        Start-Sleep -Seconds 1
    }
    $processes += & $start 'server' 'php' @('artisan', 'serve', '--host=127.0.0.1', "--port=$port")
    $processes += & $start 'queue' 'php' @('artisan', 'queue:work', '--tries=3', '--sleep=1')
    $processes += & $start 'schedule' 'php' @('artisan', 'schedule:work')
    $processes += & $start 'ssr' 'php' @('artisan', 'inertia:start-ssr')

    Start-Sleep -Seconds 3
    Write-Host ''
    Write-Host "  Online di: $url" -ForegroundColor Green
    Write-Host "  Log: storage\logs\preview\   Stop: Ctrl+C"
    Write-Host ''

    while ($true) {
        Start-Sleep -Seconds 3
        $down = $processes | Where-Object { $_.HasExited }
        if ($down) { throw "Proses berhenti sendiri (lihat storage\logs\preview): $($down.ProcessName -join ', ')" }
    }
}
finally {
    Write-Host '==> Menghentikan preview'
    foreach ($process in $processes) {
        # /T also stops children (php artisan serve starts php -S).
        if ($process -and -not $process.HasExited) { taskkill /T /F /PID $process.Id 2>$null | Out-Null }
    }
    Set-EnvValue 'APP_URL' 'http://localhost:8000'
    Set-EnvValue 'ASSET_URL' ''
    php artisan optimize:clear | Out-Null
    Write-Host '    .env kembali ke http://localhost:8000. Jalankan composer dev seperti biasa.'
}
