const { test, expect } = require('@playwright/test');

test.describe('Site Inspector Dashboard', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/frontend/site-inspector-dashboard.html');
  });

  test('should display assigned projects', async ({ page }) => {
    await expect(page.locator('.assigned-projects')).toBeVisible();
  });

  test('should create inspection report', async ({ page }) => {
    await page.click('text=New Inspection');
    
    await page.fill('input[name="inspection_date"]', '2026-02-17');
    await page.selectOption('select[name="inspection_type"]', 'Site Inspection');
    await page.fill('textarea[name="findings"]', 'All work meets quality standards');
    await page.selectOption('select[name="status"]', 'Approved');
    
    await page.click('button:has-text("Submit Report")');
    await expect(page.locator('.report-submitted')).toBeVisible();
  });

  test('should view inspection history', async ({ page }) => {
    await page.click('text=Inspection History');
    await expect(page.locator('.inspection-list')).toBeVisible();
  });
});
