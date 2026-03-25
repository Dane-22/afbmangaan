# Google Sheets Integration via make.com - Setup Guide

This guide explains how to connect your AFB Mangaan Attendance System with Google Sheets using make.com (formerly Integromat).

---

## Architecture Overview

```
┌─────────────────┐         ┌──────────────┐         ┌─────────────────┐
│  Google Sheets  │ ──────► │  make.com    │ ──────► │  PHP Webhook    │
│  (Data Source)  │         │  (Bridge)    │         │  (Import API)   │
└─────────────────┘         └──────────────┘         └─────────────────┘
                                                              │
                                                              ▼
                                                       ┌──────────────┐
                                                       │   MySQL DB   │
                                                       └──────────────┘
```

**Two-way flow:**
1. **Import (Sheets → PHP)**: Bulk import members/events from Google Sheets
2. **Export (PHP → Sheets)**: Sync attendance records to Google Sheets for reporting

---

## Prerequisites

1. **make.com account** (free tier: 1,000 operations/month)
2. **Google Sheets** with your data
3. **Publicly accessible PHP server** (make.com needs to reach your webhook)

---

## Part 1: Configure PHP Application

### Step 1: Set Webhook Secret

Edit `config/webhook.php`:

```php
$webhookConfig = [
    'secret' => 'your-very-long-random-secret-key-here-32-chars',
    'make_webhook_url' => null, // Will set later
    'enable_logging' => true,
];
```

**Generate a strong secret:**
```bash
openssl rand -base64 32
```

### Step 2: Verify Webhook Endpoint

The endpoint is already created at `api/webhook_import.php`.

Test it works:
1. Visit `http://localhost/afb_mangaan/webhook_test.php`
2. Or run: `php tests/webhook_test.php`

---

## Part 2: Setup Google Sheets

### Create Your Import Sheet

Create a Google Sheet with columns for members:

| fullname | category | contact | email | qr_token | status |
|----------|----------|---------|-------|----------|--------|
| Juan Cruz | Adult | 09123456789 | juan@email.com | AFB001 | Active |
| Maria Santos | Youth | 09187654321 | maria@email.com | | Active |

**Notes:**
- `qr_token` can be left empty (auto-generated)
- `category` options: Youth, Adult, Senior, Child
- `status` options: Active, Archived

### Create Your Export Sheet (Optional)

For receiving attendance data from PHP:

| Timestamp | Event | Event Date | Attendee | Category | Status | Method |
|-----------|-------|------------|----------|----------|--------|--------|
| | | | | | | |

---

## Part 3: Setup make.com Scenario (Import: Sheets → PHP)

### Step 1: Create New Scenario

1. Log in to [make.com](https://www.make.com)
2. Click **"Create a new scenario"**
3. Click the **+** button to add first module

### Step 2: Add Google Sheets Trigger

1. Search for **"Google Sheets"**
2. Select **"Watch Rows"** trigger
3. Connect your Google account
4. Select your spreadsheet and sheet
5. Set **"Trigger"** to "On new row" or "On updated row"
6. Specify the **row range** (e.g., A:F for member data)
7. Click **OK**

### Step 3: Add HTTP Module (Make a Request)

1. Click **+** to add next module
2. Search for **"HTTP"**
3. Select **"Make a request"**

Configure:
- **URL**: `https://your-domain.com/api/webhook_import.php`
  - Replace with your actual domain (ngrok for localhost)
- **Method**: `POST`
- **Body type**: `Raw`
- **Content type**: `JSON (application/json)`
- **Request content**:

```json
{
  "entity": "attendee",
  "action": "create",
  "data": {
    "fullname": "{{1.fullname}}",
    "category": "{{1.category}}",
    "contact": "{{1.contact}}",
    "email": "{{1.email}}",
    "qr_token": "{{1.qr_token}}",
    "status": "{{1.status}}"
  }
}
```

**Note:** `{{1.fullname}}` references the data from the Google Sheets module (number may vary).

### Step 4: Add Headers

Click **"Show advanced settings"** → **"Headers"**:

```
X-Webhook-Secret: your-very-long-random-secret-key-here-32-chars
Content-Type: application/json
```

### Step 5: Save and Test

1. Click **"Save"** (disk icon)
2. Click **"Run once"** (play button)
3. Add a row to your Google Sheet
4. Check make.com execution log for results

---

## Part 4: Setup make.com Scenario (Export: PHP → Sheets)

This syncs attendance records FROM your PHP app TO Google Sheets.

### Step 1: Get Webhook URL from make.com

1. In make.com, add a **"Webhooks"** module
2. Select **"Custom webhook"**
3. Click **"Add"** → **"Save"**
4. Copy the webhook URL (looks like: `https://hook.make.com/xxxxxxxx`)

### Step 2: Configure PHP

Edit `config/webhook.php`:

```php
$webhookConfig = [
    'secret' => 'your-secret-key',
    'make_webhook_url' => 'https://hook.make.com/xxxxxxxx',
    'enable_logging' => true,
];
```

### Step 3: Add Google Sheets Module in make.com

1. Add **"Google Sheets"** → **"Add Row"**
2. Select your spreadsheet and sheet for attendance
3. Map the values from the webhook:
   - **Timestamp**: `{{timestamp}}`
   - **Event**: `{{data.event_name}}`
   - **Attendee**: `{{data.attendee_name}}`
   - **Status**: `{{data.status}}`
   - etc.

### Step 4: Test Connection

1. Visit `http://localhost/afb_mangaan/webhook_test.php`
2. Click **"Test make.com Connection"**
3. Or use the PHP test function:

```php
require_once 'functions/make_sync.php';
$result = testMakeConnection();
print_r($result);
```

---

## Part 5: Run Locally with ngrok (for Testing)

Since make.com needs a public URL, use ngrok for local development:

### Step 1: Download ngrok

```bash
# Windows (already in your project folder)
ngrok.exe

# Or download from https://ngrok.com/download
```

### Step 2: Start ngrok

```bash
ngrok http 80
```

### Step 3: Get Public URL

ngrok will show something like:
```
Forwarding  https://abc123.ngrok.io -> http://localhost:80
```

### Step 4: Update make.com

Use `https://abc123.ngrok.io/afb_mangaan/api/webhook_import.php` as your webhook URL.

---

## Troubleshooting

### "Unauthorized: Invalid webhook secret"

- Verify `X-Webhook-Secret` header in make.com matches `config/webhook.php`
- Check for trailing spaces

### "Database error"

- Check MySQL is running
- Verify database credentials in `.env`
- Check PHP error logs

### make.com shows "Connection timeout"

- Ensure your server is publicly accessible (use ngrok for local)
- Check firewall settings

### Data not appearing in Google Sheets

- Check make.com execution log for errors
- Verify webhook payload format
- Ensure Google Sheet has correct column headers

### Too many operations in make.com

- Free tier: 1,000 operations/month
- Use filters to reduce triggers
- Consider paid plan for high volume

---

## Security Best Practices

1. **Always use HTTPS** in production
2. **Keep webhook secret private** - never commit to git
3. **Validate payloads** - the endpoint checks required fields
4. **Use strong secrets** - at least 32 characters, random
5. **Enable logging** during setup, disable in production

---

## Rate Limits

| Service | Limit |
|---------|-------|
| Google Sheets API | 300 reads/min, 60 writes/min |
| make.com Free | 1,000 operations/month |
| make.com Operations | Depends on plan |

**Important**: If you have 100+ people scanning in within 1 minute, Google Sheets will fail. MySQL must remain the primary database.

---

## Files Reference

| File | Purpose |
|------|---------|
| `api/webhook_import.php` | Receives data from make.com |
| `config/webhook.php` | Webhook configuration |
| `functions/make_sync.php` | Sends data to make.com |
| `functions/attendance_logic.php` | Triggers sync after attendance |
| `webhook_test.php` | Browser-based test interface |
| `tests/webhook_test.php` | Command-line test script |
| `.env.example` | Environment variable template |

---

## Support

For issues:
1. Check PHP error logs
2. Check make.com execution logs
3. Run test script: `php tests/webhook_test.php`
4. Visit test page: `webhook_test.php`

---

**Last Updated**: 2024
