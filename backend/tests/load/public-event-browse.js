import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = __ENV.BASE_URL || 'http://localhost:8123';
const eventId = __ENV.PUBLIC_EVENT_ID || '1';

export const options = {
  vus: 100,
  duration: '30s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<2000'],
  },
};

export default function () {
  const response = http.get(`${baseUrl}/public/events/${eventId}`, {
    headers: { Accept: 'application/json' },
  });

  check(response, {
    'status is 200': (r) => r.status === 200,
    'has event payload': (r) => r.body && r.body.length > 0,
  });

  sleep(0.2);
}
