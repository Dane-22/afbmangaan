// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/stress',
  fullyParallel: true,
  retries: 0,
  repeatEach: 100, // Run each test 100 times
  workers: 100, // 100 Concurrent Operators
  timeout: 120000,
  reporter: 'html',
  use: {
    baseURL: 'http://localhost/afbmangaan/',
    trace: 'off', // Turn off trace to save memory
    video: 'off', // Turn off video to save memory
    headless: true, // Must be headless for 500 workers
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    }
  ],
});
