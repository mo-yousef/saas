# NORDBOOKING Directory Structure

## Overview
This document outlines the new organized directory structure for the NORDBOOKING project.

## Root Directory Structure

```
/
├── config/                 # Configuration files
│   ├── app.php            # Main application configuration
│   ├── database.php       # Database configuration
│   └── stripe.php         # Payment processing configuration
├── src/                   # Organized source code
│   ├── Classes/           # PHP classes by functionality
│   ├── Functions/         # Utility functions (from functions.php)
│   └── Utilities/         # Helper utilities and tools
├── assets/                # Static assets (already organized)
│   ├── css/              # Stylesheets
│   ├── js/               # JavaScript files
│   ├── images/           # Images and graphics
│   ├── svg-icons/        # SVG icons
│   └── legal/            # Legal documents
├── templates/             # WordPress template files
├── dashboard/             # Dashboard-specific files
├── includes/              # WordPress includes
├── functions/             # Legacy functions (to be migrated)
├── classes/               # Legacy classes (to be migrated)
├── lib/                   # Third-party libraries
├── docs/                  # Documentation
├── tests/                 # Test files (if needed)
└── [WordPress theme files] # Standard WP theme files
```

## Migration Plan

### Phase 1: Create Structure (Current Task)
- ✅ Create config/ directory
- ✅ Create src/ directory with subdirectories
- ✅ Document structure

### Phase 2: Move Classes (Next Task)
- Move classes/ → src/Classes/
- Update autoloader paths
- Test functionality

### Phase 3: Extract Functions (Following Task)
- Break down functions.php
- Move functions to src/Functions/
- Update includes

### Phase 4: Configuration (Later Task)
- Create configuration files
- Extract API keys and settings
- Update references

## Benefits

1. **Clear Separation**: Each directory has a specific purpose
2. **Scalability**: Easy to add new functionality
3. **Maintainability**: Easier to find and modify code
4. **Security**: Sensitive config separated from code
5. **Standards**: Follows modern PHP project conventions

## File Naming Conventions

- **Classes**: PascalCase (e.g., `BookingManager.php`)
- **Functions**: snake_case (e.g., `format_booking_date.php`)
- **Config**: lowercase (e.g., `app.php`, `database.php`)
- **Utilities**: descriptive names (e.g., `email-helper.php`)

## Autoloading Strategy

The new structure will support PSR-4 autoloading:

```php
// Autoloader configuration
$autoloader = [
    'NORDBOOKING\\Classes\\' => 'src/Classes/',
    'NORDBOOKING\\Functions\\' => 'src/Functions/',
    'NORDBOOKING\\Utilities\\' => 'src/Utilities/',
];
```

## Configuration Access

Centralized configuration will be accessible via:

```php
// Example usage
$stripe_key = config('stripe.secret_key');
$db_settings = config('database.optimization');
```

This structure provides a solid foundation for the remaining optimization tasks.