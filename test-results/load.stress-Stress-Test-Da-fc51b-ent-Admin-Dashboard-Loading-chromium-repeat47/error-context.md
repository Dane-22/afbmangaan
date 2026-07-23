# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: load.stress.spec.js >> Stress Test: Dashboard Analytics >> Concurrent Admin Dashboard Loading
- Location: tests\stress\load.stress.spec.js:4:3

# Error details

```
Error: page.goto: net::ERR_ABORTED at http://localhost/afbmangaan/login.php
Call log:
  - navigating to "http://localhost/afbmangaan/login.php", waiting until "load"

```

# Test source

```ts
  1  | const { test, expect } = require('@playwright/test');
  2  | 
  3  | test.describe('Stress Test: Dashboard Analytics', () => {
  4  |   test('Concurrent Admin Dashboard Loading', async ({ page }) => {
  5  |     // 1. Login
> 6  |     await page.goto('login.php');
     |                ^ Error: page.goto: net::ERR_ABORTED at http://localhost/afbmangaan/login.php
  7  |     await page.fill('input[name="username"]', 'admin');
  8  |     await page.fill('input[name="password"]', 'admin123');
  9  |     await page.selectOption('select[name="church"]', 'AFB Mangaan');
  10 |     await page.click('button[type="submit"]');
  11 | 
  12 |     // 2. Load Dashboard
  13 |     await page.goto('dashboard.php');
  14 |     
  15 |     // 3. Verify it loads without crashing
  16 |     await expect(page.locator('.stat-card').first()).toBeVisible({ timeout: 15000 });
  17 |     
  18 |     // 4. Load Reports Page
  19 |     await page.goto('reports.php');
  20 |     
  21 |     // 5. Generate Report
  22 |     await page.fill('input[name="from_date"]', '2025-01-01');
  23 |     await page.click('button[type="submit"]');
  24 |     
  25 |     // Ensure the table loads
  26 |     await expect(page.locator('table.table').first()).toBeVisible({ timeout: 15000 });
  27 |   });
  28 | });
  29 | 
```