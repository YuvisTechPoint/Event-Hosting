# Deploying the frontend on Vercel

This repo is a monorepo. Vercel must build the **frontend** only (Laravel backend is deployed separately).

Root `vercel.json` configures:

- **Install:** `frontend/` dependencies
- **Build:** CSR Vite build → `frontend/dist`
- **SPA rewrites:** all routes → `index.html`

## Required environment variables (Vercel project settings)

Set these for **Production** and **Preview**:

| Variable | Example |
|----------|---------|
| `VITE_API_URL_CLIENT` | `https://your-api.example.com` |
| `VITE_API_URL_SERVER` | `https://your-api.example.com` |
| `VITE_FRONTEND_URL` | `https://your-app.vercel.app` |
| `VITE_APP_NAME` | `Event Hosting` |

Optional: `VITE_STRIPE_PUBLISHABLE_KEY`, `VITE_PUSHER_*` for payments and realtime.

## Vercel project settings

1. **Production Branch:** `main` (not Dependabot branches)
2. **Root Directory:** leave empty — root `vercel.json` handles the monorepo paths

After pushing, trigger **Redeploy** on the latest `main` deployment.
