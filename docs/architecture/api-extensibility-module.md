# API & Extensibility Module — Architecture Stub

> **Status:** Planning stub for Part 2 §2.9. Not implemented.  
> **Parent:** [platform-roadmap-part2.md](../platform-roadmap-part2.md)

## Purpose

Make Event Hosting a **platform developers can build on** — documented REST API, personal access tokens, expanded outbound webhooks, and predictable versioning.

## Current baseline (already shipped)

| Capability | Location |
|------------|----------|
| REST routes | `backend/routes/api.php` (~500+ lines) |
| JWT session auth | Existing auth middleware |
| Sanctum PAT model | `PersonalAccessTokenDomainObject` |
| Outbound webhooks | `WebhookDispatchService`, `webhooks` + `webhook_logs` |
| Domain event types | `DomainEventType` enum (order, attendee, product, check-in) |
| Embed widget | `frontend/src/embed/widget.js` |

## Gaps to close (P0–P1)

1. **OpenAPI 3.0 spec** generated or maintained alongside routes
2. **PAT management UI** — create, revoke, name, scope (account-level)
3. **Public docs page** — Redoc at `/docs/api` or static site
4. **Webhook expansion** — waitlist, session, sponsor, judging events
5. **API versioning** — `/api/v1/*` prefix with deprecation policy

## Proposed module layout

```
app/Services/Infrastructure/ApiDocs/
  OpenApiSpecBuilder.php

app/Http/Actions/Account/
  CreatePersonalAccessTokenAction.php
  ListPersonalAccessTokensAction.php
  RevokePersonalAccessTokenAction.php

frontend/src/components/routes/account/
  ApiKeys/                    # PAT UI
```

## Webhook event expansion (planned enum values)

| Event | Trigger |
|-------|---------|
| `waitlist.entry.created` | Waitlist signup |
| `waitlist.offer.sent` | Offer issued |
| `session.created` / `updated` | Part 1 sessions |
| `submission.created` | Hackathon submission |
| `judging.score.submitted` | Judge score saved |
| `sponsor.lead.captured` | Sponsor lead scan |

Follow existing pattern in `WebhookEventListener` → queue job → `WebhookDispatchService`.

## Rate limiting

| Tier | Limit | Storage |
|------|-------|---------|
| Session JWT | Existing throttle | Laravel default |
| PAT (default) | 120 req/min | Redis `RateLimiter` keyed by token id |
| PAT (elevated) | Configurable per account | `account_configuration` JSONB |

## Versioning policy (draft)

- Breaking changes only in new major prefix (`/api/v2`)
- `v1` supported ≥ 12 months after `v2` GA
- Response header: `X-API-Version: 2026-06-06`

## SDK strategy

Generate `@eventhosting/sdk` from OpenAPI via `openapi-typescript` + thin fetch wrapper. Publish when PAT + docs stable.

## Security checklist

- [ ] PAT shown once on create (mirror Stripe key UX)
- [ ] Scopes: `read:events`, `write:events`, `read:orders`, etc.
- [ ] Revoke on password reset (optional policy)
- [ ] Webhook URL validation (`NoInternalUrlRule` already exists)

## Next implementation steps (when prioritized)

1. Add Scribe or manual `docs/api/openapi.yaml` for top 20 organizer endpoints
2. PAT CRUD actions + account settings UI
3. Add 3 high-value webhook events (waitlist, submission, session)
4. Introduce `/api/v1` route group as alias to current routes
