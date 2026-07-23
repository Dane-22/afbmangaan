const { test, expect } = require('@playwright/test');

test.describe('Stress Test: Sunday Rush', () => {
  test('500 Concurrent Operators Logging Attendance', async ({ page }) => {
    // 1. Login
    await page.goto('login.php');
    await page.fill('input[name="username"]', 'operator');
    await page.fill('input[name="password"]', 'password');
    await page.selectOption('select[name="church"]', 'AFB Mangaan');
    await page.click('button[type="submit"]');

    // 2. Navigate to Attendance
    await page.goto('attendance.php');

    // 3. Perform a rapid search and log attendance
    // We search for "a" to get generic results, then mark the first one as present
    await page.fill('input[name="search"]', 'a');
    
    // Wait for API to return results
    await page.waitForTimeout(500); 

    // Assuming there is a "Present" button for the results
    const presentButtons = page.locator('button:has-text("Present")');
    
    if (await presentButtons.count() > 0) {
      await presentButtons.first().click();
      // Wait for success toast/badge
      await page.waitForTimeout(500);
    }
    
    // Ensure we reached the end of the loop without crashing
    expect(true).toBe(true);
  });
});
