# Dash Invoice Backend

A Laravel-based REST API for managing companies and invoices with payment integration.

## Requirements

- PHP >= 8.2
- Composer
- MySQL/PostgreSQL
- Redis
## Installation

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
- Strip+
e keys (`STRIPE_*`)
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

### Code Formatting

```bash
./vendor/bin/pint
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

- Company management (CRUD operations)
- Invoice management with products
- CSV import/export functionality
- Stripe payment integration
- VAT ID validation
- Mail template assignments
- Global settings management