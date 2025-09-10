from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # The base URL will be provided by the testing environment, so I can use a relative path.
    page.goto("/booking/test-company-inc/", wait_until="networkidle")

    # Wait for the zip code input to be visible
    zip_input = page.locator("#NORDBOOKING-zip")
    expect(zip_input).to_be_visible()

    # Click the submit button without entering a zip code to trigger an error
    page.locator("#NORDBOOKING-area-check-form button[type=submit]").click()

    # Check for the error message
    error_feedback = page.locator("#NORDBOOKING-location-feedback")
    expect(error_feedback).to_have_class(
        "NORDBOOKING-feedback-error"
    )
    expect(error_feedback).to_be_visible()

    # Now enter a valid zip code to trigger a success message
    # From the test files, it seems like the zip code check is mocked and will succeed.
    zip_input.fill("12345")

    # The success message should appear after a debounce
    page.wait_for_timeout(1000)

    success_feedback = page.locator("#NORDBOOKING-location-feedback")
    expect(success_feedback).to_have_class(
        "NORDBOOKING-feedback-success"
    )
    expect(success_feedback).to_be_visible()

    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
