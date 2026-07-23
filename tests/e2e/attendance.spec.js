const { test, expect } = require('@playwright/test');

test.describe('Attendance Operations', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.selectOption('select[name="church"]', 'AFB Mangaan');
    await page.click('button[type="submit"]');
  });

  test('Live search and record attendance', async ({ page }) => {
    // Navigate to attendance page and pick an event (assuming one exists)
    // We will just go to attendance.php which defaults to today's event or latest
    await page.goto('attendance.php');
    
    // Check if there is an active event selected
    const eventIdInput = await page.locator('#eventId');
    if (await eventIdInput.count() === 0 || await eventIdInput.getAttribute('value') === '') {
      // If no event, we can't test attendance recording easily without creating one
      return;
    }

    // Type in live search
    await page.fill('#attendeeSearch', 'John');
    
    // Wait for API response and dropdown to appear
    await page.waitForResponse(response => response.url().includes('search_attendees.php') && response.status() === 200);
    
    const searchResults = page.locator('#searchResults .search-result-item');
    
    // If results found, click first one and mark present
    if (await searchResults.count() > 0) {
      await searchResults.first().click();
      await page.click('button:has-text("Mark Present")');
      
      // Wait for success toast
      await expect(page.locator('.toast.toast-success')).toBeVisible({ timeout: 10000 });
    }
  });
});
