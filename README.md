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
- **Charts**: Chart.js 4.x
- **Icons**: Phosphor Icons
- **QR Scanner**: html5-qrcode
- **PDF**: Dompdf 2.x
- **Excel**: PhpSpreadsheet 1.x

## Installation

### Prerequisites
- WAMP/XAMPP/LAMP server
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer (optional, for PDF/Excel features)

### Setup Steps

1. **Clone or extract files** to your web root (e.g., `c:\wamp64\www\afb_mangaan\`)

2. **Import the database**:
   ```bash
   mysql -u root -p < afb_mangaan_db.sql
   ```
   Or use phpMyAdmin to import `afb_mangaan_db.sql`

3. **Configure environment**:
   - Copy `.env` and update database credentials:
   ```
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=afb_mangaan_db
   DB_USER=root
   DB_PASSWORD=your_password
   ```

4. **Install dependencies** (for PDF/Excel export):
   ```bash
   cd c:\wamp64\www\afb_mangaan
   composer install
   ```
   If Composer is not available, CSV export will still work.

5. **Access the system**:
   ```
   http://localhost/afb_mangaan/
   ```

## Default Credentials

| Username | Password | Role   |
|----------|----------|--------|
| admin    | admin123 | Admin  |
| operator | password | Operator |

## Project Structure

```
afb_mangaan/
├── api/                    # AJAX API endpoints
│   ├── dashboard_stats.php
│   ├── delete_attendance.php
│   ├── export_attendance.php
│   ├── export_members.php
│   ├── export_report.php   # PDF/CSV/Excel exports
│   ├── get_attendance.php
│   ├── get_attendee_by_qr.php
│   ├── record_attendance.php
│   └── search_attendees.php
├── assets/
│   ├── css/
│   │   ├── animations.css  # Custom animations
│   │   └── main.css        # Theme variables & components
│   └── js/
│       ├── attendance_ajax.js    # Live search & QR scanner
│       ├── dashboard_charts.js   # Chart.js integration
│       └── theme_handler.js      # Light/Dark toggle
├── config/
│   └── db.php             # PDO connection
├── functions/
│   ├── activity_logger.php
│   ├── attendance_logic.php
│   ├── auth_functions.php
│   └── report_engine.php
├── includes/
│   ├── auth_check.php     # Session validation
│   ├── footer.php
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
