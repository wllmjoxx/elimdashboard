<#
Builds a release zip for cPanel shared hosting from the current commit.
The zip holds vendor/ (without dev packages) and public/build, so the
hosting needs neither Composer nor Node. Run in the project folder:

    powershell -ExecutionPolicy Bypass -File deploy\cpanel\build-release.ps1

Output: Downloads\elim-release-<commit>.zip. Upload it in cPanel and follow
deploy/cpanel/README.md.
#>
param(
    # Build from HEAD even with uncommitted changes (they are left out).
    [switch] $AllowDirty
)
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
Set-Location $root

function Invoke-Step([string] $label, [scriptblock] $command) {
    Write-Host "==> $label"
    & $command
    if ($LASTEXITCODE -ne 0) { throw "$label gagal (exit $LASTEXITCODE)." }
}

# Only committed code goes out, so the zip always matches one commit.
if (-not $AllowDirty -and (git status --porcelain)) {
    throw 'Ada perubahan yang belum di-commit. Commit atau stash dulu, lalu jalankan lagi.'
}

$commit = (git rev-parse --short HEAD).Trim()
$branch = (git rev-parse --abbrev-ref HEAD).Trim()
$work = Join-Path $env:TEMP "elim-release-$commit"
$zip = Join-Path ([Environment]::GetFolderPath('UserProfile')) "Downloads\elim-release-$commit.zip"

if (Test-Path $work) { Remove-Item $work -Recurse -Force }
New-Item -ItemType Directory $work | Out-Null

Invoke-Step "Kode ($branch @ $commit)" {
    git archive --format=zip -o "$work.src.zip" HEAD
    if ($LASTEXITCODE -eq 0) { tar -xf "$work.src.zip" -C $work }
}
Remove-Item "$work.src.zip" -Force

Invoke-Step 'Dependensi PHP (tanpa paket dev)' {
    Push-Location $work
    try { composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --no-scripts }
    finally { Pop-Location }
}

# Built in the project folder (node_modules lives here); no SSR bundle, so
# pages render in the browser and Inertia skips the SSR server on its own.
Invoke-Step 'Aset front end' {
    php artisan optimize:clear | Out-Null
    npm run build
}
Copy-Item (Join-Path $root 'public\build') (Join-Path $work 'public\build') -Recurse

# Not needed on the server.
foreach ($path in 'tests', '.github', 'node_modules') {
    $full = Join-Path $work $path
    if (Test-Path $full) { Remove-Item $full -Recurse -Force }
}
Set-Content (Join-Path $work 'RELEASE') "$commit $branch $(Get-Date -Format 'yyyy-MM-dd HH:mm')" -Encoding ascii

Invoke-Step 'Zip' {
    if (Test-Path $zip) { Remove-Item $zip -Force }
    tar -a -c -f $zip -C $work .
}
Remove-Item $work -Recurse -Force

$size = '{0:N1} MB' -f ((Get-Item $zip).Length / 1MB)
Write-Host ''
Write-Host "Selesai: $zip ($size)" -ForegroundColor Green
Write-Host 'Unggah ke cPanel lalu ikuti deploy/cpanel/README.md.'
