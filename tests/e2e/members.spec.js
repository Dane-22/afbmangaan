const { test, expect } = require('@playwright/test');

test.describe('Member Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login before each test
    await page.goto('login.php');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.selectOption('select[name="church"]', 'AFB Mangaan');
    await page.click('button[type="submit"]');
  });

  test('Add a new member', async ({ page }) => {
    await page.goto('members.php?action=add');
    
    const uniqueId = Date.now().toString().slice(-6);
    await page.fill('#addMemberForm input[name="fullname"]', `Test User ${uniqueId}`);
    await page.selectOption('select[name="category"]', 'Youth');
    
    await page.click('button[type="submit"]');
    
    await expect(page.locator('.badge-success')).toBeVisible();
    await expect(page.locator('.badge-success')).toContainText('successfully');
  });
});
