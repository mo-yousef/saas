from playwright.sync_api import sync_playwright, expect, Page

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    try:
        # Login
        page.goto("http://localhost:8888/wp-login.php")
        page.get_by_label("Username or Email Address").fill("user")
        page.get_by_label("Password").fill("password")
        page.get_by_role("button", name="Log In").click()

        # Wait for login to complete and dashboard to load
        expect(page.get_by_role("heading", name="Dashboard")).to_be_visible(timeout=10000)

        # Go to the service edit page
        page.goto("http://localhost:8888/wp-admin/admin.php?page=service-edit&service_id=1")

        # --- Test Case 1: Show choices container ---

        # Find the first option item and expand it
        first_option = page.locator(".option-item").first
        first_option.get_by_role("button", name="Toggle Option").click()

        # Change the type to 'Dropdown'
        dropdown_label = first_option.get_by_text("Dropdown")
        dropdown_label.click()

        # Assert the choices container is visible
        choices_container = first_option.locator(".choices-container")
        expect(choices_container).to_be_visible()

        # Add a choice
        add_choice_button = first_option.get_by_role("button", name="Add Choice")
        add_choice_button.click()
        add_choice_button.click()

        # Take a screenshot
        page.screenshot(path="jules-scratch/verification/verification_show_choices.png")
        print("Successfully took screenshot showing the choices.")

        # --- Test Case 2: Hide choices container ---

        # Change the type back to 'Text Input'
        text_input_label = first_option.get_by_text("Text Input")
        text_input_label.click()

        # Assert the choices container is hidden
        expect(choices_container).to_be_hidden()

        # Take a screenshot
        page.screenshot(path="jules-scratch/verification/verification_hide_choices.png")
        print("Successfully took screenshot hiding the choices.")

    except Exception as e:
        print(f"An error occurred: {e}")
        page.screenshot(path="jules-scratch/verification/error.png")

    finally:
        browser.close()

with sync_playwright() as playwright:
    run(playwright)
