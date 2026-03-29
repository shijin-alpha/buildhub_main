const { test, expect } = require('@playwright/test');

const ARCHITECT_CREDENTIALS = {
  email: 'saviojoseph2026@mca.ajce.in',
  password: 'Savio@123'
};

test.describe('Architect Design Editor Tests', () => {
  
  test.beforeEach(async ({ page }) => {
    // Navigate to login page
    await page.goto('http://localhost:3000/login');
    await page.waitForLoadState('domcontentloaded');
    
    // Login as architect
    console.log('Logging in as architect:', ARCHITECT_CREDENTIALS.email);
    await page.fill('input[type="email"], input[name="email"]', ARCHITECT_CREDENTIALS.email);
    await page.fill('input[type="password"], input[name="password"]', ARCHITECT_CREDENTIALS.password);
    
    // Click login button
    await page.click('button[type="submit"], button:has-text("Login")');
    
    // Wait for navigation to dashboard
    await page.waitForTimeout(3000);
    console.log('Current URL after login:', page.url());
    
    // Take screenshot after login
    await page.screenshot({ path: 'test-results/architect-after-login.png', fullPage: true });
  });

  test('should load architect dashboard successfully', async ({ page }) => {
    console.log('Testing architect dashboard load...');
    
    // Verify we're on the architect dashboard
    expect(page.url()).toContain('architect');
    
    // Check for dashboard elements
    const dashboardVisible = await page.isVisible('text=Dashboard');
    expect(dashboardVisible).toBeTruthy();
    
    await page.screenshot({ path: 'test-results/architect-dashboard-loaded.png', fullPage: true });
    console.log('✓ Dashboard loaded successfully');
  });

  test('should display quick action buttons and verify clickability', async ({ page }) => {
    console.log('Testing quick action buttons...');
    
    // Wait for quick actions to load
    await page.waitForSelector('.quick-actions', { timeout: 10000 });
    
    // Check for "Upload New Design" button
    const uploadDesignButton = page.locator('button:has-text("Upload New Design")');
    await expect(uploadDesignButton).toBeVisible();
    console.log('✓ Upload New Design button is visible');
    
    // Verify button is clickable
    await expect(uploadDesignButton).toBeEnabled();
    console.log('✓ Upload New Design button is clickable');
    
    // Check for "Browse Requests" button
    const browseRequestsButton = page.locator('button:has-text("Browse Requests")');
    await expect(browseRequestsButton).toBeVisible();
    await expect(browseRequestsButton).toBeEnabled();
    console.log('✓ Browse Requests button is visible and clickable');
    
    // Check for "My Portfolio" button
    const portfolioButton = page.locator('button:has-text("My Portfolio")');
    await expect(portfolioButton).toBeVisible();
    await expect(portfolioButton).toBeEnabled();
    console.log('✓ My Portfolio button is visible and clickable');
    
    await page.screenshot({ path: 'test-results/architect-quick-actions.png', fullPage: true });
  });

  test('should click Upload New Design and navigate to requests tab', async ({ page }) => {
    console.log('Testing Upload New Design button click...');
    
    // Wait for and click Upload New Design button
    await page.waitForSelector('button:has-text("Upload New Design")', { timeout: 10000 });
    await page.click('button:has-text("Upload New Design")');
    
    // Wait for navigation/tab change
    await page.waitForTimeout(2000);
    
    // Verify we're on the requests tab
    const requestsTabActive = await page.isVisible('text=Assigned Requests');
    expect(requestsTabActive).toBeTruthy();
    
    await page.screenshot({ path: 'test-results/architect-requests-tab.png', fullPage: true });
    console.log('✓ Successfully navigated to requests tab');
  });

  test('should display and interact with assigned requests', async ({ page }) => {
    console.log('Testing assigned requests section...');
    
    // Navigate to requests tab
    await page.click('button:has-text("Browse Requests")');
    await page.waitForTimeout(2000);
    
    // Check if there are any assigned requests
    const hasRequests = await page.isVisible('.request-card, .project-card');
    
    if (hasRequests) {
      console.log('✓ Assigned requests found');
      
      // Check for Create Design button
      const createDesignButton = page.locator('button:has-text("Create Design")').first();
      if (await createDesignButton.isVisible()) {
        await expect(createDesignButton).toBeEnabled();
        console.log('✓ Create Design button is clickable');
      }
      
      // Check for Upload Design button (📤 Upload Design)
      const uploadDesignButton = page.locator('button:has-text("📤 Upload Design")').first();
      if (await uploadDesignButton.isVisible()) {
        await expect(uploadDesignButton).toBeEnabled();
        console.log('✓ Upload Design button (📤) is clickable');
      }
    } else {
      console.log('ℹ No assigned requests found');
    }
    
    await page.screenshot({ path: 'test-results/architect-assigned-requests.png', fullPage: true });
  });

  test('should click Upload Design button and open technical details modal', async ({ page }) => {
    console.log('Testing Upload Design modal...');
    
    // Navigate to requests tab
    await page.click('button:has-text("Browse Requests")');
    await page.waitForTimeout(2000);
    
    // Check if Upload Design button exists
    const uploadButton = page.locator('button:has-text("📤 Upload Design")').first();
    
    if (await uploadButton.isVisible()) {
      console.log('✓ Upload Design button found');
      
      // Click the button
      await uploadButton.click();
      await page.waitForTimeout(2000);
      
      // Check if modal opened
      const modalVisible = await page.isVisible('.modal, .form-modal, [role="dialog"]');
      
      if (modalVisible) {
        console.log('✓ Technical details modal opened');
        await page.screenshot({ path: 'test-results/architect-upload-modal-opened.png', fullPage: true });
        
        // Check for modal close button
        const closeButton = page.locator('button:has-text("Close"), button:has-text("Cancel")').first();
        if (await closeButton.isVisible()) {
          await expect(closeButton).toBeEnabled();
          console.log('✓ Modal close button is clickable');
        }
      } else {
        console.log('ℹ Modal did not open (might need accepted project)');
      }
    } else {
      console.log('ℹ No Upload Design button found (no accepted projects)');
    }
    
    await page.screenshot({ path: 'test-results/architect-upload-design-test.png', fullPage: true });
  });

  test('should navigate to My Portfolio tab', async ({ page }) => {
    console.log('Testing My Portfolio navigation...');
    
    // Click My Portfolio button
    await page.click('button:has-text("My Portfolio")');
    await page.waitForTimeout(2000);
    
    // Verify we're on the designs tab
    const portfolioVisible = await page.isVisible('text=My Designs, text=My Portfolio');
    expect(portfolioVisible).toBeTruthy();
    
    await page.screenshot({ path: 'test-results/architect-portfolio.png', fullPage: true });
    console.log('✓ Successfully navigated to portfolio');
  });

  test('should test Create Design button functionality', async ({ page }) => {
    console.log('Testing Create Design button...');
    
    // Navigate to requests
    await page.click('button:has-text("Browse Requests")');
    await page.waitForTimeout(2000);
    
    // Look for Create Design button
    const createButton = page.locator('button:has-text("Create Design")').first();
    
    if (await createButton.isVisible()) {
      console.log('✓ Create Design button found');
      
      // Verify it's clickable
      await expect(createButton).toBeEnabled();
      console.log('✓ Create Design button is clickable');
      
      // Click it
      await createButton.click();
      await page.waitForTimeout(2000);
      
      // Check if house plans tab or design editor opened
      const designEditorOpened = await page.isVisible('text=House Plans, text=Design Editor, text=Create Design');
      
      if (designEditorOpened) {
        console.log('✓ Design editor/house plans opened');
        await page.screenshot({ path: 'test-results/architect-design-editor-opened.png', fullPage: true });
      } else {
        console.log('ℹ Design editor did not open as expected');
      }
    } else {
      console.log('ℹ No Create Design button found');
    }
    
    await page.screenshot({ path: 'test-results/architect-create-design-test.png', fullPage: true });
  });

  test('should verify all navigation tabs are clickable', async ({ page }) => {
    console.log('Testing navigation tabs...');
    
    const tabs = [
      'Dashboard',
      'Requests',
      'My Designs',
      'House Plans',
      'My Library'
    ];
    
    for (const tabName of tabs) {
      const tabButton = page.locator(`button:has-text("${tabName}"), a:has-text("${tabName}")`).first();
      
      if (await tabButton.isVisible()) {
        await expect(tabButton).toBeEnabled();
        console.log(`✓ ${tabName} tab is clickable`);
        
        // Click the tab
        await tabButton.click();
        await page.waitForTimeout(1500);
        
        await page.screenshot({ path: `test-results/architect-tab-${tabName.toLowerCase().replace(' ', '-')}.png`, fullPage: true });
      } else {
        console.log(`ℹ ${tabName} tab not found`);
      }
    }
  });

  test('should test refresh dashboard button', async ({ page }) => {
    console.log('Testing refresh dashboard button...');
    
    // Look for refresh button
    const refreshButton = page.locator('button:has-text("Refresh"), button:has-text("🔄")').first();
    
    if (await refreshButton.isVisible()) {
      console.log('✓ Refresh button found');
      
      // Verify it's clickable
      await expect(refreshButton).toBeEnabled();
      console.log('✓ Refresh button is clickable');
      
      // Click it
      await refreshButton.click();
      await page.waitForTimeout(2000);
      
      console.log('✓ Refresh button clicked successfully');
    } else {
      console.log('ℹ Refresh button not found');
    }
    
    await page.screenshot({ path: 'test-results/architect-refresh-test.png', fullPage: true });
  });

  test('should verify profile button is clickable', async ({ page }) => {
    console.log('Testing profile button...');
    
    // Look for profile button
    const profileButton = page.locator('.header-profile, button[class*="profile"], [class*="ProfileButton"]').first();
    
    if (await profileButton.isVisible()) {
      console.log('✓ Profile button found');
      
      // Click it
      await profileButton.click();
      await page.waitForTimeout(1500);
      
      // Check if profile menu/drawer opened
      const profileMenuVisible = await page.isVisible('[class*="profile-menu"], [class*="profile-drawer"], text=Logout');
      
      if (profileMenuVisible) {
        console.log('✓ Profile menu opened');
      }
      
      await page.screenshot({ path: 'test-results/architect-profile-menu.png', fullPage: true });
    } else {
      console.log('ℹ Profile button not found');
    }
  });

  test('should test complete design editor workflow', async ({ page }) => {
    console.log('Testing complete design editor workflow...');
    
    // Step 1: Navigate to requests
    await page.click('button:has-text("Browse Requests")');
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'test-results/workflow-step1-requests.png', fullPage: true });
    console.log('✓ Step 1: Navigated to requests');
    
    // Step 2: Check for accepted projects
    const hasAcceptedProjects = await page.isVisible('text=Accepted Project');
    
    if (hasAcceptedProjects) {
      console.log('✓ Step 2: Found accepted projects');
      
      // Step 3: Click Upload Design button
      const uploadButton = page.locator('button:has-text("📤 Upload Design")').first();
      if (await uploadButton.isVisible()) {
        await uploadButton.click();
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'test-results/workflow-step3-upload-clicked.png', fullPage: true });
        console.log('✓ Step 3: Clicked Upload Design button');
        
        // Step 4: Verify modal opened
        const modalOpened = await page.isVisible('.modal, .form-modal, [role="dialog"]');
        if (modalOpened) {
          console.log('✓ Step 4: Technical details modal opened');
          
          // Step 5: Check for form fields
          const hasFormFields = await page.isVisible('input, textarea, select');
          if (hasFormFields) {
            console.log('✓ Step 5: Form fields are present');
          }
          
          await page.screenshot({ path: 'test-results/workflow-step5-modal-form.png', fullPage: true });
        }
      }
    } else {
      console.log('ℹ No accepted projects found for workflow test');
    }
    
    await page.screenshot({ path: 'test-results/workflow-complete.png', fullPage: true });
  });

  test('should test Create New Plan button in House Plans section', async ({ page }) => {
    console.log('Testing Create New Plan button in House Plans section...');
    
    // Step 1: Navigate to House Plans tab
    const housePlansTab = page.locator('button:has-text("House Plans"), a:has-text("House Plans")').first();
    
    if (await housePlansTab.isVisible()) {
      console.log('✓ House Plans tab found');
      await housePlansTab.click();
      await page.waitForTimeout(2000);
      await page.screenshot({ path: 'test-results/house-plans-tab-opened.png', fullPage: true });
      console.log('✓ Navigated to House Plans section');
      
      // Step 2: Look for Create New Plan button
      const createNewPlanButton = page.locator('button.create-btn:has-text("Create New Plan")');
      
      if (await createNewPlanButton.isVisible()) {
        console.log('✓ Create New Plan button found');
        
        // Step 3: Verify button is enabled
        await expect(createNewPlanButton).toBeEnabled();
        console.log('✓ Create New Plan button is enabled');
        
        // Step 4: Click the button
        await createNewPlanButton.click();
        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'test-results/create-new-plan-clicked.png', fullPage: true });
        console.log('✓ Create New Plan button clicked successfully');
        
        // Step 5: Check if design editor or modal opened
        const editorOpened = await page.isVisible('text=Design Editor, text=Create Design, .design-editor, .plan-editor, [role="dialog"]');
        
        if (editorOpened) {
          console.log('✓ Design editor or modal opened after clicking Create New Plan');
          await page.screenshot({ path: 'test-results/create-new-plan-editor-opened.png', fullPage: true });
        } else {
          console.log('ℹ Design editor did not open (checking for other UI changes)');
          
          // Check for any form or input fields that might have appeared
          const hasNewContent = await page.isVisible('input, textarea, canvas, .editor-canvas');
          if (hasNewContent) {
            console.log('✓ New content appeared after clicking Create New Plan');
          }
        }
      } else {
        console.log('ℹ Create New Plan button not found in House Plans section');
        
        // Take screenshot to see what's available
        await page.screenshot({ path: 'test-results/house-plans-section-no-button.png', fullPage: true });
      }
    } else {
      console.log('ℹ House Plans tab not found');
      await page.screenshot({ path: 'test-results/no-house-plans-tab.png', fullPage: true });
    }
    
    await page.screenshot({ path: 'test-results/create-new-plan-test-complete.png', fullPage: true });
  });

});
