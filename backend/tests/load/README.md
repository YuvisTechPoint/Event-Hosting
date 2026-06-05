# Hi.Events Load Tests (Phase 8.1)

Load scripts exercise public read paths and authenticated write paths against a running Hi.Events instance. They are **not** part of the default PHPUnit suite.

## Prerequisites

- A running Hi.Events stack (Docker dev or staging) with at least one **live** public event ID
- [k6](https://k6.io/docs/get-started/installation/) installed locally

## Environment variables

| Variable | Description | Example |
|----------|-------------|---------|
| `BASE_URL` | API root URL (no trailing slash) | `http://localhost:8123` |
| `PUBLIC_EVENT_ID` | Live event ID for browse/checkout scenarios | `1` |
| `AUTH_TOKEN` | Bearer token for check-in scans (staff user) | `eyJ...` |
| `CHECK_IN_LIST_SHORT_ID` | Public check-in list short ID | `chk_abc123` |
| `ATTENDEE_PUBLIC_ID` | Attendee public ID for check-in POST | `att_xyz` |

## Scenarios

| Script | Concurrent users | Critical path |
|--------|------------------|---------------|
| `public-event-browse.js` | 100 VUs | Visitor views public event page |
| `concurrent-orders.js` | 50 VUs | Ticket purchase reservation (create order) |
| `concurrent-checkins.js` | 20 VUs | Staff QR check-in scans |

## Run

From the repository root:

```bash
cd backend/tests/load

# 100 concurrent public event page views
k6 run -e BASE_URL=http://localhost:8123 -e PUBLIC_EVENT_ID=1 public-event-browse.js

# 50 concurrent order creations (requires live event with available tickets)
k6 run -e BASE_URL=http://localhost:8123 -e PUBLIC_EVENT_ID=1 concurrent-orders.js

# 20 concurrent check-in scans (requires auth token + check-in list)
k6 run \
  -e BASE_URL=http://localhost:8123 \
  -e AUTH_TOKEN="$TOKEN" \
  -e CHECK_IN_LIST_SHORT_ID=chk_abc \
  -e ATTENDEE_PUBLIC_ID=att_xyz \
  concurrent-checkins.js
```

## Success criteria

- `http_req_failed` rate below 1%
- p95 latency under 2s for browse, under 5s for order creation
- No 5xx responses in the summary

## CI note

Load tests are intentionally excluded from `php artisan test`. Run them manually before major releases or against staging in a dedicated pipeline job with k6 installed.
