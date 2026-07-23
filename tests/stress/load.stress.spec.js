const { test, expect } = require('@playwright/test');

test.describe('Stress Test: Dashboard Analytics', () => {
  test('Concurrent Admin Dashboard Loading', async ({ page }) => {
    // 1. Login
    await page.goto('login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.selectOption('select[name="church"]', 'AFB Mangaan');
    await page.click('button[type="submit"]');

    // 2. Load Dashboard
    await page.goto('dashboard.php');
    
    // 3. Verify it loads without crashing
    await expect(page.locator('.stat-card').first()).toBeVisible({ timeout: 15000 });
    
    // 4. Load Reports Page
    await page.goto('reports.php');
    
    // 5. Generate Report
    await page.fill('input[name="from_date"]', '2025-01-01');
    await page.click('button[type="submit"]');
    
    // Ensure the table loads
    await expect(page.locator('table.table').first()).toBeVisible({ timeout: 15000 });
  });
});
