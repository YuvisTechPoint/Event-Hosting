/**
 * Injects Vercel API proxy rewrites and frontend env for production builds.
 *
 * Required on Vercel:
 *   BACKEND_URL=https://your-laravel-api.example.com  (no trailing slash, no /api suffix)
 *
 * Optional:
 *   VITE_APP_NAME, VITE_STRIPE_PUBLISHABLE_KEY, etc.
 */
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const vercelPath = path.join(root, 'vercel.json');
const frontendEnvPath = path.join(root, 'frontend', '.env.production.local');

const backendUrl = (process.env.BACKEND_URL || process.env.VITE_API_URL_SERVER || '')
    .trim()
    .replace(/\/$/, '')
    .replace(/\/api$/, '');

const isVercel = Boolean(process.env.VERCEL);

const vercelUrl = process.env.VERCEL_URL
    ? `https://${process.env.VERCEL_URL}`
    : process.env.VITE_FRONTEND_URL || '';

const envLines = [];

if (vercelUrl) {
    envLines.push(`VITE_FRONTEND_URL=${vercelUrl}`);
}

// Browser always uses same-origin /api proxy on Vercel when BACKEND_URL is set.
if (backendUrl || isVercel) {
    envLines.push('VITE_API_URL_CLIENT=/api');
}

if (backendUrl) {
    envLines.push(`VITE_API_URL_SERVER=${backendUrl}`);
} else if (process.env.VITE_API_URL_SERVER) {
    envLines.push(`VITE_API_URL_SERVER=${process.env.VITE_API_URL_SERVER}`);
} else if (process.env.VITE_API_URL_CLIENT && !isVercel) {
    envLines.push(`VITE_API_URL_CLIENT=${process.env.VITE_API_URL_CLIENT}`);
}

if (process.env.VITE_APP_NAME) {
    envLines.push(`VITE_APP_NAME=${process.env.VITE_APP_NAME}`);
}

if (envLines.length > 0) {
    fs.writeFileSync(frontendEnvPath, envLines.join('\n') + '\n');
    console.log(`Wrote ${frontendEnvPath}:\n${envLines.join('\n')}`);
} else {
    const message =
        'WARNING: BACKEND_URL is not set. Auth and API calls will fail on Vercel.\n' +
        'Set BACKEND_URL in Vercel → Settings → Environment Variables to your Laravel API URL.';
    console.warn(message);
    if (isVercel) {
        console.warn('Continuing build without API proxy — UI may load but login will not work.');
    }
}

const vercel = JSON.parse(fs.readFileSync(vercelPath, 'utf8'));

const rewrites = [];

if (backendUrl) {
    rewrites.push({
        source: '/api/:path*',
        destination: `${backendUrl}/:path*`,
    });
    console.log(`API proxy: /api/* → ${backendUrl}/:path*`);
}

rewrites.push({
    source: '/((?!api/).*)',
    destination: '/index.html',
});

vercel.rewrites = rewrites;

fs.writeFileSync(vercelPath, JSON.stringify(vercel, null, 2) + '\n');
console.log('Updated vercel.json rewrites for deployment build.');
