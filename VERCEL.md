# Deploying the frontend on Vercel

The Vercel deployment is **frontend only**. Login, registration, and all API features require a **separate Laravel backend** (Railway, Render, Fly.io, Docker VPS, etc.).

## Required: connect frontend to backend

### 1. Deploy the Laravel API

Deploy `backend/` somewhere public, e.g. `https://your-api.railway.app`.

Run migrations and create a user:

```bash
php artisan migrate --force
php artisan tinker  # create account via register API or seeders
```

### 2. Set Vercel environment variables

In **Vercel → Project → Settings → Environment Variables** (Production + Preview):

| Variable | Value | Required |
|----------|-------|----------|
| `BACKEND_URL` | `https://your-api.railway.app` | **Yes** — no trailing slash |
| `VITE_APP_NAME` | `Event Hosting` | Recommended |

`BACKEND_URL` is used at build time to:

- Proxy `/api/*` on your Vercel domain → Laravel API (same as local Vite proxy)
- Set `VITE_API_URL_CLIENT=/api` so auth calls hit the proxy

### 3. Redeploy

Push to `main` or click **Redeploy** after setting env vars. The build runs `scripts/prepare-vercel-build.mjs` automatically.

### 4. Backend CORS (only if NOT using `/api` proxy)

If you set `VITE_API_URL_CLIENT` to the full backend URL instead of `/api`, add your Vercel URL to backend env:

```
CORS_ALLOWED_ORIGINS=https://event-hosting-snowy.vercel.app,https://your-custom-domain.com
```

When using the `/api` proxy (default with `BACKEND_URL`), CORS is not needed for browser requests.

## Why login showed "check your email and password"

Without `BACKEND_URL`, login POSTs went to `https://your-app.vercel.app/api/auth/login`, which returned the SPA `index.html` instead of JSON from Laravel — so auth always failed.

## Local development

Use the helper script (do not paste raw `php` commands unless PHP is on PATH):

```powershell
.\scripts\dev.ps1
```

Or:

```powershell
cd backend
.\artisan.ps1 serve --host=127.0.0.1 --port=1234
cd ..\frontend
yarn dev:csr
```

## Production branch

Set **Production Branch** to `main` (not Dependabot preview branches).
