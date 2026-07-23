# AFB Mangaan Attendance System - API Documentation

## Base URL
`/api/`

## Authentication

The API supports dual authentication modes:
1. **Cookie-based Session Auth:** Automatically used when API calls are made from the authenticated frontend dashboard (browser).
2. **JSON Web Token (JWT) Auth:** Intended for external/programmatic access. Pass the JWT in the `Authorization` header.

**Header Example:**
```
Authorization: Bearer <your_jwt_token>
```

---

## Endpoints

### 1. Dashboard Statistics
Retrieve aggregated statistics for the dashboard.

**Endpoint:** `GET /dashboard_stats.php`

**Parameters:**
- `type` (optional): `all` (default), `trends`, `categories`, `event_types`, `retention`

**Response (Success):**
```json
{
    "success": true,
    "trends": [...],
    "categories": [...],
    "total_members": 150,
    "consistent": [...],
    "at_risk": [...],
    "consistent_count": 80,
    "at_risk_count": 20
}
```

### 2. Health Check
Public endpoint to verify system and database connectivity.

**Endpoint:** `GET /health.php`

**Parameters:** None

**Response:**
```json
{
    "status": "healthy",
    "timestamp": "2026-07-23T12:00:00+00:00",
    "services": {
        "database": "ok",
        "webhook": "configured"
    }
}
```

---

## Standard Error Responses
All API endpoints share standard JSON error structures.

**401 Unauthorized**
```json
{
    "success": false,
    "error": "Unauthorized - Invalid or missing JWT"
}
```

**400 Bad Request**
```json
{
    "success": false,
    "error": "Invalid input provided",
    "code": 400,
    "details": null
}
```
