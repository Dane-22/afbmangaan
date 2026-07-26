# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: dashboard.spec.js >> Dashboard Analytics & Reporting >> Reports page loads and generates data
- Location: tests\e2e\dashboard.spec.js:28:3

# Error details

```
Test timeout of 30000ms exceeded.
```

```
Error: page.selectOption: Test timeout of 30000ms exceeded.
Call log:
  - waiting for locator('select[name="category"]')

```

# Page snapshot

```yaml
- table [ref=e3]:
  - rowgroup [ref=e4]:
    - 'row "( ! ) Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column ''event_date'' in ''where clause'' in C:\\wamp64\\www\\afbmangaan\\functions\\report_engine.php on line 88" [ref=e5]':
      - 'columnheader "( ! ) Fatal error: Uncaught PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column ''event_date'' in ''where clause'' in C:\\wamp64\\www\\afbmangaan\\functions\\report_engine.php on line 88" [ref=e6]'
    - 'row "( ! ) PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column ''event_date'' in ''where clause'' in C:\\wamp64\\www\\afbmangaan\\functions\\report_engine.php on line 88" [ref=e7]':
      - 'columnheader "( ! ) PDOException: SQLSTATE[42S22]: Column not found: 1054 Unknown column ''event_date'' in ''where clause'' in C:\\wamp64\\www\\afbmangaan\\functions\\report_engine.php on line 88" [ref=e8]'
    - row "Call Stack" [ref=e9]:
      - columnheader "Call Stack" [ref=e10]
    - row "# Time Memory Function Location" [ref=e11]:
      - columnheader "#" [ref=e12]
      - columnheader "Time" [ref=e13]
      - columnheader "Memory" [ref=e14]
      - columnheader "Function" [ref=e15]
      - columnheader "Location" [ref=e16]
    - 'row "1 0.0002 448064 {main}( ) ...\\reports.php:0" [ref=e17]':
      - cell "1" [ref=e18]
      - cell "0.0002" [ref=e19]
      - cell "448064" [ref=e20]
      - 'cell "{main}( )" [ref=e21]'
      - cell "...\\reports.php:0" [ref=e22]
    - row "2 0.0051 588576 getReportSummary( $fromDate = '2026-06-25', $toDate = '2026-07-25' ) ...\\reports.php:18" [ref=e23]:
      - cell "2" [ref=e24]
      - cell "0.0051" [ref=e25]
      - cell "588576" [ref=e26]
      - cell "getReportSummary( $fromDate = '2026-06-25', $toDate = '2026-07-25' )" [ref=e27]
      - cell "...\\reports.php:18" [ref=e28]
    - row "3 0.0051 589032 prepare( $query = 'SELECT COUNT(*) as total_events FROM events WHERE 1=1 AND event_date >= ? AND event_date <= ?' ) ...\\report_engine.php:88" [ref=e29]:
      - cell "3" [ref=e30]
      - cell "0.0051" [ref=e31]
      - cell "589032" [ref=e32]
      - cell "prepare( $query = 'SELECT COUNT(*) as total_events FROM events WHERE 1=1 AND event_date >= ? AND event_date <= ?' )" [ref=e33]:
        - link "prepare" [ref=e34] [cursor=pointer]:
          - /url: http://www.php.net/PDO.prepare
        - text: ( $query = 'SELECT COUNT(*) as total_events FROM events WHERE 1=1 AND event_date >= ? AND event_date <= ?' )
      - cell "...\\report_engine.php:88" [ref=e35]
```

# Test source

```ts
  1  | const { test, expect } = require('@playwright/test');
  2  | 
  3  | test.describe('Dashboard Analytics & Reporting', () => {
  4  |   test.beforeEach(async ({ page }) => {
  5  |     // Login before each test
  6  |     await page.goto('login.php');
  7  |     await page.fill('input[name="username"]', 'admin');
  8  |     await page.fill('input[name="password"]', 'admin123');
  9  |     await page.selectOption('select[name="church"]', 'AFB Mangaan');
  10 |     await page.click('button[type="submit"]');
  11 |     await expect(page).toHaveURL(/.*dashboard\.php/);
  12 |   });
  13 | 
  14 |   test('Dashboard loads all data and charts without errors', async ({ page }) => {
  15 |     // Check key stats cards are present
  16 |     await expect(page.locator('.stats-grid')).toBeVisible();
  17 |     await expect(page.locator('#totalMembers')).not.toBeEmpty();
  18 | 
  19 |     // Wait for network idle to ensure charts have fetched their APIs
  20 |     await page.waitForLoadState('networkidle');
  21 | 
  22 |     // Check if chart canvases are present
  23 |     await expect(page.locator('#attendanceTrendChart')).toBeVisible();
  24 |     await expect(page.locator('#categoryDistributionChart')).toBeVisible();
  25 |     await expect(page.locator('#retentionChart')).toBeVisible();
  26 |   });
  27 | 
  28 |   test('Reports page loads and generates data', async ({ page }) => {
  29 |     await page.goto('reports.php');
  30 |     await page.waitForLoadState('domcontentloaded');
  31 |     await expect(page).toHaveURL(/.*reports\.php/);
  32 |     
  33 |     // Select category and generate report
> 34 |     await page.selectOption('select[name="category"]', 'WMO', { force: true });
     |                ^ Error: page.selectOption: Test timeout of 30000ms exceeded.
  35 |     await page.click('form button[type="submit"]', { force: true });
  36 | 
  37 |     // Ensure no fatal errors occur and report table/data loads
  38 |     await expect(page.locator('.card').first()).toBeVisible();
  39 |   });
  40 | });
  41 | 
```