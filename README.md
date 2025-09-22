# Nord Booking Theme

This is a WordPress theme designed to provide booking and service management functionalities for businesses. It operates on a multi-tenant model where "Business Owners" can manage their own services, bookings, and customers.

## Documentation Structure

All documentation has been organized into the `/docs/` directory:

- `docs/README.md` - Documentation overview and quick start
- `docs/SYSTEM_OVERVIEW.md` - Complete system overview and architecture
- `docs/INSTALLATION_GUIDE.md` - Installation and setup instructions
- `docs/ADMIN_GUIDE.md` - Consolidated admin interface guide
- `docs/SUBSCRIPTION_SYSTEM.md` - Subscription management documentation
- `docs/STRIPE_INTEGRATION.md` - Stripe setup and configuration guide
- `docs/DISCOUNT_SYSTEM.md` - Discount system implementation and usage
- `docs/INVOICE_SYSTEM.md` - Invoice management system
- `docs/WORKER_MANAGEMENT.md` - Worker invitation and management system
- `docs/TROUBLESHOOTING.md` - Common issues and solutions
- `docs/DEVELOPMENT_NOTES.md` - Development guidelines and technical notes

Debug and test files have been moved to `/debug/` directory for better organization.

**Quick Start**: Read `docs/SYSTEM_OVERVIEW.md` first, then follow `docs/INSTALLATION_GUIDE.md` for setup.

## Core Features

- **Service Management:** Create and manage services with detailed options, pricing, and visuals.
- **Booking and Scheduling:** A public-facing booking form that allows customers to book services based on the business's availability.
- **Customer Management:** A dashboard area to view and manage customer information.
- **Multi-tenancy:** Designed for multiple "Business Owner" user roles, each with their own set of data.

---

## Service Management Details

Services are the core of the booking system. They can be configured extensively from the "Services" section of the dashboard.

### Creating & Updating Services

- **Basic Information:** Each service has a name, description, and base price.
- **Duration:** The duration of the service can be set in minutes, with a **minimum duration of 30 minutes** enforced by the system.
- **Service Options:** Add custom options to services to allow for customer choices. The available option types include:
  - `Option Toggle / Yes or No`
  - `Short Answer`
  - `Number Field`
  - `Select from List`
  - `Single Choice`
  - `Long Answer / Additional Notes`
  - `Item Quantity / Number of Items`
  - `Area (m²)`
  - `Distance (km)`
- **Service Visuals:** Each service can have a main image and an icon.

### Service Icons

The theme includes an icon selector that allows business owners to assign a visual icon to each service.

- **Preset Icons:** The selector uses a predefined set of SVG icons.
- **Adding New Presets:** To add a new icon to the list of presets, simply add a new `.svg` file to the `assets/svg-icons/presets/` directory. The system will automatically detect it and display it in the icon selector dialog.

---

## Feature: Business Slug for Public Booking URLs

This feature allows for user-friendly URLs for the public booking form, structured as `https://yourdomain.com/business-slug/booking/`.

### 1. Setting the Business Slug for a Business Owner

Each user with the "Business Owner" role can have a unique "Business Slug" associated with their account.

- **How to Set/Edit:**

  1.  Navigate to **Users** in the WordPress Admin Dashboard.
  2.  Edit the profile of a "Business Owner" user.
  3.  Scroll down to the **"Nord Booking Settings"** section and find the **"Business Slug"** field.
  4.  Enter a unique, URL-friendly slug (e.g., `acme-cleaning`).
  5.  Click **"Update Profile"** to save.

- **Important Notes:**
  - **Uniqueness:** Slugs must be unique.
  - **URL Friendliness:** The system automatically sanitizes the input.
  - **Changing Slugs:** Changing a slug will break the old URL.

### 2. The New URL Structure

Once a slug is set, the public booking form is accessible via: `https://yourdomain.com/<business-slug>/booking/`

### 3. Flushing Rewrite Rules (Permalinks)

If the new URLs result in a "Page Not Found" (404) error, you must flush WordPress's rewrite rules.

- **How to Flush:**
  1.  Navigate to **Settings > Permalinks**.
  2.  Click the **"Save Changes"** button (no changes are needed).

---

## Development Notes

### Asset Versioning & Cache Busting

The theme uses a `NORDBOOKING_VERSION` constant defined in `functions.php` to version its CSS and JavaScript assets. If you make changes to any `.js` or `.css` files, you should **increment this version number** to ensure users' browsers download the new files instead of using a cached version.

```php
// functions.php
define( 'NORDBOOKING_VERSION', '0.1.5' );
```

### Dialog Component

The theme includes a universal dialog component located in `assets/js/dialog.js`. To maintain a consistent UI, use this component for any new modals or dialogs.

- **Usage Example:**
  ```javascript
  const myDialog = new MoBookingDialog({
    title: "Confirm Action",
    content: "<p>Are you sure you want to proceed?</p>",
    buttons: [
      {
        label: "Cancel",
        class: "secondary",
        onClick: (dialog) => dialog.close(),
      },
      {
        label: "Confirm",
        class: "primary",
        onClick: (dialog) => {
          console.log("Confirmed!");
          dialog.close();
        },
      },
    ],
  });
  myDialog.show();
  ```
