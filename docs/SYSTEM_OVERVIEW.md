# NORDBOOKING System Overview

## About NORDBOOKING

NORDBOOKING is a comprehensive WordPress theme designed for booking and service management businesses. It operates on a multi-tenant model where "Business Owners" can manage their own services, bookings, and customers with full subscription-based access control.

## Core Features

### 🎯 Service Management
- Create and manage services with detailed options, pricing, and visuals
- **Duration Control**: Minimum 30-minute duration enforcement
- **Service Options**: 9 different option types including toggles, text fields, quantities, areas, and distances
- **Visual Assets**: Main images and SVG icon selector with preset library
- **Service Areas**: Geographic service area management

### 📅 Booking & Scheduling
- Public-facing booking form with real-time availability
- Multi-service booking support
- Customer information collection
- Special instructions and property access details
- Pet information handling
- Service frequency options (one-time, recurring)

### 👥 Customer Management
- Comprehensive customer database
- Booking history tracking
- Customer communication tools
- Multi-tenant data isolation

### 💳 Subscription System
- Stripe-powered subscription management
- Free trial periods with automatic conversion
- Real-time status synchronization
- Invoice management and PDF generation
- Customer billing portal integration

### 🎫 Discount System
- Percentage and fixed-amount discount codes
- Service-level discount control
- Usage tracking and limits
- Real-time validation and application

### 👷 Worker Management
- Role-based access control (Manager, Staff, Viewer)
- Email invitation system
- Permission management
- Team collaboration tools

## Architecture

### Multi-Tenant Design
- **Business Owners**: Primary account holders with full access
- **Workers**: Team members with role-based permissions
- **Customers**: End users who book services
- **Data Isolation**: Complete separation between different businesses

### Technology Stack
- **Backend**: PHP 7.4+, WordPress 5.0+
- **Database**: MySQL with optimized schema
- **Frontend**: Modern JavaScript (ES6+), CSS Grid/Flexbox
- **Payments**: Stripe API integration
- **Caching**: WordPress object cache support

### Key Components

#### Classes (`/classes/`)
- `Services.php` - Service management and configuration
- `Bookings.php` - Booking creation and management
- `Customers.php` - Customer data handling
- `Subscription.php` - Subscription lifecycle management
- `Discounts.php` - Discount code system
- `Auth.php` - Authentication and authorization
- `Database.php` - Database schema and operations

#### Dashboard (`/dashboard/`)
- Responsive admin interface for business owners
- Real-time data updates via AJAX
- Mobile-optimized design
- Role-based navigation

#### Public Interface (`/templates/`)
- Customer-facing booking forms
- Responsive design
- Real-time validation
- Stripe checkout integration

## Business Slug Feature

### URL Structure
Each business owner can have a custom URL slug for their booking form:
```
https://yourdomain.com/business-slug/booking/
```

### Configuration
1. Edit Business Owner user profile in WordPress admin
2. Set unique "Business Slug" in NORDBOOKING settings section
3. Flush permalinks if needed (Settings → Permalinks → Save)

## Performance Features

### Caching Strategy
- Object caching for frequently accessed data
- Query result caching with TTL
- Asset versioning for cache busting

### Database Optimization
- Composite indexes for common query patterns
- Optimized table structure
- Connection pooling support

### Monitoring
- Performance monitoring dashboard
- Slow query detection
- Memory usage tracking
- Health check endpoints

## Security Features

### Authentication
- WordPress user integration
- Role-based access control
- Session management
- Password policies

### Data Protection
- Input sanitization and validation
- SQL injection prevention
- CSRF protection via nonces
- Rate limiting on public endpoints

### Payment Security
- PCI compliance through Stripe
- Webhook signature verification
- Secure API key storage
- Test/Live mode separation

## Development Guidelines

### Asset Management
- Version constant: `NORDBOOKING_VERSION` in `functions.php`
- Increment version when updating CSS/JS files
- Automatic cache busting

### Code Standards
- PSR-4 autoloading for classes
- WordPress coding standards
- Comprehensive error handling
- Extensive logging

### Testing
- Unit tests for core functionality
- Integration tests for Stripe
- Performance benchmarking
- Security scanning

## Deployment Considerations

### Server Requirements
- PHP 7.4+ with required extensions
- MySQL 5.7+ or MariaDB 10.2+
- WordPress 5.0+
- SSL certificate (required for Stripe)
- Adequate memory limits (256MB+ recommended)

### Scaling Recommendations
- Database indexing for large datasets
- CDN for static assets
- Redis/Memcached for object caching
- Load balancing for high traffic

### Backup Strategy
- Regular database backups
- File system backups
- Stripe data synchronization
- Disaster recovery procedures

## Integration Points

### WordPress Integration
- Custom post types and fields
- User role extensions
- Admin menu integration
- Hook system utilization

### Third-Party Services
- **Stripe**: Payment processing and subscription management
- **Email Services**: Notification delivery
- **Analytics**: Performance tracking
- **Monitoring**: System health checks

## Future Roadmap

### Planned Features
- Advanced reporting and analytics
- Mobile app support
- API extensions for third-party integrations
- Multi-language support
- Advanced automation workflows

### Scalability Improvements
- Microservices architecture consideration
- Database sharding for large deployments
- Advanced caching strategies
- Performance optimization

This system provides a robust, scalable foundation for booking-based businesses with comprehensive subscription management and multi-tenant capabilities.