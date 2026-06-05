# Event Hosting Platform (Greenfield Scaffold)

Parallel **Next.js 14** monorepo for future migration. Production app remains **`frontend/`** (Vite + React) + **`backend/`** (Laravel).

## Structure

```
platform/
├── apps/web/          Next.js 14 App Router starter
├── packages/database/ Prisma schema stub (maps to hackathon_* tables)
└── packages/ui/       Shared UI placeholder
```

## Quick start

```bash
cd platform
yarn install
yarn dev
```

## Migration strategy

See [docs/adr/001-platform-architecture.md](../docs/adr/001-platform-architecture.md).

1. **Now:** Extend Laravel API (hackathon engine, analytics, discovery)
2. **Next:** OpenAPI spec shared between Laravel and Next.js
3. **Later:** Port read-heavy pages to RSC; keep payments/auth on Laravel until parity

## Backend of record

The Laravel API at `backend/` remains authoritative for:

- Auth, billing, Stripe Connect
- Events, tickets, orders
- Hackathon teams, projects, judging

Do not duplicate business logic in Next.js API routes until explicitly migrated.
