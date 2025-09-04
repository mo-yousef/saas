from playwright.sync_api import sync_playwright, Page, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # The README indicates the booking form is at /{slug}/booking/
    # I'll try 'mobooking' as the slug. If this fails, I might need to find another slug.
    page.goto("http://localhost/mobooking/booking/")

    # --- Step 1: Area Check ---
    # The form now starts at step 2 if area check is disabled.
    # Let's check if step 1 is even visible.
    step1 = page.locator("#mobooking-step-1")
    if step1.is_visible():
        # Click next without filling in the zip code
        page.locator("#mobooking-step-1 button[type=submit]").click()
        # Expect an error message
        expect(page.locator("#mobooking-zip + .mobooking-error-message")).to_be_visible()
        # Fill the zip to proceed
        page.locator("#mobooking-zip").fill("10001")
        page.locator("#mobooking-step-1 button[type=submit]").click()


    # --- Step 2: Service Selection ---
    # Wait for services to load
    expect(page.locator(".mobooking-services-grid")).to_be_visible(timeout=10000)
    # Click next without selecting a service
    page.locator("#mobooking-step-2 .mobooking-btn-primary").click()
    # Expect an error message
    expect(page.locator("#mobooking-services-container + .mobooking-error-message")).to_be_visible()
    # Select a service to proceed
    page.locator(".mobooking-service-card").first.click()

    # --- Step 7: Customer Details ---
    # The form auto-advances, so we should be on step 3. Let's get to step 7.
    page.locator("#mobooking-step-3 .mobooking-btn-primary").click()
    # Handle conditional steps 4 and 5
    if page.locator("#mobooking-step-4").is_visible():
        page.locator("#mobooking-step-4 .mobooking-btn-primary").click()
    if page.locator("#mobooking-step-5").is_visible():
        page.locator("#mobooking-step-5 .mobooking-btn-primary").click()

    # We should be on step 6 (Date/Time) now.
    # Click next without selecting a date/time
    page.locator("#mobooking-step-6 .mobooking-btn-primary").click()
    expect(page.locator("#mobooking-service-date + .mobooking-error-message")).to_be_visible()
    # Select a date to proceed
    page.locator(".flatpickr-day:not(.flatpickr-disabled)").first.click()
    # Wait for time slots
    expect(page.locator(".mobooking-time-slot")).to_be_visible(timeout=10000)
    page.locator(".mobooking-time-slot").first.click()
    page.locator("#mobooking-step-6 .mobooking-btn-primary").click()

    # Now we are on Step 7: Customer Details
    # Click "Review Booking" without filling out the form
    page.locator("#mobooking-step-7 .mobooking-btn-primary").click()

    # Expect error messages for all required fields
    name_error = page.locator("#mobooking-customer-name ~ .mobooking-error-message")
    email_error = page.locator("#mobooking-customer-email ~ .mobooking-error-message")
    phone_error = page.locator("#mobooking-customer-phone ~ .mobooking-error-message")
    address_error = page.locator("#mobooking-service-address ~ .mobooking-error-message")

    expect(name_error).to_be_visible()
    expect(email_error).to_be_visible()
    expect(phone_error).to_be_visible()
    expect(address_error).to_be_visible()

    # Take a screenshot to verify the new validation styling
    page.screenshot(path="jules-scratch/verification/validation_errors.png")

    # --- Test that errors are cleared on input ---
    page.locator("#mobooking-customer-name").fill("John Doe")
    expect(name_error).not_to_be_visible()

    page.locator("#mobooking-customer-email").fill("john.doe@example.com")
    expect(email_error).not_to_be_visible()

    page.locator("#mobooking-customer-phone").fill("1234567890")
    expect(phone_error).not_to_be_visible()

    page.locator("#mobooking-service-address").fill("123 Main St")
    expect(address_error).not_to_be_visible()

    # Take a final screenshot to show the cleared errors
    page.screenshot(path="jules-scratch/verification/validation_cleared.png")

    browser.close()

with sync_playwright() as p:
    run(p)
