from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # The user is already logged in in the test environment.
    # The URL is based on the WordPress admin structure.
    # We need to find a service to edit. Let's go to the services page and click the first one.
    page.goto("http://localhost:8888/wp-admin/admin.php?page=mobooking-services")

    # Find the first "View" link and click it.
    # The link is inside a card with a data-service-id attribute.
    first_service_card = page.locator('.card[data-service-id]').first
    view_link = first_service_card.get_by_role("link", name="View")

    # Wait for the link to be visible before clicking
    expect(view_link).to_be_visible()
    view_link.click()

    # Now we should be on the service edit page.
    # Wait for the header of the edit page to be visible.
    expect(page.get_by_role("heading", name="Edit Service")).to_be_visible()

    # Take a screenshot of the desktop layout
    page.screenshot(path="jules-scratch/verification/desktop_layout.png")

    # Change viewport to mobile size
    page.set_viewport_size({"width": 375, "height": 812})

    # Take a screenshot of the mobile layout
    page.screenshot(path="jules-scratch/verification/mobile_layout.png")

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
