from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # Log in
    page.goto("http://localhost:8080/wp-login.php")
    page.fill('input[name="log"]', "admin")
    page.fill('input[name="pwd"]', "password")
    page.click('input[name="wp-submit"]')

    # Wait for login to complete
    expect(page).to_have_url("http://localhost:8080/wp-admin/")

    # Go to the service edit page for service_id=1
    page.goto("http://localhost:8080/dashboard/service-edit/?service_id=1")

    # Switch to the "Service Options" tab
    options_tab = page.locator('button[data-tab="service-options"]')
    expect(options_tab).to_be_visible()
    options_tab.click()

    # Verify that existing options are displayed
    # Based on the database, there should be some options.
    # Let's check for at least one option item.
    expect(page.locator(".option-item")).to_have_count(1, timeout=10000)

    # Click the "Add Option" button
    add_option_button = page.locator("#add-option-btn")
    expect(add_option_button).to_be_visible()
    add_option_button.click()

    # Verify that a new option item has been added
    expect(page.locator(".option-item")).to_have_count(2)

    # Take a screenshot
    page.screenshot(path="jules-scratch/verification/verification.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
