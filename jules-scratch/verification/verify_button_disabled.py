from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # The user did not provide a base URL, so I will try with a common default.
    base_url = "http://localhost:8888"
    page.goto(f"{base_url}/booking/test-company-inc/", wait_until="networkidle")

    # Check that the button is disabled initially
    submit_button = page.locator("#mobooking-area-check-form button[type=submit]")
    expect(submit_button).to_be_disabled()

    # Enter a valid zip code
    zip_input = page.locator("#mobooking-zip")
    zip_input.fill("12345")

    # The button should be enabled after the debounce and successful ajax call
    page.wait_for_timeout(1000)
    expect(submit_button).to_be_enabled()

    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
