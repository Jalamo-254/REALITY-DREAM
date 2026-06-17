# PowerShell helper to install deps and optimize images on Windows
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Push-Location $scriptDir\..\
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    Write-Error "npm not found. Install Node.js and npm first: https://nodejs.org/"
    Exit 1
}
Write-Host "Installing npm dependencies..."
npm install
if ($LASTEXITCODE -ne 0) { Write-Error "npm install failed"; Exit 1 }
Write-Host "Running image optimization..."
npm run optimize-images
if ($LASTEXITCODE -ne 0) { Write-Error "Image optimization failed"; Exit 1 }
Write-Host "Done. Optimized images are in Assets/images/optimized"
Pop-Location
