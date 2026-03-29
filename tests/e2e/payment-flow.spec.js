const { test, expect } = require('@playwright/test');

test.describe('Payment Flow', () => {
  const HOMEOWNER_CREDENTIALS = {
    email: 'shijinthomas123@gmail.com',
    password: 'Shijin@123'
  };

  test('should create custom payment request', async ({ page }) => {
    await page.goto('http://localhost:3000/homeowner-dashboard');
    
    const currentUrl = page.url();
    if (currentUrl.includes('login')) {
      await page.fill('input[type="email"], input[name="email"]', HOMEOWNER_CREDENTIALS.email);
      await page.fill('input[type="password"], input[name="password"]', HOMEOWNER_CREDENTIALS.password);
      await page.click('button[type="submit"], button:has-text("Login")');
      await page.waitForURL('**/homeowner-dashboard', { timeout: 10000 });
    }
    
    await page.click('text=Custom Payment Request');
    await page.fill('input[name="amount"]', '5000');
    await page.fill('textarea[name="description"]', 'Additional materials needed');
    await page.click('button:has-text("Submit Request")');
    
    await expect(page.locator('.request-submitted')).toBeVisible();
  });

  test('should verify payment with blockchain', async ({ page }) => {
    await page.goto('/test_receipt_verification_blockchain_integration.php');
    
    await expect(page.locator('.blockchain-hash')).toBeVisible();
    await expect(page.locator('.verification-status')).toContainText('Verified');
  });

  test('should display payment history', async ({ page }) => {
    await page.goto('http://localhost:3000/homeowner-dashboard');
    
    const currentUrl = page.url();
    if (currentUrl.includes('login')) {
      await page.fill('input[type="email"], input[name="email"]', HOMEOWNER_CREDENTIALS.email);
      await page.fill('input[type="password"], input[name="password"]', HOMEOWNER_CREDENTIALS.password);
      await page.click('button[type="submit"], button:has-text("Login")');
      await page.waitForURL('**/homeowner-dashboard', { timeout: 10000 });
    }
    
    await page.click('text=Payment History');
    await expect(page.locator('.payment-history-table')).toBeVisible();
  });
});
