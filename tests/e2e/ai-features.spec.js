const { test, expect } = require('@playwright/test');

test.describe('AI Features', () => {
  test('should generate room improvement suggestions', async ({ page }) => {
    await page.goto('/frontend/room-improvement.html');
    
    await page.fill('input[name="room_type"]', 'Living Room');
    await page.fill('input[name="budget"]', '10000');
    await page.click('button:has-text("Generate Ideas")');
    
    await expect(page.locator('.ai-suggestions')).toBeVisible({ timeout: 10000 });
  });

  test('should display risk assessment', async ({ page }) => {
    await page.goto('/test_risk_assessment_frontend.html');
    
    await expect(page.locator('.risk-score')).toBeVisible();
    await expect(page.locator('.risk-factors')).toBeVisible();
  });

  test('should show schedule tracking', async ({ page }) => {
    await page.goto('/test_schedule_tracking_system.html');
    
    await expect(page.locator('.schedule-timeline')).toBeVisible();
    await expect(page.locator('.milestone-indicator')).toBeVisible();
  });

  test('should perform AI self-evaluation', async ({ page }) => {
    await page.goto('/test_ai_self_evaluation_system.html');
    
    await page.click('button:has-text("Run Evaluation")');
    await expect(page.locator('.evaluation-results')).toBeVisible({ timeout: 15000 });
  });
});
