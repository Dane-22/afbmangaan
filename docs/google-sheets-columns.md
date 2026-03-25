# Google Sheets Column Mapping Guide

**make.com Webhook URL:** `https://hook.eu1.make.com/toplmvfj479kvfkx4f0erlifwrsnstml`

---

## Database Structure Overview

The AFB Mangaan Attendance System has 5 main tables:

1. **attendees** - Church members
2. **events** - Church events/services
3. **attendance_logs** - Attendance records
4. **users** - System users (admins/operators)
5. **system_logs** - Activity logs

---

## Sheet 1: Members Import (Sheets → Database)

Use this sheet to bulk import or update church members.

### Required Columns

| Column | Data Type | Required | Description | Example |
|--------|-----------|----------|-------------|---------|
| **fullname** | Text | ✓ YES | Member's full name | Juan Dela Cruz |
| **category** | Text | No | Age group: Youth, Adult, Senior, Child | Adult |
| **contact** | Text | No | Phone number | 09123456789 |
| **email** | Text | No | Email address | juan@email.com |
| **qr_token** | Text | No | QR code (auto-generated if empty) | AFB001001 |
| **status** | Text | No | Active or Archived | Active |

### Column Headers (Row 1)

Copy this exact header row into your Google Sheet:

```
fullname	category	contact	email	qr_token	status
```

Or as a comma-separated header:
```csv
fullname,category,contact,email,qr_token,status
```

### Sample Data

| fullname | category | contact | email | qr_token | status |
|----------|----------|---------|-------|----------|--------|
| Juan Dela Cruz | Adult | 09123456789 | juan@email.com | | Active |
| Maria Santos | Youth | 09187654321 | maria@email.com | | Active |
| Pedro Penduko | Senior | 09111222333 | pedro@email.com | | Active |
| Ana Makiling | Adult | 09444555666 | ana@email.com | | Active |
| Diego Silang | Youth | 09777888999 | diego@email.com | | Active |

### Category Options

- `Youth` - Ages 13-30
- `Adult` - Ages 31-59
- `Senior` - Ages 60+
- `Child` - Ages 0-12

### Status Options

- `Active` - Member is active (default)
- `Archived` - Member is no longer active

### QR Token Notes

- If left empty, the system auto-generates: `AFB` + padded ID (e.g., `AFB001001`)
- Each QR token must be unique
- QR tokens are used for QR code scanning at events

---

## Sheet 2: Events Import (Sheets → Database)

Use this sheet to bulk import or update church events.

### Required Columns

| Column | Data Type | Required | Description | Example |
|--------|-----------|----------|-------------|---------|
| **event_name** | Text | ✓ YES | Event name | Sunday Worship Service |
| **event_date** | Date | ✓ YES | Event date (YYYY-MM-DD) | 2026-03-16 |
| **event_time** | Time | No | Event time (HH:MM:SS) | 09:00:00 |
| **location** | Text | No | Event location | Main Sanctuary |
| **type** | Text | No | Event type | Sunday Service |
| **status** | Text | No | Event status | Upcoming |
| **description** | Text | No | Event description | Regular Sunday service |

### Column Headers (Row 1)

```
event_name	event_date	event_time	location	type	status	description
```

Or CSV format:
```csv
event_name,event_date,event_time,location,type,status,description
```

### Sample Data

| event_name | event_date | event_time | location | type | status | description |
|------------|------------|------------|----------|------|--------|-------------|
| Sunday Worship Service | 2026-03-16 | 09:00:00 | Main Sanctuary | Sunday Service | Upcoming | Regular Sunday service |
| Midweek Prayer Meeting | 2026-03-18 | 19:00:00 | Fellowship Hall | Midweek Service | Upcoming | Wednesday prayer meeting |
| Youth Fellowship Night | 2026-03-20 | 18:00:00 | Youth Room | Special Event | Upcoming | Monthly youth gathering |
| Church Anniversary | 2026-04-05 | 17:00:00 | Main Sanctuary | Special Event | Upcoming | 25th Church Anniversary |

### Event Type Options

- `Sunday Service` - Regular Sunday worship
- `Midweek Service` - Wednesday prayer/Bible study
- `Special Event` - Special occasions
- `Meeting` - Church meetings
- `Other` - Miscellaneous events

### Event Status Options

- `Upcoming` - Future event (default)
- `Ongoing` - Currently happening
- `Completed` - Past event
- `Cancelled` - Cancelled event

### Date/Time Format

- **Date**: Use `YYYY-MM-DD` format (e.g., `2026-03-16`)
- **Time**: Use 24-hour format `HH:MM:SS` (e.g., `09:00:00`, `14:30:00`)

---

## Sheet 3: Attendance Export (Database → Sheets)

This sheet receives attendance data FROM your PHP application.

The system automatically sends data when attendance is recorded.

### Columns (Auto-populated)

| Column | Description | Example |
|--------|-------------|---------|
| **timestamp** | When sync occurred | 2026-03-16 09:15:32 |
| **event_id** | Event ID from database | 1 |
| **event_name** | Name of the event | Sunday Worship Service |
| **event_date** | Date of the event | 2026-03-16 |
| **event_time** | Time of the event | 09:00:00 |
| **event_type** | Type of event | Sunday Service |
| **event_location** | Location of event | Main Sanctuary |
| **attendee_id** | Attendee ID | 5 |
| **attendee_name** | Name of attendee | Juan Dela Cruz |
| **attendee_category** | Attendee's category | Adult |
| **attendee_qr_token** | QR code used | AFB001001 |
| **attendee_contact** | Contact number | 09123456789 |
| **attendee_email** | Email address | juan@email.com |
| **status** | Present or Absent | Present |
| **method** | How attendance was recorded | QR Scan |
| **sync_timestamp** | When record was synced | 2026-03-16 09:15:33 |

### Column Headers (Row 1)

```
timestamp	event_id	event_name	event_date	event_time	event_type	event_location	attendee_id	attendee_name	attendee_category	attendee_qr_token	attendee_contact	attendee_email	status	method	sync_timestamp
```

---

## make.com Configuration

### For Members Import (Sheet → PHP)

**Trigger Module:** Google Sheets → Watch Rows

**HTTP Module Configuration:**

```
URL: https://hook.eu1.make.com/toplmvfj479kvfkx4f0erlifwrsnstml
Method: POST
Headers:
  X-Webhook-Secret: your-configured-secret
  Content-Type: application/json

Body (JSON):
{
  "entity": "attendee",
  "action": "create",
  "import_type": "single",
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

**Import Types:**
- `single` - Import one record at a time (default)
- `bulk` - Import multiple records as array
- `sync` - Synchronization operation
- `backup` - Restore from backup data

### For Events Import (Sheet → PHP)

**HTTP Module Body:**

```json
{
  "entity": "event",
  "action": "create",
  "data": {
    "event_name": "{{1.event_name}}",
    "event_date": "{{1.event_date}}",
    "event_time": "{{1.event_time}}",
    "location": "{{1.location}}",
    "type": "{{1.type}}",
    "status": "{{1.status}}",
    "description": "{{1.description}}"
  }
}
```

### For Attendance Export (PHP → Sheet)

**Trigger Module:** Webhooks → Custom webhook

**Google Sheets Module:** Add Row

Map these values:
- `{{timestamp}}` → Timestamp column
- `{{data.event_name}}` → Event Name column
- `{{data.attendee_name}}` → Attendee Name column
- etc.

---

## Data Validation Rules

### Attendee (Member) Validation

| Field | Rules |
|-------|-------|
| fullname | Required, max 100 characters |
| category | Must be: Youth, Adult, Senior, Child (default: Adult) |
| contact | Optional, max 20 characters |
| email | Optional, max 100 characters, should be valid email format |
| qr_token | Optional, must be unique if provided |
| status | Must be: Active or Archived (default: Active) |

### Event Validation

| Field | Rules |
|-------|-------|
| event_name | Required, max 150 characters |
| event_date | Required, must be valid date (YYYY-MM-DD) |
| event_time | Optional, format HH:MM:SS |
| location | Optional, max 200 characters |
| type | Must be: Sunday Service, Midweek Service, Special Event, Meeting, Other (default: Sunday Service) |
| status | Must be: Upcoming, Ongoing, Completed, Cancelled (default: Upcoming) |
| description | Optional, text field |

---

## Tips for Google Sheets Setup

1. **Format dates properly**: Use Date format for event_date column
2. **Data validation**: Add dropdown lists for category, type, and status columns
3. **Freeze first row**: Keep headers visible when scrolling
4. **Share appropriately**: Only share with authorized church staff
5. **Back up regularly**: Export CSV copies periodically
6. **Test first**: Use "Active" status only after testing with a few records

---

## Example: Complete Google Sheets Setup

### Members Sheet

```
┌─────────────┬──────────┬─────────────┬─────────────────┬──────────┬─────────┐
│ fullname    │ category │ contact     │ email           │ qr_token │ status  │
├─────────────┼──────────┼─────────────┼─────────────────┼──────────┼─────────┤
│ Juan Cruz   │ Adult    │ 09123456789 │ juan@email.com  │          │ Active  │
│ Maria S.    │ Youth    │ 09187654321 │ maria@email.com │          │ Active  │
│ Pedro P.    │ Senior   │ 09111222333 │ pedro@email.com │          │ Active  │
└─────────────┴──────────┴─────────────┴─────────────────┴──────────┴─────────┘
```

### Events Sheet

```
┌──────────────────────┬────────────┬────────────┬────────────────┬────────────────┬───────────┬────────────────────────┐
│ event_name           │ event_date │ event_time │ location       │ type           │ status    │ description            │
├──────────────────────┼────────────┼────────────┼────────────────┼────────────────┼───────────┼────────────────────────┤
│ Sunday Worship       │ 2026-03-16 │ 09:00:00   │ Main Sanctuary │ Sunday Service │ Upcoming  │ Regular Sunday service │
│ Midweek Prayer       │ 2026-03-18 │ 19:00:00   │ Fellowship Hall│ Midweek Service│ Upcoming  │ Wednesday prayer       │
└──────────────────────┴────────────┴────────────┴────────────────┴────────────────┴───────────┴────────────────────────┘
```

---

## Troubleshooting Common Issues

### "Fullname is required" Error
- Ensure the fullname column has data in all rows
- Check for empty cells or spaces-only values

### "Invalid category" Error
- Use exact values: Youth, Adult, Senior, Child
- Check for typos or extra spaces

### "Event already exists" Error
- Combination of event_name + event_date must be unique
- Change the name or date to create a new event

### "Invalid date format" Error
- Use YYYY-MM-DD format (e.g., 2026-03-16)
- Don't use MM/DD/YYYY or other formats

### Data not syncing
1. Check make.com execution log for errors
2. Verify webhook URL is correct
3. Check X-Webhook-Secret header matches config/webhook.php
4. Ensure PHP server is accessible from internet (use ngrok for local)

---

## Security Notes

⚠️ **Important:** Keep your Google Sheets private and only share with authorized church staff.

⚠️ **Webhook Secret:** The X-Webhook-Secret header must match the secret in `config/webhook.php`

⚠️ **Rate Limits:** Google Sheets API has limits (60 writes/minute). For bulk imports, add delays in make.com.

---

**Document Version:** 1.0  
**Created:** March 16, 2026  
**make.com Webhook:** https://hook.eu1.make.com/toplmvfj479kvfkx4f0erlifwrsnstml
