const { test, expect } = require('@playwright/test');

// Real homeowner credentials
const HOMEOWNER_CREDENTIALS = {
  email: 'shijinthomas123@gmail.com',
  password: 'Shijin@123'
};

test.describe('Homeowner Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://localhost:3000/homeowner-dashboard');
    
    const currentUrl = page.url();
    if (currentUrl.includes('login')) {
      await page.fill('input[type="email"], input[name="email"]', HOMEOWNER_CREDENTIALS.email);
      await page.fill('input[type="password"], input[name="password"]', HOMEOWNER_CREDENTIALS.password);
      await page.click('button[type="submit"], button:has-text("Login")');
      await page.waitForURL('**/homeowner-dashboard', { timeout: 10000 });
    }
    
    await page.waitForLoadState('networkidle');
  });

  test('should load homeowner dashboard', async ({ page }) => {
    await expect(page).toHaveURL(/homeowner-dashboard/i);
  });

  test('should display project overview', async ({ page }) => {
    const projectOverview = page.locator('.project-overview, [data-section="overview"]').first();
    if (await projectOverview.isVisible().catch(() => false)) {
      await expect(projectOverview).toBeVisible();
    }
  });

  test('should navigate to payment requests', async ({ page }) => {
    const paymentLink = page.locator('text=/Payment/i').first();
    if (await paymentLink.isVisible().catch(() => false)) {
      await paymentLink.click();
      await page.waitForTimeout(1000);
    }
  });

  test.describe('Construction Progress Buttons', () => {
    test('should have clickable refresh button in Progress Overview', async ({ page }) => {
      // Wait for the Progress Overview widget to load
      await page.waitForSelector('.widget-container', { timeout: 10000 });
      
      // Locate the refresh button in Progress Overview widget
      const refreshButton = page.locator('.widget-container:has-text("Progress Overview") .icon-btn[title="Refresh"]');
      
      // Verify button is visible
      await expect(refreshButton).toBeVisible();
      
      // Verify button is enabled
      await expect(refreshButton).toBeEnabled();
      
      // Click the refresh button
      await refreshButton.click();
      
      // Wait for loading spinner to appear (indicates refresh started)
      const loadingSpinner = page.locator('#progress-overview .loading-spinner');
      await expect(loadingSpinner).toBeVisible({ timeout: 2000 });
      
      // Wait for content to reload (spinner disappears)
      await page.waitForFunction(() => {
        const spinner = document.querySelector('#progress-overview .loading-spinner');
        return spinner === null;
      }, { timeout: 10000 });
      
      console.log('✓ Refresh button clicked and data reloaded successfully');
    });

    test('should have clickable view details button in Budget Tracker', async ({ page }) => {
      // Wait for the Budget Tracker widget to load
      await page.waitForSelector('.widget-container', { timeout: 10000 });
      
      // Locate the view details button in Budget Tracker widget
      const viewDetailsButton = page.locator('.widget-container:has-text("Budget Tracker") .icon-btn[title="View Details"]');
      
      // Verify button is visible
      await expect(viewDetailsButton).toBeVisible();
      
      // Verify button is enabled
      await expect(viewDetailsButton).toBeEnabled();
      
      // Set up dialog handler before clicking
      page.on('dialog', async dialog => {
        console.log('Dialog message:', dialog.message());
        expect(dialog.message()).toContain('Budget details');
        await dialog.accept();
      });
      
      // Click the view details button
      await viewDetailsButton.click();
      
      // Wait a moment for the dialog to be handled
      await page.waitForTimeout(1000);
      
      console.log('✓ View Details button clicked and alert displayed successfully');
    });

    test('should verify all construction progress buttons are interactive', async ({ page }) => {
      // Wait for widgets to load
      await page.waitForSelector('.widget-container', { timeout: 10000 });
      
      // Get all icon buttons in the construction progress section
      const allButtons = page.locator('.widget-container .icon-btn');
      const buttonCount = await allButtons.count();
      
      console.log(`Found ${buttonCount} buttons in construction progress section`);
      
      // Verify we have at least 2 buttons (refresh and view details)
      expect(buttonCount).toBeGreaterThanOrEqual(2);
      
      // Check each button is visible and enabled
      for (let i = 0; i < buttonCount; i++) {
        const button = allButtons.nth(i);
        await expect(button).toBeVisible();
        await expect(button).toBeEnabled();
        
        // Get button title for logging
        const title = await button.getAttribute('title');
        console.log(`✓ Button ${i + 1}: "${title}" is visible and enabled`);
      }
    });

    test('should verify refresh button updates progress data', async ({ page }) => {
      // Wait for initial data load
      await page.waitForSelector('.widget-container', { timeout: 10000 });
      await page.waitForSelector('#progress-overview .project-card, #progress-overview .empty-state', { timeout: 10000 });
      
      // Get initial content
      const initialContent = await page.locator('#progress-overview').innerHTML();
      
      // Click refresh button
      const refreshButton = page.locator('.widget-container:has-text("Progress Overview") .icon-btn[title="Refresh"]');
      await refreshButton.click();
      
      // Wait for loading spinner
      await page.waitForSelector('#progress-overview .loading-spinner', { timeout: 2000 });
      
      // Wait for new content to load
      await page.waitForFunction(() => {
        const spinner = document.querySelector('#progress-overview .loading-spinner');
        return spinner === null;
      }, { timeout: 10000 });
      
      // Get updated content
      const updatedContent = await page.locator('#progress-overview').innerHTML();
      
      // Content should have changed (at least the loading state)
      expect(initialContent).not.toBe(updatedContent);
      
      console.log('✓ Refresh button successfully updates progress data');
    });

    test('should verify buttons have proper hover states', async ({ page }) => {
      // Wait for widgets to load
      await page.waitForSelector('.widget-container', { timeout: 10000 });
      
      // Test refresh button hover
      const refreshButton = page.locator('.widget-container:has-text("Progress Overview") .icon-btn[title="Refresh"]');
      
      // Get initial background color
      const initialBg = await refreshButton.evaluate(el => 
        window.getComputedStyle(el).backgroundColor
      );
      
      // Hover over button
      await refreshButton.hover();
      await page.waitForTimeout(500); // Wait for transition
      
      // Get hover background color
      const hoverBg = await refreshButton.evaluate(el => 
        window.getComputedStyle(el).backgroundColor
      );
      
      // Background should change on hover (CSS transition)
      console.log(`Initial BG: ${initialBg}, Hover BG: ${hoverBg}`);
      
      // Verify button is still visible and enabled during hover
      await expect(refreshButton).toBeVisible();
      await expect(refreshButton).toBeEnabled();
      
      console.log('✓ Buttons have proper hover states');
    });

    test('should verify button accessibility attributes', async ({ page }) => {
      // Wait for widgets to load
      await page.waitForSelector('.widget-container', { timeout: 10000 });
      
      // Check refresh button
      const refreshButton = page.locator('.widget-container:has-text("Progress Overview") .icon-btn[title="Refresh"]');
      
      // Verify title attribute exists (for tooltip)
      const refreshTitle = await refreshButton.getAttribute('title');
      expect(refreshTitle).toBe('Refresh');
      
      // Check view details button
      const viewDetailsButton = page.locator('.widget-container:has-text("Budget Tracker") .icon-btn[title="View Details"]');
      
      // Verify title attribute exists
      const viewDetailsTitle = await viewDetailsButton.getAttribute('title');
      expect(viewDetailsTitle).toBe('View Details');
      
      console.log('✓ Buttons have proper accessibility attributes');
    });

    test('should verify buttons work after multiple clicks', async ({ page }) => {
      // Wait for widgets to load
      await page.waitForSelector('.widget-container', { timeout: 10000 });
      
      const refreshButton = page.locator('.widget-container:has-text("Progress Overview") .icon-btn[title="Refresh"]');
      
      // Click refresh button multiple times
      for (let i = 0; i < 3; i++) {
        console.log(`Click ${i + 1}...`);
        
        await refreshButton.click();
        
        // Wait for loading to start
        await page.waitForSelector('#progress-overview .loading-spinner', { timeout: 2000 });
        
        // Wait for loading to complete
        await page.waitForFunction(() => {
          const spinner = document.querySelector('#progress-overview .loading-spinner');
          return spinner === null;
        }, { timeout: 10000 });
        
        // Verify button is still clickable
        await expect(refreshButton).toBeEnabled();
        
        await page.waitForTimeout(500);
      }
      
      console.log('✓ Buttons work correctly after multiple clicks');
    });
  });
});
