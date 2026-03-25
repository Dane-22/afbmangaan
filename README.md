# AFB Mangaan Attendance & Analytics System

A comprehensive church attendance management system with QR code support, real-time analytics, and export capabilities.

## Features

### Core Features
- **Hybrid Attendance System**: Live search + QR code scanner
- **Member Management**: CRUD operations with category tracking
- **Event Management**: Schedule and manage church events
- **Real-time Analytics**: Dashboard with Chart.js visualizations
- **Member Retention Tracking**: Identify consistent vs at-risk members

### Security
- MD5 password hashing
- Session-based authentication with timeout
- Role-based access control (Admin/Operator/Viewer)
- Activity logging and audit trail
- Environment-based configuration

### Export Capabilities
- **CSV Export**: Universal compatibility
- **PDF Export**: Formatted reports via Dompdf
- **Excel Export**: Native .xlsx via PhpSpreadsheet

### UI/UX
- Light/Dark mode toggle with persistent preference
- Responsive design for desktop and mobile
- Animate.css for smooth transitions
- Geometric AFB Mangaan branding

## Tech Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (vanilla)

This repository contains **two versions** of the attendance system:

### Version 1: Vercel + Google Sheets (Recommended - No Hosting Cost)
- **Frontend**: Single Page Application (SPA) deployed on Vercel
- **Backend**: Serverless Node.js functions
- **Database**: Google Sheets (free, 300 reads/60 writes per minute)
- **Cost**: $0
- **Best for**: Churches without hosting budget

### Version 2: PHP + MySQL (Traditional)
- **Frontend**: PHP templates with vanilla JS
- **Backend**: PHP with PDO
- **Database**: MySQL
- **Cost**: Requires web hosting
- **Best for**: Churches with existing hosting infrastructure

---

## 📊 Features

### Dashboard
- Real-time attendance statistics
- Today's event overview
- 7-day activity chart
- Category breakdown (Youth, Adult, Senior, Child)

### Members Management
- Add/edit/archive members
- Auto-generated QR codes
- Search by name or QR token
- Category-based filtering

### Events Management
- Create and manage church events
- Event types: Sunday Service, Midweek Service, Special Event, Meeting
- Status tracking: Upcoming, Ongoing, Completed, Cancelled

### Attendance Recording
- Quick member search
- Mark Present/Absent
- Real-time attendance list
- Method tracking (Manual, QR Scan)

### Reports
- Attendance trends
- Category-based statistics
- Export capabilities (via Google Sheets)

---

## 🚀 Vercel Deployment

### Prerequisites
1. Google account (for Google Sheets)
2. Vercel account (free)
3. Node.js installed locally

### Environment Variables

Set these in Vercel dashboard or via CLI:

```bash
GOOGLE_SHEETS_ID=your-spreadsheet-id
GOOGLE_SERVICE_ACCOUNT_EMAIL=service-account@project.iam.gserviceaccount.com
GOOGLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n"
JWT_SECRET=your-random-secret-key
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your-secure-password
```

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | Authenticate |
| GET | `/api/dashboard` | Get stats |
| GET | `/api/attendees` | List members |
| POST | `/api/attendees` | Create member |
| PUT | `/api/attendees` | Update member |
| DELETE | `/api/attendees` | Archive member |
| GET | `/api/events` | List events |
| POST | `/api/events` | Create event |
| PUT | `/api/events` | Update event |
| DELETE | `/api/events` | Cancel event |
| GET | `/api/attendance` | Get records |
| POST | `/api/attendance` | Record attendance |
| DELETE | `/api/attendance` | Delete record |

---

## 📁 Project Structure

### Vercel Version (Root)
```
afb_mangaan/
├── api/                       # Serverless functions
│   ├── _utils/sheets.js      # Google Sheets utilities
│   ├── attendees.js          # Members CRUD
│   ├── events.js             # Events CRUD
│   ├── attendance.js         # Attendance recording
│   ├── login.js              # Authentication
│   └── dashboard.js          # Stats & analytics
├── index.html                # Single Page Application
├── vercel.json               # Vercel configuration
├── package.json              # Dependencies
├── VERCEL_DEPLOY.md          # Deployment guide
└── README.md                 # This file
│   ├── header.php
│   └── sidebar.php
├── .env                   # Environment configuration
├── .gitignore
├── afb_mangaan_db.sql     # Database schema
├── attendance.php
├── composer.json          # Dependencies
├── dashboard.php
├── events.php
├── index.php              # Login page
├── logs.php               # System logs (admin only)
├── logout.php
├── members.php
├── reports.php            # Reports & exports
└── settings.php
```

## Usage Guide

### Recording Attendance
1. Go to **Attendance** page
2. Select an event from the dropdown
3. Use **Search** tab to find members by name/QR
4. Or use **QR Scanner** tab for QR code scanning
5. Click "Mark Present" or "Mark Absent"

### Managing Members
1. Go to **Members** page
2. Click "Add Member" to create new entries
3. QR tokens are auto-generated
4. Use filters to find specific members
5. Archive members instead of deleting

### Creating Events
1. Go to **Events** page
2. Click "Create Event"
3. Set date, time, location, and type
4. Event status can be updated as it progresses

### Generating Reports
1. Go to **Reports** page (via Dashboard link)
2. Select date range and filters
3. View summary statistics
4. Export as PDF, CSV, or Excel

### User Roles
- **Admin**: Full access including user management and logs
- **Operator**: Can record attendance, manage members/events
- **Viewer**: Read-only dashboard access

## QR Code System

QR tokens follow the format: `AFB######` (e.g., `AFB001001`)
- Each member has a unique QR token
- Tokens are displayed in member profiles
- QR codes can be scanned for quick attendance
- Tokens are auto-generated on member creation

## Theme Customization

Edit CSS variables in `assets/css/main.css`:

```css
:root {
    --primary: #6366f1;      /* Main brand color */
    --success: #22c55e;      /* Success states */
    --danger: #ef4444;       /* Error states */
    --warning: #f59e0b;      /* Warning states */
    --bg-sidebar: #1e293b;   /* Sidebar background */
    /* ... more variables */
}
```

## API Endpoints

All API endpoints return JSON responses:

- `GET/POST api/search_attendees.php?q={query}` - Search members
- `POST api/record_attendance.php` - Record attendance
- `GET api/get_attendance.php?event_id={id}` - Get event attendance
- `GET api/dashboard_stats.php?type={trends|categories|retention}` - Dashboard data
- `GET api/export_report.php?format={csv|pdf|xlsx}` - Export reports

## Troubleshooting

### Database Connection Issues
- Verify `.env` credentials match your MySQL setup
- Ensure MySQL service is running
- Check for correct port (default 3306)

### QR Scanner Not Working
- Ensure camera permissions are granted
- Use HTTPS or localhost (required for camera access)
- Test with a modern browser (Chrome/Edge/Firefox)

### PDF/Excel Export Not Working
- Run `composer install` to install dependencies
- Ensure `vendor/` directory exists
- Check PHP error logs for details

### Session Timeout Too Fast
- Edit `includes/auth_check.php`
- Modify `$timeout = 3600;` (seconds)

## License

This project is proprietary to AFB Mangaan.

## Support

For technical support or feature requests, contact the system administrator.

---

**Version**: 1.0.0  
**Last Updated**: February 2026
