const { test, expect } = require('@playwright/test');

test.describe('Authentication & Security', () => {
  
  test('Successful login and logout flow', async ({ page }) => {
    // Navigate to login page
    await page.goto('login.php');
    
    // Fill credentials
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.selectOption('select[name="church"]', 'AFB Mangaan');
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Should be redirected to dashboard
    await expect(page).toHaveURL(/.*dashboard\.php/);
    
    // Check if church indicator is visible
    await expect(page.locator('.church-indicator')).toBeVisible();
    
    // Test logout
    await page.click('#userDropdownBtn');
    await page.click('text=Logout');
    
    // Should be back to login
    await expect(page).toHaveURL(/.*index\.php/);
  });

  test('Invalid login shows error message', async ({ page }) => {
    await page.goto('login.php');
    
    // Fill invalid credentials
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'wrongpassword');
    await page.selectOption('select[name="church"]', 'AFB Mangaan');
    
    // Submit form
    await page.click('button[type="submit"]');
    
    // Error message should be visible
    const errorAlert = page.locator('.alert-error');
    await expect(errorAlert).toBeVisible();
    await expect(errorAlert).toContainText('Invalid username, password, or church');
  });

  test('Protected routes redirect unauthenticated users', async ({ page }) => {
    // Try to access dashboard directly
    await page.goto('dashboard.php');
    
    // Should be redirected to login
    await expect(page).toHaveURL(/.*index\.php/);
    
    // Try to access members directly
    await page.goto('members.php');
    await expect(page).toHaveURL(/.*index\.php/);
  });
});
