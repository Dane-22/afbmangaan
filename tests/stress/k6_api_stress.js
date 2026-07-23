import http from 'k6/http';
import { check, sleep } from 'k6';

// Stress Test Configuration for AFB Mangaan APIs
export const options = {
  stages: [
    { duration: '5s', target: 20 },   // Warm up with 20 VUs
    { duration: '15s', target: 100 }, // Scale up to 100 concurrent users
    { duration: '15s', target: 250 }, // Push load to 250 concurrent users
    { duration: '5s', target: 0 },    // Ramp down
  ],
  thresholds: {
    http_req_failed: ['rate<0.05'],    // Error rate < 5%
    http_req_duration: ['p(95)<1000'], // 95% of requests respond under 1s
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost/afbmangaan';

export default function () {
  // 1. Health Check Endpoint
  const healthRes = http.get(`${BASE_URL}/api/health.php`);
  check(healthRes, {
    'Health Check Status 200': (r) => r.status === 200,
    'Database is OK': (r) => {
      try {
        return r.status === 200 && r.json().services && r.json().services.database === 'ok';
      } catch (e) {
        return false;
      }
    },
  });

  // 2. Search Attendees API (simulates live search)
  const searchRes = http.get(`${BASE_URL}/api/search_attendees.php?query=a`);
  check(searchRes, {
    'Search API Status 200': (r) => r.status === 200,
  });

  sleep(0.2); // Short 200ms delay between requests
}
