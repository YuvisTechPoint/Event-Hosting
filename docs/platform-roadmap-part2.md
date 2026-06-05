# Platform Roadmap — Part 2 High-End Features

> **Previous:** [Part 1 — Foundation (1.1–1.3)](./platform-roadmap-part1.md)  
> **Related:** [Enterprise & Compliance Module](./architecture/enterprise-compliance-module.md)

**Product direction:** Next-Generation Event & Developer Portfolio Platform (Devfolio + Luma capabilities on top of **Event Hosting**, the Hi.Events fork).

**Scope:** Part 2 covers fifteen high-end product areas (2.1–2.15) that differentiate Event Hosting beyond baseline ticketing. This is a **strategic roadmap only** — not an implementation spec.

**Honest scope:** Sections 2.1–2.15 represent roughly **3–4 years** of product work for a small team. Most items are **net-new**; overlaps with today's codebase are called out explicitly.

---

## Current baseline (what Event Hosting already has)

Use these as extension points — do not rebuild.

| Area | Maturity | Key paths / tables |
|------|----------|-------------------|
| Bulk email to attendees | Partial | `messages`, `outgoing_messages`, `SendEventEmailJob`, `MessageTypeEnum` |
| Email templates (Liquid) | Partial | `email_templates` (account → organizer → event hierarchy) |
| Marketing opt-in | Partial | `users.marketing_opted_in_at`, `event_settings.show_marketing_opt_in` |
| Affiliate / attribution tracking | Partial | `affiliates` (organizer promo partners, not attendee referrals) |
| Messaging tiers (anti-abuse) | Partial | `account_messaging_tiers`, admin tier assignment |
| Webhooks (outgoing) | Partial | `webhooks`, `webhook_logs`, `DomainEventType` |
| Order audit trail | Partial | `order_audit_logs` (order lifecycle only, not admin actions) |
| Account RBAC | Partial | `account_users.role` — `SUPERADMIN`, `ADMIN`, `ORGANIZER` only |
| Multi-account tenancy | Strong | `accounts` → `organizers` → `events` isolation via `account_id` |
| JWT auth + impersonation | Strong | `LoginService`, superadmin impersonation |
| Organizer reports | Partial | `/organizers/{id}/reports/{type}`, export actions |
| Event analytics | Partial | `event_statistics`, `EventAnalyticsFetchService`, dashboard charts |
| Public event pages + SEO | Partial | `HomepageDesigner/`, `event_settings.seo_*`, `homepage_theme_settings` |
| Embed widget | Strong | `WidgetEditor/`, `src/embed/widget.js` |
| Waitlist | Strong | `waitlist_entries`, auto-offer flow |
| Per-event calendar add | Partial | `AddToCalendarCTA`, `CalendarOptionsPopover` (no account-level sync) |
| i18n / locale | Partial | Lingui (`en`, `de`, `es`, `fr`, `hu`, `it`, …), per-order/attendee `locale` |
| Cookie consent (tracking) | Partial | `CookieConsentBanner`, organizer tracking pixels |
| Public discovery stub | Partial | `/discover` route, `GET public/events` (basic list, no map/federated search) |
| Realtime | Partial | Soketi/Echo, `EventRealtimeBroadcastService` |
| Stripe + Connect | Strong | Payments, refunds, platform fees |

**Gap honesty:** ~**85–95% of Part 2** is net-new product surface. Highest overlap is in **2.11** (email), **2.12** (tenancy + partial RBAC/audit), **2.13** (public pages + discover stub), and **2.9** (webhooks + embed).

---

## Cross-cutting architecture

### Monolith modules vs microservices

**Recommendation: stay monolith-modular.** Event Hosting's DDD layout (`Action → Handler → Service → Repository`) supports new bounded contexts as Laravel modules without splitting deployables.

| New module | Suggested namespace / path |
|------------|------------------------------|
| AI / matching | `app/Services/Domain/Matching/` |
| Streaming | `app/Services/Infrastructure/Streaming/` (adapter to Mux/LiveKit) |
| Campaigns | `app/Services/Domain/Campaign/` |
| Compliance | `app/Services/Domain/Compliance/` |
| Discovery | `app/Services/Domain/Discovery/` |

Extract to separate services only when a subsystem needs **independent scaling** (e.g. video ingest, embedding workers) — not at MVP.

### Shared infrastructure (already present)

| Service | Use in Part 2 |
|---------|---------------|
| PostgreSQL | Primary store; add `pgvector` extension for embeddings (2.1) |
| Redis | Job queues, rate limits, session cache, real-time presence |
| Laravel queues | Email/SMS campaigns, webhook retries, AI batch jobs |
| S3 / disk storage | Recordings, generated social assets, export bundles |
| Soketi/Echo | Live dashboards, chat, judging updates |
| Stripe | Sponsorship tiers, bounties, crowdfunding (2.5) |

### Security & privacy (AI + enterprise)

- **PII minimization:** Store embeddings keyed by `user_id` / `developer_profile_id`; never embed raw emails in vector metadata.
- **Consent gates:** AI matching, proximity networking, and recording require explicit opt-in tied to `event_settings` or per-registration flags.
- **Data residency:** Enterprise module (2.12) should support export/delete before enabling cross-region AI vendors.
- **Audit:** Extend beyond `order_audit_logs` to `admin_audit_logs` for all mutating admin actions.

### Mobile strategy

**PWA-first** (Phase P1): offline schedule, push via web push, QR check-in already works in mobile browsers.  
**React Native** (Phase P2): defer until 2.8 feature set justifies app-store presence.

---

## 2.1 AI-Powered Smart Matching & Recommendations

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| AI team matching (hackathon) | | ✓ | ✓ | Part 1 `hackathon_teams`, `developer_profiles`, skill tags |
| Smart event recommendations | | | ✓ | Part 1 `community_follows`, public events API |
| Mentor–mentee matching | | | ✓ | Part 1 speakers/sessions, mentor pool entity |
| Sponsor–project matching (NLP) | | | ✓ | Part 1 `hackathon_submissions`, sponsor module (2.5) |
| "People you should meet" | | | ✓ | Attendee graph, opt-in, embeddings |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Embeddings | **Integrate** OpenAI / Voyage / Cohere embeddings API |
| Vector search | **Build** on PostgreSQL `pgvector` (keeps ops simple) |
| Personality quiz | **Build** lightweight in-app quiz → feature vector |
| ML recommendations | **Build** v1 (rules + cosine similarity); **Buy** personalization later (Algolia Recommend, AWS Personalize) |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Profile skill tags + embedding pipeline | P1 | M |
| Team matching MVP (solo registrants) | P1 | L |
| Event recommendations (followed organizers + tags) | P2 | M |
| Mentor/sponsor NLP matching | P2 | L |
| Attendee networking suggestions | P2 | M |

### Stack fit

- **Laravel:** `MatchingService`, queued embedding jobs, Redis cache for similarity results.
- **React:** Match suggestions UI in hackathon dashboard + optional networking tab.
- **PostgreSQL:** `user_embeddings`, `developer_profile_skills` with `pgvector` column.
- **Redis:** Rate-limit matching recomputation.

### Schema / API touchpoints

- **Extend:** `developer_profiles.metadata`, `attendees` (add `networking_opt_in`, `skill_tags` JSONB).
- **New:** `user_embeddings`, `match_suggestions`, `matching_quiz_responses`.
- **API:** `POST /events/{id}/matching/teams`, `GET /users/me/recommendations/events`.

---

## 2.2 Integrated Live Streaming & Virtual Event Studio

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Built-in RTMP/WebRTC streaming | | | ✓ | Infrastructure budget |
| Multi-speaker stage | | | ✓ | Streaming provider |
| Interactive overlays (polls, Q&A) | | ✓ | ✓ | Soketi (chat exists for orders, not stages) |
| Breakout rooms | | | ✓ | Streaming provider |
| Hybrid event mode (QR digital layer) | | ✓ | ✓ | Check-in QR, sessions (Part 1) |
| Auto-record + AI summarize | | | ✓ | 2.2 + 2.15 knowledge base |
| Instant clip generation | | | ✓ | Transcription pipeline |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Streaming infra | **Buy/Integrate** Mux, LiveKit, or Daily.co — do not build CDN/ingest |
| Transcription | **Integrate** OpenAI Whisper API or Deepgram |
| Summarization | **Integrate** LLM API |
| Polls/Q&A UI | **Build** on existing React + Soketi |
| Breakout rooms | **Buy** via provider SDK |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Mux/LiveKit adapter + embed player on event page | P1 | L |
| Session-linked stream URLs (Part 1 `event_sessions`) | P1 | M |
| Live Q&A + polls overlay | P2 | L |
| Recording → transcript → summary pipeline | P2 | L |
| Breakout rooms + hybrid QR join | P2 | XL |

### Stack fit

- **Laravel:** Webhook handlers for stream lifecycle; signed playback tokens.
- **React:** `StreamStage` component, session page embed.
- **Soketi:** Q&A and reaction channels per session.
- **S3:** Recording assets.

### Schema / API touchpoints

- **Extend:** `event_sessions` → `stream_provider`, `stream_key`, `playback_id`, `recording_url`.
- **New:** `session_polls`, `session_questions`, `stream_recordings`.
- **API:** `POST /events/{id}/sessions/{sid}/stream`, webhooks from Mux/LiveKit.

---

## 2.3 Advanced Analytics & Insights Dashboard

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Real-time attendee / engagement analytics | | ✓ | ✓ | `event_statistics`, Soketi |
| Registration funnel (views → attendance) | | ✓ | ✓ | `event_statistics.unique_views`, orders |
| Demographic breakdown | | ✓ | ✓ | Registration questions, `EventAnalyticsFetchService` |
| NPS + post-event surveys | | | ✓ | 2.11 drip + forms |
| Comparative analytics (event vs event) | | | ✓ | Historical stats |
| Sponsor ROI dashboard | | | ✓ | 2.5 sponsorship |
| Developer profile analytics | | | ✓ | Part 1 `developer_profiles` |
| Community health metrics | | | ✓ | Part 1 `community_follows` |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Dashboards | **Build** (extend `EventDashboard`, organizer reports) |
| Product analytics | **Integrate** PostHog or Plausible for marketing site; **Build** for organizer-facing |
| Sentiment analysis | **Integrate** LLM API on chat export (P2) |
| Data warehouse | **Integrate** Metabase on read replica (P2) |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Funnel dashboard (views → orders → check-ins) | P0 | M |
| Cohort comparisons + date filters | P1 | M |
| Real-time session attendance (with 2.2) | P2 | L |
| Sponsor/developer/community dashboards | P2 | L |

### Stack fit

- **Laravel:** Extend `EventAnalyticsFetchService`; materialized views or nightly rollup jobs.
- **React:** Extend `EventAnalytics/`, chart components already in dashboard.
- **PostgreSQL:** `event_daily_statistics` (exists), add `funnel_snapshots` if needed.
- **Redis:** Cache expensive aggregates.

### Schema / API touchpoints

- **Extend:** `event_statistics`, `event_daily_statistics`, organizer report handlers.
- **New:** `survey_responses`, `nps_scores` (or generic `feedback_submissions`).
- **API:** `GET /events/{id}/analytics/funnel`, extend existing stats endpoints.

---

## 2.4 Hackathon Project Collaboration Suite

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Cloud IDE / workspace | | | ✓ | Part 1 teams |
| Git integration + deploy previews | | | ✓ | OAuth to GitHub |
| Kanban board | | | ✓ | Team entity |
| Design whiteboard | | | ✓ | Excalidraw embed |
| Enhanced submissions + plagiarism | | ✓ | ✓ | Part 1 `hackathon_submissions` |
| Project heartbeat timeline | | | ✓ | Git webhooks, activity feed |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| IDE | **Integrate** GitHub Codespaces link or **Buy** StackBlitz WebContainer (P2) — do not build full IDE |
| Git | **Integrate** GitHub API |
| Kanban | **Build** lightweight board or **Integrate** embedded Trello/Linear |
| Whiteboard | **Integrate** Excalidraw SDK |
| Plagiarism | **Integrate** MOSS/GitHub search API + **Build** heuristics |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Team kanban + task assignments | P1 | M |
| GitHub repo link + commit activity feed | P1 | M |
| Structured submission form v2 | P1 | S |
| Embedded whiteboard | P2 | M |
| Cloud IDE integration | P2 | L |
| Plagiarism detection | P2 | L |

### Stack fit

- **Laravel:** GitHub webhook receiver, submission validation.
- **React:** Team workspace tab, drag-and-drop kanban.
- **Redis:** Activity stream buffer.

### Schema / API touchpoints

- **Extend:** `hackathon_submissions` (tech_stack, track, api_usage JSONB).
- **New:** `team_tasks`, `team_activity_events`, `submission_plagiarism_flags`.
- **API:** `GET/POST /events/{id}/teams/{tid}/tasks`, GitHub webhook route.

---

## 2.5 Advanced Sponsorship & Monetization Engine

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Sponsor marketplace | | | ✓ | Public organizer pages |
| Dynamic sponsorship tiers | | | ✓ | Products pattern |
| Bounty system | | | ✓ | Submissions, payouts |
| Virtual sponsor booths | | | ✓ | Event homepage designer |
| Revenue splitting | | ✓ | ✓ | Stripe Connect (single account today) |
| Crypto / crowdfunding | | | ✓ | Payment rails |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Tier checkout | **Build** on `products` / `product_prices` pattern |
| Marketplace discovery | **Build** organizer-facing listings (P2) |
| Payout splits | **Integrate** Stripe Connect multi-party transfers |
| Crypto | **Integrate** Coinbase Commerce or defer |
| Crowdfunding | **Build** pledge products + **Integrate** Stripe |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Sponsorship products + tier config | P1 | M |
| Sponsor landing pages on event site | P1 | M |
| Bounty definitions + eligibility rules | P2 | L |
| Revenue split rules engine | P2 | L |
| Marketplace + crowdfunding | P2 | XL |

### Stack fit

- **Laravel:** Extend order/payment handlers; sponsor-specific promo codes.
- **React:** Sponsor admin UI, booth designer (extend HomepageDesigner).
- **Stripe:** Multi-party payouts, invoicing.

### Schema / API touchpoints

- **Extend:** `products` (sponsorship type), `affiliates` (sponsor attribution).
- **New:** `sponsor_tiers`, `bounties`, `bounty_submissions`, `revenue_split_rules`.
- **API:** `POST /events/{id}/sponsors/tiers`, `GET /sponsors/marketplace`.

---

## 2.6 Gamification & Reputation System

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| XP & leveling | | | ✓ | Activity events |
| Achievement badges | | ✓ | ✓ | Part 1 `developer_badges` (scaffolded) |
| Leaderboards | | | ✓ | XP ledger |
| Skill endorsements | | | ✓ | Developer profiles |
| NFT / POAP certificates | | | ✓ | Optional web3 |
| Streaks & quests | | | ✓ | Gamification engine |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| XP/achievements | **Build** rules engine + Part 1 badges |
| Leaderboards | **Build** Redis sorted sets |
| NFT credentials | **Integrate** POAP.xyz API (optional P2) — defer by default |
| Endorsements | **Build** LinkedIn-style on `developer_profiles` |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Badge automation (attended, submitted, won) | P1 | M |
| XP ledger + profile level display | P2 | M |
| Leaderboards (event + platform) | P2 | M |
| Quests/streaks | P2 | L |
| NFT/POAP | P2 | M (optional) |

### Stack fit

- **Laravel:** `GamificationService` listening to domain events (check-in, submission).
- **React:** Profile badge strip, leaderboard widgets.
- **Redis:** Leaderboard rankings.

### Schema / API touchpoints

- **Extend:** `developer_badges` (exists in Part 1 scaffold).
- **New:** `user_xp_ledger`, `achievements`, `endorsements`, `quests`.
- **API:** `GET /users/{id}/gamification`, event-triggered award jobs.

---

## 2.7 Advanced Networking & Social Layer

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| AI speed networking (video) | | | ✓ | 2.1 matching, WebRTC |
| Digital business cards (QR) | | ✓ | ✓ | Attendee QR, profiles |
| DM & group chat | | | ✓ | Messaging infra |
| Connection requests | | | ✓ | Social graph |
| Mutual availability scheduler | | | ✓ | Part 1 sessions/agenda |
| Social feed | | | ✓ | Part 1 follows |
| Warm introductions | | | ✓ | Connection graph |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Video 1:1 | **Integrate** Daily.co / LiveKit |
| Chat | **Build** on Soketi + PostgreSQL persistence |
| Business cards | **Build** vCard + QR on attendee profile |
| Feed | **Build** activity table + follow graph |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Connection requests + digital card QR | P1 | M |
| Event-scoped group chat | P2 | L |
| Speed networking rounds | P2 | L |
| Social feed + warm intros | P2 | L |

### Stack fit

- **Laravel:** Chat messages API, connection graph.
- **React:** Networking tab, chat UI.
- **Soketi:** Real-time chat channels.

### Schema / API touchpoints

- **Extend:** `attendees` (public_profile_slug, networking_visible).
- **New:** `user_connections`, `chat_channels`, `chat_messages`, `networking_rounds`.
- **API:** `POST /connections`, `GET /events/{id}/chat/channels`.

---

## 2.8 Mobile-First Experience & Native App

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Native app (RN/Flutter) | | | ✓ | API coverage (2.9) |
| Push notifications | | ✓ | ✓ | Web push possible first |
| Offline schedule | | | ✓ | PWA service worker |
| AR navigation | | | ✓ | Venue maps |
| Proximity networking | | | ✓ | Bluetooth, opt-in |
| Wearables | | | ✓ | Native app |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Mobile MVP | **Build** PWA with service worker + web push |
| Native app | **Build** React Native (P2) sharing API client |
| AR | **Integrate** ARKit/ARCore via native shell (P2) |
| Proximity | **Build** BLE beacon optional module (P2) |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| PWA manifest + offline schedule cache | P1 | M |
| Web push for event reminders | P1 | M |
| React Native shell (check-in, tickets, schedule) | P2 | XL |
| AR / proximity / wearables | P2 | XL |

### Stack fit

- **React:** Vite PWA plugin, responsive check-in already exists.
- **Laravel:** Push subscription storage, notification dispatch.
- **Redis:** Push queue.

### Schema / API touchpoints

- **New:** `push_subscriptions`, `offline_bundle_manifests`.
- **API:** Mobile-optimized `GET /users/me/tickets`, `GET /events/{id}/schedule`.

---

## 2.9 Developer-First API & Extensibility Platform

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Comprehensive REST API | | ✓ | ✓ | Internal API exists, not public partner API |
| GraphQL | | | ✓ | REST first |
| Webhooks (broad coverage) | | ✓ | ✓ | `webhooks`, limited `DomainEventType` |
| Embeddable widgets | | ✓ | ✓ | Ticket widget only |
| Zapier/Make | | | ✓ | Public API + OAuth |
| Custom forms engine | | ✓ | ✓ | `questions` at checkout |
| Plugin marketplace | | | ✓ | Extensibility framework |
| White-label | | ✓ | ✓ | Theming exists, not full white-label |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Public REST API | **Build** versioned `/api/v1/public/` surface |
| OAuth2 for partners | **Build** Laravel Passport or Sanctum tokens |
| Webhooks | **Extend** existing `WebhookEventListener` + more `DomainEventType` values |
| Zapier | **Integrate** Zapier Platform app (P1) |
| Plugin system | **Build** webhook + settings schema registry (P2) |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| API key management + public docs (OpenAPI) | P0 | M |
| Expand webhook event types | P0 | S |
| OAuth2 partner auth | P1 | M |
| Zapier integration (5–10 triggers/actions) | P1 | M |
| Calendar/countdown embed widgets | P1 | M |
| GraphQL + plugin marketplace | P2 | XL |

### Stack fit

- **Laravel:** API routes, Sanctum/Passport, webhook dispatcher (exists).
- **React:** Developer portal UI for keys/webhooks.
- **PostgreSQL:** `api_keys`, `oauth_clients`.

### Schema / API touchpoints

- **Extend:** `webhooks.event_types`, `DomainEventType` enum.
- **New:** `api_keys`, `oauth_access_tokens`, `plugin_installations`.
- **API:** Versioned public REST; document existing internal patterns.

---

## 2.10 Advanced Judging & Evaluation System

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Multi-round judging | | | ✓ | Part 1 submissions |
| Configurable rubrics | | | ✓ | Forms engine |
| Blind judging | | | ✓ | Submissions |
| Conflict-of-interest detection | | | ✓ | User/org graph |
| Live judging UI | | | ✓ | Submissions + media |
| Audience voting | | | ✓ | Anti-fraud |
| AI pre-screening | | | ✓ | 2.1 NLP |
| Deliberation room | | | ✓ | 2.2 video/chat |
| Results visualization | | | ✓ | Public results page |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Rubrics & scoring | **Build** weighted criteria engine |
| Judge panel UI | **Build** React judging dashboard |
| Video deliberation | **Integrate** Daily.co room |
| Anti-fraud voting | **Build** rate limits + account verification |
| AI pre-screen | **Integrate** LLM completeness check |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Rubric builder + judge scoring UI | P1 | L |
| Multi-round pipeline | P1 | M |
| Blind mode + COI flags | P2 | M |
| Audience vote + results page | P2 | M |
| AI pre-screen + deliberation room | P2 | L |

### Stack fit

- **Laravel:** `JudgingService`, score aggregation, audit trail.
- **React:** Judge portal, side-by-side submission compare.
- **PostgreSQL:** Normalized scores; judge assignments.

### Schema / API touchpoints

- **Extend:** `hackathon_submissions` (round, blind_id).
- **New:** `judging_rounds`, `rubrics`, `rubric_criteria`, `judge_scores`, `audience_votes`.
- **API:** `POST /events/{id}/judging/scores`, `GET /events/{id}/results`.

---

## 2.11 Advanced Communication & Marketing

> **Highest overlap with existing codebase.** Extend `messages`, `email_templates`, and `outgoing_messages` rather than replacing them.

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Email campaign builder (drag-and-drop) | | ✓ | ✓ | `email_templates` (Liquid, not visual builder) |
| Segmented messaging | | ✓ | ✓ | `MessageTypeEnum`, `recipient_ids`/`product_ids` JSONB — no check-in/tags segments |
| SMS & WhatsApp | | | ✓ | Notification channel abstraction |
| Automated drip campaigns | | | ✓ | Trigger engine, templates |
| Branded event microsites | | ✓ | ✓ | `HomepageDesigner`, `seo_*` — no A/B testing |
| Social media kit generator | | | ✓ | Image generation pipeline |
| Referral system (attendee incentives) | | ✓ | ✓ | `affiliates` tracks partner codes, not attendee referrals |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Visual email builder | **Integrate** Unlayer or Beefree SDK embedded in React |
| Template rendering | **Keep** Liquid engine in Laravel for send-time merge |
| SMS | **Integrate** Twilio or Vonage |
| WhatsApp | **Integrate** Twilio WhatsApp Business API |
| Drip automation | **Build** campaign scheduler on Laravel queues + Redis |
| Microsite A/B tests | **Build** lightweight variant table + conversion tracking |
| Social kit | **Build** server-side image compositing (Intervention Image) or **Integrate** Bannerbear |
| Referrals | **Build** on `promo_codes` pattern + unique attendee share links |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Segment builder (ticket, check-in, tags, waitlist) | P0 | M |
| Drip sequence engine (registration → reminder → thank-you) | P0 | L |
| Visual email designer (pre/during/post templates) | P1 | L |
| Referral links + incentive rules | P1 | M |
| SMS/WhatsApp + channel preferences | P1 | L |
| Microsite A/B headlines/CTAs | P2 | M |
| Social kit generator | P2 | M |

### Stack fit

- **Laravel:** `CampaignService`, extend `SendEventEmailMessagesService`; Twilio notification channel; queue-driven drip steps.
- **React:** Campaign builder UI, segment picker (reuse attendee table filters).
- **PostgreSQL:** `campaigns`, `campaign_steps`, `campaign_enrollments`.
- **Redis:** Drip schedule timers, send-rate throttling (respect `account_messaging_tiers`).

### Schema / API touchpoints

- **Extend:** `messages` (link to `campaign_id`), `email_templates` (add `campaign_template_type`), `affiliates` or new `referral_codes`.
- **New:** `campaigns`, `campaign_steps`, `campaign_enrollments`, `message_segments`, `notification_channel_preferences`, `microsite_variants`, `referral_redemptions`.
- **API:** `POST /events/{id}/campaigns`, `POST /campaigns/{id}/enroll`, `GET /events/{id}/segments/preview`.

### 2.11 MVP implementation status (2026-06-06)

**Shipped in codebase (foundation):**

| Component | Path |
|-----------|------|
| Migrations | `message_segments`, `drip_campaigns`, `drip_campaign_steps` |
| Segment resolver | `MessageSegmentResolverService` — ticket type, check-in, registration status rules |
| Drip engine | `ProcessDripCampaignStepJob` → existing `SendMessageHandler` / mail infra |
| Registration trigger | `EnrollAttendeesInDripCampaignsListener` on `OrderStatusChangedEvent` |
| API | `/events/{id}/drip-campaigns`, `/events/{id}/message-segments`, test-send endpoint |
| Admin UI | Event → Drip Campaigns (minimal list + create with 3-step default) |

**Explicitly deferred (post-MVP):**

- Drag-and-drop visual email designer (Unlayer/Beefree embed)
- SMS & WhatsApp channels (Twilio)
- Social media kit generator (Bannerbear / server-side compositing)
- Microsite A/B testing variants
- Full attendee referral system (beyond existing organizer `affiliates`)
- `campaign_enrollments` tracking table (steps scheduled via queued jobs per attendee for now)
- Scheduled campaign batch trigger (enum exists; listener not wired yet)

---

## 2.12 Enterprise & Compliance

> **Partial today:** multi-account tenancy, 3-role RBAC, `order_audit_logs`, cookie consent for tracking pixels. See [enterprise-compliance-module.md](./architecture/enterprise-compliance-module.md).

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| SSO / SAML | | | ✓ | Identity provider |
| Granular RBAC (judge, mentor, volunteer, …) | | ✓ | ✓ | `Role` enum has 3 roles only |
| Admin audit logs (all actions) | | ✓ | ✓ | `order_audit_logs` is order-scoped only |
| GDPR/CCPA (export, delete, consent) | | ✓ | ✓ | Cookie banner exists; no DSAR workflow |
| SOC 2 Type II | | | ✓ | Org process + technical controls |
| Custom data retention policies | | | ✓ | Soft deletes exist, no policy engine |
| Multi-tenancy (isolated envs) | | ✓ | ✓ | Account isolation strong; no dedicated shards |
| SLA + status page | | | ✓ | Ops |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| SSO/SAML | **Integrate** WorkOS, Auth0, or Laravel Socialite + SAML package |
| RBAC | **Build** permission matrix on top of `account_users` |
| Audit logs | **Build** `admin_audit_logs` middleware on all mutating Actions |
| GDPR export/delete | **Build** DSAR workflow (queued ZIP export, anonymize) |
| SOC 2 | **Buy** compliance automation (Vanta/Drata) + engineering controls |
| Status page | **Integrate** Instatus or Better Stack |
| Data retention | **Build** policy rules + scheduled purge jobs |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Granular RBAC + custom roles | P0 | L |
| Admin audit log middleware | P0 | M |
| GDPR export + delete (DSAR) | P1 | L |
| SSO/SAML (WorkOS) | P1 | M |
| Data retention policies | P1 | M |
| SOC 2 control implementation | P2 | XL |
| Per-tenant dedicated infra (enterprise) | P2 | XL |

### Stack fit

- **Laravel:** Policy gates, `AuditLogMiddleware`, `ComplianceExportJob`.
- **React:** Role management UI, DSAR self-service in account settings.
- **PostgreSQL:** `roles`, `permissions`, `role_permissions`, `admin_audit_logs`, `data_retention_policies`.
- **S3:** Export bundles (encrypted, expiring links).

### Schema / API touchpoints

- **Extend:** `account_users` (role_id FK instead of enum string), `users` (SSO subject id).
- **New:** `roles`, `permissions`, `admin_audit_logs`, `dsar_requests`, `consent_records`, `data_retention_policies`.
- **API:** `GET /accounts/{id}/audit-logs`, `POST /users/me/data-export`, `DELETE /users/me/data-erasure`.

---

## 2.13 Event Discovery & Search

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Map-based discovery | | | ✓ | `events.location_details` JSONB |
| Advanced filters (tech stack, a11y, …) | | ✓ | ✓ | `/discover` stub, basic public list |
| Curated collections | | | ✓ | Editorial CMS |
| Calendar sync (Google/Outlook/Apple) | | ✓ | ✓ | Per-event `AddToCalendarCTA` only |
| Event series (recurring) | | | ✓ | Single events today |
| Federated search (events/projects/people) | | | ✓ | Part 1 portfolio + follows |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Geo search | **Build** PostGIS or lat/lng + radius query on `events.location_details` |
| Full-text search | **Integrate** Meilisearch or Algolia (federated index) |
| Map UI | **Build** Mapbox GL in React |
| Calendar sync | **Integrate** Google/Outlook OAuth + ICS feed; extend existing `CalendarOptionsPopover` |
| Event series | **Build** `event_series` parent + instance generation |
| Curated collections | **Build** admin CMS for collection → event mappings |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Discover page filters (date, price, format, category) | P0 | M |
| Account-level calendar feed (webcal/ICS for followed organizers) | P1 | M |
| Map view + geo index | P1 | L |
| Event series + shared branding | P1 | L |
| Federated search index | P2 | L |
| Curated collections | P2 | M |

### Stack fit

- **Laravel:** `DiscoverySearchService`, ICS feed endpoint, series generator command.
- **React:** Extend `/discover`, map component, filter chips.
- **PostgreSQL:** PostGIS extension or indexed lat/lng; `event_series`.
- **Meilisearch:** Federated index for events, profiles, projects.

### Schema / API touchpoints

- **Extend:** `events` (`series_id`, `accessibility_tags` JSONB), `events.location_details` (geo index).
- **New:** `event_series`, `curated_collections`, `collection_events`, `saved_searches`.
- **API:** `GET /public/discover`, `GET /public/calendar.ics`, `GET /public/search`.

---

## 2.14 Accessibility & Inclusivity

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Live captioning | | | ✓ | 2.2 streaming |
| Sign language overlay | | | ✓ | Stream layout |
| Screen reader / ARIA | | ✓ | ✓ | Mantine components; not audited WCAG-wide |
| Accessibility tags on events | | | ✓ | Event metadata |
| Real-time translation | | | ✓ | Streaming + chat |
| Inclusive design (contrast, dyslexia font) | | ✓ | ✓ | Theme settings partial |
| Pronoun support | | | ✓ | User/attendee profiles |
| Code of conduct engine | | | ✓ | Moderation queue |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Live captions | **Integrate** Deepgram / Rev.ai on stream audio |
| Translation | **Integrate** Google Cloud Translation or DeepL |
| Sign language | **Build** PiP layout slot in stream player |
| ARIA audit | **Build** WCAG 2.1 AA pass on public + checkout flows |
| a11y tags | **Build** organizer checklist → searchable metadata |
| Inclusive theme | **Build** high-contrast + OpenDyslexic toggle in theme |
| CoC reporting | **Build** incident form + moderation queue |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Accessibility tags + discover filters (links 2.13) | P0 | S |
| Pronoun fields on attendee/profile | P0 | S |
| WCAG audit + keyboard nav fixes (public/checkout) | P1 | L |
| Inclusive theme tokens (contrast, font size) | P1 | M |
| Live captions on streams | P2 | M |
| CoC incident reporting + moderation | P2 | M |
| Real-time translation | P2 | L |

### Stack fit

- **Laravel:** `AccessibilityMetadata` on events; incident reports API.
- **React:** Theme toggles, ARIA fixes, caption overlay component.
- **PostgreSQL:** `incident_reports`, `moderation_actions`.

### Schema / API touchpoints

- **Extend:** `event_settings` (`accessibility_tags`, `code_of_conduct_url`), `users`/`attendees` (`pronouns`).
- **New:** `incident_reports`, `inclusive_theme_preferences`.
- **API:** `POST /events/{id}/incidents`, `PATCH /events/{id}/accessibility`.

---

## 2.15 Post-Event & Long-Term Value

### Gap table

| Feature | Exists? | Partial? | Net-new | Dependencies |
|---------|---------|----------|---------|--------------|
| Knowledge base (from recordings/slides) | | | ✓ | 2.2 recordings, Part 1 sessions |
| Alumni network (cohort groups) | | | ✓ | Event attendees, chat (2.7) |
| Project incubation pipeline | | | ✓ | Part 1 submissions, 2.5 sponsors |
| Continuous learning paths | | ✓ | ✓ | 2.1 recommendations |
| Impact reports | | ✓ | ✓ | Analytics (2.3), diversity from questions |
| Testimonials & reviews | | | ✓ | Public organizer reputation |

### Build vs Buy vs Integrate

| Capability | Recommendation |
|------------|----------------|
| Knowledge base | **Build** search-indexed articles auto-generated from transcripts (2.2) |
| Alumni groups | **Build** auto-create group from `attendees` + optional chat channel |
| Incubation tracking | **Build** milestone tracker on `hackathon_submissions` |
| Learning paths | **Build** rules + **Integrate** external course links |
| Impact reports | **Build** PDF/HTML generator from stats + survey data |
| Reviews | **Build** moderated star ratings on organizers/events |

### Phase & effort

| Item | Phase | Effort |
|------|-------|--------|
| Post-event drip + feedback survey (ties 2.11) | P0 | M |
| Alumni group auto-provision + directory | P1 | M |
| Event/org review system | P1 | M |
| Impact report generator | P1 | M |
| Knowledge base from session transcripts | P2 | L |
| Incubation pipeline for winning projects | P2 | L |
| Learning path recommendations | P2 | M |

### Stack fit

- **Laravel:** `PostEventService`, report generation (DomPDF exists in many Laravel apps).
- **React:** Alumni hub, knowledge base reader, review widgets on organizer pages.
- **PostgreSQL:** `alumni_groups`, `knowledge_articles`, `project_milestones`, `event_reviews`.
- **Meilisearch:** Knowledge base full-text search.

### Schema / API touchpoints

- **Extend:** `hackathon_submissions` (post_event_status, milestones), `organizers` (aggregate_rating).
- **New:** `alumni_groups`, `alumni_group_members`, `knowledge_articles`, `event_reviews`, `impact_reports`.
- **API:** `GET /events/{id}/alumni`, `GET /events/{id}/knowledge-base`, `POST /events/{id}/reviews`.

---

## Recommended build order (top 3)

Priorities updated after incorporating **2.11–2.15**. Favor extensions to proven infrastructure over greenfield AI/streaming.

| Priority | Feature | Section | Why first | Risk |
|----------|---------|---------|-----------|------|
| **1** | Segmented messaging + drip campaign engine | 2.11 | Builds directly on `messages`, `email_templates`, `outgoing_messages`, `account_messaging_tiers`; immediate organizer ROI; unlocks post-event surveys (2.15) | Low |
| **2** | Public API keys + webhook expansion + Zapier | 2.9 | `webhooks` and embed widget exist; opens ecosystem and enterprise integrations without ML/streaming cost | Low |
| **3** | Granular RBAC + admin audit logs | 2.12 | `account_users` + `order_audit_logs` prove patterns; unlocks enterprise sales and compliance path (DSAR, SSO next) | Medium |

**Runners-up (after top 3):**

- **Discover filters + calendar feed** (2.13) — extends `/discover` and Part 1 `community_follows`.
- **Funnel analytics dashboard** (2.3) — extends `event_statistics` and `EventAnalytics`.
- **Hackathon judging rubrics** (2.10) — high differentiation once Part 1 submissions exist.

**Defer to P2:** Native streaming studio (2.2), cloud IDE (2.4), NFT credentials (2.6), AR navigation (2.8), federated search (2.13), live translation (2.14).

---

## Part 3 recommendation (next prompt)

Use this prompt for the next planning session:

> **PART 3: Production Hardening, Ops & Go-to-Market**
>
> For Event Hosting (Hi.Events fork), create `docs/platform-roadmap-part3.md` covering:
>
> 1. **Operational readiness** — observability (logs, metrics, tracing), on-call, backup/restore, queue monitoring, Stripe webhook reliability, email deliverability (SPF/DKIM/DMARC).
> 2. **Security hardening** — pen-test checklist, OWASP top 10 for Laravel/React, secrets rotation, rate limiting audit, dependency scanning.
> 3. **Scale path** — read replicas, CDN for widget/embed, horizontal queue workers, Soketi clustering, database connection pooling.
> 4. **Deployment** — native Windows dev vs Docker production, CI/CD pipeline, zero-downtime migrations, feature flags.
> 5. **Go-to-market** — pricing tiers aligned with `account_messaging_tiers`, enterprise SSO upsell, organizer migration from Eventbrite/Luma, open-source vs hosted positioning.
> 6. **Sequenced execution plan** — merge Part 1 Phase A, Part 2 top-3, and ops hardening into a single 6-month roadmap with milestones.
>
> Cross-reference: `docs/platform-roadmap-part1.md`, `docs/platform-roadmap-part2.md`, `docs/architecture/enterprise-compliance-module.md`.

---

## Files in this roadmap session

| File | Purpose |
|------|---------|
| `docs/platform-roadmap-part2.md` | This document (2.1–2.15) |
| `docs/architecture/enterprise-compliance-module.md` | Enterprise/compliance one-pager |
| `docs/platform-roadmap-part1.md` | Updated Part 2 link (2.1–2.15) |
