import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  timeout: 90 * 1000, // Increased to 90s
  retries: 2, // Increased retries
  testDir: './tests',
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: 'http://localhost:3000',
    // Use more stable artifact settings for Windows
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    // Increased action timeout
    actionTimeout: 30 * 1000,
    // Increased navigation timeout
    navigationTimeout: 90 * 1000,
    // Add wait for load state
    waitForLoadState: 'networkidle',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:3000',
    reuseExistingServer: true,
    cwd: '.',
    timeout: 120 * 1000, // Increased server startup timeout
    // Add retry for server startup
    retries: 3,
  },
});


