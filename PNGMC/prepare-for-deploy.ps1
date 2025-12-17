# PowerShell Script to Prepare PNGMC Files for Deployment
# Run this from: C:\xampp\htdocs\sms2\
# Creates a clean copy ready for Ubuntu server

$source = "PNGMC"
$destination = "PNGMC-clean"
$zipFile = "PNGMC.zip"

Write-Host "=========================================="
Write-Host "Preparing PNGMC PHP files for deployment"
Write-Host "=========================================="
Write-Host ""

# Check if source exists
if (-not (Test-Path $source)) {
    Write-Host "Error: $source folder not found!" -ForegroundColor Red
    Write-Host "Please run this script from: C:\xampp\htdocs\sms2\" -ForegroundColor Yellow
    exit 1
}

# Remove old clean copy if exists
if (Test-Path $destination) {
    Write-Host "Removing old clean copy..." -ForegroundColor Yellow
    Remove-Item -Path $destination -Recurse -Force
}

# Remove old ZIP if exists
if (Test-Path $zipFile) {
    Write-Host "Removing old ZIP file..." -ForegroundColor Yellow
    Remove-Item -Path $zipFile -Force
}

Write-Host "Copying files..." -ForegroundColor Green
Copy-Item -Path $source -Destination $destination -Recurse

Write-Host "Cleaning up unnecessary files..." -ForegroundColor Yellow

# Remove log files (will be recreated on server)
$logPath = Join-Path $destination "logs"
if (Test-Path $logPath) {
    Get-ChildItem -Path $logPath -Filter "*.log" | Remove-Item -Force -ErrorAction SilentlyContinue
    Write-Host "  Removed log files" -ForegroundColor Gray
}

# Remove .env if exists (will create new on server)
$envFile = Join-Path $destination ".env"
if (Test-Path $envFile) {
    Write-Host "  Removing: .env (will create new on server)" -ForegroundColor Gray
    Remove-Item -Path $envFile -Force -ErrorAction SilentlyContinue
}

# Remove .git if exists
$gitPath = Join-Path $destination ".git"
if (Test-Path $gitPath) {
    Write-Host "  Removing: .git" -ForegroundColor Gray
    Remove-Item -Path $gitPath -Recurse -Force -ErrorAction SilentlyContinue
}

# Create logs directory structure (empty)
Write-Host "Creating directory structure..." -ForegroundColor Green
$logDir = Join-Path $destination "logs"
if (-not (Test-Path $logDir)) {
    New-Item -Path $logDir -ItemType Directory -Force | Out-Null
}

# Create .gitkeep in logs
$gitkeep = Join-Path $logDir ".gitkeep"
if (-not (Test-Path $gitkeep)) {
    New-Item -Path $gitkeep -ItemType File -Force | Out-Null
}

Write-Host "Creating ZIP archive..." -ForegroundColor Green
Compress-Archive -Path $destination -DestinationPath $zipFile -Force

# Get file size
$zipSize = (Get-Item $zipFile).Length / 1MB
Write-Host ""
Write-Host "=========================================="
Write-Host "Preparation Complete!" -ForegroundColor Green
Write-Host "=========================================="
Write-Host ""
Write-Host "Created: $zipFile" -ForegroundColor Green
Write-Host "Size: $([math]::Round($zipSize, 2)) MB" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Copy $zipFile to your Ubuntu server"
Write-Host "2. Extract to /var/www/pngmc/"
Write-Host "3. Run setup commands (see PNGMC_DEPLOYMENT_UBUNTU.md)"
Write-Host ""
Write-Host "Files ready for deployment!" -ForegroundColor Green
Write-Host ""

