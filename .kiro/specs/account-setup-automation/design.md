# Design Document

## Overview

This feature enhances the user onboarding experience by automatically setting up demo data and default configurations when new business owner accounts are created. The implementation will extend the existing `setup_new_business_owner` method in the `Auth` class to include automated creation of demo services, availability slots, discount codes, and default settings.

## Architecture

The implementation follows the existing architecture patterns in the NORDBOOKING system:

- **Auth Class**: Handles user registration and initial setup
- **Services Class**: Manages service creation and management
- **Discounts Class**: Handles discount code creation
- **Availability Class**: Manages availability slot configuration
- **Settings Class**: Handles system settings and defaults

The automation will be integrated into the existing registration flow without disrupting current functionality.

## Components and Interfaces

### 1. Enhanced Auth Class Setup Method

The `setup_new_business_owner` method will be extended to call new initialization methods:

```php
private function setup_new_business_owner(\WP_User $user, string $company_name, string $plan = ''): array {
    // ... existing code ...
    
    // New automated setup calls
    $this->create_demo_services($user->ID);
    $this->setup_default_availability($user->ID);
    $this->create_demo_discounts($user->ID, $company_name);
    $this->configure_default_settings($user->ID);
    
    // ... rest of existing code ...
}
```

### 2. Demo Services Creation

**Method**: `create_demo_services(int $user_id)`

Creates three predefined demo services with realistic data:

1. **Basic House Cleaning** - Standard residential cleaning service
2. **Deep Cleaning Service** - Comprehensive cleaning with detailed options
3. **Office Cleaning** - Commercial cleaning service

Each service will include:
- Realistic pricing ($50-150 range)
- Appropriate duration (1-4 hours)
- Descriptive content
- Service options where applicable

### 3. Default Availability Setup

**Method**: `setup_default_availability(int $user_id)`

Configures standard business availability:
- Monday through Friday: Active
- Time slots: 9:00 AM to 5:00 PM
- 30-minute slot duration (using existing default)
- Weekend slots: Inactive by default

### 4. Demo Discount Codes

**Method**: `create_demo_discounts(int $user_id, string $company_name)`

Creates two demo discount codes under "Demo Compound":

1. **Percentage Discount**: 
   - Code: "DEMO10"
   - 10% off
   - Valid for 30 days
   - Usage limit: 50

2. **Fixed Amount Discount**:
   - Code: "WELCOME5"
   - $5 off
   - Valid for 30 days
   - Usage limit: 100

### 5. Default Settings Configuration

**Method**: `configure_default_settings(int $user_id)`

Modifies the existing default settings to:
- Set `bf_enable_location_check` to '0' (disabled)
- Maintain all other existing defaults

### 6. Service Areas Message Display

Enhances the service areas page to show informational message when location check is disabled:

- Check `bf_enable_location_check` setting
- Display contextual message with link to booking form settings
- Hide message when location check is enabled

## Data Models

### Demo Services Data Structure

```php
$demo_services = [
    [
        'name' => 'Basic House Cleaning',
        'description' => 'Standard residential cleaning service including dusting, vacuuming, and bathroom cleaning.',
        'duration' => 120, // 2 hours
        'price' => 75.00,
        'is_active' => 1,
        'service_options' => [
            [
                'name' => 'Inside Oven Cleaning',
                'price' => 15.00,
                'type' => 'addon'
            ],
            [
                'name' => 'Inside Refrigerator',
                'price' => 10.00,
                'type' => 'addon'
            ]
        ]
    ],
    // ... additional services
];
```

### Demo Discounts Data Structure

```php
$demo_discounts = [
    [
        'code' => 'DEMO10',
        'type' => 'percentage',
        'value' => 10.00,
        'company_name' => 'Demo Compound',
        'usage_limit' => 50,
        'expiry_date' => date('Y-m-d', strtotime('+30 days')),
        'is_active' => 1
    ],
    // ... additional discounts
];
```

### Default Availability Structure

```php
$default_availability = [
    'monday' => ['09:00', '17:00'],
    'tuesday' => ['09:00', '17:00'],
    'wednesday' => ['09:00', '17:00'],
    'thursday' => ['09:00', '17:00'],
    'friday' => ['09:00', '17:00'],
    'saturday' => [], // Inactive
    'sunday' => []    // Inactive
];
```

## Error Handling

### Graceful Degradation Strategy

The automated setup will use a fail-safe approach:

1. **Individual Component Isolation**: Each setup component (services, discounts, availability) will be wrapped in try-catch blocks
2. **Logging**: All errors will be logged but won't prevent account creation
3. **Partial Success**: If some components fail, the account creation will still succeed
4. **User Notification**: Success message will indicate what was set up automatically

### Error Recovery

```php
try {
    $this->create_demo_services($user_id);
    $setup_results['services'] = true;
} catch (Exception $e) {
    error_log("NORDBOOKING: Demo services creation failed: " . $e->getMessage());
    $setup_results['services'] = false;
}
```

### Database Transaction Considerations

- Each component will handle its own database operations
- No cross-component transactions to avoid rollback complexity
- Individual component failures won't affect other components

## Testing Strategy

### Unit Testing Approach

1. **Mock Database Operations**: Use WordPress testing framework with database mocking
2. **Component Isolation**: Test each setup method independently
3. **Integration Testing**: Test the complete registration flow with automation
4. **Edge Case Testing**: Test with various company names, special characters, and edge conditions

### Test Scenarios

#### Demo Services Creation Tests
- Verify all three services are created with correct data
- Test service options are properly associated
- Validate pricing and duration values
- Test with existing services (should not conflict)

#### Availability Setup Tests
- Confirm Monday-Friday slots are active
- Verify weekend slots are inactive
- Test time slot generation (9 AM - 5 PM)
- Validate slot duration matches system default

#### Discount Codes Tests
- Verify both discount types are created
- Test company name association ("Demo Compound")
- Validate expiry dates and usage limits
- Test code uniqueness across tenants

#### Settings Configuration Tests
- Confirm location check is disabled by default
- Verify other settings remain unchanged
- Test settings persistence

#### Service Areas Message Tests
- Verify message displays when location check is disabled
- Confirm message is hidden when location check is enabled
- Test message content and navigation links

### Performance Testing

- Measure registration time impact (should be minimal)
- Test with concurrent registrations
- Validate database query efficiency

## Implementation Phases

### Phase 1: Core Automation Setup
1. Extend `setup_new_business_owner` method
2. Implement demo services creation
3. Add default availability configuration
4. Update default settings for location check

### Phase 2: Demo Discounts and UI
1. Implement demo discount creation
2. Add service areas message functionality
3. Update UI components for message display

### Phase 3: Testing and Refinement
1. Comprehensive testing of all components
2. Performance optimization
3. Error handling refinement
4. Documentation updates

## Security Considerations

### Data Validation
- All demo data will be properly sanitized before database insertion
- Company names will be validated and escaped
- Pricing values will be validated as proper numeric types

### Permission Checks
- Setup methods will verify user permissions
- Database operations will use prepared statements
- User ID validation will be enforced

### Rate Limiting
- No additional rate limiting needed as this runs once per registration
- Existing registration rate limiting will apply

## Compatibility

### WordPress Compatibility
- Uses existing WordPress user creation hooks
- Compatible with WordPress multisite if applicable
- Follows WordPress coding standards

### Plugin Compatibility
- No conflicts with existing NORDBOOKING functionality
- Maintains backward compatibility
- Uses existing database schema and methods

### Database Compatibility
- Uses existing table structures
- No schema changes required
- Compatible with existing data migration tools