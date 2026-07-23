const { test, expect } = require('@playwright/test');

test.describe('Event Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.selectOption('select[name="church"]', 'AFB Mangaan');
    await page.click('button[type="submit"]');
  });

  test('Create a new event', async ({ page }) => {
    await page.goto('events.php?action=create');
    
    const eventName = `Test Event ${Date.now()}`;
    await page.fill('input[name="event_name"]', eventName);
    await page.fill('input[name="start_date"]', '2025-12-31');
    await page.fill('input[name="event_time"]', '10:00');
    await page.selectOption('select[name="type"]', 'Sunday Service');
    
    await page.click('button[type="submit"]');
    
    // Should show success message and redirect back to events list
    await expect(page.locator('.badge-success')).toBeVisible();
    await expect(page.locator('.badge-success')).toContainText('created successfully');
  });
});
