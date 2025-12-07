#!/bin/bash
set -e

echo "Setting up test database..."

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
until docker-compose exec -T db mysqladmin ping -h localhost -u root -proot --silent; do
    sleep 1
done

# Create test database
echo "Creating test database 'dash_invoice_test'..."
docker-compose exec -T db mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS dash_invoice_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || true

# Grant privileges
echo "Granting privileges to laravel user..."
docker-compose exec -T db mysql -u root -proot -e "GRANT ALL PRIVILEGES ON dash_invoice_test.* TO 'laravel'@'%'; FLUSH PRIVILEGES;" || true

echo "Test database setup complete!"
echo "You can now run tests with: php artisan test"

