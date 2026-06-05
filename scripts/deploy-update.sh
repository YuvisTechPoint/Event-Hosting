#!/usr/bin/env bash
# Event Hosting — production zero-downtime update deployment.
# Builds backend/frontend, backs up PostgreSQL, migrates, and rolling-restarts app services.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
COMPOSE_FILE="${COMPOSE_FILE:-docker/production/docker-compose.yml}"
COMPOSE_PATH="${REPO_ROOT}/${COMPOSE_FILE}"
COMPOSE_DIR="$(dirname "${COMPOSE_PATH}")"
BACKUP_DIR="${REPO_ROOT}/backups"

SKIP_PULL=false
PULL_BASE_IMAGES=false
RUN_OPTIMIZE=false

usage() {
    cat <<'EOF'
Usage: scripts/deploy-update.sh [OPTIONS]

Deploy an Event Hosting production update with database backup and health verification.

Options:
  --skip-pull    Skip git pull (deploy local changes only)
  --pull         Pass --pull to docker compose build (refresh base images)
  --optimize     Run php artisan config:cache and route:cache after migrate
  -h, --help     Show this help

Environment:
  COMPOSE_FILE   Path to compose file (default: docker/production/docker-compose.yml)
  HEALTH_URL     Override health check URL (default: APP_URL or http://localhost:PORT/api/health)
EOF
}

log() {
    echo "[Event Hosting deploy] $*"
}

die() {
    echo "[Event Hosting deploy] ERROR: $*" >&2
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --skip-pull)
            SKIP_PULL=true
            shift
            ;;
        --pull)
            PULL_BASE_IMAGES=true
            shift
            ;;
        --optimize)
            RUN_OPTIMIZE=true
            shift
            ;;
        -h | --help)
            usage
            exit 0
            ;;
        *)
            die "Unknown option: $1 (use --help)"
            ;;
    esac
done

if [[ ! -f "${COMPOSE_PATH}" ]]; then
    die "Compose file not found: ${COMPOSE_PATH}"
fi

ENV_FILE="${COMPOSE_DIR}/.env"
if [[ -f "${ENV_FILE}" ]]; then
    set -a
    # shellcheck disable=SC1090
    source "${ENV_FILE}"
    set +a
else
    log "Warning: ${ENV_FILE} not found; using defaults for backup and health URLs"
fi

POSTGRES_USER="${POSTGRES_USER:-hievents}"
POSTGRES_DB="${POSTGRES_DB:-hievents}"
NGINX_HTTP_PORT="${NGINX_HTTP_PORT:-80}"

compose() {
    docker compose -f "${COMPOSE_PATH}" --project-directory "${COMPOSE_DIR}" "$@"
}

resolve_health_url() {
    if [[ -n "${HEALTH_URL:-}" ]]; then
        echo "${HEALTH_URL}"
        return
    fi

    if [[ -n "${APP_URL:-}" ]]; then
        echo "${APP_URL%/}/api/health"
        return
    fi

    echo "http://localhost:${NGINX_HTTP_PORT}/api/health"
}

wait_for_compose_health() {
    local service="$1"
    local timeout="${2:-180}"
    local elapsed=0
    local interval=5

    log "Waiting for ${service} to become healthy (timeout ${timeout}s)..."

    while [[ "${elapsed}" -lt "${timeout}" ]]; do
        local container_id
        container_id="$(compose ps -q "${service}" 2>/dev/null || true)"

        if [[ -n "${container_id}" ]]; then
            local status
            status="$(docker inspect --format='{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "${container_id}" 2>/dev/null || echo "unknown")"

            if [[ "${status}" == "healthy" || "${status}" == "running" ]]; then
                log "${service} is ${status}"
                return 0
            fi
        fi

        sleep "${interval}"
        elapsed=$((elapsed + interval))
    done

    die "${service} did not become healthy within ${timeout}s"
}

verify_http_health() {
    local url="$1"
    local timeout="${2:-120}"
    local elapsed=0
    local interval=5

    log "Verifying health at ${url}..."

    while [[ "${elapsed}" -lt "${timeout}" ]]; do
        local body
        local http_code

        body="$(curl -sS -m 10 -w '\n%{http_code}' "${url}" 2>/dev/null || true)"
        http_code="$(echo "${body}" | tail -n1)"
        body="$(echo "${body}" | sed '$d')"

        if [[ "${http_code}" == "200" ]] && echo "${body}" | grep -q '"status"[[:space:]]*:[[:space:]]*"ok"'; then
            log "Health check passed (HTTP 200, status=ok)"
            return 0
        fi

        log "Health check not ready yet (HTTP ${http_code:-000}); retrying..."
        sleep "${interval}"
        elapsed=$((elapsed + interval))
    done

    die "Health check failed at ${url} — expected HTTP 200 with \"status\":\"ok\""
}

mkdir -p "${BACKUP_DIR}"

if [[ "${SKIP_PULL}" == "false" ]]; then
    log "Pulling latest git changes..."
    git -C "${REPO_ROOT}" pull --ff-only
else
    log "Skipping git pull (--skip-pull)"
fi

BUILD_ARGS=(build --no-cache backend frontend)
if [[ "${PULL_BASE_IMAGES}" == "true" ]]; then
    BUILD_ARGS+=(--pull)
    log "Building backend and frontend (--no-cache, --pull)..."
else
    log "Building backend and frontend (--no-cache)..."
fi
compose "${BUILD_ARGS[@]}"

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_FILE="${BACKUP_DIR}/pre_migrate_${TIMESTAMP}.sql"

log "Creating PostgreSQL backup: ${BACKUP_FILE}"
compose exec -T postgres pg_dump -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" --no-owner --no-acl > "${BACKUP_FILE}"

if [[ ! -s "${BACKUP_FILE}" ]]; then
    die "Backup file is empty: ${BACKUP_FILE}"
fi

log "Backup complete ($(wc -c < "${BACKUP_FILE}" | tr -d ' ') bytes)"

log "Running database migrations..."
compose run --rm --no-deps backend php artisan migrate --force

if [[ "${RUN_OPTIMIZE}" == "true" ]]; then
    log "Caching configuration and routes..."
    compose run --rm --no-deps backend php artisan config:cache
    compose run --rm --no-deps backend php artisan route:cache
else
    log "Skipping config/route cache (pass --optimize to enable)"
fi

log "Rolling restart: backend → frontend → queue_worker → scheduler"

compose up -d --no-deps backend
wait_for_compose_health backend 180

compose up -d --no-deps frontend
wait_for_compose_health frontend 120

compose up -d --no-deps queue_worker
log "queue_worker recreated"

compose up -d --no-deps scheduler
log "scheduler recreated"

HEALTH_CHECK_URL="$(resolve_health_url)"
verify_http_health "${HEALTH_CHECK_URL}" 120

log "Deployment complete."
