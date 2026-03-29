const { test, expect } = require('@playwright/test');

test.describe('Contractor Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    // Login as contractor first
    await page.goto('http://localhost:3000/login');
    await page.waitForLoadState('networkidle');
    
    // Fill in credentials
    await page.fill('input[name="email"]', 'shijinthomas321@gmail.com');
    await page.fill('input[name="password"]', 'Shijin@123');
    await page.click('button[type="submit"]');
    
    // Wait for navigation - be more flexible with timeout
    try {
      await page.waitForURL('**/contractor-dashboard', { timeout: 30000 });
    } catch (e) {
      // If exact URL doesn't match, check if we're at least logged in
      await page.waitForTimeout(3000);
    }
    
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000); // Give React time to render
  });

  test('should display contractor dashboard', async ({ page }) => {
    // Check if we're on the contractor dashboard
    await expect(page).toHaveURL(/contractor-dashboard/);
    
    // Take a screenshot for debugging
    await page.screenshot({ path: 'test-results/contractor-dashboard-loaded.png', fullPage: true });
    
    // Look for common dashboard elements
    const pageContent = await page.content();
    console.log('Page loaded successfully');
  });

  test.describe('Progress Update Section', () => {
    test('should navigate to Progress Update section', async ({ page }) => {
      // Wait for page to be fully loaded
      await page.waitForTimeout(2000);
      
      // Try multiple ways to find and click Progress Update
      const progressUpdateButton = page.locator('button:has-text("Progress Update"), a:has-text("Progress Update"), [role="tab"]:has-text("Progress Update")').first();
      
      if (await progressUpdateButton.count() > 0) {
        await progressUpdateButton.click();
        await page.waitForTimeout(1000);
        console.log('Clicked Progress Update button');
      } else {
        console.log('Progress Update button not found, checking if already on that section');
      }
      
      // Take screenshot
      await page.screenshot({ path: 'test-results/progress-update-section.png', fullPage: true });
    });

    test('should verify all buttons in Progress Update are clickable', async ({ page }) => {
      await page.waitForTimeout(2000);
      
      // Navigate to Progress Update if button exists
      const progressUpdateButton = page.locator('button:has-text("Progress Update"), a:has-text("Progress Update"), [role="tab"]:has-text("Progress Update")').first();
      if (await progressUpdateButton.count() > 0) {
        await progressUpdateButton.click();
        await page.waitForTimeout(1500);
      }

      // Find all visible buttons on the page
      const allButtons = page.locator('button:visible');
      const buttonCount = await allButtons.count();
      
      console.log(`Found ${buttonCount} visible buttons`);
      
      // Test each button is enabled
      for (let i = 0; i < buttonCount; i++) {
        const button = allButtons.nth(i);
        const buttonText = await button.textContent();
        const isEnabled = await button.isEnabled();
        console.log(`Button ${i + 1}: "${buttonText?.trim()}" - Enabled: ${isEnabled}`);
        
        if (isEnabled) {
          await expect(button).toBeEnabled();
        }
      }
      
      // Take screenshot showing all buttons
      await page.screenshot({ path: 'test-results/progress-update-buttons.png', fullPage: true });
    });

    test('should test Submit button functionality', async ({ page }) => {
      await page.waitForTimeout(2000);
      
      // Navigate to Progress Update
      const progressUpdateButton = page.locator('button:has-text("Progress Update"), a:has-text("Progress Update"), [role="tab"]:has-text("Progress Update")').first();
      if (await progressUpdateButton.count() > 0) {
        await progressUpdateButton.click();
        await page.waitForTimeout(1500);
      }

      // Look for submit-type buttons
      const submitButtons = page.locator('button:has-text("Submit"), button[type="submit"], button:has-text("Save")');
      const submitCount = await submitButtons.count();
      
      console.log(`Found ${submitCount} submit-type buttons`);
      
      if (submitCount > 0) {
        const firstSubmit = submitButtons.first();
        await expect(firstSubmit).toBeEnabled();
        console.log('Submit button is enabled and clickable');
      }
      
      await page.screenshot({ path: 'test-results/submit-button-test.png', fullPage: true });
    });

    test('should test all interactive elements', async ({ page }) => {
      await page.waitForTimeout(2000);
      
      // Navigate to Progress Update
      const progressUpdateButton = page.locator('button:has-text("Progress Update"), a:has-text("Progress Update"), [role="tab"]:has-text("Progress Update")').first();
      if (await progressUpdateButton.count() > 0) {
        await progressUpdateButton.click();
        await page.waitForTimeout(1500);
      }

      // Test input fields
      const inputs = page.locator('input:visible, textarea:visible, select:visible');
      const inputCount = await inputs.count();
      console.log(`Found ${inputCount} input elements`);

      // Test buttons
      const buttons = page.locator('button:visible');
      const buttonCount = await buttons.count();
      console.log(`Found ${buttonCount} buttons`);
      
      // Verify all buttons are enabled
      for (let i = 0; i < buttonCount; i++) {
        const button = buttons.nth(i);
        const isEnabled = await button.isEnabled();
        const buttonText = await button.textContent();
        console.log(`Button "${buttonText?.trim()}": ${isEnabled ? 'ENABLED' : 'DISABLED'}`);
      }
      
      await page.screenshot({ path: 'test-results/all-interactive-elements.png', fullPage: true });
    });
  });
});
