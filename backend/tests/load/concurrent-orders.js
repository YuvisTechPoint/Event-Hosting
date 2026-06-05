import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = __ENV.BASE_URL || 'http://localhost:8123';
const eventId = __ENV.PUBLIC_EVENT_ID || '1';

export const options = {
  vus: 50,
  duration: '30s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<5000'],
  },
};

export default function () {
  const sessionId = `load-${__VU}-${Date.now()}`;
  const payload = JSON.stringify({
    session_identifier: sessionId,
    order_locale: 'en',
    products: [],
  });

  const response = http.post(`${baseUrl}/public/events/${eventId}/order`, payload, {
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
  });

  check(response, {
    'order endpoint responds': (r) => r.status === 200 || r.status === 422,
    'no server error': (r) => r.status < 500,
  });

  sleep(0.5);
}
