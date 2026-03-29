const { test, expect } = require('@playwright/test');

// Real homeowner credentials
const HOMEOWNER_CREDENTIALS = {
  email: 'shijinthomas123@gmail.com',
  password: 'Shijin@123'
};

test.describe('Construction Progress - Detailed Feature Testing', () => {
  
  test.beforeEach(async ({ page }) => {
    // Go directly to homeowner dashboard
    await page.goto('http://localhost:3000/homeowner-dashboard');
    
    // Check if login is required
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

  test('should verify enhanced progress update component', async ({ page }) => {
    // Look for EnhancedProgressUpdate component features
    const enhancedFeatures = [
      'text=/Stage Progress/i',
      'text=/Materials Used/i',
      'text=/Workers Present/i',
      'text=/Weather Conditions/i'
    ];
    
    for (const selector of enhancedFeatures) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        console.log(`Found enhanced feature: ${selector}`);
      }
    }
  });

  test('should check for real-time progress percentage updates', async ({ page }) => {
    // Find progress percentage elements
    const progressElements = await page.locator('text=/\\d+%/').all();
    
    console.log(`Found ${progressElements.length} progress percentage indicators`);
    
    for (let i = 0; i < Math.min(progressElements.length, 5); i++) {
      const text = await progressElements[i].textContent();
      console.log(`Progress indicator ${i + 1}: ${text}`);
    }
    
    expect(progressElements.length).toBeGreaterThan(0);
  });

  test('should verify stage completion status indicators', async ({ page }) => {
    // Look for completion status badges/icons
    const statusIndicators = [
      '.status-badge',
      '.completion-status',
      'text=/Completed|In Progress|Not Started|Pending/i',
      '[data-status]'
    ];
    
    let foundStatuses = [];
    for (const selector of statusIndicators) {
      const elements = await page.locator(selector).all();
      for (const element of elements) {
        if (await element.isVisible().catch(() => false)) {
          const text = await element.textContent();
          foundStatuses.push(text.trim());
        }
      }
    }
    
    console.log('Found status indicators:', foundStatuses);
  });

  test('should test progress chart/graph visualization', async ({ page }) => {
    // Look for chart elements (canvas, svg)
    const chartSelectors = [
      'canvas',
      'svg',
      '.chart',
      '.progress-chart',
      '[data-chart]'
    ];
    
    for (const selector of chartSelectors) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        console.log(`Found chart element: ${selector}`);
        await expect(element).toBeVisible();
        
        // Take screenshot of chart
        await element.screenshot({ path: `test-results/chart-${selector.replace(/[^a-z]/gi, '')}.png` });
        break;
      }
    }
  });

  test('should verify contractor daily report details', async ({ page }) => {
    // Look for detailed daily report information
    const reportFields = [
      'text=/Work Description/i',
      'text=/Progress Percentage/i',
      'text=/Date/i',
      'text=/Stage/i',
      'text=/Notes/i'
    ];
    
    let foundFields = 0;
    for (const selector of reportFields) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        foundFields++;
        console.log(`Found report field: ${selector}`);
      }
    }
    
    console.log(`Found ${foundFields} out of ${reportFields.length} report fields`);
  });

  test('should check image gallery functionality', async ({ page }) => {
    // Look for image gallery or lightbox
    const images = page.locator('img[src*="progress"], img[src*="construction"], img[alt*="progress"]');
    const imageCount = await images.count();
    
    console.log(`Found ${imageCount} progress images`);
    
    if (imageCount > 0) {
      // Click first image to test lightbox/modal
      await images.first().click().catch(() => {});
      await page.waitForTimeout(1000);
      
      // Look for modal/lightbox
      const modal = page.locator('.modal, .lightbox, [role="dialog"]').first();
      if (await modal.isVisible().catch(() => false)) {
        console.log('Image modal/lightbox opened successfully');
        await page.screenshot({ path: 'test-results/image-modal.png' });
        
        // Close modal
        await page.keyboard.press('Escape');
      }
    }
  });

  test('should verify inspection report integration', async ({ page }) => {
    // Look for inspection report cards/items
    const inspectionElements = page.locator('.inspection-report, [data-inspection]');
    const count = await inspectionElements.count();
    
    console.log(`Found ${count} inspection reports`);
    
    if (count > 0) {
      // Click first inspection to view details
      await inspectionElements.first().click().catch(() => {});
      await page.waitForTimeout(1000);
      
      // Look for inspection details
      const detailFields = [
        'text=/Inspector Name/i',
        'text=/Inspection Date/i',
        'text=/Status/i',
        'text=/Findings/i'
      ];
      
      for (const selector of detailFields) {
        const element = page.locator(selector).first();
        if (await element.isVisible().catch(() => false)) {
          console.log(`Found inspection detail: ${selector}`);
        }
      }
    }
  });

  test('should test schedule tracking timeline', async ({ page }) => {
    // Look for timeline visualization
    const timelineElements = [
      '.timeline-item',
      '.milestone',
      '[data-timeline-item]',
      '.schedule-item'
    ];
    
    for (const selector of timelineElements) {
      const elements = page.locator(selector);
      const count = await elements.count();
      
      if (count > 0) {
        console.log(`Found ${count} timeline items with selector: ${selector}`);
        
        // Get details of first few items
        for (let i = 0; i < Math.min(count, 3); i++) {
          const text = await elements.nth(i).textContent();
          console.log(`Timeline item ${i + 1}: ${text?.substring(0, 100)}`);
        }
        break;
      }
    }
  });

  test('should verify AI risk assessment display', async ({ page }) => {
    // Look for risk score and factors
    const riskElements = [
      'text=/Risk Score/i',
      'text=/Cost Overrun/i',
      'text=/Time Delay/i',
      'text=/Risk Level/i',
      '.risk-score',
      '.risk-factor'
    ];
    
    let riskData = {};
    for (const selector of riskElements) {
      const element = page.locator(selector).first();
      if (await element.isVisible().catch(() => false)) {
        const text = await element.textContent();
        riskData[selector] = text?.trim();
        console.log(`Risk data - ${selector}: ${text?.trim()}`);
      }
    }
    
    console.log('Complete risk data:', riskData);
  });

  test('should check document preview functionality', async ({ page }) => {
    // Look for document links/buttons
    const documentLinks = page.locator('a[href*=".pdf"], button:has-text("View Document"), .document-link');
    const count = await documentLinks.count();
    
    console.log(`Found ${count} document links`);
    
    if (count > 0) {
      // Try to preview first document
      const firstDoc = documentLinks.first();
      const docText = await firstDoc.textContent();
      console.log(`First document: ${docText}`);
      
      // Click to preview (might open in new tab or modal)
      await firstDoc.click().catch(() => {});
      await page.waitForTimeout(2000);
    }
  });

  test('should verify responsive design on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });
    await page.reload();
    await page.waitForLoadState('networkidle');
    
    // Take mobile screenshot
    await page.screenshot({ path: 'test-results/progress-mobile.png', fullPage: true });
    
    // Check if mobile menu exists
    const mobileMenu = page.locator('.mobile-menu, .hamburger, [data-mobile-menu]').first();
    if (await mobileMenu.isVisible().catch(() => false)) {
      console.log('Mobile menu found');
      await mobileMenu.click();
      await page.waitForTimeout(1000);
    }
  });

  test('should test search/filter functionality', async ({ page }) => {
    // Look for search input
    const searchInput = page.locator('input[type="search"], input[placeholder*="Search"]').first();
    
    if (await searchInput.isVisible().catch(() => false)) {
      await searchInput.fill('foundation');
      await page.waitForTimeout(1000);
      
      console.log('Search functionality tested');
      await page.screenshot({ path: 'test-results/search-results.png' });
    }
  });

  test('should verify data refresh/reload functionality', async ({ page }) => {
    // Look for refresh button
    const refreshButton = page.locator('button:has-text("Refresh"), button[title*="Refresh"], .refresh-button').first();
    
    if (await refreshButton.isVisible().catch(() => false)) {
      await refreshButton.click();
      await page.waitForLoadState('networkidle');
      console.log('Data refresh triggered');
    }
  });

  test('should capture network activity for progress section', async ({ page }) => {
    const networkActivity = {
      requests: [],
      responses: []
    };
    
    page.on('request', request => {
      networkActivity.requests.push({
        url: request.url(),
        method: request.method(),
        resourceType: request.resourceType()
      });
    });
    
    page.on('response', response => {
      networkActivity.responses.push({
        url: response.url(),
        status: response.status(),
        statusText: response.statusText()
      });
    });
    
    await page.reload();
    await page.waitForLoadState('networkidle');
    
    console.log(`Captured ${networkActivity.requests.length} requests`);
    console.log(`Captured ${networkActivity.responses.length} responses`);
    
    // Log API calls
    const apiCalls = networkActivity.requests.filter(r => 
      r.url.includes('/api/') || r.url.includes('.php')
    );
    console.log('API calls:', apiCalls);
  });
});
