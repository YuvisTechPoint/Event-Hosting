<div align="center">

<img src="https://hievents-public.s3.us-west-1.amazonaws.com/website/github-banner.png?v=1" alt="Event Hosting - Open Source Event Ticketing Platform" width="100%">

# Event Hosting

### Open-source event ticketing and management platform

Sell tickets online for conferences, nightlife events, concerts, club nights, workshops, and festivals.  
Self-hosted or cloud. Your events, your brand, your data.

[Try Cloud ?](https://app.hi.events/auth/register?utm_source=gh-readme) ï¿½ [Live Demo](https://app.hi.events/event/2/hievents-conference-2030?utm_source=gh-readme) ï¿½ [Documentation](https://hi.events/docs?utm_source=gh-readme) ï¿½ [Website](https://hi.events?utm_source=gh-readme)

[![License: AGPL v3](https://img.shields.io/badge/License-AGPL_v3-blue.svg)](https://github.com/HiEventsDev/Hi.Events/blob/develop/LICENCE)
[![GitHub Release](https://img.shields.io/github/v/release/HiEventsDev/Hi.Events?include_prereleases)](https://github.com/HiEventsDev/Hi.Events/releases)
[![Run Unit Tests](https://github.com/HiEventsDev/Hi.Events/actions/workflows/unit-tests.yml/badge.svg?event=push)](https://github.com/HiEventsDev/Hi.Events/actions/workflows/unit-tests.yml)
[![Docker Pulls](https://img.shields.io/docker/pulls/daveearley/hi.events-all-in-one)](https://hub.docker.com/r/daveearley/hi.events-all-in-one)

<a href="https://trendshift.io/repositories/10563" target="_blank">
  <img src="https://trendshift.io/api/badge/repositories/10563" alt="HiEventsDev%2FEvent Hosting | Trendshift" width="250" height="55"/>
</a>

</div>

<br>

## Table of Contents

- [Why Event Hosting?](#why-event-hosting)
- [Features](#features)
- [Compare](#compare)
- [Quick Start](#quick-start)
- [Install Without Docker](#install-without-docker)
- [Event Hosting Cloud](#event-hosting-cloud)
- [Contributing](#contributing)
- [Development](#development)
- [Security](#security)
- [Support](#support)
- [Changelog](#changelog)
- [License](#license)

<br>

## Why Event Hosting?

Most ticketing platforms charge per-ticket fees and lock your data into their ecosystem. **Event Hosting is a modern,
open-source alternative to Eventbrite, Tickettailor, Dice.fm, and other ticketing platforms** for organizers who want
full control over branding, checkout, data, and infrastructure.

Built for nightlife promoters, festival organizers, venues, community groups, and conference hosts.

<br>

<img alt="Event Hosting Dashboard" src="https://hievents-public.s3.us-west-1.amazonaws.com/website/github-screenshot.png"/>

<br>

## Features

<table>
<tr>
<td width="50%" valign="top">

### ??? Ticketing & Sales

- Flexible ticket types (free, paid, donation, tiered)
- Hidden and locked tickets behind promo codes
- Promo codes and pre-sale access
- Product add-ons (merch, upgrades, extras)
- Product categories for organization
- Full tax and fee support (VAT, service fees)
- Capacity management and shared limits

</td>
<td width="50%" valign="top">

### ?? Branding & Customization

- Beautiful, conversion-optimized checkout
- Customizable PDF ticket designs
- Branded organizer homepage
- Drag-and-drop event page builder
- Embeddable ticket widget
- SEO tools (meta tags, Open Graph)

</td>
</tr>
<tr>
<td width="50%" valign="top">

### ?? Attendee Management

- Custom checkout questions
- Advanced search, filtering, and export (CSV/XLSX)
- Full and partial refunds
- Bulk messaging by ticket type
- QR code check-in with scan logs
- Access-controlled check-in lists

</td>
<td width="50%" valign="top">

### ?? Analytics & Growth

- Real-time sales dashboard
- Affiliate and referral tracking
- Advanced reporting (sales, tax, promos)
- Webhooks (Zapier, Make, CRMs)

</td>
</tr>
<tr>
<td colspan="2" valign="top">

### ?? Operations

Multi-user roles and permissions ï¿½ Stripe Connect instant payouts ï¿½ Offline payment methods ï¿½ Offline event support ï¿½
Automatic invoicing ï¿½ Event archive ï¿½ Multi-language support ï¿½ Full REST API

</td>
</tr>
</table>

<br>

## Compare

| Feature                          | Event Hosting | Eventbrite | Tickettailor | Dice    |
|:---------------------------------|:----------|:-----------|:-------------|:--------|
| Self-hosted option               | ?         | ?          | ?            | ?       |
| Open source                      | ?         | ?          | ?            | ?       |
| No per-ticket fees (self-hosted) | ?         | ?          | ?            | ?       |
| Full custom branding             | ?         | Limited    | ?            | Limited |
| Affiliate tracking               | ?         | ?          | ?            | ?       |
| API access                       | ?         | ?          | ?            | Limited |
| Own your data                    | ?         | ?          | ?            | ?       |

<br>

## Quick Start

### One-Click Deploy

[![Deploy on DigitalOcean](https://www.deploytodo.com/do-btn-blue.svg)](https://github.com/HiEventsDev/Hi.Events-digitalocean)
[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://github.com/HiEventsDev/Hi.Events-render.com)
[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/template/8CGKmu?referralCode=KvSr11)
[![Deploy on Zeabur](https://zeabur.com/button.svg)](https://zeabur.com/templates/8DIRY6)

### Docker

```bash
git clone git@github.com:HiEventsDev/Hi.Events.git
cd Hi.Events/docker/all-in-one

# Generate keys (Linux/macOS)
echo "APP_KEY=base64:$(openssl rand -base64 32)" >> .env
echo "JWT_SECRET=$(openssl rand -base64 32)" >> .env

docker compose up -d
```

> [!TIP]
> **Windows users:** See `./docker/all-in-one/README.md` for key generation instructions.

Open `http://localhost:8123` and create your account.

**Production (multi-service):** For postgres, redis, queue workers, Soketi WebSockets, and nginx, use the compose stack in [`docker/production/`](./docker/production/README.md).

?? [Full installation guide](https://hi.events/docs/getting-started?utm_source=gh-readme)

<br>

## Install Without Docker

This section covers setting up Event Hosting locally without Docker.

### Native / Windows

From the repository root:

```powershell
.\dev.cmd
```

Or `.\scripts\dev.ps1` (CSR) or `.\scripts\dev.ps1 -Ssr` (SSR). The script starts PostgreSQL via `pg_ctl` if needed, locates WinGet PHP, and reuses ports `:1234` / `:5678` if servers are already up. First-time setup: `.\scripts\start-local-windows-native.ps1`. Stop: `.\scripts\stop-dev.ps1`.

Do **not** paste multiple commands on one line (`cd frontend` while already in `frontend/`, or `yarn build` after `yarn dev:ssr`). The API proxy error `ECONNREFUSED /users/me` means the Laravel backend on `:1234` is not running — use `.\dev.cmd` from the repo root instead.



**?? For a faster and more reliable setup, we strongly recommend using the official [Docker setup](https://hi.events/docs/getting-started/quick-start).**

### Prerequisites

1. [Install PHP 8.2 or higher](https://www.php.net/downloads.php)
2. [Install Composer](https://getcomposer.org/download/)
3. [Install PostgreSQL](https://www.postgresql.org/download/)
4. [Install Node.js](https://nodejs.org/en)
5. [Install Yarn](https://yarnpkg.com/getting-started/install)

Ensure the following PHP extensions are installed: `gd`, `pdo_pgsql`, `sodium`, `curl`, `intl`, `mbstring`, `xml`, `zip`, `bcmath`.

### Backend Setup

```bash
git clone https://github.com/youraccount/Hi.Events.git
cd Hi.Events/backend
cp .env.example .env
```

Update `.env` with your database credentials:

```bash
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

Configure mail (Mailtrap or log driver):

```bash
MAIL_MAILER=log
```

Set URLs:

```bash
APP_URL=http://localhost
APP_PORT=8000
APP_FRONTEND_URL=http://localhost:5678
```

Install and run:

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

Set storage in `.env`:

```bash
FILESYSTEM_PUBLIC_DISK=public
FILESYSTEM_PRIVATE_DISK=local
APP_CDN_URL=http://localhost:8000/storage
```

Optional Stripe configuration:

```bash
STRIPE_PUBLIC_KEY=your_public_key
STRIPE_SECRET_KEY=your_secret_key
STRIPE_WEBHOOK_SECRET=your_webhook_secret
```

Visit `http://localhost:8000` to verify the backend.

### Frontend Setup

```bash
cd frontend
cp .env.example .env
```

Update `.env`:

```bash
VITE_API_URL_CLIENT=http://localhost:8000
VITE_API_URL_SERVER=http://localhost:8000
VITE_FRONTEND_URL=http://localhost:5678
VITE_STRIPE_PUBLISHABLE_KEY=pk_test_XXXXXXXX
```

Install, build, and start:

```bash
yarn install
yarn build
yarn start
```

Visit `http://localhost:5678` to view the frontend.

### Troubleshooting

- **Composer errors:** Run `php -m` to verify required PHP extensions are installed.
- **Database connection issues:** Check `.env` credentials and ensure PostgreSQL is running.
- **Mail errors:** Verify mail server credentials or use the `log` driver.
- **Frontend not connecting:** Confirm API URLs in both frontend and backend `.env` files.

<br>

## Event Hosting Cloud

Prefer not to self-host? **[Event Hosting Cloud](https://app.hi.events/auth/register?utm_source=gh-readme)** is a fully
managed option with zero setup, automatic updates, and managed infrastructure.

[Get started ?](https://app.hi.events/auth/register?utm_source=gh-readme)

<br>

## Contributing

Thank you for your interest in contributing to Event Hosting! We welcome contributions from the community.

> **IMPORTANT: Open a discussion or issue BEFORE submitting a PR for anything beyond trivial fixes (typos, broken links, etc.).** PRs submitted without prior discussion will be closed if the PR description is left empty or the template is left unedited.

### Reporting Bugs

Open an issue in our [GitHub repository](https://github.com/HiEventsDev/Hi.Events/issues) with as much detail as possible.

### Suggesting Enhancements

Open an issue with a detailed description of the proposed enhancement and its benefits.

### Pull Requests

?? Please open an issue or discussion before starting any significant work.

1. Fork the repository.
2. Create a branch from `develop` (e.g., `feature/new-feature` or `bugfix/issue-123`).
3. Make your changes following our coding standards.
4. Commit with a descriptive message.
5. Push to your fork and open a PR to `develop`.

Your pull request should include:

- A clear description of the changes and the problem they address.
- Relevant issue numbers (e.g., `Fixes #123`).
- Documentation updates, if applicable.
- Tests for new functionality or bug fixes, if applicable.
- A demo or screenshots, if the changes are visual.

A CLA bot will check if you have signed the [Contributor License Agreement](./CLA.md). Sign by commenting on the PR: `I have read the CLA Document and I hereby sign the CLA`.

### Development Setup

Follow the [Getting Started with Local Development guide](https://hi.events/docs/getting-started/local-development).

#### Coding Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) for PHP.
- Use ES6+ and the [Airbnb JavaScript Style Guide](https://github.com/airbnb/javascript).
- Follow the [React/JSX Style Guide](https://github.com/airbnb/javascript/tree/master/react) for React components.

#### Translations

**Backend:** Wrap translatable strings in the `__()` helper. Extract with `php artisan langscanner`.

**Frontend:** Use [Lingui](https://lingui.dev/) ï¿½ wrap strings in `t` or `Trans`. Run:

```bash
yarn messages:extract && yarn messages:compile
```

#### Database Changes

Use Laravel migrations for schema changes. Generate with `php artisan make:migration create_XXX_table`, then regenerate domain objects:

```bash
php artisan generate-domain-objects
```

### AI / Bot Contributors

- Add ?? to the PR title and each commit message.
- All other guidelines above still apply.

<br>

## Development

This section covers architecture, commands, and coding standards for contributors working in the codebase.

### Key Commands

#### Backend (Laravel)

Commands must be executed in the `backend` docker container:

```bash
cd docker/development

docker compose -f docker-compose.dev.yml exec backend php artisan migrate
docker compose -f docker-compose.dev.yml exec backend php artisan generate-domain-objects
docker compose -f docker-compose.dev.yml exec backend php artisan test
docker compose -f docker-compose.dev.yml exec backend php artisan test --filter=TestName
docker compose -f docker-compose.dev.yml exec backend php artisan test --testsuite=Unit
docker compose -f docker-compose.dev.yml exec backend ./vendor/bin/pint --test
```

#### Frontend (React + Vite) — SSR

From the **repo root** (recommended on Windows: `.\scripts\dev.ps1 -Ssr`), or from `frontend/`:

```bash
yarn install
yarn dev:ssr              # Requires backend on http://127.0.0.1:1234
yarn build                # SSR build
yarn messages:extract     # Extract translatable strings
yarn messages:compile     # Compile translations
npx tsc --noEmit          # TypeScript validation
```

#### Docker Development

```bash
cd docker/development
./start-dev.sh                     # Unsigned SSL certs
./start-dev.sh --certs=signed      # Signed certs with mkcert
```

### Backend Guidelines

#### Architecture Flow

- Request flow: **Action ? Handler ? Domain Service ? Repository**
- Handlers can use repositories directly when a service would be overkill
- No Eloquent in handlers or services ï¿½ Eloquent belongs in repositories only
- Favour composition over inheritance

#### General Standards

- **ALWAYS** wrap translatable strings in `__()` helper
- Domain Objects are auto-generated via `php artisan generate-domain-objects` ï¿½ never edit manually
- **Always** create unit tests for new features in `backend/tests/Unit/`
- **DON'T** add comments unless absolutely necessary
- **ALWAYS** sanitize user-provided content with `HtmlPurifierService` before storing

#### DTOs

- Use Spatie Laravel Data package for all new DTOs
- **ALWAYS** extend `BaseDataObject`, not `BaseDTO`
- **ALWAYS** favor DTOs over arrays when returning multiple values from services

#### HTTP Actions

- Always extend `BaseAction.php`
- **ALWAYS** use BaseAction response methods: `resourceResponse()`, `jsonResponse()`, `errorResponse()`, `deletedResponse()`, etc.
- Always use `isActionAuthorized` for non-public endpoints
- **DON'T** create actions handling multiple entity types with optional parameters

#### Exception Handling

- **DON'T** use generic exceptions like `InvalidArgumentException` and `RuntimeException`
- **DO** use custom exceptions and catch them in actions

#### Repository Pattern

- Favour existing repository methods over creating bespoke ones

#### Database & Migrations

- **DO** use auto-incrementing integer IDs (`$table->id()`), not UUIDs
- Use anonymous class syntax for migrations

#### Testing

- **DON'T** use `RefreshDatabase` ï¿½ use `DatabaseTransactions` instead
- Unit tests extend Laravel's TestCase; use Mockery for mocking

### Frontend Guidelines

- This is an SSR app ï¿½ ensure safe usage of `window` and `document`
- Favour existing components over creating new ones
- **ALWAYS** add translations for new user-facing strings (Lingui `t` or `Trans`)
- Use React Query for all API interactions
- Use Mantine UI components; prefer SCSS modules for layout
- **DO** use `showSuccess`, `showError` from `frontend/src/utilites/notifications.tsx`
- **DO** use `useFormErrorResponseHandler` for validation errors

### Workflows

#### Database Changes

1. Create migration: `php artisan make:migration create_XXX_table`
2. Run migration: `php artisan migrate`
3. Regenerate Domain Objects: `php artisan generate-domain-objects`

#### Before Finalizing Changes

1. Frontend: `cd frontend && npx tsc --noEmit`
2. Backend: `docker compose -f docker-compose.dev.yml exec backend php artisan test --testsuite=Unit`

<br>

## Security

### Reporting a Vulnerability

If you discover a security vulnerability, please disclose it responsibly. Do not publicly disclose until we have addressed it.

- **Urgent issues:** Email [security@hi.events](mailto:security@hi.events)
- **Non-urgent issues:** [Open a GitHub issue](https://github.com/HiEventsDev/Hi.Events/issues) labeled as a security concern

We aim to respond within 48 hours and will notify you when the vulnerability is addressed.

When reporting, include:

- A description of the vulnerability and its impact.
- Steps to reproduce, including code snippets or screenshots.
- Potential mitigations or workarounds.
- Your contact information and software version.

### PGP Public Key

```plaintext
-----BEGIN PGP PUBLIC KEY BLOCK-----

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
=YbpK
-----END PGP PUBLIC KEY BLOCK-----
```

<br>

## Support

?? [Documentation](https://hi.events/docs?utm_source=gh-readme) ï¿½ ?? [hello@hi.events](mailto:hello@hi.events) ï¿½
?? [GitHub Issues](https://github.com/HiEventsDev/Hi.Events/issues)

<br>

## Changelog

Stay updated with new features and improvements on
the [releases page](https://github.com/HiEventsDev/Hi.Events/releases).

<br>

## License

Event Hosting is licensed under **AGPL-3.0 with additional terms**. Commercial licensing
available. [Learn more](https://hi.events/licensing).

<br>

<div align="center">

**[Website](https://hi.events)** ï¿½ **[Documentation](https://hi.events/docs)** ï¿½ **[Twitter/X](https://x.com/HiEventsTickets)**

Made with ?? in Ireland

</div>
