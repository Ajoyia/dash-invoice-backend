#!/bin/bash
set -e

echo "Waiting for MySQL to be ready..."
until mysqladmin ping -h localhost -u root -proot --silent; do
    sleep 1
done

echo "Creating test database if it doesn't exist..."
mysql -h localhost -u root -proot -e "CREATE DATABASE IF NOT EXISTS dash_invoice_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || true

echo "Granting privileges to laravel user..."
mysql -h localhost -u root -proot -e "GRANT ALL PRIVILEGES ON dash_invoice_test.* TO 'laravel'@'%'; FLUSH PRIVILEGES;" || true

echo "Test database setup complete!"
