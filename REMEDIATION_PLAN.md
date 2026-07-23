# AFB Mangaan Security & Quality Remediation Plan

This plan provides a phased approach to address 40 identified issues in the AFB Mangaan Attendance System, prioritizing critical security vulnerabilities while systematically improving code quality, performance, and maintainability over a 3-month timeline.

---

## Phase 1: Critical Security Fixes (Week 1)

### 1.1 Replace MD5 with Bcrypt Password Hashing
**Files**: `functions/auth_functions.php`
**Priority**: CRITICAL

**Implementation**:
```php
// Replace all MD5 usage with password_hash/password_verify
// Line 20: Change login validation
if ($user && password_verify($password, $user['password'])) {
    // Create session
}

// Line 105: Change password update
$newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

// Line 132: Change user creation
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
```

**Migration Script Required**:
- Create migration script to rehash all existing passwords
- Force password reset on next login for all users
- Update database schema to accommodate longer password hashes

---

### 1.2 Remove Default Credentials & Force Password Reset
**Files**: `afb_mangaan_db.sql`, `install.php`, `login.php`
**Priority**: CRITICAL

**Implementation**:
1. Remove hardcoded credentials from SQL file
2. Add password reset requirement flag to users table
3. Implement first-login password change flow
4. Update documentation to remove credential references

**Database Migration**:
```sql
ALTER TABLE users ADD COLUMN must_change_password BOOLEAN DEFAULT TRUE;
```

---

### 1.3 Remove Hardcoded Webhook Configuration
**Files**: `config/webhook.php`
**Priority**: CRITICAL

**Implementation**:
```php
// Remove hardcoded fallbacks
$webhookConfig = [
    'secret' => getenv('WEBHOOK_SECRET') ?: throw new Exception('WEBHOOK_SECRET must be set'),
    'make_webhook_url' => getenv('MAKE_WEBHOOK_URL') ?: null, // Allow null for optional
    'enable_logging' => getenv('WEBHOOK_LOGGING') === 'true',
];
```

**Action Items**:
- Update .env.example with required variables
- Add startup validation for required config
- Document webhook setup process

---

### 1.4 Implement CSRF Protection
**Files**: All form pages, `includes/`
**Priority**: CRITICAL

**Implementation**:
1. Create CSRF token generation/verification functions:

```php
// functions/csrf.php
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
```

2. Add token to all forms:
```php
<input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
```

3. Verify on POST requests:
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        die('CSRF validation failed');
    }
}
```

---

### 1.5 Secure Session Configuration
**Files**: `includes/auth_check.php`, `config/session.php`
**Priority**: HIGH

**Implementation**:
```php
// Add at top of auth_check.php before session_start()
ini_set('session.cookie_secure', '1'); // HTTPS only
ini_set('session.cookie_httponly', '1'); // No JavaScript access
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
ini_set('session.use_strict_mode', '1'); // Reject uninitialized IDs

// Regenerate session ID on login
session_regenerate_id(true);
```

**Create new file**: `config/session.php` for centralized session config

---

## Phase 2: Stability & Data Integrity (Weeks 2-4)

### 2.1 Fix Database Category Data Inconsistency
**Files**: `afb_mangaan_db.sql`
**Priority**: HIGH

**Implementation**:
1. Update sample data to match schema ENUM values:
```sql
-- Replace incorrect values
UPDATE attendees SET category = 'MCYO' WHERE category = 'Adult';
UPDATE attendees SET category = 'WMO' WHERE category = 'Youth';  
UPDATE attendees SET category = 'CCMO' WHERE category = 'Senior';
UPDATE attendees SET category = 'KIDS' WHERE category = 'Child';
```

2. Update INSERT statements in SQL file to use correct values
3. Add category mapping documentation

---

### 2.2 Add Input Validation Layer
**Files**: `api/`, `functions/validation.php`
**Priority**: HIGH

**Implementation**:
1. Create validation library:
```php
// functions/validation.php
function validateInteger($value, $min = null, $max = null) {
    if (!is_numeric($value)) return false;
    $int = (int)$value;
    if ($min !== null && $int < $min) return false;
    if ($max !== null && $int > $max) return false;
    return true;
}

function validateString($value, $minLength = 0, $maxLength = 255) {
    if (!is_string($value)) return false;
    $len = strlen($value);
    return $len >= $minLength && $len <= $maxLength;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
```

2. Update API endpoints to use validation:
```php
// api/record_attendance.php
if (!validateInteger($_POST['event_id'], 1)) {
    echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
    exit;
}
```

---

### 2.3 Implement Rate Limiting
**Files**: `functions/rate_limiter.php`, `api/`
**Priority**: HIGH

**Implementation**:
1. Create rate limiter using database:
```php
// functions/rate_limiter.php
function checkRateLimit($identifier, $limit = 100, $window = 3600) {
    $pdo = getDB();
    $key = md5($identifier);
    
    // Clean old entries
    $pdo->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)")->execute([$window]);
    
    // Check current count
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$key, $window]);
    $count = $stmt->fetch()['count'];
    
    if ($count >= $limit) {
        return false;
    }
    
    // Log this request
    $pdo->prepare("INSERT INTO rate_limits (identifier, created_at) VALUES (?, NOW())")->execute([$key]);
    return true;
}
```

2. Create rate_limits table:
```sql
CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_identifier (identifier),
    KEY idx_created_at (created_at)
);
```

3. Add to API endpoints:
```php
if (!checkRateLimit($_SERVER['REMOTE_ADDR'], 60, 60)) {
    echo json_encode(['success' => false, 'message' => 'Rate limit exceeded']);
    exit;
}
```

---

### 2.4 Add Database Indexes
**Files**: `afb_mangaan_db.sql`
**Priority**: MEDIUM

**Implementation**:
```sql
-- Add performance indexes
CREATE INDEX idx_attendees_fullname ON attendees(fullname);
CREATE INDEX idx_attendees_qr_token ON attendees(qr_token);
CREATE INDEX idx_attendance_log_time ON attendance_logs(log_time);
CREATE INDEX idx_events_start_date ON events(start_date);
CREATE INDEX idx_events_status ON events(status);
CREATE INDEX idx_system_logs_timestamp ON system_logs(timestamp);
```

---

### 2.5 Standardize Error Handling
**Files**: `functions/error_handler.php`, all API files
**Priority**: MEDIUM

**Implementation**:
1. Create standardized error response:
```php
// functions/error_handler.php
function sendErrorResponse($message, $code = 400, $details = null) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code,
        'details' => $details
    ]);
    exit;
}

function sendSuccessResponse($data = null) {
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    exit;
}
```

2. Update all API endpoints to use these functions

---

## Phase 3: Performance & Quality (Month 2)

### 3.1 Implement Caching Layer
**Files**: `functions/cache.php`, config
**Priority**: MEDIUM

**Implementation**:
1. Choose caching strategy (Redis recommended, file-based fallback):
```php
// functions/cache.php
function cacheGet($key, $default = null) {
    // Try Redis first, fallback to file cache
    $redis = getRedisConnection();
    if ($redis) {
        $value = $redis->get($key);
        if ($value !== false) return json_decode($value, true);
    }
    
    // File cache fallback
    $file = __DIR__ . '/../cache/' . md5($key) . '.json';
    if (file_exists($file) && (time() - filemtime($file)) < 3600) {
        return json_decode(file_get_contents($file), true);
    }
    
    return $default;
}

function cacheSet($key, $value, $ttl = 3600) {
    $redis = getRedisConnection();
    if ($redis) {
        return $redis->setex($key, $ttl, json_encode($value));
    }
    
    // File cache fallback
    $file = __DIR__ . '/../cache/' . md5($key) . '.json';
    file_put_contents($file, json_encode($value));
}
```

2. Cache dashboard statistics (5-minute TTL)
3. Cache category distributions (1-hour TTL)

---

### 3.2 Optimize Database Queries
**Files**: `functions/attendance_logic.php`
**Priority**: MEDIUM

**Implementation**:
1. Fix N+1 query in retention stats:
```php
// Replace CROSS JOIN with subquery approach
function getRetentionStats($months = 3) {
    $pdo = getDB();
    $church = $_SESSION['church'] ?? 'AFB Mangaan';
    
    // Get events in date range first
    $eventStmt = $pdo->prepare("
        SELECT id, start_date 
        FROM events 
        WHERE start_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
        AND status = 'Completed'
        AND church = ?
    ");
    $eventStmt->execute([$months, $church]);
    $events = $eventStmt->fetchAll();
    
    if (empty($events)) {
        return ['consistent' => [], 'at_risk' => [], 'consistent_count' => 0, 'at_risk_count' => 0];
    }
    
    $eventIds = array_column($events, 'id');
    $placeholders = str_repeat('?,', count($eventIds) - 1) . '?';
    
    // Get attendance stats in single query
    $stmt = $pdo->prepare("
        SELECT a.id, a.fullname, a.category,
               COUNT(DISTINCT al.event_id) as attended_events,
               ? as total_events
        FROM attendees a
        LEFT JOIN attendance_logs al ON a.id = al.attendee_id 
            AND al.event_id IN ($placeholders)
            AND al.status = 'Present'
        WHERE a.status = 'Active' AND a.church = ?
        GROUP BY a.id, a.fullname, a.category
    ");
    
    $params = array_merge([count($eventIds)], $eventIds, [$church]);
    $stmt->execute($params);
    // ... rest of logic
}
```

---

### 3.3 Implement Testing Framework
**Files**: `tests/`, `phpunit.xml`, `composer.json`
**Priority**: HIGH

**Implementation**:
1. Add PHPUnit to composer.json:
```json
{
    "require-dev": {
        "phpunit/phpunit": "^10.0"
    }
}
```

2. Create phpunit.xml configuration
3. Write initial tests:
   - Authentication tests
   - Attendance recording tests
   - Input validation tests
   - Database query tests

4. Set up test database
5. Create test fixtures

---

### 3.4 Add API Authentication (JWT)
**Files**: `functions/jwt_auth.php`, `api/`
**Priority**: HIGH

**Implementation**:
1. Add firebase/php-jwt to composer.json
2. Create JWT functions:
```php
// functions/jwt_auth.php
function generateJwt($userId) {
    $payload = [
        'user_id' => $userId,
        'iat' => time(),
        'exp' => time() + 3600 // 1 hour expiration
    ];
    return JWT::encode($payload, getenv('JWT_SECRET'), 'HS256');
}

function verifyJwt($token) {
    try {
        return JWT::decode($token, getenv('JWT_SECRET'), ['HS256']);
    } catch (Exception $e) {
        return false;
    }
}
```

3. Add JWT middleware for API endpoints
4. Keep session auth for web interface

---

### 3.5 Implement Async Webhook Queue
**Files**: `functions/webhook_queue.php`, `jobs/`
**Priority**: MEDIUM

**Implementation**:
1. Create webhook queue table:
```sql
CREATE TABLE webhook_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payload JSON NOT NULL,
    url VARCHAR(500) NOT NULL,
    attempts INT DEFAULT 0,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

2. Create queue processor script:
```php
// jobs/process_webhooks.php
while (true) {
    $stmt = $pdo->query("
        SELECT * FROM webhook_queue 
        WHERE status = 'pending' AND attempts < 3 
        ORDER BY created_at ASC LIMIT 10
    ");
    
    foreach ($stmt->fetchAll() as $job) {
        // Send webhook
        // Update status
    }
    
    sleep(5); // Check every 5 seconds
}
```

3. Run as background process or cron job

---

## Phase 4: Long-term Improvements (Month 3+)

### 4.1 Complete API Documentation
**Files**: `docs/API.md`, Swagger/OpenAPI spec
**Priority**: MEDIUM

**Implementation**:
1. Create OpenAPI 3.0 specification
2. Document all endpoints with examples
3. Add authentication documentation
4. Include error response documentation
5. Set up Swagger UI for interactive docs

---

### 4.2 Implement Disaster Recovery Plan
**Files**: `docs/DISASTER_RECOVERY.md`, backup scripts
**Priority**: HIGH

**Implementation**:
1. Create automated backup script:
```bash
#!/bin/bash
# scripts/backup.sh
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p afb_mangaan_db > backups/db_$DATE.sql
tar -czf backups/full_$DATE.tar.gz afb_mangaan_db.sql uploads/
```

2. Set up cron job for daily backups
3. Create offsite backup sync (AWS S3, Google Cloud Storage)
4. Document restoration procedure
5. Test restoration monthly

---

### 4.3 Add Health Check Endpoint
**Files**: `api/health.php`
**Priority**: LOW

**Implementation**:
```php
// api/health.php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'services' => []
];

// Check database
try {
    $pdo = getDB();
    $pdo->query("SELECT 1");
    $health['services']['database'] = 'ok';
} catch (Exception $e) {
    $health['services']['database'] = 'error';
    $health['status'] = 'degraded';
}

// Check webhook service
$webhookUrl = getWebhookConfig('make_webhook_url');
$health['services']['webhook'] = $webhookUrl ? 'configured' : 'not_configured';

echo json_encode($health);
```

---

### 4.4 Improve Accessibility
**Files**: All templates, CSS
**Priority**: LOW

**Implementation**:
1. Add ARIA labels to all form inputs
2. Ensure keyboard navigation works
3. Add focus indicators in CSS
4. Test with screen readers
5. Verify color contrast ratios (WCAG AA)

---

### 4.5 Add Comprehensive Logging
**Files**: `functions/logger.php`, config
**Priority**: MEDIUM

**Implementation**:
1. Implement structured logging:
```php
// functions/logger.php
function logError($message, $context = []) {
    $logEntry = [
        'timestamp' => date('c'),
        'level' => 'ERROR',
        'message' => $message,
        'context' => $context,
        'user_id' => $_SESSION['user_id'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ];
    file_put_contents(__DIR__ . '/../logs/error.log', json_encode($logEntry) . "\n", FILE_APPEND);
}

function logInfo($message, $context = []) {
    // Similar implementation for info logs
}
```

2. Add performance logging
3. Set up log rotation
4. Integrate with log management (optional)

---

## Implementation Checklist

### Week 1 Checklist
- [ ] Replace MD5 with bcrypt
- [ ] Create password migration script
- [ ] Remove default credentials
- [ ] Implement password reset flow
- [ ] Remove hardcoded webhook config
- [ ] Implement CSRF protection
- [ ] Secure session configuration
- [ ] Test all security changes

### Weeks 2-4 Checklist
- [ ] Fix database category inconsistency
- [ ] Create validation library
- [ ] Add validation to all API endpoints
- [ ] Implement rate limiting
- [ ] Add database indexes
- [ ] Standardize error handling
- [ ] Update .env.example
- [ ] Test stability improvements

### Month 2 Checklist
- [ ] Set up Redis or file cache
- [ ] Cache dashboard statistics
- [ ] Optimize retention stats query
- [ ] Install PHPUnit
- [ ] Write initial test suite
- [ ] Implement JWT authentication
- [ ] Create webhook queue system
- [ ] Set up background job processor
- [ ] Performance testing

### Month 3+ Checklist
- [ ] Create OpenAPI specification
- [ ] Set up Swagger UI
- [ ] Create backup scripts
- [ ] Set up automated backups
- [ ] Implement offsite backup sync
- [ ] Document disaster recovery
- [ ] Test backup restoration
- [ ] Add health check endpoint
- [ ] Improve accessibility
- [ ] Implement structured logging
- [ ] Set up log rotation

---

## Risk Mitigation

### Deployment Risks
- **Test all changes in staging environment first**
- **Create database backups before migrations**
- **Implement feature flags for gradual rollout**
- **Have rollback plan ready for each phase**

### Breaking Changes
- Password migration will require user intervention
- API changes may affect integrations
- Database schema changes need careful testing
- Cache implementation may have initial performance impact

### Resource Requirements
- Redis server for caching (or use file-based fallback)
- Additional storage for backups
- Background process for webhook queue
- CI/CD pipeline for testing

---

## Success Metrics

### Security Metrics
- Zero critical vulnerabilities after Phase 1
- All passwords hashed with bcrypt
- CSRF protection on 100% of forms
- Session security flags implemented

### Performance Metrics
- Dashboard load time < 2 seconds
- API response time < 500ms (p95)
- Database query time < 100ms (p95)
- Cache hit rate > 80%

### Quality Metrics
- Test coverage > 70%
- Zero critical bugs in production
- All API endpoints documented
- Error rate < 0.1%

---

## Next Steps

1. **Review and approve this plan**
2. **Set up staging environment**
3. **Create database backup**
4. **Begin Phase 1 implementation**
5. **Test each change thoroughly**
6. **Deploy to production after testing**
7. **Monitor for issues**
8. **Proceed to next phase**

---

**Plan Version**: 1.0  
**Created**: July 22, 2026  
**Estimated Completion**: October 2026  
**Total Estimated Effort**: 120-160 hours
