from playwright.sync_api import sync_playwright, expect

def run_verification():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()

        # Navigate to the local file
        page.goto("file:///app/dashboard/page-booking-form.php")

        # Click on the Design tab
        design_tab_selector = 'a[data-tab="design"]'
        page.click(design_tab_selector)

        # Wait for the design tab pane to be visible
        design_pane_selector = '#design'
        expect(page.locator(design_pane_selector)).to_be_visible()

        # Take a screenshot
        page.screenshot(path="jules-scratch/verification/verification.png")

        browser.close()

if __name__ == "__main__":
    run_verification()
