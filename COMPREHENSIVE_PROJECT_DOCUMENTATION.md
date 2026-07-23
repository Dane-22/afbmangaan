# AFB Mangaan Attendance & Analytics System - Comprehensive Project Documentation

**Version**: 1.0.0  
**Last Updated**: July 22, 2026  
**Repository**: Dane-22/afbmangaan  
**Project Type**: Church Attendance Management System

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [System Architecture](#2-system-architecture)
3. [Features & Capabilities](#3-features--capabilities)
4. [Technology Stack](#4-technology-stack)
5. [Installation & Setup](#5-installation--setup)
6. [Configuration](#6-configuration)
7. [Database Schema](#7-database-schema)
8. [API Reference](#8-api-reference)
9. [User Guide](#9-user-guide)
10. [Security Considerations](#10-security-considerations)
11. [Known Issues & Remediation](#11-known-issues--remediation)
12. [Troubleshooting](#12-troubleshooting)
13. [Development Guidelines](#13-development-guidelines)

---

## 1. Project Overview

### 1.1 Purpose

The AFB Mangaan Attendance & Analytics System is a comprehensive web-based solution designed for managing church attendance across two congregations:
- **AFB Mangaan** (Main congregation)
- **AFB Lettac Sur** (Branch congregation)

### 1.2 Key Objectives

- Streamline attendance recording with hybrid manual and QR code scanning
- Provide real-time analytics and member retention tracking
- Enable data-driven decision making for church leadership
- Maintain complete audit trails for accountability
- Support multi-location church management

### 1.3 Target Users

- **Administrators**: Full system access and user management
- **Operators**: Attendance recording, member/event management
- **Viewers**: Read-only access to reports and analytics

### 1.4 Deployment Models

The system supports two deployment architectures:

#### Model 1: PHP + MySQL (Traditional)
- **Frontend**: PHP templates with vanilla JavaScript
- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 5.7+
- **Hosting**: Traditional web hosting (WAMP/XAMPP/LAMP)
- **Cost**: Requires hosting provider

#### Model 2: Vercel + Google Sheets (Serverless)
- **Frontend**: Single Page Application
- **Backend**: Serverless Node.js functions
- **Database**: Google Sheets API
- **Hosting**: Vercel (free tier available)
- **Cost**: $0 (within Google Sheets limits)

---

## 2. System Architecture

### 2.1 Application Structure

```
afb_mangaan/
├── api/                          # API endpoints
│   ├── _utils/
│   │   └── sheets.js            # Google Sheets utilities
│   ├── dashboard_stats.php      # Analytics data
│   ├── delete_attendance.php    # Attendance deletion
│   ├── export_attendance.php    # CSV export
│   ├── export_members.php       # Member export
│   ├── export_report.php        # Report generation
│   ├── get_attendance.php       # Attendance retrieval
│   ├── record_attendance.php    # Attendance recording
│   ├── search_attendees.php     # Member search
│   └── webhook_import.php       # Webhook handler
├── assets/
│   ├── css/
│   │   ├── animations.css       # Custom animations
│   │   └── main.css            # Main stylesheet
│   ├── img/
│   │   ├── lettacsur-logo.png
│   │   ├── logo-black.png
│   │   └── logo-white.png
│   └── js/
│       ├── attendance_ajax.js  # Attendance AJAX handlers
│       ├── dashboard_charts.js # Chart.js integration
│       └── theme_handler.js    # Theme toggle logic
├── config/
│   ├── db.php                  # Database configuration
│   └── webhook.php             # make.com webhook config
├── demo/                        # Demo HTML files
├── docs/                        # Documentation
├── functions/
│   ├── activity_logger.php     # Audit trail functions
│   ├── attendance_logic.php    # Core attendance logic
│   ├── auth_functions.php      # Authentication functions
│   ├── make_sync.php           # Google Sheets sync
│   └── report_engine.php       # Report generation
├── includes/
│   ├── auth_check.php          # Session validation
│   ├── footer.php              # Page footer
│   ├── header.php              # Page header with navigation
│   └── sidebar.php             # Sidebar navigation
├── tests/
│   └── webhook_test.php         # Webhook testing
├── .env.example                # Environment template
├── .gitignore
├── afb_mangaan_db.sql         # Database schema
├── animations.js              # Landing page animations
├── attendance.php              # Attendance recording page
├── attendance_audit.php        # Attendance audit log
├── composer.json              # PHP dependencies
├── dashboard.php              # Main dashboard
├── events.php                 # Event management
├── index.php                  # Public landing page
├── install.php                # Installation wizard
├── login.php                  # Authentication page
├── logout.php                 # Session termination
├── logs.php                   # System logs viewer
├── members.php                # Member management
├── package.json               # Node.js dependencies
├── reports.php                # Reports and analytics
├── settings.php               # System settings
├── setup_db.php               # Database setup script
└── style.css                  # Landing page styles
```

### 2.2 Data Flow

```
User Interface
    ↓
PHP Backend (functions/)
    ↓
MySQL Database (config/db.php)
    ↓
Analytics Dashboard (dashboard.php)
    ↓
Reports & Exports (reports.php)
```

### 2.3 Authentication Flow

```
Login Page (login.php)
    ↓
auth_functions.php::loginUser()
    ↓
Session Creation (includes/auth_check.php)
    ↓
Role-Based Access Control
    ↓
Protected Pages
```

---

## 3. Features & Capabilities

### 3.1 Core Features

#### Attendance Recording
- **Manual Entry**: Search and mark members present/absent
- **QR Code Scanning**: Camera-based QR code scanning
- **Bulk Actions**: Mark all members present at once
- **Real-time Updates**: AJAX-based status updates
- **Method Tracking**: Manual, QR Scan, or Search methods
- **Time Stamping**: Automatic log time recording

#### Member Management
- **CRUD Operations**: Create, read, update, archive members
- **Auto-Generated QR Codes**: Unique tokens per member
- **Category Classification**: MCYO, WMO, CCMO, KIDS
- **Search & Filter**: By name, QR token, contact, category
- **Dual View Modes**: Desktop table and mobile grid
- **Export Capabilities**: CSV export of member lists

#### Event Management
- **Event Scheduling**: Create single or multi-day events
- **Recurring Events**: Auto-generate 52 weekly events
- **Status Tracking**: Upcoming, Ongoing, Completed, Cancelled
- **Event Types**: Sunday Service, Midweek Service, Special Event, Meeting, Other
- **Location Management**: Track event venues
- **Description Support**: Detailed event information

#### Analytics Dashboard
- **Real-time Statistics**: Total members, today's event, retention metrics
- **Attendance Trends**: 7-day line charts
- **Category Distribution**: Doughnut charts by member category
- **Member Retention**: Consistent vs at-risk member analysis
- **Recent Activity**: Latest 5 system actions
- **Quick Actions**: Direct links to common tasks

#### Reports & Exports
- **Date Range Filtering**: Custom date range selection
- **Event Filtering**: Specific or all events
- **Category Breakdown**: Statistics by member category
- **Multiple Export Formats**: CSV, PDF (Dompdf), Excel (PhpSpreadsheet)
- **Top Attendees Leaderboard**: Top 10 by attendance rate
- **Summary Statistics**: Total events, attendance count, rates

### 3.2 Advanced Features

#### Multi-Church Support
- **Church Isolation**: Data separated by congregation
- **Church Selection**: Login-time church selection
- **Church Indicator**: Visual badge showing current branch
- **Cross-Church Analytics**: Aggregate reporting capability

#### Activity Logging
- **Comprehensive Audit Trail**: All significant actions logged
- **IP Tracking**: Client IP address recording
- **User Agent Logging**: Browser/device information
- **Action Classification**: LOGIN, LOGOUT, ATTENDANCE_RECORD, etc.
- **Log Viewer**: Admin-only log viewing interface

#### Theme System
- **Light/Dark Mode**: User preference persistence
- **System Preference Detection**: Automatic theme detection
- **LocalStorage Storage**: Theme persistence across sessions
- **Custom Event Dispatch**: Theme change notifications
- **Chart Theme Updates**: Automatic chart color adaptation

#### QR Code System
- **Unique Token Generation**: AFB###### format
- **Camera Integration**: Browser-based QR scanning
- **Fallback Manual Entry**: Manual token input option
- **Token Validation**: Church-specific token lookup
- **Printable QR Codes**: Member card generation

---

## 4. Technology Stack

### 4.1 Backend Technologies

#### PHP
- **Version**: 7.4+
- **Database Layer**: PDO (PHP Data Objects)
- **Session Management**: Native PHP sessions
- **Password Hashing**: MD5 (legacy - upgrade recommended to bcrypt)

#### Database
- **Engine**: MySQL 5.7+ / MariaDB 10.3+
- **Charset**: utf8mb4 (full Unicode support)
- **Storage Engine**: InnoDB (transaction support)
- **Connection**: PDO with prepared statements

#### PHP Libraries (Composer)
```json
{
  "require": {
    "php": ">=7.4",
    "dompdf/dompdf": "^2.0",
    "phpoffice/phpspreadsheet": "^1.28"
  }
}
```

### 4.2 Frontend Technologies

#### HTML/CSS
- **HTML5**: Semantic markup
- **CSS3**: Custom properties (variables), Flexbox, Grid
- **Responsive Design**: Mobile-first approach
- **Animations**: Animate.css + custom animations

#### JavaScript
- **Core**: Vanilla JavaScript (ES6+)
- **Charts**: Chart.js for data visualization
- **Icons**: Phosphor Icons (unpkg CDN)
- **Animations**: GSAP with ScrollTrigger (landing page)
- **QR Scanning**: HTML5-QRCode library

#### Fonts
- **Headings**: Cinzel (serif, church aesthetic)
- **Body**: Inter (sans-serif, readability)

### 4.3 External Integrations

#### make.com
- **Purpose**: Google Sheets synchronization
- **Method**: Webhook-based data push
- **Authentication**: Secret key validation
- **Timeout**: 1 second (non-blocking)

#### Google Sheets (Vercel Version)
- **API**: Google Sheets API v4
- **Authentication**: Service account OAuth2
- **Limits**: 300 reads/60 writes per minute (free tier)

---

## 5. Installation & Setup

### 5.1 Prerequisites

#### Required Software
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- Composer (PHP package manager)
- Node.js 24.x (for Vercel deployment only)

#### Optional Software
- Git (for version control)
- phpMyAdmin (for database management)
- WAMP/XAMPP/MAMP (local development environment)

### 5.2 Installation Steps

#### Step 1: Clone Repository
```bash
git clone https://github.com/Dane-22/afbmangaan.git
cd afbmangaan
```

#### Step 2: Install PHP Dependencies
```bash
composer install
```

#### Step 3: Configure Environment
```bash
cp .env.example .env
```

Edit `.env` file:
```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=afb_mangaan_db
DB_USER=root
DB_PASSWORD=your_password
QR_PREFIX=AFB
WEBHOOK_SECRET=your-random-secret-key-32-chars
MAKE_WEBHOOK_URL=https://hook.make.com/your-webhook
WEBHOOK_LOGGING=true
SESSION_LIFETIME=7200
```

#### Step 4: Database Setup

**Option A: Import SQL File**
```bash
mysql -u root -p < afb_mangaan_db.sql
```

**Option B: Auto-Creation**
The system will automatically create the database on first run if configured correctly in `config/db.php`.

#### Step 5: Web Server Configuration

**Apache (httpd.conf)**
```apache
DocumentRoot "c:/wamp64/www/afbmangaan"
<Directory "c:/wamp64/www/afbmangaan">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

**Nginx (nginx.conf)**
```nginx
server {
    listen 80;
    server_name afb-mangaan.local;
    root /var/www/afbmangaan;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

#### Step 6: File Permissions
```bash
chmod 755 /assets/uploads
chmod 644 *.php
chmod 644 *.css *.js
```

### 5.3 Default Credentials

| Username | Password | Role | Church |
|----------|----------|------|--------|
| admin | admin123 | admin | AFB Mangaan |
| operator | password | operator | AFB Mangaan |
| admin | admin123 | admin | AFB Lettac Sur |
| operator | password | operator | AFB Lettac Sur |

**⚠️ SECURITY WARNING**: Change default credentials immediately after first login!

### 5.4 Vercel Deployment (Alternative)

#### Step 1: Install Node.js Dependencies
```bash
npm install
```

#### Step 2: Configure Environment Variables
Set these in Vercel dashboard:
```env
GOOGLE_SHEETS_ID=your-spreadsheet-id
GOOGLE_SERVICE_ACCOUNT_EMAIL=service-account@project.iam.gserviceaccount.com
GOOGLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\n..."
JWT_SECRET=your-random-secret-key
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your-secure-password
```

#### Step 3: Deploy
```bash
vercel --prod
```

---

## 6. Configuration

### 6.1 Database Configuration

**File**: `config/db.php`

```php
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'name' => getenv('DB_NAME') ?: 'afb_mangaan_db',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4'
];
```

### 6.2 Webhook Configuration

**File**: `config/webhook.php`

```php
$webhookConfig = [
    'secret' => getenv('WEBHOOK_SECRET') ?: 'change-this-secret',
    'make_webhook_url' => getenv('MAKE_WEBHOOK_URL') ?: null,
    'enable_logging' => getenv('WEBHOOK_LOGGING') === 'true',
];
```

### 6.3 Session Configuration

**File**: `includes/auth_check.php`

```php
// Session timeout (seconds)
$timeout = 3600; // 1 hour

// Security flags (recommended for production)
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
```

### 6.4 Theme Configuration

**File**: `assets/css/main.css`

```css
:root {
    --primary: #6366f1;
    --success: #22c55e;
    --danger: #ef4444;
    --warning: #f59e0b;
    --bg-sidebar: #1e293b;
    /* ... more variables */
}

[data-theme="dark"] {
    --bg-primary: #0d0d0d;
    --text-primary: #f5f3ef;
    /* ... dark theme overrides */
}
```

---

## 7. Database Schema

### 7.1 Table: users

System users with authentication credentials.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK, AI) | Unique user identifier |
| church | ENUM | 'AFB Mangaan' or 'AFB Lettac Sur' |
| username | VARCHAR(50) | Login username |
| password | VARCHAR(32) | MD5 hashed password |
| fullname | VARCHAR(100) | Display name |
| role | ENUM | 'admin', 'operator', 'viewer' |
| status | ENUM | 'Active' or 'Inactive' |
| created_at | TIMESTAMP | Account creation time |
| updated_at | TIMESTAMP | Last update time |

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE KEY (church, username)

### 7.2 Table: attendees

Church members with QR codes.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK, AI) | Member ID |
| church | ENUM | Associated church branch |
| fullname | VARCHAR(100) | Member name |
| category | ENUM | 'MCYO', 'WMO', 'CCMO', 'KIDS' |
| contact | VARCHAR(20) | Phone number |
| email | VARCHAR(100) | Email address |
| qr_token | VARCHAR(64) | Unique QR identifier |
| status | ENUM | 'Active' or 'Archived' |
| created_at | TIMESTAMP | Registration date |
| updated_at | TIMESTAMP | Last update time |

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE KEY (qr_token)
- INDEX (church, category, status)

### 7.3 Table: events

Church events and services.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK, AI) | Event ID |
| church | ENUM | Host church |
| event_name | VARCHAR(150) | Event title |
| start_date | DATE | Event start |
| end_date | DATE | Event end (multi-day) |
| event_time | TIME | Scheduled time |
| location | VARCHAR(200) | Venue |
| type | ENUM | Event type |
| status | ENUM | Event status |
| description | TEXT | Event details |
| created_by | INT (FK) | User who created |
| created_at | TIMESTAMP | Creation time |
| updated_at | TIMESTAMP | Last update time |

**Indexes**:
- PRIMARY KEY (id)
- INDEX (church, start_date, status, type)
- FOREIGN KEY (created_by) → users(id)

### 7.4 Table: attendance_logs

Attendance records.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK, AI) | Record ID |
| event_id | INT (FK) | Reference to event |
| attendee_id | INT (FK) | Reference to member |
| status | ENUM | 'Present' or 'Absent' |
| log_time | TIMESTAMP | Recording time |
| logged_by | INT (FK) | User who recorded |
| method | ENUM | 'Manual', 'QR Scan', 'Search' |
| notes | VARCHAR(255) | Optional notes |

**Indexes**:
- PRIMARY KEY (id)
- UNIQUE KEY (event_id, attendee_id)
- INDEX (log_time, status)
- FOREIGN KEY (event_id) → events(id)
- FOREIGN KEY (attendee_id) → attendees(id)
- FOREIGN KEY (logged_by) → users(id)

### 7.5 Table: system_logs

Audit trail for system actions.

| Column | Type | Description |
|--------|------|-------------|
| id | INT (PK, AI) | Log entry ID |
| user_id | INT (FK) | Acting user |
| action | VARCHAR(50) | Action type |
| details | TEXT | Description |
| ip_address | VARCHAR(45) | Client IP |
| user_agent | VARCHAR(255) | Browser info |
| timestamp | TIMESTAMP | When occurred |

**Indexes**:
- PRIMARY KEY (id)
- INDEX (user_id, action, timestamp)
- FOREIGN KEY (user_id) → users(id)

### 7.6 Entity Relationships

```
users (1) ───< (N) events (created_by)
users (1) ───< (N) attendance_logs (logged_by)
users (1) ───< (N) system_logs (user_id)

attendees (1) ───< (N) attendance_logs (attendee_id)
events (1) ───< (N) attendance_logs (event_id)
```

---

## 8. API Reference

### 8.1 Attendance Recording

**Endpoint**: `POST /api/record_attendance.php`

**Parameters**:
- `event_id` (INT, required): Event identifier
- `attendee_id` (INT, required): Member identifier
- `status` (ENUM, optional): 'Present' (default) or 'Absent'
- `method` (ENUM, optional): 'Manual' (default), 'QR Scan', 'Search'
- `notes` (STRING, optional): Optional notes

**Response**:
```json
{
  "success": true,
  "message": "Attendance recorded successfully",
  "log_time": "2026-07-22 10:30:00",
  "time_formatted": "10:30 AM"
}
```

### 8.2 Attendee Search

**Endpoint**: `GET /api/search_attendees.php?q={query}`

**Parameters**:
- `q` (STRING, required): Search query (name, QR token, or contact)

**Response**:
```json
[
  {
    "id": 1,
    "fullname": "Juan Dela Cruz",
    "category": "MCYO",
    "qr_token": "AFB001001",
    "contact": "09123456789"
  }
]
```

### 8.3 Dashboard Statistics

**Endpoint**: `GET /api/dashboard_stats.php?type={type}`

**Types**:
- `trends`: Monthly attendance trends
- `categories`: Category distribution
- `retention`: Consistent vs at-risk counts
- `event_types`: Attendance by event type

**Response (trends)**:
```json
{
  "success": true,
  "trends": [
    {"month": "2026-01", "events": 4, "attendance": 156},
    {"month": "2026-02", "events": 5, "attendance": 203}
  ]
}
```

### 8.4 Event Attendance

**Endpoint**: `GET /api/get_attendance.php?event_id={id}`

**Parameters**:
- `event_id` (INT, required): Event identifier

**Response**:
```json
[
  {
    "id": 1,
    "event_id": 5,
    "attendee_id": 1,
    "status": "Present",
    "log_time": "2026-07-22 10:30:00",
    "fullname": "Juan Dela Cruz",
    "category": "MCYO"
  }
]
```

### 8.5 Export Endpoints

**CSV Export**:
```
GET /api/export_attendance.php?event_id={id}&format=csv
GET /api/export_members.php
GET /api/export_report.php?format=csv&from_date={date}&to_date={date}
```

**PDF Export**:
```
GET /api/export_report.php?format=pdf&from_date={date}&to_date={date}
```

**Excel Export**:
```
GET /api/export_report.php?format=xlsx&from_date={date}&to_date={date}
```

### 8.6 Attendance Deletion

**Endpoint**: `POST /api/delete_attendance.php`

**Parameters**:
- `attendance_id` (INT, required): Attendance record ID

**Response**:
```json
{
  "success": true,
  "message": "Attendance record deleted"
}
```

---

## 9. User Guide

### 9.1 Recording Attendance

1. Navigate to **Attendance** page
2. Select an event from the dropdown
3. Use **Search** tab to find members by name/QR
4. Or use **QR Scanner** tab for QR code scanning
5. Click "Mark Present" or "Mark Absent" for each member
6. Use "Mark All Present" for bulk action

### 9.2 Managing Members

1. Go to **Members** page
2. Click "Add Member" to create new entries
3. Fill in required fields (Full Name, Category)
4. QR token is auto-generated (AFB###### format)
5. Use filters to find specific members
6. Archive members instead of deleting (preserves data)

### 9.3 Creating Events

1. Go to **Events** page
2. Click "Create Event"
3. Set date, time, location, and type
4. Check "Recurring Event" for weekly events
5. Event status can be updated as it progresses

### 9.4 Generating Reports

1. Go to **Reports** page
2. Select date range and filters
3. View summary statistics
4. Export as PDF, CSV, or Excel
5. Top attendees leaderboard shows engagement

### 9.5 Dashboard Navigation

- **Statistics Cards**: Quick overview of key metrics
- **Charts**: Visual attendance trends and distribution
- **Recent Activity**: Latest system actions
- **Quick Actions**: Direct links to common tasks

### 9.6 User Roles

| Role | Permissions |
|------|-------------|
| **Admin** | Full access including user management, logs, system settings |
| **Operator** | Record attendance, manage members/events, view reports |
| **Viewer** | Read-only dashboard and report access |

---

## 10. Security Considerations

### 10.1 Current Security Measures

- **Password Hashing**: MD5 (legacy - upgrade recommended)
- **Session Management**: 1-hour timeout with regeneration
- **SQL Injection Prevention**: PDO prepared statements
- **XSS Protection**: Output encoding with htmlspecialchars()
- **Activity Logging**: Complete audit trail
- **Role-Based Access Control**: Three-tier permission system

### 10.2 Security Recommendations

#### Critical (Immediate Action Required)
1. **Replace MD5 with bcrypt**: Use `password_hash()` and `password_verify()`
2. **Change Default Credentials**: Remove admin/admin123 and operator/password
3. **Implement CSRF Protection**: Add tokens to all state-changing forms
4. **Secure Session Configuration**: Add secure, HttpOnly, SameSite flags

#### High Priority
5. **Add Rate Limiting**: Prevent brute force attacks on login
6. **Implement Input Validation**: Server-side validation for all inputs
7. **Remove Hardcoded Secrets**: Move webhook URL to environment variables
8. **Add IP Whitelisting**: For webhook endpoints

#### Medium Priority
9. **Enable HTTPS**: Required for production deployment
10. **Add CAPTCHA**: For repeated failed login attempts
11. **Implement API Authentication**: JWT tokens for API access
12. **Regular Security Audits**: Periodic code reviews

### 10.3 Known Vulnerabilities

Refer to `PROJECT_REVIEW_ISSUES.md` for detailed vulnerability analysis and `REMEDIATION_PLAN.md` for phased remediation approach.

---

## 11. Known Issues & Remediation

### 11.1 Critical Issues

#### 1. MD5 Password Hashing
**Severity**: CRITICAL  
**Impact**: Passwords can be cracked with modern hardware  
**Solution**: Migrate to bcrypt with password_hash()  
**Timeline**: Week 1

#### 2. Default Weak Credentials
**Severity**: CRITICAL  
**Impact**: Immediate security risk if not changed  
**Solution**: Force password change on first login  
**Timeline**: Week 1

#### 3. Exposed Webhook Secret
**Severity**: CRITICAL  
**Impact**: Data injection attacks possible  
**Solution**: Remove hardcoded values, use environment variables  
**Timeline**: Week 1

#### 4. Missing CSRF Protection
**Severity**: CRITICAL  
**Impact**: Cross-Site Request Forgery attacks  
**Solution**: Implement CSRF token generation/verification  
**Timeline**: Week 1

### 11.2 Database Issues

#### Category Data Inconsistency
**Severity**: HIGH  
**Issue**: Sample data uses 'Adult', 'Youth' vs schema 'MCYO', 'WMO'  
**Solution**: Update sample data to match schema ENUM values  
**Timeline**: Week 2

#### Missing Database Indexes
**Severity**: MEDIUM  
**Issue**: Performance degradation with large datasets  
**Solution**: Add indexes on frequently queried columns  
**Timeline**: Week 2

### 11.3 Code Quality Issues

#### Inconsistent Error Handling
**Severity**: MEDIUM  
**Issue**: Mixed response formats across API endpoints  
**Solution**: Implement standardized error response format  
**Timeline**: Week 3

#### Code Duplication
**Severity**: MEDIUM  
**Issue**: Church filtering logic repeated in multiple functions  
**Solution**: Create helper function for church context  
**Timeline**: Week 3

### 11.4 Performance Issues

#### N+1 Query Problem
**Severity**: MEDIUM  
**Issue**: Retention stats query causes performance issues  
**Solution**: Optimize query structure, add pagination  
**Timeline**: Month 2

#### No Cached Layer
**Severity**: MEDIUM  
**Issue**: Unnecessary database queries for static data  
**Solution**: Implement Redis or file-based caching  
**Timeline**: Month 2

### 11.5 Remediation Timeline

**Week 1**: Critical security fixes  
**Weeks 2-4**: Stability and data integrity  
**Month 2**: Performance and quality improvements  
**Month 3+**: Long-term enhancements

Refer to `REMEDIATION_PLAN.md` for detailed implementation steps.

---

## 12. Troubleshooting

### 12.1 Database Connection Issues

**Symptoms**: "Database connection failed" error

**Solutions**:
1. Verify `.env` credentials match MySQL setup
2. Ensure MySQL service is running
3. Check correct port (default 3306)
4. Test connection with phpMyAdmin
5. Check firewall settings

### 12.2 QR Scanner Not Working

**Symptoms**: Camera doesn't activate or scan fails

**Solutions**:
1. Ensure camera permissions are granted
2. Use HTTPS or localhost (required for camera access)
3. Test with modern browser (Chrome/Edge/Firefox)
4. Check if another application is using the camera
5. Verify HTML5-QRCode library is loaded

### 12.3 PDF/Excel Export Not Working

**Symptoms**: Export fails or returns error

**Solutions**:
1. Run `composer install` to install dependencies
2. Ensure `vendor/` directory exists
3. Check PHP error logs for details
4. Verify write permissions for export directory
5. Check Dompdf/PhpSpreadsheet compatibility

### 12.4 Session Timeout Too Fast

**Symptoms**: Logged out frequently

**Solutions**:
1. Edit `includes/auth_check.php`
2. Modify `$timeout = 3600;` (increase seconds)
3. Check server session garbage collection settings
4. Verify cookie settings in php.ini

### 12.5 Charts Not Displaying

**Symptoms**: Dashboard charts show blank

**Solutions**:
1. Check browser console for JavaScript errors
2. Verify Chart.js library is loaded
3. Check API endpoint responses
4. Ensure data is being fetched correctly
5. Test with different browser

### 12.6 Theme Not Persisting

**Symptoms**: Theme resets on page reload

**Solutions**:
1. Check localStorage is enabled in browser
2. Verify theme_handler.js is loaded
3. Check for JavaScript errors in console
4. Test in private/incognito mode
5. Clear browser cache and cookies

---

## 13. Development Guidelines

### 13.1 Coding Standards

#### PHP
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Add PHPDoc comments to all functions
- Use prepared statements for all database queries
- Implement proper error handling with try-catch

#### JavaScript
- Use strict mode (`'use strict'`)
- Prefer const/let over var
- Use arrow functions for callbacks
- Add JSDoc comments for complex functions
- Handle promises properly with catch blocks

#### CSS
- Use CSS custom properties for theming
- Follow BEM naming convention for classes
- Use mobile-first responsive design
- Minimize specificity in selectors
- Group related styles together

### 13.2 Git Workflow

#### Branch Naming
- `feature/feature-name`: New features
- `bugfix/bug-description`: Bug fixes
- `hotfix/critical-issue`: Production hotfixes
- `docs/documentation-updates`: Documentation changes

#### Commit Messages
```
type(scope): subject

body

footer
```

Types: feat, fix, docs, style, refactor, test, chore

### 13.3 Testing Guidelines

#### Unit Testing
- Test individual functions in isolation
- Mock database connections
- Test edge cases and error conditions
- Aim for >70% code coverage

#### Integration Testing
- Test API endpoints
- Test database operations
- Test authentication flows
- Test external integrations

#### E2E Testing
- Test user workflows
- Test cross-browser compatibility
- Test mobile responsiveness
- Test performance under load

### 13.4 Performance Optimization

#### Database
- Use indexes on frequently queried columns
- Optimize complex queries with EXPLAIN
- Implement query result caching
- Use connection pooling

#### Frontend
- Minimize HTTP requests
- Use lazy loading for images
- Implement code splitting
- Optimize asset delivery (CDN)

#### Caching
- Cache dashboard statistics (5-minute TTL)
- Cache category distributions (1-hour TTL)
- Implement browser caching headers
- Use CDN for static assets

### 13.5 Documentation Standards

#### Code Comments
- Document complex logic
- Explain non-obvious decisions
- Add usage examples for functions
- Keep comments up-to-date

#### API Documentation
- Document all endpoints
- Include request/response examples
- Document error responses
- Provide usage examples

#### User Documentation
- Write clear, step-by-step instructions
- Include screenshots where helpful
- Provide troubleshooting tips
- Keep documentation current

---

## Appendix A: Category Definitions

| Category | Full Name | Description |
|----------|-----------|-------------|
| MCYO | Married Couples Youth Organization | Young married couples ministry |
| WMO | Women's Missionary Organization | Women's ministry and outreach |
| CCMO | Children's Church Missionary Organization | Children's ministry |
| KIDS | Children's Ministry | General children's programs |
| Visitors | Visitors | First-time or visiting attendees |
| Other | Other | Uncategorized members |

## Appendix B: Event Status Workflow

```
Upcoming → Ongoing → Completed
    ↓
Cancelled (any point)
```

## Appendix C: QR Token Format

**Structure**: `AFB` + 6-digit zero-padded member ID  
**Example**: `AFB001042` (Member ID 1042)  
**Pattern**: Prefix + Zero-padded numeric ID  
**Generation**: `generateQRToken($attendeeId)` in `functions/attendance_logic.php`

## Appendix D: Environment Variables Reference

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| DB_HOST | Yes | localhost | Database server hostname |
| DB_PORT | No | 3306 | Database server port |
| DB_NAME | Yes | afb_mangaan_db | Database name |
| DB_USER | Yes | root | Database username |
| DB_PASSWORD | Yes | (empty) | Database password |
| QR_PREFIX | No | AFB | QR code token prefix |
| WEBHOOK_SECRET | Yes | (change) | Webhook authentication secret |
| MAKE_WEBHOOK_URL | No | (null) | make.com webhook URL |
| WEBHOOK_LOGGING | No | true | Enable webhook logging |
| SESSION_LIFETIME | No | 7200 | Session timeout in seconds |

---

## Support & Contact

For technical support, feature requests, or bug reports:
- **Documentation**: Refer to this file and related docs
- **Issues**: Check `PROJECT_REVIEW_ISSUES.md` for known problems
- **Remediation**: See `REMEDIATION_PLAN.md` for improvement roadmap
- **Technical Details**: Review `TECHNICAL_DOCUMENTATION.md` for implementation specifics

---

**Document Version**: 1.0  
**Last Updated**: July 22, 2026  
**Maintained By**: Development Team  
**License**: Proprietary to AFB Mangaan
