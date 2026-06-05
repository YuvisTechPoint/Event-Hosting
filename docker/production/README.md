# Event Hosting — Production Docker Deployment

Multi-service production stack for Event Hosting (Hi.Events fork): PostgreSQL, Redis, Laravel backend, queue worker, scheduler, Soketi WebSockets, Vite/React SSR frontend, and Nginx reverse proxy.

## Prerequisites

- Docker Engine 24+ and Docker Compose v2
- A `.env` file in this directory (copy from `.env.example`)

## Quick start

```bash
cd docker/production
cp .env.example .env
# Edit .env — set APP_KEY, JWT_SECRET, POSTGRES_PASSWORD, REDIS_PASSWORD, PUSHER_* keys, and URLs

docker compose -f docker-compose.yml up -d --build
```

The app is available at `http://localhost` (or your configured `APP_URL`).

## Services

| Service        | Description                                      |
|----------------|--------------------------------------------------|
| `postgres`     | PostgreSQL 15 with persistent volume             |
| `redis`        | Redis 7 with password and AOF persistence        |
| `backend`      | Laravel API (port 8080 internal)                 |
| `queue_worker` | `php artisan queue:work`                         |
| `scheduler`    | Laravel schedule loop (every 60s)                |
| `soketi`       | Self-hosted Pusher-compatible WebSocket server   |
| `frontend`     | Vite/React SSR production server (port 5678)     |
| `nginx`        | Reverse proxy — `/api` → backend, `/` → frontend |

## Required environment variables

Generate secrets before first deploy:

```bash
# APP_KEY (run inside backend container or locally with Laravel)
php artisan key:generate --show

# JWT secret
openssl rand -base64 64

# Database / Redis / Pusher credentials
openssl rand -hex 16   # use for POSTGRES_PASSWORD, REDIS_PASSWORD, PUSHER_APP_KEY, PUSHER_APP_SECRET
```

Minimum required vars in `.env`:

- `APP_KEY`, `JWT_SECRET`
- `APP_URL`, `APP_FRONTEND_URL`
- `POSTGRES_PASSWORD`, `REDIS_PASSWORD`
- `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`
- `VITE_API_URL_CLIENT`, `VITE_API_URL_SERVER`, `VITE_FRONTEND_URL`

See `.env.example` for the full list.

## Zero-downtime updates

From the repository root (requires bash on Linux/macOS/CI, or PowerShell on Windows):

```bash
./scripts/deploy-update.sh              # pull, build, backup, migrate, rolling restart
./scripts/deploy-update.sh --skip-pull  # deploy local changes without git pull
./scripts/deploy-update.sh --optimize   # also run config:cache and route:cache
```

Windows: `.\scripts\deploy-update.ps1` (same flags: `-SkipPull`, `-Pull`, `-Optimize`). Database dumps are written to `backups/` before each migrate. Brief API unavailability can occur while the single backend container restarts.

## Common operations

```bash
# View logs
docker compose -f docker-compose.yml logs -f backend

# Run migrations manually
docker compose -f docker-compose.yml exec backend php artisan migrate --force

# Restart a service
docker compose -f docker-compose.yml restart queue_worker

# Stop everything
docker compose -f docker-compose.yml down
```

## HTTPS

The default Nginx config listens on port 80. For TLS:

1. Mount certificates into the `nginx` service at `./nginx/ssl/` (included in compose)
2. Place certificates in `nginx/ssl/` and uncomment the HTTPS server block in `nginx/conf.d/hievents.conf`
3. Set `VITE_*` and `APP_*` URLs to `https://`

Use a reverse proxy (Caddy, Traefik, cloud load balancer) in front of Nginx if preferred.

## Development vs production

This stack is separate from the development environment in `docker/development/`. The dev compose (`docker-compose.dev.yml`) keeps Mailpit, MinIO, and hot-reload workflows unchanged.

## Health checks

- Backend: `GET /api/health` (via Nginx) or `GET /health` (direct)
- Nginx proxies `/api/*` to the Laravel backend on port 8080

## Storage

Uploaded files are stored in the shared `storage_data` volume mounted at `/var/www/html/storage` (backend) and served by Nginx at `/storage/` when using the `public` filesystem disk.

For S3-compatible storage, configure `FILESYSTEM_*` and `AWS_*` variables in `.env` instead.

## Performance tuning

### Redis caching

Production sets `CACHE_DRIVER=redis` by default. Tune cache TTLs in `.env`:

| Variable | Default | Purpose |
|----------|---------|---------|
| `APP_PUBLIC_EVENT_CACHE_TTL` | 300 | Public event page API responses (seconds) |
| `APP_HOMEPAGE_TICKET_QUANTITIES_CACHE_TTL` | 30 | Ticket availability on event homepages |
| `APP_ANALYTICS_CACHE_TTL` | 300 | Event/organizer stats and analytics |

Set any TTL to empty/null in config to disable that cache layer.

### Response compression and ETags

Nginx enables gzip for JSON, JS, CSS, and SVG. Public GET routes also return `ETag` headers; clients sending `If-None-Match` may receive `304 Not Modified`.

### Rate limiting

The API applies Redis-backed rate limits:

- Global authenticated API: `APP_API_RATE_LIMIT_PER_MINUTE` (default 180)
- Public read endpoints: 120 req/min per IP
- Public order endpoints: 30 req/min per IP

### Database connection pooling (PgBouncer)

For high-traffic deployments, place [PgBouncer](https://www.pgbouncer.org/) between the backend and PostgreSQL:

1. Run PgBouncer in **transaction pooling** mode (recommended for Laravel).
2. Point the backend at Pgouncer: `DB_HOST=pgbouncer`, `DB_PORT=6432`.
3. Set Laravel `DB_PERSISTENT=false` (default) — persistent PHP connections conflict with transaction pooling.
4. Use a separate direct PostgreSQL connection for migrations if needed (`php artisan migrate` bypasses the pool or use a `MIGRATION_DB_*` override).

Example PgBouncer settings:

```ini
[databases]
hievents = host=postgres port=5432 dbname=hievents

[pgbouncer]
pool_mode = transaction
max_client_conn = 200
default_pool_size = 25
```

Add a `pgbouncer` service to `docker-compose.yml` when scaling beyond a single backend replica.

### Request limits

- Nginx `client_max_body_size`: 20M (file uploads)
- Laravel `ValidatePostSize` middleware validates POST body size against `post_max_size` in PHP

## Notes

- Frontend uses **Vite** env vars (`VITE_*`), not Next.js `NEXT_PUBLIC_*`.
- `VITE_*` values are baked at **build time**; changing them requires rebuilding the frontend image.
- Soketi credentials must match `PUSHER_*` backend vars and optional `VITE_PUSHER_*` frontend vars.
