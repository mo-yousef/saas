MoBooking Theme: Detailed Description

I. General Overview

Theme Name: MoBooking
Stated Purpose: A multi-tenant SaaS (Software as a Service) application designed for cleaning service businesses to manage their bookings online.
Core Idea: To provide individual cleaning businesses (tenants) with their own customizable booking system, customer management, and a dedicated dashboard to operate their services.
Architecture:
Built on WordPress.
Employs a significantly object-oriented approach with PHP classes organized by functionality (Bookings, Services, Auth, Database, etc.).
Utilizes custom database tables for core data (services, bookings, settings, etc.), indicating a departure from relying solely on WordPress posts and post meta for primary business data. This supports the multi-tenant nature and data normalization.
Features a custom front-end dashboard for business owners, separate from the standard WordPress admin backend.
Handles many operations via AJAX for a dynamic user experience.
Includes a custom class autoloader with case-sensitivity considerations.
II. Key Features

Multi-Tenancy:

Designed for multiple businesses (tenants) to use the platform.
Each tenant (user with role mobooking_business_owner) has their own isolated data: services, bookings, customers, areas, discounts, and settings.
User registration creates a new "business owner" account and initializes default settings for them.
Service Management:

Business owners can define and manage their cleaning services.
Each service includes: Name, Description, Price, Duration (in minutes), Category (e.g., Residential, Commercial), Icon (from a predefined list of Dashicons), custom Image, and Status (active/inactive).
Services can have Service Options (add-ons, variations) with types like checkbox, text input, number, select, radio, textarea, quantity. Options can have their own descriptions, be required, and have a price impact (fixed amount, percentage, or multiply by value).
Booking System:

Public Booking Form: A multi-step form for customers to book services from a specific tenant.
Step 1 (Location): ZIP code entry and validation for service area coverage.
Step 2 (Services): Selection of one or more services offered by the tenant.
Step 3 (Options): Configuration of selected service options (if any).
Step 4 (Details): Customer provides contact information (name, email, phone), service address, preferred date/time, and special instructions.
Step 5 (Review): Summary of booking details, total price, and an option to apply a discount code.
Step 6 (Confirmation): Success message with booking reference.
Customization: The appearance and behavior of the public booking form are highly customizable by the tenant (see "Booking Form Settings" under Dashboard).
Email Notifications: Automated booking confirmation emails are sent to the customer and the business owner.
Business Owner Dashboard:

A dedicated front-end dashboard for tenants to manage their business.
Accessible via /dashboard/ after login.
Features sections for Overview, Bookings, Services, Discounts, Service Areas, Booking Form configuration, and general Business Settings.
Area Management (Service Coverage):

Tenants can define geographic areas where they offer services, likely based on ZIP codes or city/regions.
The system can use external APIs (Zippopotam, GeoNames, Postcodes.io) and local data (especially for Nordic countries like Sweden) to fetch area and ZIP code information.
Customers typically enter their ZIP code first on the booking form to check for service availability.
Discount Code Management:

Tenants can create and manage discount codes (percentage or fixed amount).
Discounts can have expiry dates and usage limits.
Customers can apply valid discount codes during the booking review step.
User Authentication & Roles:

Custom user role: mobooking_business_owner with tailored capabilities.
Custom front-end login (page-login.php) and registration process.
AJAX-based authentication.
Database & Data Management:

Uses custom database tables for a normalized data structure.
Includes database creation scripts and migration tools (from potentially older JSON-based data storage).
Provides data export functionality for tenants.
Includes database health check and optimization tools (via AJAX).
PDF Generation:

Utilizes the TCPDF library for generating PDFs, likely for booking summaries or invoices.
Payments (Implied/Recommended):

The front-page.php mentions "WooCommerce + Stripe".
includes/admin-functions.php recommends installing WooCommerce.
classes/Payments/Manager.php and AdminManager.php exist.
This strongly suggests that payment processing (either for tenant subscriptions to the SaaS or for customer bookings) is intended to be handled via WooCommerce and likely Stripe.
III. Public-Facing Pages & Templates

front-page.php (Marketing Landing Page): Serves as the main promotional page for the MoBooking SaaS platform.
page-login.php (Tenant Login Page): Custom-styled login page for business owners.
templates/booking-form-public.php (Customer Booking Form): Dynamically loaded multi-step booking interface.
templates/booking-form-embed.php (Embeddable Booking Form): iframe version of the public form.
404.php: Standard error page.
index.php & page.php: Basic placeholders.
header.php & footer.php: Basic HTML structure.
IV. Tenant Dashboard Functionality (/dashboard/)

Layout: Uses dashboard/sidebar.php (branding, navigation, subscription status) and dashboard/header.php (mobile toggle, breadcrumbs, search, notifications, user menu).
Sections:
Overview (page-overview.php): KPIs, recent bookings, quick actions, setup progress, tips.
Bookings (page-bookings.php, page-single-booking.php): List/filter/search bookings, view details, update status, export, bulk actions.
Services (page-services.php): CRUD for services and their options (details, appearance, options tabs).
Discounts (page-discounts.php): CRUD for discount codes, stats, filters, quick generators.
Service Areas (page-areas.php): Manage geographic service coverage, country selection, city/ZIP search using APIs/local data.
Booking Form Settings (page-booking-form.php): Customize public form (general, design, advanced, share/embed).
Business Settings (page-settings.php): Configure business info, branding, email templates, notifications, T&Cs, business hours, advanced booking rules, data management.
V. Assets & Libraries

CSS: Mix of enqueued files (assets/css/) and extensive inline/embedded styles in page templates.
JavaScript: Core assets/js/booking-form.js, assets/js/dashboard.js, and page-specific embedded JS. jQuery is prevalent.
PHP Libraries: TCPDF (local).
External Libraries: Font Awesome (CDN), WordPress-shipped jQuery, jQuery UI, wp-color-picker.
VI. Technical Notes

Database: Custom tables with user_id for multi-tenancy. Normalized structure.
AJAX: Heavily used for dynamic dashboard and booking form interactions.
Security: Basic security headers, nonce checks, sanitization/escaping.
