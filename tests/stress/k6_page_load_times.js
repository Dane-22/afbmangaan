import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend } from 'k6/metrics';

// Custom metrics for each individual page's load time
const indexTrend = new Trend('page_index_ms');
const loginTrend = new Trend('page_login_ms');
const dashboardTrend = new Trend('page_dashboard_ms');
const attendanceTrend = new Trend('page_attendance_ms');
const membersTrend = new Trend('page_members_ms');
const eventsTrend = new Trend('page_events_ms');
const reportsTrend = new Trend('page_reports_ms');
const settingsTrend = new Trend('page_settings_ms');
const logsTrend = new Trend('page_logs_ms');
const auditTrend = new Trend('page_attendance_audit_ms');

export const options = {
  scenarios: {
    page_load_test: {
      executor: 'constant-vus',
      vus: 10,
      duration: '15s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    page_index_ms: ['p(95)<500'],
    page_login_ms: ['p(95)<500'],
    page_dashboard_ms: ['p(95)<500'],
    page_attendance_ms: ['p(95)<500'],
    page_members_ms: ['p(95)<500'],
    page_events_ms: ['p(95)<500'],
    page_reports_ms: ['p(95)<500'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost/afbmangaan';

export default function () {
  // 1. Index / Landing Page
  let res = http.get(`${BASE_URL}/index.php`);
  indexTrend.add(res.timings.duration);
  check(res, { 'index.php 200/302': (r) => r.status === 200 || r.status === 302 });

  // 2. Login Page & CSRF Token Extraction
  res = http.get(`${BASE_URL}/login.php`);
  loginTrend.add(res.timings.duration);
  check(res, { 'login.php 200': (r) => r.status === 200 });

  const csrfMatch = res.body.match(/name="csrf_token"\s+value="([^"]+)"/);
  const csrfToken = csrfMatch ? csrfMatch[1] : '';

  // Perform Login to get session cookie
  const loginRes = http.post(`${BASE_URL}/login.php`, {
    username: 'admin',
    password: 'admin123',
    church: 'AFB Mangaan',
    csrf_token: csrfToken,
  }, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    redirects: 0,
  });

  const jar = http.cookieJar();

  // 3. Dashboard Page
  res = http.get(`${BASE_URL}/dashboard.php`, { jar });
  dashboardTrend.add(res.timings.duration);
  check(res, { 'dashboard.php 200': (r) => r.status === 200 });

  // 4. Attendance Page
  res = http.get(`${BASE_URL}/attendance.php`, { jar });
  attendanceTrend.add(res.timings.duration);
  check(res, { 'attendance.php 200': (r) => r.status === 200 });

  // 5. Members Page
  res = http.get(`${BASE_URL}/members.php`, { jar });
  membersTrend.add(res.timings.duration);
  check(res, { 'members.php 200': (r) => r.status === 200 });

  // 6. Events Page
  res = http.get(`${BASE_URL}/events.php`, { jar });
  eventsTrend.add(res.timings.duration);
  check(res, { 'events.php 200': (r) => r.status === 200 });

  // 7. Reports Page
  res = http.get(`${BASE_URL}/reports.php`, { jar });
  reportsTrend.add(res.timings.duration);
  check(res, { 'reports.php 200': (r) => r.status === 200 });

  // 8. Settings Page
  res = http.get(`${BASE_URL}/settings.php`, { jar });
  settingsTrend.add(res.timings.duration);
  check(res, { 'settings.php 200': (r) => r.status === 200 });

  // 9. System Logs Page
  res = http.get(`${BASE_URL}/logs.php`, { jar });
  logsTrend.add(res.timings.duration);
  check(res, { 'logs.php 200': (r) => r.status === 200 });

  // 10. Attendance Audit Page
  res = http.get(`${BASE_URL}/attendance_audit.php`, { jar });
  auditTrend.add(res.timings.duration);
  check(res, { 'attendance_audit.php 200': (r) => r.status === 200 });

  sleep(0.5);
}
