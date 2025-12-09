# Code Quality Standards

This document outlines the code quality standards and best practices implemented in this project.

## Type Safety

- All methods have explicit return types
- All parameters have type hints
- PHPStan level 8 static analysis enabled
- Null safety checks implemented throughout

## Exception Handling

### Custom Exceptions

The project uses custom exceptions for better error handling:

- `CompanyNotFoundException` - When a company resource is not found
- `InvoiceNotFoundException` - When an invoice resource is not found
- `ValidationException` - For validation errors with detailed error messages
- `UnauthorizedException` - For authorization failures

### Exception Handling Pattern

Controllers catch specific exceptions and return consistent JSON responses:

```php
try {
    // Business logic
} catch (ValidationException $e) {
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'errors' => $e->errors(),
    ], 422);
} catch (CompanyNotFoundException $e) {
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
    ], 404);
}
```

## Constants

Magic strings are replaced with constants in `App\Constants`:

- `INVOICE_TYPES` - Invoice type values
- `INVOICE_STATUSES` - Invoice status values
- `COMPANY_STATUSES` - Company status values
- `DEFAULT_PAGINATION` - Default pagination size

## Error Response Format

All API responses follow a consistent format:

### Success Response
```json
{
    "success": true,
    "message": "Operation successful",
    "data": { ... }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": { ... } // Optional, for validation errors
}
```

## Code Organization

### Repository Pattern

- Interfaces for all repositories
- Separation of read and write operations
- Query repositories for complex queries

### Service Layer

- Business logic in services
- Services depend on repository interfaces
- Dependency injection throughout

### Request Validation

- FormRequest classes for validation
- Type-safe validation rules
- Custom error messages

## Testing

- PHPUnit for unit and feature tests
- Test coverage for critical business logic
- Factory classes for test data generation

## Static Analysis

PHPStan is configured at level 8 to catch:
- Type errors
- Null pointer exceptions
- Unused code
- Potential bugs

Run analysis with:
```bash
composer analyse
```

## Code Formatting

Laravel Pint ensures consistent code style:
- PSR-12 coding standard
- Automatic formatting
- No manual style decisions needed

Run formatting with:
```bash
composer format
```

