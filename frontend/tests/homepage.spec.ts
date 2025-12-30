import { test, expect } from '@playwright/test';

test('homepage loads and shows key sections', async ({ page }) => {
  await page.goto('/');

  await expect(page.locator('header.hero-header')).toBeVisible();
  await expect(page.getByRole('heading', { name: 'BuildHub – Smart Construction Platform' })).toBeVisible();

  // Navigate to Login via header link
  await page.getByRole('link', { name: 'Login' }).click();
  await expect(page).toHaveURL(/\/login$/);
});



