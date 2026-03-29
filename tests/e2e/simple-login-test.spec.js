const { test, expect } = require('@playwright/test');

const HOMEOWNER_CREDENTIALS = {
  email: 'shijinthomas123@gmail.com',
  password: 'Shijin@123'
};

test.describe('Simple Login Test', () => {
  test('should check if server is running', async ({ page }) => {
    console.log('Attempting to connect to http://localhost:3000');
    
    try {
      const response = await page.goto('http://localhost:3000', { 
        waitUntil: 'domcontentloaded',
        timeout: 60000 
      });
      
      console.log('Response status:', response?.status());
      console.log('Current URL:', page.url());
      
      await page.screenshot({ path: 'test-results/server-check.png', fullPage: true });
      
      expect(response?.status()).toBeLessThan(500);
    } catch (error) {
      console.error('Error connecting to server:', error.message);
      throw error;
    }
  });

  test('should navigate to homeowner dashboard', async ({ page }) => {
    console.log('Navigating to homeowner dashboard...');
    
    await page.goto('http://localhost:3000/homeowner-dashboard', {
      waitUntil: 'domcontentloaded',
      timeout: 60000
    });
    
    console.log('Current URL after navigation:', page.url());
    await page.screenshot({ path: 'test-results/dashboard-initial.png', fullPage: true });
    
    // Check if we're on login page
    if (page.url().includes('login')) {
      console.log('Redirected to login page, attempting login...');
      
      await page.fill('input[type="email"], input[name="email"]', HOMEOWNER_CREDENTIALS.email);
      await page.fill('input[type="password"], input[name="password"]', HOMEOWNER_CREDENTIALS.password);
      
      await page.screenshot({ path: 'test-results/before-login-click.png' });
      
      await page.click('button[type="submit"], button:has-text("Login")');
      
      await page.waitForTimeout(3000);
      await page.screenshot({ path: 'test-results/after-login-click.png', fullPage: true });
      
      console.log('URL after login:', page.url());
    }
    
    // Wait for any content to load
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'test-results/final-dashboard.png', fullPage: true });
  });
});
