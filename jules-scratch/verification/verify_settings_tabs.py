from playwright.sync_api import sync_playwright

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # Log in
        page.goto("http://localhost:8888/wp-login.php")
        page.fill('input[name="log"]', "admin")
        page.fill('input[name="pwd"]', "password")
        page.click('input[name="wp-submit"]')
        page.wait_for_load_state("networkidle")

        # Go to settings page
        page.goto("http://localhost:8888/wp-admin/admin.php?page=mobooking-settings")
        page.wait_for_load_state("networkidle")

        # Take screenshot of general settings tab
        page.screenshot(path="jules-scratch/verification/settings_page_general.png")

        # Go to branding tab
        page.click('a[data-tab="branding"]')
        page.wait_for_load_state("networkidle")
        page.screenshot(path="jules-scratch/verification/settings_page_branding.png")

        # Go to email notifications tab
        page.click('a[data-tab="email-notifications"]')
        page.wait_for_load_state("networkidle")
        page.screenshot(path="jules-scratch/verification/settings_page_email.png")

    except Exception as e:
        print(f"An error occurred: {e}")
        try:
            print(page.content())
        except Exception as pe:
            print(f"Could not get page content after error: {pe}")


    browser.close()

with sync_playwright() as playwright:
    run(playwright)
