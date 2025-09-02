import re
from playwright.sync_api import sync_playwright, expect

def run(playwright):
    browser = playwright.chromium.launch(headless=True)
    context = browser.new_context()
    page = context.new_page()

    # We are assuming the local development server is running on port 80.
    page.goto("http://localhost:80")

    # Wait for the page to load completely
    page.wait_for_load_state("networkidle")

    # Take a screenshot of the entire page
    page.screenshot(path="jules-scratch/verification/verification.png", full_page=True)

    browser.close()

with sync_playwright() as playwright:
    run(playwright)
