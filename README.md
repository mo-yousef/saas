# Nord Booking Theme

This theme provides booking functionalities for businesses.

## Feature: Business Slug for Public Booking URLs

This feature allows for user-friendly URLs for the public booking form, structured as `https://yourdomain.com/business-slug/booking/`.

### 1. Setting the Business Slug for a Business Owner

Each user with the "Business Owner" role (a custom role provided by this theme) can have a unique "Business Slug" associated with their account. This slug is used to generate their specific public booking form URL.

- **How to Set/Edit the Business Slug:**

  1.  Log in to your WordPress Admin Dashboard.
  2.  Navigate to **Users**.
  3.  Find the user account that has the "Business Owner" role and click on their username to edit their profile.
  4.  Scroll down to the **"Nord Booking Settings"** section.
  5.  You will see a field labeled **"Business Slug"**.
  6.  Enter a unique, URL-friendly slug for this business.
      - **Examples:** `acme-cleaning`, `johns-bakery`, `alpha-consulting`
      - **Allowed characters:** Lowercase letters (a-z), numbers (0-9), and hyphens (-). Spaces and other special characters will be automatically converted or removed.
  7.  Click **"Update Profile"** (or "Update User") at the bottom of the page to save the changes.

- **Important Notes on Slugs:**
  - **Uniqueness:** Each business slug must be unique across all users. If you try to save a slug that is already in use by another business owner, the system will prevent it and show an error message on the profile page.
  - **URL Friendliness:** The system will automatically sanitize the input to ensure it's suitable for URLs (e.g., "My Awesome Business" will become "my-awesome-business").
  - **Changing Slugs:** If you change a slug, the old URL using the previous slug will no longer work. Make sure to update any links you have shared.
  - **Empty Slug:** If you leave the slug field empty and save, any existing slug for that user will be removed, and they will not have a slug-based booking URL.

### 2. The New Public Booking Form URL Structure

Once a business slug is set for a Business Owner user, their public booking form will be accessible via:

`https://yourdomain.com/<business-slug>/booking/`

Replace `<business-slug>` with the actual slug you set in the user's profile.

- **Example:** If a business owner has the slug `premiere-event-planners`, their public booking form URL will be:
  `https://yourdomain.com/premiere-event-planners/booking/`

### 3. Flushing Rewrite Rules (Permalinks) - IMPORTANT for Setup/Troubleshooting

WordPress uses rewrite rules to understand custom URL structures like the one used for the business slug booking pages. If you find that the new URLs (e.g., `https://yourdomain.com/your-slug/booking/`) are not working and are leading to a "Page Not Found" (404) error, you likely need to flush WordPress's rewrite rules.

- **How to Flush Rewrite Rules:**

  1.  Log in to your WordPress Admin Dashboard.
  2.  Navigate to **Settings > Permalinks**.
  3.  You **do not** need to make any changes on this page.
  4.  Simply click the **"Save Changes"** button.

  This action will regenerate the rewrite rules, and WordPress should then recognize the new URL structure. This is typically needed once after the theme is activated or updated with new rewrite rules.

### 4. Fallback `?tid=` Method

If you have a generic page (e.g., a page at `/booking/`) that uses the "Public Booking Form" template, it can still be accessed using the `?tid=` parameter:

`https://yourdomain.com/booking/?tid=<tenant_id>`

Replace `<tenant_id>` with the numerical User ID of the Business Owner. This method remains functional for direct access if needed, but the slug-based URL is generally preferred for public sharing.

---

_This guidance was added based on the implementation of the business slug URL feature._
