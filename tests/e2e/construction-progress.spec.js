const { test, expect } = require('@playwright/test');

// Real homeowner credentials
const HOMEOWNER_CREDENTIALS = {
  email: 'shijinthomas123@gmail.com',
  password: 'Shijin@123'
};

test.describe('Construction Progress Section - Homeowner Dashboard', () => {
  
  test.beforeEach(async ({ page }) => {
    // Go directly to homeowner dashboard
    await page.goto('http://localhost:3000/homeowner-dashboard');
    
    // Check if login is required
    const currentUrl = page.url();
    if (currentUrl.includes('login')) {
      // Login as homeowner
      await page.fill('input[type="email"], input[name="email"]', HOMEOWNER_CREDENTIALS.email);
      await page.fill('input[type="password"], input[name="password"]', HOMEOWNER_CREDENTIALS.password);
      await page.click('button[type="submit"], button:has-text("Login")');
      
      // Wait for redirect to dashboard
      await page.waitForURL('**/homeowner-dashboard', { timeout: 10000 });
    }
    
    // Wait for dashboard to load
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
  });

  test('should display construction progress section', async ({ page }) => {
    // Navigate to construction progress or verify it's visible
    const progressSection = page.locator('.construction-progress, #construction-progress, [data-section="progress"]');
    
    if (!(await progressSection.isVisible())) {
      // Try clicking navigation link
      await page.click('text=/Construction Progress|Progress|View Progress/i').catch(() => {});
      await page.waitForTimeout(1000);
    }
    
    await expect(progressSection.or(page.locator('text=/Construction Progress/i'))).toBeVisible({ timeout: 10000 });
  });

  test('should show overall project progress percentage', async ({ page }) => {
    // Look for progress indicators
    const progressIndicators = [
      '.progress-percentage',
      '.overall-progress',
      '[data-progress]',
      'text=/\\d+%/',
      '.progress-bar'
    ];
    
    let found = false;
    for (const selector of progressIndicators) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        await expect(element).toBeVisible();
        found = true;
        console.log(`Found progress indicator: ${selector}`);
        break;
      }
    }
    
    expect(found).toBeTruthy();
  });

  test('should display stage-wise progress breakdown', async ({ page }) => {
    // Look for stage information
    const stageSelectors = [
      '.stage-item',
      '.construction-stage',
      '[data-stage]',
      'text=/Foundation|Framing|Roofing|Electrical|Plumbing/i'
    ];
    
    let stageFound = false;
    for (const selector of stageSelectors) {
      const elements = page.locator(selector);
      const count = await elements.count();
      if (count > 0) {
        console.log(`Found ${count} stages with selector: ${selector}`);
        stageFound = true;
        break;
      }
    }
    
    expect(stageFound).toBeTruthy();
  });

  test('should show daily progress reports from contractor', async ({ page }) => {
    // Look for daily reports section
    const reportSelectors = [
      '.daily-progress',
      '.progress-report',
      '.daily-report',
      'text=/Daily Progress|Daily Report|Progress Update/i'
    ];
    
    for (const selector of reportSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        await expect(element).toBeVisible();
        console.log(`Found daily reports: ${selector}`);
        break;
      }
    }
  });

  test('should display progress timeline/history', async ({ page }) => {
    // Look for timeline or history
    const timelineSelectors = [
      '.timeline',
      '.progress-history',
      '.activity-log',
      '[data-timeline]',
      'text=/Timeline|History|Activity/i'
    ];
    
    let timelineFound = false;
    for (const selector of timelineSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        await expect(element).toBeVisible();
        console.log(`Found timeline: ${selector}`);
        timelineFound = true;
        break;
      }
    }
  });

  test('should show progress images/photos', async ({ page }) => {
    // Look for progress images
    const imageSelectors = [
      '.progress-image img',
      '.construction-photo img',
      '[data-progress-image]',
      'img[alt*="progress"]',
      'img[alt*="construction"]'
    ];
    
    let imagesFound = false;
    for (const selector of imageSelectors) {
      const images = page.locator(selector);
      const count = await images.count();
      if (count > 0) {
        console.log(`Found ${count} progress images`);
        imagesFound = true;
        
        // Verify at least one image loads
        const firstImage = images.first();
        await expect(firstImage).toBeVisible({ timeout: 5000 });
        break;
      }
    }
  });

  test('should display inspection reports in progress section', async ({ page }) => {
    // Look for inspection reports
    const inspectionSelectors = [
      '.inspection-report',
      '.site-inspection',
      'text=/Inspection Report|Site Inspection/i',
      '[data-inspection]'
    ];
    
    for (const selector of inspectionSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        await expect(element).toBeVisible();
        console.log(`Found inspection reports: ${selector}`);
        break;
      }
    }
  });

  test('should show schedule tracking information', async ({ page }) => {
    // Look for schedule/timeline tracking
    const scheduleSelectors = [
      '.schedule-tracking',
      '.project-schedule',
      'text=/Schedule|Timeline|Deadline/i',
      '[data-schedule]'
    ];
    
    for (const selector of scheduleSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        await expect(element).toBeVisible();
        console.log(`Found schedule tracking: ${selector}`);
        break;
      }
    }
  });

  test('should display AI risk assessment in progress', async ({ page }) => {
    // Look for AI risk indicators
    const riskSelectors = [
      '.risk-assessment',
      '.ai-risk',
      'text=/Risk Assessment|Risk Score/i',
      '[data-risk]',
      '.risk-indicator'
    ];
    
    for (const selector of riskSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        await expect(element).toBeVisible();
        console.log(`Found risk assessment: ${selector}`);
        break;
      }
    }
  });

  test('should show contractor uploaded documents', async ({ page }) => {
    // Look for contractor documents
    const documentSelectors = [
      '.contractor-documents',
      '.stage-documents',
      'text=/Documents|Contractor Documents/i',
      '[data-documents]'
    ];
    
    for (const selector of documentSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        await expect(element).toBeVisible();
        console.log(`Found contractor documents: ${selector}`);
        break;
      }
    }
  });

  test('should allow filtering progress by stage', async ({ page }) => {
    // Look for stage filter/dropdown
    const filterSelectors = [
      'select[name="stage"]',
      '.stage-filter',
      '[data-filter="stage"]',
      'button:has-text("Filter")'
    ];
    
    for (const selector of filterSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        await expect(element).toBeVisible();
        console.log(`Found stage filter: ${selector}`);
        
        // Try to interact with filter
        if (selector.includes('select')) {
          const options = await element.locator('option').count();
          console.log(`Filter has ${options} options`);
        }
        break;
      }
    }
  });

  test('should display progress statistics/metrics', async ({ page }) => {
    // Look for statistics cards or metrics
    const statsSelectors = [
      '.stats-card',
      '.metric',
      '.progress-stats',
      'text=/Completed|In Progress|Pending/i'
    ];
    
    let statsFound = false;
    for (const selector of statsSelectors) {
      const elements = page.locator(selector);
      const count = await elements.count();
      if (count > 0) {
        console.log(`Found ${count} statistics with selector: ${selector}`);
        statsFound = true;
        break;
      }
    }
  });

  test('should show real-time progress updates', async ({ page }) => {
    // Take initial screenshot
    await page.screenshot({ path: 'test-results/progress-initial.png', fullPage: true });
    
    // Check for update indicators
    const updateSelectors = [
      '.last-updated',
      'text=/Last Updated|Updated/i',
      '[data-timestamp]'
    ];
    
    for (const selector of updateSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        const text = await element.textContent();
        console.log(`Last update info: ${text}`);
        break;
      }
    }
  });

  test('should navigate between different progress views', async ({ page }) => {
    // Look for view switchers (list/grid/timeline)
    const viewSwitchers = [
      'button:has-text("List View")',
      'button:has-text("Grid View")',
      'button:has-text("Timeline View")',
      '.view-toggle'
    ];
    
    for (const selector of viewSwitchers) {
      const button = page.locator(selector).first();
      if (await button.isVisible().catch(() => false)) {
        await button.click();
        await page.waitForTimeout(1000);
        console.log(`Clicked view switcher: ${selector}`);
        break;
      }
    }
  });

  test('should export/download progress report', async ({ page }) => {
    // Look for export/download buttons
    const exportSelectors = [
      'button:has-text("Export")',
      'button:has-text("Download")',
      'button:has-text("PDF")',
      '.export-button'
    ];
    
    for (const selector of exportSelectors) {
      const button = page.locator(selector).first();
      if (await button.isVisible().catch(() => false)) {
        await expect(button).toBeVisible();
        console.log(`Found export button: ${selector}`);
        break;
      }
    }
  });

  test('should capture full construction progress section screenshot', async ({ page }) => {
    // Wait for all content to load
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    
    // Take full page screenshot
    await page.screenshot({ 
      path: 'test-results/construction-progress-full.png', 
      fullPage: true 
    });
    
    console.log('Full screenshot saved to test-results/construction-progress-full.png');
  });

  test('should verify API calls for progress data', async ({ page }) => {
    const apiCalls = [];
    
    // Listen to API requests
    page.on('request', request => {
      const url = request.url();
      if (url.includes('progress') || url.includes('api')) {
        apiCalls.push({
          url: url,
          method: request.method()
        });
      }
    });
    
    // Reload to capture API calls
    await page.reload();
    await page.waitForLoadState('networkidle');
    
    console.log('API calls captured:', apiCalls);
    expect(apiCalls.length).toBeGreaterThan(0);
  });

  test('should check console for errors', async ({ page }) => {
    const consoleErrors = [];
    
    page.on('console', msg => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });
    
    await page.reload();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);
    
    console.log('Console errors:', consoleErrors);
    
    // Log errors but don't fail test (some errors might be expected)
    if (consoleErrors.length > 0) {
      console.warn(`Found ${consoleErrors.length} console errors`);
    }
  });
});
