import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = __ENV.BASE_URL || 'http://localhost:8123';
const checkInListShortId = __ENV.CHECK_IN_LIST_SHORT_ID || 'chk_test';
const attendeePublicId = __ENV.ATTENDEE_PUBLIC_ID || 'att_test';
const authToken = __ENV.AUTH_TOKEN || '';

export const options = {
  vus: 20,
  duration: '20s',
  thresholds: {
    http_req_failed: ['rate<0.05'],
    http_req_duration: ['p(95)<3000'],
  },
};

export default function () {
  if (!authToken) {
    return;
  }

  const response = http.post(
    `${baseUrl}/public/check-in-lists/${checkInListShortId}/check-ins`,
    JSON.stringify({ attendee_public_id: attendeePublicId }),
    {
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${authToken}`,
      },
    },
  );

  check(response, {
    'check-in responds without 5xx': (r) => r.status < 500,
  });

  sleep(0.3);
}
