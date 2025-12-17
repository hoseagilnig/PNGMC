# PowerShell Script to Prepare Laravel Files for Copying
# Run this from: C:\xampp\htdocs\sms2\
# Creates a clean copy without vendor, node_modules, etc.

$source = "laravel-sms"
$destination = "laravel-sms-clean"
$zipFile = "laravel-sms.zip"

Write-Host "=========================================="
Write-Host "Preparing Laravel files for deployment"
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

Write-Host "Removing unnecessary files..." -ForegroundColor Yellow

# Remove folders to exclude
$foldersToRemove = @(
    "vendor",
    "node_modules",
    ".git",
    "storage\logs",
    "storage\framework\cache"
)

foreach ($folder in $foldersToRemove) {
    $fullPath = Join-Path $destination $folder
    if (Test-Path $fullPath) {
        Write-Host "  Removing: $folder" -ForegroundColor Gray
        Remove-Item -Path $fullPath -Recurse -Force -ErrorAction SilentlyContinue
    }
}

# Remove .env file (will create new on server)
$envFile = Join-Path $destination ".env"
if (Test-Path $envFile) {
    Write-Host "  Removing: .env" -ForegroundColor Gray
    Remove-Item -Path $envFile -Force -ErrorAction SilentlyContinue
}

# Remove log files
$logPath = Join-Path $destination "storage\logs"
if (Test-Path $logPath) {
    Get-ChildItem -Path $logPath -Filter "*.log" | Remove-Item -Force -ErrorAction SilentlyContinue
}

# Create storage directories structure (empty)
Write-Host "Creating storage structure..." -ForegroundColor Green
$storageDirs = @(
    "storage\app\public",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs"
)

foreach ($dir in $storageDirs) {
    $fullPath = Join-Path $destination $dir
    if (-not (Test-Path $fullPath)) {
        New-Item -Path $fullPath -ItemType Directory -Force | Out-Null
    }
}

# Create .gitkeep files in empty directories
$gitkeepFiles = @(
    "storage\logs\.gitkeep",
    "storage\framework\cache\data\.gitkeep",
    "storage\framework\sessions\.gitkeep",
    "storage\framework\views\.gitkeep"
)

foreach ($file in $gitkeepFiles) {
    $fullPath = Join-Path $destination $file
    if (-not (Test-Path $fullPath)) {
        New-Item -Path $fullPath -ItemType File -Force | Out-Null
    }
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
Write-Host "2. Extract to /var/www/laravel-sms/"
Write-Host "3. Run setup commands on server"
Write-Host ""
Write-Host "Files excluded:" -ForegroundColor Gray
Write-Host "  - vendor/ (install via: composer install)"
Write-Host "  - node_modules/ (install via: npm install)"
Write-Host "  - .env (create new on server)"
Write-Host "  - .git/ (not needed)"
Write-Host "  - Log files"
Write-Host ""

