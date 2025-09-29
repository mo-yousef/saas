# How to Find Your Stripe Price ID

## Your Product Information
- **Product ID**: `prod_T2e9kSp0dB2q5s`
- **Need**: Price ID (starts with `price_`)

## Step-by-Step Guide

### 1. Go to Stripe Dashboard
Visit: https://dashboard.stripe.com/products

### 2. Find Your Product
Look for product with ID: `prod_T2e9kSp0dB2q5s`

### 3. Click on the Product
This will show you all the prices associated with this product.

### 4. Locate the Price
You should see one or more prices listed, each with:
- **Amount** (e.g., $29.00)
- **Billing interval** (e.g., Monthly, Yearly)
- **Price ID** (starts with `price_`)

### 5. Copy the Price ID
Copy the **Price ID** (NOT the Product ID) that looks like:
- `price_1234567890abcdef`
- `price_1QSjJMLVu8BmPxKRwPzgUcHt` (example)

### 6. Add to WordPress
1. Go to WordPress Admin → NORDBOOKING → Stripe Settings
2. Paste the Price ID in the "Price ID" field
3. Save the settings

## Common Mistakes

❌ **Wrong**: Using Product ID (`prod_T2e9kSp0dB2q5s`)
✅ **Correct**: Using Price ID (`price_1234567890abcdef`)

❌ **Wrong**: Using Customer ID (`cus_...`)
✅ **Correct**: Using Price ID (`price_...`)

## If You Don't Have a Price Yet

If your product doesn't have any prices:

1. Click on your product in Stripe Dashboard
2. Click "Add another price"
3. Set up your pricing:
   - **Amount**: e.g., $29.00
   - **Billing period**: Monthly/Yearly
   - **Currency**: USD (or your preference)
4. Save the price
5. Copy the new Price ID

## Test Your Setup

Once you have the correct Price ID:

1. Go to your subscription page
2. Click "Subscribe Now"
3. Use test card: `4242 4242 4242 4242`
4. Complete the checkout
5. Verify the subscription is created

## Need Help?

If you're still having trouble:
1. Check that you're copying the Price ID, not Product ID
2. Make sure the price is set to "Recurring" (not one-time)
3. Verify you're in the correct Stripe account
4. Contact support with your Product ID: `prod_T2e9kSp0dB2q5s`