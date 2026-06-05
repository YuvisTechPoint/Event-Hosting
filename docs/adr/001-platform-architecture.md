# ADR-001: Platform Architecture — Event Hosting Evolution

**Status:** Accepted  
**Date:** 2026-06-06  
**Decision:** Evolve existing Event Hosting (Laravel + React/Vite) toward the full developer-community platform spec (Parts 3–6), with an optional greenfield Next.js monorepo in `platform/` for future migration.

---

## Context

The product vision combines event ticketing (Luma), hackathons (Devfolio), community (Discord), and premium UX (Linear). The current codebase is a production-grade **Hi.Events fork** with Laravel DDD backend and React SSR/CSR frontend—not Next.js.

A full stack rewrite would delay MVP by 6–12 months. **Incremental evolution (Option A)** preserves working ticketing, payments, and check-in while adding hackathon and community modules.

---

## Target Architecture (Parts 3–6)

| Layer | Target (spec) | Current | Phase-in plan |
|-------|---------------|---------|---------------|
| Frontend | Next.js 14 RSC | React + Vite + Mantine | Keep Vite; add UX foundation (dark-first, ⌘K, skeletons). `platform/apps/web` for Next.js experiments |
| Backend | Node + GraphQL | Laravel REST | Extend Laravel API; GraphQL gateway optional Phase 5 |
| DB | Postgres + Redis + ES | Postgres + Redis | Add ES in Phase 3 for discovery/search |
| Auth | NextAuth / Clerk | JWT + cookies | Add OAuth providers incrementally |
| Real-time | Socket.io / Ably | Soketi/Pusher + Echo | Keep; extend channels for chat Phase 3 |
| Payments | Stripe Connect | ✅ Implemented | No change |
| AI | OpenAI + vector DB | — | Phase 3 matching service |

---

## Module Map

```
backend/app/
├── Services/Domain/Event/          # Ticketing, stats, analytics ✅
├── Services/Domain/Waitlist/         # Waitlist ✅
├── Services/Application/Handlers/Hackathon/  # Phase 2 🆕
├── Services/Domain/Community/      # Profiles, follows (partial)
└── Services/Infrastructure/Security/  # CSRF, headers ✅

frontend/src/
├── routes/event/Hackathon/         # Phase 2 UI 🆕
├── routes/event/EventAnalytics/    # Analytics ✅
├── routes/public/DiscoverEvents/   # Discovery ✅
└── components/common/CommandPalette/  # UX foundation 🆕

platform/                             # Greenfield Turborepo scaffold 🆕
```

---

## Database — Core Models (Part 5)

| Model | Table | Status |
|-------|-------|--------|
| Users, Organizations | users, accounts, organizers | ✅ |
| Events, Tickets, Registrations | events, products, orders, attendees | ✅ |
| PromoCode, Waitlist | promo_codes, waitlist_entries | ✅ |
| Messages, Notifications | messages, broadcasting events | ✅ Partial |
| **Teams, Projects, Submissions** | hackathon_* tables | 🆕 Phase 2 |
| **JudgingCriteria, Scores** | hackathon_judging_*, hackathon_scores | 🆕 Phase 2 |
| Sponsors, Bounties, Sessions | — | Phase 2–3 |
| Gamification (XP, Achievements) | — | Phase 3 |
| Chat Channels | — | Phase 3 |

---

## MVP Phasing (Part 6)

| Phase | Weeks | Deliverables | Repo status |
|-------|-------|--------------|-------------|
| **1 Foundation** | 1–4 | Auth, orgs, events, registration, discovery | ~80% done |
| **2 Hackathon Engine** | 5–8 | Teams, projects, judging, sponsors basics | **In progress** — schema + API + UI |
| **3 Engagement** | 9–12 | Chat, XP, AI matching, analytics v2 | Analytics v1 done |
| **4 Premium** | 13–16 | Streaming, sponsor marketplace, mobile | Check-in PWA started |
| **5 Enterprise** | 17–20 | White-label, RBAC, SSO, public API | Webhooks exist |

---

## API Conventions (Hackathon — Phase 2)

```
GET/POST  /events/{event_id}/hackathon/teams
GET/POST  /events/{event_id}/hackathon/projects
POST      /events/{event_id}/hackathon/projects/{id}/submit
GET/POST  /events/{event_id}/hackathon/judging-criteria
POST      /events/{event_id}/hackathon/scores
```

Authorization: `isActionAuthorized($eventId, EventDomainObject::class)` — same as affiliates/promo codes.

---

## Infrastructure

| Concern | Decision |
|---------|----------|
| Frontend hosting | Vercel (CSR build from `frontend/dist`) — see VERCEL.md |
| Backend hosting | Railway / Render / Fly.io / Docker — not Vercel |
| CI/CD | GitHub Actions (existing) + Vercel preview on `main` |
| Monitoring | Sentry hooks exist; expand in Phase 5 |

---

## UI/UX Principles (Part 4) — Implementation

| Principle | Implementation |
|-----------|----------------|
| Dark mode first | Mantine `defaultColorScheme="dark"` + toggle |
| Command palette | ⌘K Modal navigation |
| Skeleton loading | `PageSkeleton`, dashboard skeletons |
| Toasts | Existing `showSuccess` / `showError` |
| Empty states | Hackathon tabs with CTAs |

---

## Greenfield Scaffold (`platform/`)

Parallel **Turborepo** with Next.js 14 + Prisma stub allows experimenting with RSC/ tRPC without blocking production on Laravel. Migration path: shared OpenAPI contract → gradual endpoint port.

---

## Consequences

**Positive:** Ship hackathon features on stable ticketing core; Vercel frontend works; clear module boundaries.

**Negative:** Two frontend stacks temporarily (Vite + Next scaffold); spec’s GraphQL/Node backend deferred.

**Follow-up ADRs:** ADR-002 Elasticsearch integration, ADR-003 Chat architecture, ADR-004 Auth OAuth providers.
