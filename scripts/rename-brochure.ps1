# Rename Bronchure.pdf to Brochure.pdf if present
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $root
if (Test-Path "Bronchure.pdf") {
    Rename-Item "Bronchure.pdf" "Brochure.pdf" -Force
    Write-Output "Renamed Bronchure.pdf -> Brochure.pdf"
} else {
    Write-Output "Bronchure.pdf not found. No action taken."
}
