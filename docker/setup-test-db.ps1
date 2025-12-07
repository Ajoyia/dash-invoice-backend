# PowerShell script to setup test database
Write-Host "Setting up test database..." -ForegroundColor Green

# Wait for MySQL to be ready
Write-Host "Waiting for MySQL to be ready..." -ForegroundColor Yellow
$maxAttempts = 30
$attempt = 0
do {
    $attempt++
    $result = docker-compose exec -T db mysqladmin ping -h localhost -u root -proot 2>&1
    if ($LASTEXITCODE -eq 0) {
        break
    }
    Start-Sleep -Seconds 1
} while ($attempt -lt $maxAttempts)

if ($attempt -eq $maxAttempts) {
    Write-Host "MySQL is not ready. Please ensure Docker containers are running." -ForegroundColor Red
    exit 1
}

# Create test database
Write-Host "Creating test database 'dash_invoice_test'..." -ForegroundColor Yellow
docker-compose exec -T db mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS dash_invoice_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Grant privileges
Write-Host "Granting privileges to laravel user..." -ForegroundColor Yellow
docker-compose exec -T db mysql -u root -proot -e "GRANT ALL PRIVILEGES ON dash_invoice_test.* TO 'laravel'@'%'; FLUSH PRIVILEGES;"

Write-Host "Test database setup complete!" -ForegroundColor Green
Write-Host "You can now run tests with: php artisan test" -ForegroundColor Cyan

