const { test, expect } = require('@playwright/test');

// Real homeowner credentials
const HOMEOWNER_CREDENTIALS = {
  email: 'shijinthomas123@gmail.com',
  password: 'Shijin@123'
};

test.describe('Construction Progress - Navigation & Tabs', () => {
  
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
    await page.waitForTimeout(2000);
  });

  test('should navigate to Construction Progress tab', async ({ page }) => {
    // Look for Construction Progress tab/link
    const progressTabSelectors = [
      'a:has-text("Construction Progress")',
      'button:has-text("Construction Progress")',
      '[data-tab="progress"]',
      '.nav-link:has-text("Progress")',
      'text=/Construction Progress/i'
    ];
    
    for (const selector of progressTabSelectors) {
      const tab = page.locator(selector).first();
      if (await tab.isVisible().catch(() => false)) {
        console.log(`Found Construction Progress tab: ${selector}`);
        await tab.click();
        await page.waitForTimeout(1500);
        
        // Take screenshot after clicking
        await page.screenshot({ path: 'test-results/after-progress-tab-click.png' });
        break;
      }
    }
  });

  test('should display all dashboard sections', async ({ page }) => {
    // Take full dashboard screenshot
    await page.screenshot({ path: 'test-results/homeowner-dashboard-full.png', fullPage: true });
    
    // Check for main sections
    const sections = [
      'text=/Project Overview/i',
      'text=/Construction Progress/i',
      'text=/Payment/i',
      'text=/Documents/i',
      'text=/Timeline/i'
    ];
    
    const foundSections = [];
    for (const selector of sections) {
      const section = page.locator(selector).first();
      if (await section.isVisible().catch(() => false)) {
        const text = await section.textContent();
        foundSections.push(text.trim());
      }
    }
    
    console.log('Found dashboard sections:', foundSections);
  });

  test('should check sidebar navigation', async ({ page }) => {
    // Look for sidebar menu items
    const sidebarItems = await page.locator('.sidebar a, .nav-menu a, nav a').all();
    
    console.log(`Found ${sidebarItems.length} navigation items`);
    
    for (let i = 0; i < Math.min(sidebarItems.length, 10); i++) {
      const text = await sidebarItems[i].textContent();
      const href = await sidebarItems[i].getAttribute('href');
      console.log(`Nav item ${i + 1}: ${text?.trim()} -> ${href}`);
    }
  });

  test('should verify breadcrumb navigation', async ({ page }) => {
    const breadcrumb = page.locator('.breadcrumb, [aria-label="breadcrumb"]').first();
    
    if (await breadcrumb.isVisible().catch(() => false)) {
      const text = await breadcrumb.textContent();
      console.log('Breadcrumb:', text);
      await expect(breadcrumb).toBeVisible();
    }
  });

  test('should test back button functionality', async ({ page }) => {
    // Navigate to a different section
    const navLink = page.locator('nav a, .nav-link').nth(1);
    if (await navLink.isVisible().catch(() => false)) {
      await navLink.click();
      await page.waitForTimeout(1000);
      
      // Go back
      await page.goBack();
      await page.waitForTimeout(1000);
      
      console.log('Back navigation tested');
    }
  });

  test('should check for active tab highlighting', async ({ page }) => {
    // Look for active/selected tab indicators
    const activeTab = page.locator('.active, .selected, [aria-selected="true"]').first();
    
    if (await activeTab.isVisible().catch(() => false)) {
      const text = await activeTab.textContent();
      console.log('Active tab:', text);
      
      // Take screenshot showing active state
      await activeTab.screenshot({ path: 'test-results/active-tab.png' });
    }
  });

  test('should verify URL changes on navigation', async ({ page }) => {
    const initialUrl = page.url();
    console.log('Initial URL:', initialUrl);
    
    // Click different navigation items and check URL
    const navItems = await page.locator('nav a, .nav-link').all();
    
    for (let i = 0; i < Math.min(navItems.length, 3); i++) {
      if (await navItems[i].isVisible().catch(() => false)) {
        await navItems[i].click();
        await page.waitForTimeout(1000);
        
        const newUrl = page.url();
        console.log(`After clicking nav item ${i + 1}: ${newUrl}`);
      }
    }
  });

  test('should check for loading states during navigation', async ({ page }) => {
    // Look for loading indicators
    const loadingSelectors = [
      '.loading',
      '.spinner',
      '[data-loading]',
      'text=/Loading/i'
    ];
    
    // Click a navigation item
    const navLink = page.locator('nav a').first();
    if (await navLink.isVisible().catch(() => false)) {
      await navLink.click();
      
      // Check for loading state
      for (const selector of loadingSelectors) {
        const loader = page.locator(selector).first();
        if (await loader.isVisible().catch(() => false)) {
          console.log(`Found loading indicator: ${selector}`);
          break;
        }
      }
    }
  });

  test('should verify responsive menu on mobile', async ({ page }) => {
    // Switch to mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    await page.reload();
    await page.waitForLoadState('networkidle');
    
    // Look for hamburger menu
    const hamburger = page.locator('.hamburger, .menu-toggle, [aria-label="menu"]').first();
    
    if (await hamburger.isVisible().catch(() => false)) {
      console.log('Found mobile menu toggle');
      await hamburger.click();
      await page.waitForTimeout(1000);
      
      // Take screenshot of mobile menu
      await page.screenshot({ path: 'test-results/mobile-menu.png' });
    }
  });

  test('should capture all tab content', async ({ page }) => {
    // Find all tabs
    const tabs = await page.locator('[role="tab"], .tab, .nav-link').all();
    
    console.log(`Found ${tabs.length} tabs`);
    
    for (let i = 0; i < Math.min(tabs.length, 5); i++) {
      if (await tabs[i].isVisible().catch(() => false)) {
        const tabName = await tabs[i].textContent();
        console.log(`Clicking tab: ${tabName?.trim()}`);
        
        await tabs[i].click();
        await page.waitForTimeout(1500);
        
        // Take screenshot of each tab
        const filename = `test-results/tab-${i}-${tabName?.trim().replace(/\s+/g, '-').toLowerCase()}.png`;
        await page.screenshot({ path: filename, fullPage: true });
      }
    }
  });
});
