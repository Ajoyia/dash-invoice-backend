# Test Database Setup Guide

This project uses a separate test database `dash_invoice_test` to ensure tests don't affect the main `dash_invoice` database.

## Automatic Setup (Recommended)

The test database is automatically created when you start the Docker containers for the first time. The initialization script runs automatically.

## Manual Setup

If you need to manually create the test database, you can use one of these methods:

### Option 1: Using PowerShell (Windows)

```powershell
.\docker\setup-test-db.ps1
```

### Option 2: Using Bash Script (Linux/Mac)

```bash
chmod +x docker/setup-test-db.sh
./docker/setup-test-db.sh
```

### Option 3: Manual Docker Command

```bash
# Create the database
docker-compose exec db mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS dash_invoice_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Grant privileges
docker-compose exec db mysql -u root -proot -e "GRANT ALL PRIVILEGES ON dash_invoice_test.* TO 'laravel'@'%'; FLUSH PRIVILEGES;"
```

## Configuration

### PHPUnit Configuration

The `phpunit.xml` file is already configured to use the test database:
- Database: `dash_invoice_test`
- Host: `db` (Docker service name)
- Username: `laravel`
- Password: `root`

### Environment File

Create a `.env.testing` file in the `rest-service` directory with the following content:

```env
APP_ENV=testing
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=dash_invoice_test
DB_USERNAME=laravel
DB_PASSWORD=root
```

## Running Tests

Once the test database is set up, you can run tests:

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit

# Run specific test file
php artisan test tests/Unit/CompanyRepositoryTest.php
```

## Database Refresh

Tests use the `RefreshDatabase` trait, which automatically:
- Migrates the database before each test
- Rolls back after each test
- Ensures test isolation

## Troubleshooting

### Database doesn't exist error

If you get a "database doesn't exist" error:
1. Ensure Docker containers are running: `docker-compose ps`
2. Run the setup script: `.\docker\setup-test-db.ps1` (Windows) or `./docker/setup-test-db.sh` (Linux/Mac)
3. Verify the database exists: `docker-compose exec db mysql -u root -proot -e "SHOW DATABASES;"`

### Permission denied error

If you get permission errors:
1. Check that the `laravel` user has privileges: `docker-compose exec db mysql -u root -proot -e "SHOW GRANTS FOR 'laravel'@'%';"`
2. Re-run the grant command from the manual setup section

### Connection refused

If you get connection refused:
1. Ensure the database container is healthy: `docker-compose ps`
2. Check the database logs: `docker-compose logs db`
3. Restart the containers: `docker-compose restart db`

