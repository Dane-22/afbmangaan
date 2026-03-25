# How to Get Google Service Account Credentials

## Step 1: Create a Google Cloud Project

1. Go to https://console.cloud.google.com
2. Sign in with your Google account
3. Click "Select a project" dropdown → "New Project"
4. Name it: `AFB Mangaan Attendance`
5. Click "Create"

## Step 2: Enable Google Sheets API

1. In the left sidebar, click "APIs & Services" → "Library"
2. Search for "Google Sheets API"
3. Click on it → Click "Enable"

## Step 3: Create Service Account

1. Left sidebar → "IAM & Admin" → "Service Accounts"
2. Click "Create Service Account"
3. **Step 1 - Service account details:**
   - Name: `afb-attendance-system`
   - Click "Create and Continue"
4. **Step 2 - Grant access:**
   - Role: "Editor" (or "Viewer" if you only need read access)
   - Click "Continue"
5. **Step 3 - Grant users access:**
   - Skip this, click "Done"

## Step 4: Create and Download JSON Key

1. You should see your service account in the list
2. Click on the email (looks like `...@....iam.gserviceaccount.com`)
3. Click "Keys" tab
4. Click "Add Key" → "Create new key"
5. Select "JSON" format
6. Click "Create"
7. **A JSON file will download - save it securely!**

## Step 5: Get the Values You Need

Open the downloaded JSON file. It looks like this:

```json
{
  "type": "service_account",
  "project_id": "your-project-id",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC...\n-----END PRIVATE KEY-----\n",
  "client_email": "afb-attendance-system@your-project-id.iam.gserviceaccount.com",
  "client_id": "...",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token"
}
```

### Copy these two values:

| Your Vercel Field | JSON Field | Example |
|-------------------|------------|---------|
| `GOOGLE_SERVICE_ACCOUNT_EMAIL` | `client_email` | `afb-attendance-system@your-project-id.iam.gserviceaccount.com` |
| `GOOGLE_PRIVATE_KEY` | `private_key` | Copy the entire string including `-----BEGIN PRIVATE KEY-----` and newlines |

## Important: Format the Private Key

The private key needs to be formatted correctly for Vercel:

1. Open the JSON file in Notepad or VS Code
2. Find the `private_key` value
3. It should look like this (with actual content):
   ```
   "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQE...\n-----END PRIVATE KEY-----\n"
   ```

4. **Copy everything** between the quotes (including the `\n` characters)
5. When pasting in Vercel, the newlines (`\n`) will be converted automatically

## Step 6: Share Your Google Sheet

1. Open your Google Sheets spreadsheet
2. Click "Share" button (top right)
3. Add the `client_email` from your JSON file
4. Set permission to "Editor"
5. Click "Share"

## Step 7: Get GOOGLE_SHEETS_ID

From your Google Sheets URL:

```
https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
                          ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
                          This is your GOOGLE_SHEETS_ID
```

Copy the long string between `/d/` and `/edit`

## Summary for Vercel Environment Variables

| Variable | Where to Get It |
|----------|-----------------|
| `GOOGLE_SHEETS_ID` | From Sheets URL between `/d/` and `/edit` |
| `GOOGLE_SERVICE_ACCOUNT_EMAIL` | `client_email` from JSON file |
| `GOOGLE_PRIVATE_KEY` | `private_key` from JSON file (entire string) |
| `JWT_SECRET` | Create yourself (random 32+ character string) |
| `ADMIN_USERNAME` | Choose any (e.g., `admin`) |
| `ADMIN_PASSWORD` | Choose any password |

## Video Guide

If you prefer video, search YouTube for:
- "Google Sheets API service account setup"
- "Create Google Cloud service account for Sheets API"

---

**Need help?** Let me know which step you're stuck on!
