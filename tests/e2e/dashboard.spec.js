const { test, expect } = require('@playwright/test');

test.describe('Dashboard Analytics & Reporting', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.selectOption('select[name="church"]', 'AFB Mangaan');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/.*dashboard\.php/);
  });

  test('Dashboard loads all data and charts without errors', async ({ page }) => {
    // Check key stats cards are present
    await expect(page.locator('.stats-grid')).toBeVisible();
    await expect(page.locator('#totalMembers')).not.toBeEmpty();

    // Wait for network idle to ensure charts have fetched their APIs
    await page.waitForLoadState('networkidle');

    // Check if chart canvases are present
    await expect(page.locator('#attendanceTrendChart')).toBeVisible();
    await expect(page.locator('#categoryDistributionChart')).toBeVisible();
    await expect(page.locator('#retentionChart')).toBeVisible();
  });

  test('Reports page loads and generates data', async ({ page }) => {
    await page.goto('reports.php');
    await expect(page).toHaveURL(/.*reports\.php/);
    
    // Select date and category
    await page.fill('input[name="from_date"]', '2025-01-01');
    await page.selectOption('select[name="category"]', 'Youth');
    await page.click('button[type="submit"]');

    // Ensure no fatal errors occur and report table/data loads
    await expect(page.locator('.report-card').first()).toBeVisible();
  });
});
