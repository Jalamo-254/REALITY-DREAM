<#
PowerShell Image Optimization Script
- Requires ImageMagick (magick) installed and available on PATH
- Makes optimized copies in Assets/images/optimized

Usage (PowerShell):
  Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
  ./scripts/resize-images.ps1

What it does:
- Processes common image types (.jpg .jpeg .png)
- Strips metadata, resizes large images to max 1920x1920, sets JPEG quality to 78
- Generates WebP versions at quality 75
- Skips files that already exist in the optimized folder with same name
#>

$srcDir = Join-Path $PSScriptRoot "..\Assets\images"
$outDir = Join-Path $PSScriptRoot "..\Assets\images\optimized"

if (!(Test-Path $outDir)) {
    New-Item -ItemType Directory -Path $outDir | Out-Null
}

Get-ChildItem -Path $srcDir -File -Recurse | Where-Object { $_.Extension -match "(?i)\.(jpg|jpeg|png)$" } | ForEach-Object {
    $src = $_.FullName
    $fileName = $_.Name
    $outFileJpg = Join-Path $outDir $fileName
    $outFileWebp = [System.IO.Path]::ChangeExtension($outFileJpg, '.webp')

    # Skip if already optimized
    if ((Test-Path $outFileJpg) -and (Get-Item $outFileJpg).Length -gt 0) {
        Write-Output "Skipping (exists): $fileName"
    } else {
        Write-Output "Optimizing: $fileName"
        # Resize to max 1920x1920, strip metadata, set quality
        & magick "$src" -strip -resize 1920x1920\> -quality 78 "$outFileJpg"
    }

    # Create WebP version (overwrite if exists)
    Write-Output "Creating WebP: $([System.IO.Path]::GetFileName($outFileWebp))"
    & magick "$src" -strip -resize 1920x1920\> -quality 75 "$outFileWebp"
}

Write-Output "Done. Optimized files are in: $outDir"