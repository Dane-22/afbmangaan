# AFB Mangaan Attendance & Analytics System - Technical Documentation

**Version**: 1.0.0  
**Last Updated**: March 2026  
**Repository**: Dane-22/afbmangaan

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Architecture & Technology Stack](#2-architecture--technology-stack)
3. [Database Schema](#3-database-schema)
4. [Core Functionalities](#4-core-functionalities)
5. [File Structure & Components](#5-file-structure--components)
6. [API Reference](#6-api-reference)
7. [Authentication & Security](#7-authentication--security)
8. [Frontend Features](#8-frontend-features)
9. [Integration Capabilities](#9-integration-capabilities)
10. [Installation & Setup](#10-installation--setup)

---

## 1. Project Overview

The **AFB Mangaan Attendance & Analytics System** is a comprehensive web-based solution designed for managing church attendance across two congregations:
- **AFB Mangaan** (Main congregation)
- **AFB Lettac Sur** (Branch congregation)

### Key Features
- **Hybrid Attendance Recording**: Manual entry + QR code scanning
- **Real-time Analytics Dashboard**: Chart.js visualizations with attendance trends
- **Member Management**: CRUD operations with auto-generated QR tokens
- **Event Management**: Schedule and track church events
- **Multi-format Export**: CSV, PDF, Excel reports
- **Role-based Access Control**: Admin, Operator, and Viewer roles
- **Activity Logging**: Complete audit trail with IP tracking
- **Dark/Light Theme**: User preference persistence
- **Mobile-responsive Design**: Optimized for tablets and phones

### User Roles
| Role | Permissions |
|------|-------------|
| **Admin** | Full access including user management, logs, system settings |
| **Operator** | Record attendance, manage members/events, view reports |
| **Viewer** | Read-only dashboard and report access |

---

## 2. Architecture & Technology Stack

### Backend
- **PHP 7.4+** with PDO for database operations
- **MySQL 5.7+** (utf8mb4 charset support)
- **Session-based authentication** with 1-hour timeout
- **MD5 password hashing** (legacy, recommended to upgrade to bcrypt)

### Frontend
- **HTML5** with semantic markup
- **CSS3** with CSS custom properties (variables) for theming
- **Vanilla JavaScript** (no framework dependencies)
- **Chart.js** for analytics visualizations
- **Phosphor Icons** for iconography
- **Animate.css** for transitions
- **GSAP** with ScrollTrigger for landing page animations
- **Cinzel & Inter fonts** for typography

### Database
- **MySQL** with InnoDB engine
- **Foreign key constraints** for referential integrity
- **Indexed columns** for performance optimization

### External Integrations
- **make.com webhook** for Google Sheets synchronization
- **Dompdf** for PDF generation
- **PhpSpreadsheet** for Excel exports

---

## 3. Database Schema

### 3.1 Tables Overview

#### `users` - System Users
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK, AI) | Unique user identifier |
| `church` | ENUM | 'AFB Mangaan' or 'AFB Lettac Sur' |
| `username` | VARCHAR(50) | Login username |
| `password` | VARCHAR(32) | MD5 hashed password |
| `fullname` | VARCHAR(100) | Display name |
| `role` | ENUM | 'admin', 'operator', 'viewer' |
| `status` | ENUM | 'Active' or 'Inactive' |
| `created_at` | TIMESTAMP | Account creation time |

**Default Accounts:**
- admin/admin123 (admin role)
- operator/password (operator role)

#### `attendees` - Church Members
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK, AI) | Member ID |
| `church` | ENUM | Associated church branch |
| `fullname` | VARCHAR(100) | Member name |
| `category` | ENUM | 'MCYO', 'WMO', 'CCMO', 'KIDS' |
| `contact` | VARCHAR(20) | Phone number |
| `email` | VARCHAR(100) | Email address |
| `qr_token` | VARCHAR(64) | Unique QR identifier (e.g., AFB001001) |
| `status` | ENUM | 'Active' or 'Archived' |
| `created_at` | TIMESTAMP | Registration date |

**QR Token Format**: `AFB` + 6-digit zero-padded ID (e.g., AFB001001)

#### `events` - Church Events
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK, AI) | Event ID |
| `church` | ENUM | Host church |
| `event_name` | VARCHAR(150) | Event title |
| `start_date` | DATE | Event start |
| `end_date` | DATE | Event end (for multi-day) |
| `event_time` | TIME | Scheduled time |
| `location` | VARCHAR(200) | Venue |
| `type` | ENUM | 'Sunday Service', 'Midweek Service', 'Special Event', 'Meeting', 'Other' |
| `status` | ENUM | 'Upcoming', 'Ongoing', 'Completed', 'Cancelled' |
| `description` | TEXT | Event details |
| `created_by` | INT (FK) | User who created |

#### `attendance_logs` - Attendance Records
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK, AI) | Record ID |
| `event_id` | INT (FK) | Reference to event |
| `attendee_id` | INT (FK) | Reference to member |
| `status` | ENUM | 'Present' or 'Absent' |
| `log_time` | TIMESTAMP | Recording time |
| `logged_by` | INT (FK) | User who recorded |
| `method` | ENUM | 'Manual', 'QR Scan', 'Search' |
| `notes` | VARCHAR(255) | Optional notes |

**Constraints**: Unique combination of (event_id, attendee_id)

#### `system_logs` - Audit Trail
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT (PK, AI) | Log entry ID |
| `user_id` | INT (FK) | Acting user |
| `action` | VARCHAR(50) | Action type |
| `details` | TEXT | Description |
| `ip_address` | VARCHAR(45) | Client IP (IPv6 compatible) |
| `user_agent` | VARCHAR(255) | Browser info |
| `timestamp` | TIMESTAMP | When occurred |

### 3.2 Entity Relationships
```
users (1) ───< (N) events (created_by)
users (1) ───< (N) attendance_logs (logged_by)
users (1) ───< (N) system_logs (user_id)

attendees (1) ───< (N) attendance_logs (attendee_id)
events (1) ───< (N) attendance_logs (event_id)
```

---

## 4. Core Functionalities

### 4.1 Dashboard (`dashboard.php`)
**Location**: @/dashboard.php:1-212

**Features:**
- **Church Indicator**: Shows current branch with visual badge
- **Statistics Cards**:
  - Total active members
  - Today's event with quick link
  - Consistent members count (70%+ attendance rate)
  - At-risk members count (30% or less attendance)
- **Interactive Charts**:
  - Attendance trends (line chart, 7-day)
  - Category distribution (doughnut chart)
  - Member retention (horizontal bar chart)
- **Recent Activity Feed**: Latest 5 system actions
- **Quick Actions**: Direct links to attendance, member add, event create

**Dependencies**:
- `attendance_logic.php` - Statistics functions
- `activity_logger.php` - Recent activity
- `dashboard_charts.js` - Chart rendering

### 4.2 Attendance Recording (`attendance.php`)
**Location**: @/attendance.php:1-396

**Features:**
- **Event Selection Dropdown**: Lists all events with status
- **QR Scanner Toggle**: Show/hide QR scanner interface
- **Dual View Modes**:
  - **Desktop**: Data table with sortable columns
  - **Mobile**: Grid cards with touch-friendly buttons
- **Quick Mark Buttons**: Present/Absent per member
- **Mark All Present**: Bulk action button
- **Export**: CSV export of current event attendance
- **Real-time Updates**: AJAX-based status updates without page reload

**Attendance Status Visual Indicators**:
- Green tint: Member marked Present
- Red tint: Member marked Absent
- No tint: Not yet recorded

**JavaScript Functions**:
- `changeEvent(eventId)` - Switch between events
- `toggleQR()` - Show/hide QR scanner
- `quickMark(attendeeId, status)` - AJAX attendance recording
- `updateRowStatus(attendeeId, status)` - UI update after recording
- `markAllPresent()` - Bulk present marking

### 4.3 Member Management (`members.php`)
**Location**: @/members.php:1-359

**Features:**
- **Search & Filter**:
  - Text search (name, QR token, contact)
  - Category filter (MCYO, WMO, CCMO, KIDS)
  - Status filter (Active, Archived, All)
- **CRUD Operations**:
  - Add new member with auto-generated QR token
  - Edit existing member details
  - Archive (soft delete) members
- **Dual View Modes**: Desktop table + mobile grid
- **Export**: Full member list to CSV

**QR Token Generation**:
```php
generateQRToken($attendeeId) // Returns: AFB###### format
```

**Form Fields**:
- Full Name (required)
- Category (required)
- Contact Number (optional)
- Email (optional)
- Status (Active/Archived for edits)

### 4.4 Event Management (`events.php`)
**Location**: @/events.php:1-419

**Features:**
- **Filter by**: Status, Type
- **CRUD Operations**:
  - Create single or multi-day events
  - **Recurring Events**: Auto-generate 52 weekly events
  - Edit event details
  - Status updates (Upcoming → Ongoing → Completed → Cancelled)
- **Event Types**: Sunday Service, Midweek Service, Special Event, Meeting, Other
- **Status Workflow**: Visual badges with color coding

**Recurring Event Creation**:
- Checkbox option for weekly recurrence
- Generates 52 weeks of events automatically
- Same time, location, and type for all instances

### 4.5 Reports & Analytics (`reports.php`)
**Location**: @/reports.php:1-306

**Features:**
- **Date Range Filter**: From/To date selection
- **Event Filter**: Specific event or all events
- **Category Filter**: Filter by member category
- **Summary Statistics**:
  - Total events in range
  - Total attendance count
  - Attendance rate percentage
  - Average per event
- **Detailed Records Table**: Member, Event, Date, Status, Method
- **Top Attendees Leaderboard**: Top 10 by attendance rate
- **Category Breakdown**: Visual stat cards by category
- **Export Options**: CSV, PDF, Excel

### 4.6 Landing Page (`index.php`)
**Location**: @/index.php:1-695

**Features:**
- **Hero Section**: Gradient background with animated elements
- **Feature Grid**: 6 key features with icons
- **Church Cards**: AFB Mangaan and AFB Lettac Sur info
- **Call-to-Action**: Sign in prompt
- **Responsive Navigation**: Mobile-friendly menu
- **Theme Toggle**: Dark/light mode switcher

**Animations**:
- Animate.css fade-in effects
- Staggered delays for visual hierarchy

---

## 5. File Structure & Components

### 5.1 Root Level Files

| File | Purpose |
|------|---------|
| `index.php` | Public landing page |
| `login.php` | Authentication page |
| `dashboard.php` | Main dashboard with analytics |
| `attendance.php` | Attendance recording interface |
| `members.php` | Member management |
| `events.php` | Event scheduling |
| `reports.php` | Reports and exports |
| `logs.php` | System audit logs (admin only) |
| `settings.php` | System configuration |
| `logout.php` | Session termination |

### 5.2 Configuration (`/config/`)

#### `db.php`
**Location**: @/config/db.php:1-80

**Responsibilities**:
- Environment variable loading from `.env`
- PDO database connection with error handling
- Auto-database creation if not exists
- UTF-8 collation configuration
- `getDB()` helper function

**Environment Variables**:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=afb_mangaan_db
DB_USER=root
DB_PASSWORD=
```

#### `webhook.php`
**Location**: @/config/webhook.php (open in IDE)

**Purpose**: make.com integration endpoint for Google Sheets synchronization

### 5.3 Functions (`/functions/`)

#### `attendance_logic.php`
**Location**: @/functions/attendance_logic.php:1-274

**Core Functions**:

| Function | Purpose |
|----------|---------|
| `recordAttendance($eventId, $attendeeId, $status, $method, $notes)` | Main attendance recording with upsert logic |
| `searchAttendees($query, $limit)` | Search by name/QR/contact |
| `getAttendeeByQR($qrToken)` | QR token lookup |
| `getTodayEvent()` | Get current day's active event |
| `getEvents($filters)` | List events with optional filters |
| `getEventAttendance($eventId)` | All attendance for an event |
| `getEventStats($eventId)` | Present/absent counts with category breakdown |
| `getRetentionStats($months)` | Consistent vs at-risk member analysis |
| `getAttendanceTrends($months)` | Monthly attendance data for charts |
| `deleteAttendance($attendanceId)` | Remove attendance record |
| `generateQRToken($attendeeId)` | Create QR identifier |

#### `auth_functions.php`
**Location**: @/functions/auth_functions.php:1-201

**Authentication Functions**:

| Function | Purpose |
|----------|---------|
| `loginUser($username, $password, $church)` | Validate and create session |
| `logoutUser()` | Destroy session and log action |
| `isLoggedIn()` | Check session status |
| `hasRole($requiredRoles)` | Role authorization check |
| `getCurrentUser()` | Return user info array |
| `getCurrentChurch()` | Get active church branch |
| `updatePassword($userId, $current, $new)` | Password change |
| `createUser($username, $password, $fullname, $role)` | Admin user creation |
| `updateUserStatus($userId, $status)` | Activate/deactivate user |
| `getAllUsers($status)` | List all system users |

#### `activity_logger.php`
**Location**: @/functions/activity_logger.php:1-146

**Logging Functions**:

| Function | Purpose |
|----------|---------|
| `logActivity($userId, $action, $details)` | Write to system_logs table |
| `getSystemLogs($filters, $page, $perPage)` | Paginated log retrieval |
| `getLogActions()` | Distinct action types for filters |
| `clearOldLogs($daysToKeep)` | Log cleanup (default 90 days) |
| `getLoginSummary($days)` | Daily login statistics |
| `getRecentActivity($limit)` | Dashboard activity feed |

#### `report_engine.php`
**Location**: @/functions/report_engine.php:1-246

**Report Functions**:

| Function | Purpose |
|----------|---------|
| `getAttendanceReport($eventId, $from, $to, $category)` | Detailed attendance query |
| `getReportSummary($from, $to)` | Aggregated statistics |
| `exportToCSV($data, $filename)` | CSV download headers |
| `getMemberAttendanceHistory($attendeeId, $limit)` | Individual member timeline |
| `getMonthlyComparison($year)` | Year-over-year data |
| `getTopAttendees($limit, $from, $to)` | Leaderboard by attendance rate |

### 5.4 Includes (`/includes/`)

#### `auth_check.php`
**Location**: @/includes/auth_check.php:1-31

**Responsibilities**:
- Start/resume session
- Verify login status (redirect if not logged in)
- Session timeout check (1 hour)
- Reset timer on activity
- Load current user info

#### `header.php`
**Responsibilities**:
- HTML5 boilerplate
- CSS/JS includes
- Sidebar navigation
- Theme toggle button
- User profile dropdown
- Church indicator

#### `footer.php`
**Responsibilities**:
- Closing tags
- Toast notification container
- Global JavaScript initialization

#### `sidebar.php`
**Responsibilities**:
- Logo and branding
- Navigation menu items
- Active state highlighting
- Collapsible on mobile

### 5.5 API Endpoints (`/api/`)

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `record_attendance.php` | POST | Record/update attendance |
| `search_attendees.php` | GET | Search members |
| `get_attendance.php` | GET | Event attendance list |
| `delete_attendance.php` | POST | Remove attendance record |
| `dashboard_stats.php` | GET | Chart data (trends, categories, retention) |
| `export_attendance.php` | GET | CSV export for event |
| `export_members.php` | GET | CSV export for members |
| `export_report.php` | GET | CSV/PDF/Excel reports |

**Example API Response**:
```json
{
  "success": true,
  "message": "Attendance recorded successfully"
}
```

### 5.6 Assets (`/assets/`)

#### CSS (`/assets/css/`)

**`main.css`** (1889 lines)
**Location**: @/assets/css/main.css:1-200

**Sections**:
- CSS variables (light/dark themes)
- Reset & base styles
- Layout (app container, sidebar)
- Components (buttons, cards, forms, tables, badges)
- Utilities (spacing, text, display)
- Responsive breakpoints
- QR scanner styling
- Mobile grid view styles

**Theme System**:
```css
:root { /* Light theme variables */ }
[data-theme="dark"] { /* Dark theme overrides */ }
```

**`animations.css`**
Custom animations extending Animate.css

#### JavaScript (`/assets/js/`)

**`dashboard_charts.js`**
**Location**: @/assets/js/dashboard_charts.js:1-348

**Features**:
- Chart.js initialization for 4 chart types
- AJAX data fetching from dashboard_stats.php
- Theme-aware chart updates
- Export chart data to JSON
- Exposed API: `window.DashboardCharts`

**Chart Types**:
1. **Attendance Trends**: Line chart (attendance + events)
2. **Category Distribution**: Doughnut chart
3. **Event Type**: Bar chart
4. **Member Retention**: Horizontal bar chart

**`attendance_ajax.js`**
QR scanner integration and attendance AJAX handlers

**`theme_handler.js`**
- Theme toggle functionality
- LocalStorage persistence
- System preference detection
- `themechange` custom event dispatch

---

## 6. API Reference

### 6.1 Attendance Recording

**Endpoint**: `POST /api/record_attendance.php`

**Parameters**:
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `event_id` | INT | Yes | Event identifier |
| `attendee_id` | INT | Yes | Member identifier |
| `status` | ENUM | No | 'Present' (default) or 'Absent' |
| `method` | ENUM | No | 'Manual' (default), 'QR Scan', 'Search' |
| `notes` | STRING | No | Optional notes |

**Response**:
```json
{
  "success": true|false,
  "message": "Status message"
}
```

### 6.2 Dashboard Statistics

**Endpoint**: `GET /api/dashboard_stats.php?type={type}`

**Types**:
- `trends` - Monthly attendance trends
- `categories` - Category distribution
- `retention` - Consistent vs at-risk counts
- `event_types` - Attendance by event type

**Response Example (trends)**:
```json
{
  "success": true,
  "trends": [
    {"month": "2026-01", "events": 4, "attendance": 156},
    {"month": "2026-02", "events": 5, "attendance": 203}
  ]
}
```

### 6.3 Attendee Search

**Endpoint**: `GET /api/search_attendees.php?q={query}`

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

### 6.4 Export Endpoints

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

---

## 7. Authentication & Security

### 7.1 Session Management

**Timeout**: 1 hour (3600 seconds)  
**Cookie**: PHP session with secure attributes  
**Regeneration**: Timer resets on each activity

**Session Variables**:
```php
$_SESSION['user_id']      // User ID
$_SESSION['username']     // Login name
$_SESSION['fullname']     // Display name
$_SESSION['role']         // admin|operator|viewer
$_SESSION['church']       // AFB Mangaan|AFB Lettac Sur
$_SESSION['login_time']   // Unix timestamp
```

### 7.2 Password Security

**Current**: MD5 hashing (legacy)  
**Recommended Upgrade**: Password_hash() with bcrypt

### 7.3 Activity Logging

All significant actions are logged:
- LOGIN / LOGOUT
- ATTENDANCE_RECORD / ATTENDANCE_UPDATE / ATTENDANCE_DELETE
- MEMBER_CREATE / MEMBER_UPDATE / MEMBER_ARCHIVE
- EVENT_CREATE / EVENT_UPDATE / EVENT_STATUS
- USER_CREATED / PASSWORD_CHANGE
- LOGS_CLEARED

**Log Details Captured**:
- User ID
- Action type
- Description
- IP address
- User agent
- Timestamp

### 7.4 Input Sanitization

- **SQL**: PDO prepared statements (parameterized queries)
- **HTML**: `htmlspecialchars()` for output
- **XSS Protection**: Output encoding on all user data

---

## 8. Frontend Features

### 8.1 Responsive Design

**Breakpoints**:
- Mobile: < 768px
- Tablet: 768px - 1024px
- Desktop: > 1024px

**Mobile-First Approach**:
- Desktop/Mobile view toggle classes
- Touch-friendly button sizing (min 44px)
- Stacked layouts on small screens

### 8.2 Theme System

**Storage**: `localStorage.theme`  
**Values**: 'light' | 'dark'  
**Attribute**: `<html data-theme="dark">`

**CSS Implementation**:
```css
:root { /* Light theme */ }
[data-theme="dark"] { /* Dark overrides */ }
```

**JavaScript**:
```javascript
theme_handler.js // Toggle and persistence
```

### 8.3 Animations

**Library**: Animate.css  
**Custom**: `animations.css`

**Common Animations**:
- `animate__fadeIn` - Page elements
- `animate__fadeInUp` - Cards on scroll
- `animate__fadeInDown` - Notifications

### 8.4 Icons

**Library**: Phosphor Icons (unpkg CDN)  
**Style**: Outlined, 1.5rem default  
**Usage**: `<i class="ph ph-icon-name"></i>`

---

## 9. Integration Capabilities

### 9.1 Google Sheets Sync (make.com)

**File**: `functions/make_sync.php`

**Function**: `syncAttendanceToSheets($eventId, $attendeeId, $status, $method)`

**Trigger**: Every attendance recording

**Webhook**: Configured in `config/webhook.php`

### 9.2 Export Formats

**CSV**: Universal compatibility  
**PDF**: Dompdf library, formatted reports  
**Excel**: PhpSpreadsheet, native .xlsx

---

## 10. Installation & Setup

### 10.1 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (for dependencies)
- WAMP/XAMPP/MAMP (local development)

### 10.2 Installation Steps

1. **Clone Repository**:
```bash
git clone https://github.com/Dane-22/afbmangaan.git
cd afbmangaan
```

2. **Install Dependencies**:
```bash
composer install
```

3. **Environment Configuration**:
```bash
cp .env.example .env
```

Edit `.env`:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=afb_mangaan_db
DB_USER=root
DB_PASSWORD=your_password
```

4. **Database Setup**:
```bash
# Option 1: Import SQL
mysql -u root -p < afb_mangaan_db.sql

# Option 2: Auto-creation (system will create on first run)
```

5. **Web Server Configuration**:
   - Point document root to project folder
   - Ensure `mod_rewrite` enabled (Apache)
   - Set proper permissions (755 directories, 644 files)

### 10.3 Default Login Credentials

| Username | Password | Role | Church |
|----------|----------|------|--------|
| admin | admin123 | admin | AFB Mangaan |
| operator | password | operator | AFB Mangaan |
| admin | admin123 | admin | AFB Lettac Sur |
| operator | password | operator | AFB Lettac Sur |

### 10.4 File Permissions

```bash
chmod 755 /assets/uploads (if applicable)
chmod 644 *.php
chmod 644 *.css *.js
```

### 10.5 Security Recommendations

1. **Change Default Passwords** immediately
2. **Enable HTTPS** for production
3. **Update MD5 to bcrypt** for password hashing
4. **Restrict Database Access** to localhost
5. **Regular Backups** of MySQL database
6. **Keep Dependencies Updated**

---

## Appendix A: Category Definitions

| Category | Description |
|----------|-------------|
| **MCYO** | Married Couples Youth Organization |
| **WMO** | Women's Missionary Organization |
| **CCMO** | Children's Church Missionary Organization |
| **KIDS** | Children's Ministry |

## Appendix B: Event Status Workflow

```
Upcoming → Ongoing → Completed
    ↓
Cancelled (any point)
```

## Appendix C: QR Token Format

**Structure**: `AFB` + 6-digit member ID
**Example**: `AFB001042` (Member ID 1042)
**Pattern**: Prefix + Zero-padded numeric ID

---

**End of Documentation**
