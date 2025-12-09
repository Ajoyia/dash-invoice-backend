# Dash Invoice Backend

A Laravel-based REST API for managing companies and invoices with payment integration.

## Requirements

- PHP >= 8.2
- Composer
- MySQL/PostgreSQL
- Redis

## Quick Start with Docker (Recommended)

The easiest way to run this project locally is using Docker:

1. **Prerequisites**: Install [Docker Desktop](https://www.docker.com/products/docker-desktop)

2. **Start the application**:
   ```bash
   docker-compose up -d --build
   ```

3. **Run initial setup**:
   ```bash
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   docker-compose exec app php artisan db:seed
   ```

4. **Access the application**: http://localhost:8000

📖 **For detailed Docker setup instructions, see [DOCKER_SETUP.md](DOCKER_SETUP.md)**

## Manual Installation (Without Docker)

### 1. Install Dependencies

```bash
composer install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Environment

Edit `.env` file and update:

- Database credentials (`DB_*`)
- Redis credentials (`REDIS_*`)
- JWT key (`JWT_KEY`)
- Other service configurations

### 4. Database Setup

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed
```

### 5. Start Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## Project Structure

```text
├── rest-service/          # Laravel API backend
│   ├── app/              # Application code
│   ├── database/         # Migrations and seeders
│   ├── routes/           # API routes
│   └── config/           # Configuration files
```

## Common Commands

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear
```

### Generate Swagger Documentation

```bash
php artisan l5-swagger:generate
```

### Run Tests

```bash
php artisan test
```

### Code Quality

#### Code Formatting

```bash
composer format
# or
./vendor/bin/pint
```

#### Static Analysis (PHPStan)

```bash
composer analyse
# or
./vendor/bin/phpstan analyse
```

#### Run Tests

```bash
composer test
# or
php artisan test
```

## API Documentation

To generate the Swagger API documentation, run the following command:

```bash
php artisan l5-swagger:generate
```

After generating Swagger docs, access the API documentation at:

```text
http://localhost:8000/api/documentation
```

## Main Features

- Company management
- Invoice management with products
- CSV import/export functionality
- VAT ID validation
- Mail template assignments
- Global settings management

## Code Quality Standards

This project follows following standards for clean, scalable code:

- **Type Safety**: Full type declarations and return types throughout
- **Static Analysis**: PHPStan level 8 for comprehensive type checking
- **Exception Handling**: Custom exceptions for better error handling
- **Code Formatting**: Laravel Pint for consistent code style
- **SOLID Principles**: Repository pattern, dependency injection, interface segregation
- **PSR Standards**: PSR-4 autoloading, PSR-12 coding style
- **Error Handling**: Consistent error response format across all endpoints
- **Null Safety**: Proper null checks and type guards throughout