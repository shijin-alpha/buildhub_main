// Authentication helper functions for tests

async function loginAsHomeowner(page, email = 'shijinthomas123@gmail.com', password = 'Shijin@123') {
  await page.goto('http://localhost:3000/homeowner-dashboard');
  
  const currentUrl = page.url();
  if (currentUrl.includes('login')) {
    await page.fill('input[type="email"], input[name="email"]', email);
    await page.fill('input[type="password"], input[name="password"]', password);
    await page.click('button[type="submit"], button:has-text("Login")');
    await page.waitForURL('**/homeowner-dashboard', { timeout: 10000 });
  }
  
  await page.waitForLoadState('networkidle');
}

async function loginAsContractor(page, email = 'contractor@example.com', password = 'password123') {
  await page.goto('/frontend/contractor-dashboard.html');
  await page.fill('input[type="email"]', email);
  await page.fill('input[type="password"]', password);
  await page.click('button[type="submit"]');
}

async function loginAsInspector(page, email = 'inspector@example.com', password = 'password123') {
  await page.goto('/frontend/site-inspector-dashboard.html');
  await page.fill('input[type="email"]', email);
  await page.fill('input[type="password"]', password);
  await page.click('button[type="submit"]');
}

module.exports = {
  loginAsHomeowner,
  loginAsContractor,
  loginAsInspector,
};
