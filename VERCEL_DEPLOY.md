# Vercel + Google Sheets Deployment Guide

Deploy the AFB Mangaan Attendance System on Vercel using Google Sheets as your free database.

---

## Architecture

```
┌─────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   User      │────▶│  Vercel (UI)    │────▶│  Serverless API │
│  Browser    │◀────│  index.html     │◀────│  (Node.js)      │
└─────────────┘     └─────────────────┘     └────────┬────────┘
                                                      │
                                                      ▼
                                               ┌──────────────┐
                                               │ Google Sheets│
                                               │  (Database)  │
                                               └──────────────┘
```

---

## Step 1: Create Google Sheets Database

### 1.1 Create a New Google Sheet

1. Go to [Google Sheets](https://sheets.google.com)
2. Create a new spreadsheet named "AFB Mangaan Attendance DB"
3. **Important**: Click "Share" → "Share with anyone" → Set to "Editor" (we'll lock this down later)
4. Copy the **Sheet ID** from the URL:
   - URL: `https://docs.google.com/spreadsheets/d/SHEET_ID/edit`
   - Save this ID for later

### 1.2 Create 3 Sheets (Tabs)

**Sheet 1 - "Attendees"** (Members):
```
A1: id | fullname | category | contact | email | qr_token | status | created_at | updated_at
A2: 1  | Juan Dela Cruz | Adult | 09123456789 | juan@email.com | AFB000001 | Active | 2026-03-16T00:00:00Z | 2026-03-16T00:00:00Z
```

**Sheet 2 - "Events"**:
```
A1: id | event_name | event_date | event_time | location | type | status | description | created_at | updated_at
A2: 1  | Sunday Worship | 2026-03-16 | 09:00:00 | Main Sanctuary | Sunday Service | Upcoming | Regular service | 2026-03-16T00:00:00Z | 2026-03-16T00:00:00Z
```

**Sheet 3 - "Attendance"**:
```
A1: id | event_id | attendee_id | status | log_time | method | notes
A2: 1  | 1        | 1           | Present| 2026-03-16T09:15:00Z | Manual | 
```

### 1.3 Create Service Account (Required for API Access)

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Create a new project (or select existing)
3. Enable **Google Sheets API**:
   - APIs & Services → Library → Search "Google Sheets API" → Enable
4. Create Service Account:
   - IAM & Admin → Service Accounts → Create
   - Name: "afb-attendance-system"
   - Role: "Editor" (for Sheets)
5. Create JSON Key:
   - Click on the service account → Keys → Add Key → Create New Key → JSON
   - Download the JSON file
6. **Share your Sheet** with the service account email:
   - Copy the `client_email` from the JSON (looks like: `...@....iam.gserviceaccount.com`)
   - In your Google Sheet: Share → Add the service account email → Editor

---

## Step 2: Prepare for Deployment

### 2.1 Install Dependencies

```bash
npm install
```

### 2.2 Environment Variables

Create `.env.local` file (don't commit this):

```env
# Google Sheets Configuration
GOOGLE_SHEETS_ID=your-spreadsheet-id-here
GOOGLE_SERVICE_ACCOUNT_EMAIL=your-service-account@project.iam.gserviceaccount.com
GOOGLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC...\n-----END PRIVATE KEY-----\n"

# Authentication
JWT_SECRET=your-random-secret-key-min-32-characters
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin123
```

**Note**: The `GOOGLE_PRIVATE_KEY` needs newlines escaped as `\n` for Vercel.

---

## Step 3: Deploy to Vercel

### Option A: Deploy via CLI (Recommended)

1. Install Vercel CLI:
```bash
npm i -g vercel
```

2. Login to Vercel:
```bash
vercel login
```

3. Deploy:
```bash
vercel
```

4. Set environment variables on Vercel:
```bash
vercel env add GOOGLE_SHEETS_ID
vercel env add GOOGLE_SERVICE_ACCOUNT_EMAIL
vercel env add GOOGLE_PRIVATE_KEY
vercel env add JWT_SECRET
vercel env add ADMIN_USERNAME
vercel env add ADMIN_PASSWORD
```

5. Redeploy with env vars:
```bash
vercel --prod
```

### Option B: Deploy via GitHub + Vercel Dashboard

1. Push code to GitHub
2. Go to [Vercel Dashboard](https://vercel.com)
3. Import your GitHub repository
4. In Project Settings → Environment Variables, add:
   - `GOOGLE_SHEETS_ID`
   - `GOOGLE_SERVICE_ACCOUNT_EMAIL`
   - `GOOGLE_PRIVATE_KEY`
   - `JWT_SECRET`
   - `ADMIN_USERNAME`
   - `ADMIN_PASSWORD`
5. Deploy

---

## API Endpoints

Once deployed, your API will be available at `https://your-app.vercel.app/api/`

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/login` | POST | Authenticate user |
| `/api/dashboard` | GET | Get dashboard stats |
| `/api/attendees` | GET | List all members |
| `/api/attendees` | POST | Create member |
| `/api/attendees` | PUT | Update member |
| `/api/attendees` | DELETE | Archive member |
| `/api/events` | GET | List events |
| `/api/events` | POST | Create event |
| `/api/events` | PUT | Update event |
| `/api/events` | DELETE | Cancel event |
| `/api/attendance` | GET | Get attendance records |
| `/api/attendance` | POST | Record attendance |
| `/api/attendance` | DELETE | Delete attendance |

---

## Default Login Credentials

- **Username**: `admin` (or your `ADMIN_USERNAME` env var)
- **Password**: `admin123` (or your `ADMIN_PASSWORD` env var)

**⚠️ Important**: Change these in production!

---

## Google Sheets Rate Limits

Google Sheets API has these limits:
- **Read**: 300 requests per 60 seconds per project
- **Write**: 60 requests per 60 seconds per project

**For most church attendance systems, this is plenty.**

If you hit limits:
- Add caching (implement in API)
- Batch operations
- Consider upgrading to Google Workspace

---

## Troubleshooting

### "Unable to access spreadsheet"
- Make sure you shared the sheet with the service account email
- Check that Google Sheets API is enabled in Google Cloud Console
- Verify `GOOGLE_SHEETS_ID` is correct

### "Invalid credentials" on login
- Check `ADMIN_USERNAME` and `ADMIN_PASSWORD` env vars are set
- Clear browser localStorage and try again

### "Method not allowed"
- Check you're using the correct HTTP method (GET/POST/PUT/DELETE)

### CORS errors
- The API includes CORS headers. If issues persist, check browser console for specific errors.

---

## Cost

**100% FREE** for:
- Vercel Hobby plan (serverless functions)
- Google Sheets API (within limits)
- Google Cloud (within free tier)

---

## Next Steps

1. Set up your Google Sheets database
2. Create a service account and download JSON key
3. Deploy to Vercel with environment variables
4. Access your app at the Vercel URL
5. Login with default credentials
6. Start managing members and events!

---

## Files Structure

```
afb_mangaan/
├── api/
│   ├── _utils/
│   │   └── sheets.js          # Google Sheets utilities
│   ├── attendees.js           # Members API
│   ├── events.js              # Events API
│   ├── attendance.js          # Attendance API
│   ├── login.js               # Auth API
│   └── dashboard.js           # Stats API
├── index.html                 # Main SPA application
├── vercel.json                # Vercel config
├── package.json               # Dependencies
└── README.md                  # This file
```

---

## Support

For issues:
1. Check Vercel function logs (Dashboard → Functions)
2. Check browser console for errors
3. Verify Google Sheets sharing permissions
4. Test API endpoints directly with curl/Postman

---

**Last Updated**: March 16, 2026
