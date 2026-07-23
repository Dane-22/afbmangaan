import http from 'k6/http';
import { check, sleep } from 'k6';

// k6 Options: Ramp up to 100 Virtual Users (VUs) over 30 seconds
export const options = {
  stages: [
    { duration: '10s', target: 20 },  // Ramp up to 20 VUs
    { duration: '20s', target: 100 }, // Ramp up to 100 VUs
    { duration: '15s', target: 100 }, // Hold 100 VUs (Sunday rush simulation)
    { duration: '10s', target: 0 },   // Ramp down to 0
  ],
  thresholds: {
    http_req_failed: ['rate<0.05'], // Error rate < 5%
    http_req_duration: ['p(95)<2000'], // 95% of requests complete within 2s
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost/afbmangaan';

export default function () {
  // 1. Visit Login Page & Extract CSRF Token
  const loginPageRes = http.get(`${BASE_URL}/login.php`);
  check(loginPageRes, {
    'Login page status 200': (r) => r.status === 200,
  });

  // Regex to extract csrf token input field value
  const csrfMatch = loginPageRes.body.match(/name="csrf_token"\s+value="([^"]+)"/);
  const csrfToken = csrfMatch ? csrfMatch[1] : '';

  // 2. Perform Login POST
  const loginPayload = {
    username: 'admin',
    password: 'admin123',
    church: 'AFB Mangaan',
    csrf_token: csrfToken,
  };

  const loginRes = http.post(`${BASE_URL}/login.php`, loginPayload, {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    redirects: 0, // Expect redirect to dashboard
  });

  check(loginRes, {
    'Login successful (redirect or 200)': (r) => r.status === 302 || r.status === 200,
  });

  // Extract cookies for authenticated session requests
  const jar = http.cookieJar();

  // 3. Load Dashboard Page
  const dashboardRes = http.get(`${BASE_URL}/dashboard.php`, { jar });
  check(dashboardRes, {
    'Dashboard status 200': (r) => r.status === 200,
  });

  // 4. Load Reports Page
  const reportsRes = http.get(`${BASE_URL}/reports.php`, { jar });
  check(reportsRes, {
    'Reports status 200': (r) => r.status === 200,
  });

  // Pace requests slightly like real human interaction (0.5s - 1.5s delay)
  sleep(Math.random() * 1 + 0.5);
}
