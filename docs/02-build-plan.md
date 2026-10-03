# USA Smart Gamers — Build Plan

Companion to [01-playusa-blueprint.md](./01-playusa-blueprint.md). Goal: a WordPress site with the same
feature set, page types, UX and architecture as PlayUSA.com, filled with **our own** brand, content and
affiliate data, hosted on our existing DigitalOcean droplet and deployed from GitHub.

---

## 1. Guiding decisions

| # | Decision | Why |
|---|---|---|
| D1 | **Custom theme `usasmartgamers` + custom plugin `usasmartgamers-core`** (same pattern as OntarioGamers) | Theme = presentation only; plugin = data model, blocks, redirects, schema. Swapping the theme never loses data. |
| D2 | **Classic (hybrid) PHP theme + Gutenberg for content** | Mirrors PlayUSA (`catena-unicorn` is a classic theme with custom blocks). Editors build pages with our blocks; PHP templates own header/footer/CPT singles. |
| D3 | **Custom dynamic Gutenberg blocks** (`block.json` + `render.php`, editor UI built with `@wordpress/scripts`) | Server-rendered = SEO-friendly, cacheable, and data (operators, offers) updates everywhere at once. |
| D4 | **Operators, offers, slots, providers, states are structured data (CPTs/taxonomies)**, never hard-coded in page text | Change a bonus once → every toplist, card, review box and sticky CTA updates. This is the core of the PlayUSA model. |
| D5 | Use proven free plugins for commodity features (SEO, tables, user reviews, gamification); build only what is our differentiator | Faster, cheaper, less code to maintain on a small server. |
| D6 | **Geo-targeting via Cloudflare visitor-location headers**, rendered client-side over a page-cached default | Lets us page-cache everything while still showing state-legal offers. |
| D7 | **Git is the source of truth for code; the database is the source of truth for content** | Code ships by CI; content is edited in wp-admin and protected by nightly backups. |
| D8 | Host isolated on the shared droplet per the `add-wordpress-site` skill (own container, port 8082, own DB on the shared MySQL, mem cap) | Zero impact on OntarioGamers and ZuriStay. |

## 2. Target architecture

```
                 GitHub (cliffdoyle/usasmartgamers)
                 │  push / PR
                 ▼
        GitHub Actions ── CI: php -l, phpcs, npm build ── CD (main only):
                 │        rsync built theme+plugin over SSH (restricted key, rrsync)
                 ▼        → purge Cloudflare cache → smoke test
 Visitor ─HTTPS─► Cloudflare (proxy, SSL Flexible, WAF, CDN, geo headers)
                 │  Origin Rule: host = usasmartgamers.* → port 8082
                 ▼
 DigitalOcean droplet 143.110.219.173 (shared, 1 vCPU / 2 GB)
 ├─ :80   igaming-wordpress-1   (OntarioGamers — untouched)
 ├─ :443  hotel-caddy-1         (ZuriStay — untouched)
 ├─ :8082 usasmartgamers-wordpress-1  (NEW, mem_limit, page cache)
 │         ├─ bind: /root/usasmartgamers/src/wp-content/themes/usasmartgamers
 │         └─ bind: /root/usasmartgamers/src/wp-content/plugins/usasmartgamers-core
 └─ igaming-db-1 (shared MySQL 8) ── NEW database `usasmartgamers` + user with rights on that DB only
 Nightly: /root/backup-usasmartgamers.sh (DB + uploads, 03:45) ; DO droplet backups recommended
```

## 3. Repository layout

```
usasmartgamers/
├─ .github/workflows/
│  ├─ ci.yml                 # PRs + pushes: lint & build
│  └─ deploy.yml             # main: build → rsync → purge CF → smoke test
├─ docker-compose.yml        # local dev: wordpress + mysql + phpmyadmin, binds theme/plugin
├─ docker-compose.prod.yml   # reference copy of the server compose (no secrets)
├─ .env.example
├─ package.json              # @wordpress/scripts build for blocks + theme assets
├─ composer.json             # phpcs + WordPress coding standards (dev only)
├─ wp-content/
│  ├─ themes/usasmartgamers/
│  │  ├─ style.css, functions.php, theme.json (design tokens)
│  │  ├─ header.php, footer.php, front-page.php, page.php, page-templates/evergreen.php
│  │  ├─ single.php (news), single-usg_slot.php, single-usg_operator.php (if review is a CPT),
│  │  ├─ archive.php, category.php, author.php, search.php, 404.php
│  │  ├─ template-parts/ (mega-nav, byline, breadcrumbs, sticky-toc, sticky-cta, author-box, post-card…)
│  │  └─ assets/src/{scss,js} → assets/build (gitignored, built in CI)
│  └─ plugins/usasmartgamers-core/
│     ├─ usasmartgamers-core.php
│     ├─ includes/ (cpt.php, taxonomies.php, meta.php, admin/, redirects.php, geo.php,
│     │             schema.php, rest.php, cron.php, settings.php)
│     ├─ blocks/ (toplist, cta-card, bonus-card, review-summary, pros-cons, faq, expert-insight,
│     │           how-we-test, state-map, game-grid, news-feed, team-grid, calculator-*, …)
│     └─ tools/seed/ (WP-CLI seed scripts: states, providers, demo operators)
├─ docs/ (this plan, blueprint, runbooks)
└─ skill.md
```

## 4. Data model (in `usasmartgamers-core`)

| Object | Type | Key fields |
|---|---|---|
| **Operator** | CPT `usg_operator` (not public; data source) | name, logo (light/dark), brand colour, vertical (casino/sweeps/sportsbook/prediction/lottery/poker), license/regulators, states available, payout speed, RTP/win rate, min deposit, payment methods, app store ratings, pros/cons, overall score + sub-scores (with one-line justifications), "suits players who…", review page link |
| **Offer** | CPT `usg_offer` (linked to Operator) | headline ("Up to 1,000 Bonus Spins"), short CTA label, bullets, promo code / "no code", full T&Cs + RG text, states/countries where valid, affiliate URL per geo, start/end dates, exclusive flag |
| **Toplist** | CPT `usg_toplist` | ordered list of operators, default offer per item, highlight pill per item, **geo rules** (US state → alternative order/offers; non-US fallback list), skin (tabbed / cards / table) |
| **Slot** | CPT `usg_slot`, URL `/slots/{provider}/{slot}/` | RTP, volatility, max win, bet range, paylines, reels, features (taxonomy), demo URL/embed, release date, series, rating |
| **Provider** | taxonomy `usg_provider` (with term meta: logo, description) → `/slots/{provider}/` | |
| **US State** | taxonomy `usg_state` | legal status per vertical (legal / pending bill / not likely), regulator, launch date, helpline, bill-tracker notes |
| **Payment method** | taxonomy `usg_payment` | logo, typical speed, fees |
| **News** | core `post` + categories (legislation, financial, industry, promos, per-state) | URL `/news/{slug}/` |
| **Blog** | CPT `usg_blog` → `/press-play/`-style section (name TBD) | |
| **Evergreen pages** | core `page` with `evergreen` template | hubs, reviews, state pages, guides — built from blocks |
| **Authors** | users + user meta | job title, credentials, bio, socials, expertise, fun facts, Q&A, avatar; per-content **fact-checker** + "last updated" meta |
| **Click log** | custom table `wp_usg_clicks` | time, operator, offer, placement tag, page, geo — for reporting |

Field UI: **ACF Pro** (≈$49/yr) is the fastest way to build these admin screens (repeaters for sub-scores,
toplist items, geo rules). Free fallback: Carbon Fields or hand-rolled meta boxes (+30–40% effort).

## 5. Plugin stack

| Need | Choice | Cost |
|---|---|---|
| SEO, sitemaps, breadcrumbs, base schema | Yoast SEO (same as PlayUSA) | Free (News SEO add-on optional, or we emit a news sitemap ourselves) |
| Tables | TablePress | Free |
| User reviews + sub-ratings | Site Reviews (same as PlayUSA) | Free |
| Custom fields | ACF Pro (see §4) | ~$49/yr |
| Accounts (register/login/profile) | User Registration (free) — Pro only if we need multi-part/conditional forms | Free → paid optional |
| Gamification (coins, tasks, streaks, redemptions) | GamiPress core + our glue code | Free core |
| Page cache | WP Super Cache (simple, bypasses logged-in users) + Cloudflare CDN for static assets | Free |
| Security | Cloudflare WAF/Bot Fight + Limit Login Attempts Reloaded + WP 2FA; no heavy scanners (RAM) | Free |
| Forms | Contact Form 7 or WPForms Lite | Free |
| SMTP (account emails) | WP Mail SMTP + transactional provider (Brevo/Mailgun free tier) | Free tier |
| Analytics | GA4 via GTM (consent-aware) | Free |
| Slot demos | Start: manual provider demo iframe URL per slot. Later: SlotsLaunch-type API | Free → paid |

Everything else (toplists, CTA cards, affiliate redirects, geo, schema, calculators, finder, bill tracker,
sticky TOC/CTA, mega-nav) is **our code**.

## 6. Key feature designs

### 6.1 Affiliate redirects `/claim/{operator}/`
Rewrite rule → resolves Operator + Offer by visitor geo → logs click → `302` to affiliate URL (with sub-ID
`placement-page`). Headers `X-Robots-Tag: noindex, nofollow`, `Cache-Control: no-store`; path disallowed in
robots.txt; links rendered with `rel="nofollow sponsored noopener"`.

### 6.2 Geo-targeting
- Cloudflare → *Rules → Transform Rules → Managed Transforms → "Add visitor location headers"* →
  origin receives `CF-IPCountry` + `CF-Region-Code` (e.g. `US` + `NJ`).
- Pages are rendered and cached with the **default national list**. A tiny JS calls
  `GET /wp-json/usg/v1/toplist/{id}` (uncached, reads CF headers) and swaps in state-specific items.
- Manual **state picker** (cookie) overrides auto-detection; non-US visitors get a fallback list or a
  "not available in your location" note.
- `/claim/` always re-checks geo server-side so the visitor lands on a legal offer.

### 6.3 Freshness automation
Title/H1 tokens `[month]`, `[year]` resolved at render; WP-cron job sets "Last updated" on offer changes;
expired offers auto-hide from toplists (end date).

### 6.4 Schema
Yoast base graph + our additions per template: Review/Product/AggregateRating/Offer (operator review),
VideoGame + AggregateRating (slot), NewsArticle (news), BlogPosting (blog), FAQPage (FAQ block),
ProfilePage/Person (author).

### 6.5 Compliance (must ship with MVP)
21+ notice, 1-800-GAMBLER, state helplines, operator T&Cs rendered under every offer, affiliate disclosure
(footer + how-we-test), RG page, privacy policy, cookie consent banner, only operators licensed in the
visitor's state for real-money verticals. Legal review of sweepstakes coverage per state.

## 7. CI/CD pipeline (GitHub → server)

**`ci.yml`** (every push & PR): checkout → `php -l` on all PHP → phpcs (WordPress standard, warnings only
at first) → `npm ci && npm run build` → upload build artifact.

**`deploy.yml`** (push to `main` or manual `workflow_dispatch`, GitHub *Environment* `production`, optional
required reviewer):
1. Build (same as CI).
2. `rsync -az --delete` the built theme and plugin to
   `/root/usasmartgamers/src/wp-content/{themes/usasmartgamers,plugins/usasmartgamers-core}/`
   (bind-mounted → live instantly, no container restart).
3. Purge Cloudflare cache via API token (zone-scoped "Cache Purge" permission only).
4. Smoke test: `https://<domain>/?nc=<sha>` returns 200 and contains a build marker; `/wp-login.php` 200.
5. On failure → job fails, Actions notifies; rollback = `git revert` + push (redeploys previous code).

**Security of the deploy path**
- A **new dedicated SSH key** for CI (never the admin `hetzner_ed25519` key). Its `authorized_keys` line is
  locked down: `command="rrsync /root/usasmartgamers/src",restrict ssh-ed25519 …` — the key can only rsync
  inside that folder; no shell, no access to other apps.
- GitHub secrets: `DEPLOY_SSH_KEY`, `DEPLOY_HOST`, `DEPLOY_KNOWN_HOSTS`, `CF_API_TOKEN`, `CF_ZONE_ID`, `SITE_URL`.
- No `.env`, DB dumps or uploads ever in git (`.gitignore`).

**Content/DB changes** (new CPT fields, options, seed data) ship as idempotent code: plugin activation/upgrade
routines keyed on a `usg_db_version` option, or WP-CLI seed scripts run manually via the skill's CLI pattern.

## 8. Phased roadmap

Each phase ends with something deployed and verifiable on the live domain.

| Phase | Scope | Done when |
|---|---|---|
| **0. Foundations** | Repo scaffold (theme + plugin skeletons, local docker-compose, package.json, phpcs); server provisioning per skill (DB, container :8082, backups, cron); Cloudflare DNS + Origin Rule + geo headers; WordPress install; CI/CD pipeline live | Pushing a change to `main` updates the live placeholder theme automatically; OntarioGamers & ZuriStay unaffected |
| **1. Design system & chrome** | `theme.json` tokens (our palette/fonts), header + 3-level mega-nav + mobile menu, footer (disclosure, columns, RG line, socials), breadcrumbs, byline (author/fact-checker/updated), sticky section TOC, sticky footer CTA, back-to-top, author box, 404/search | Any page looks like a finished site shell on mobile & desktop; Lighthouse mobile ≥ 85 |
| **2. Data model & admin** | CPTs/taxonomies/fields from §4, admin columns, author profile fields, seed scripts (50 states + legal status, providers, payment methods) | Editors can create operators/offers/toplists/slots in wp-admin |
| **3. Monetisation core** | Toplist block (tabbed, cards, table skins), CTA card, bonus card, promo-code copy, `/claim/` redirect + click log + admin report, geo-targeting + state picker | A toplist renders state-correct offers for a NJ vs TX vs non-US visitor; clicks are logged |
| **4. Templates** | Home, evergreen hub, operator review (summary box, grades, sections), state hub, state promo page, sweepstakes review, slot single + provider archive, news landing + single, category, blog, author, author hub | One fully-built example of every template with real content |
| **5. Content blocks & SEO** | FAQ (+schema), pros/cons, expert insight, how-we-test, comparison tables, US state map, game grid, news feed, team grid, as-seen-on, CTA banner, tabs, read-more; schema per §6.4; Yoast config, sitemaps, robots, Google Search Console, GA4/GTM, cookie consent | Rich-results test passes for review/FAQ/slot/news pages |
| **6. Content load & launch (MVP)** | Legal pages, RG, about/editorial/how-we-rate, initial content set (e.g. 3 hubs, 10 operator reviews, 5 state pages, 30 slots, 10 news); performance pass (page cache, image sizes/WebP, critical CSS); security hardening; backup restore drill | **Public launch** — checklist in §9 passes |
| **7. Tools** | Bonus/wagering, odds, implied probability, hedge, Martingale calculators; tools hub; Casino Finder (REST + JS filter); Bill Tracker (state tables + map driven by `usg_state` data) | Tools work without page reload and are linked from hubs |
| **8. Community & gamification** | Accounts (register/login/profile), user reviews with sub-ratings + moderation, GamiPress coins/tasks/streaks/referrals, rewards catalogue + redemption workflow, notifications bell, logged-in cache bypass | A user can register, rate a casino, earn coins, and request a reward |
| **9. Scale** | Slot demo API integration, internal-link automation, news workflow (editorial roles, scheduled posts, Google News), more verticals (sports, prediction markets, lottery), A/B tests on CTAs | Ongoing |

Phases 0–6 = MVP. Phase 8 is the heaviest on server resources — **resize the droplet to 4 GB before
enabling it** (see §10).

## 9. Launch checklist (from the skill + site-specific)

- [ ] `https://<domain>/` 200, shows our site (not OntarioGamers), no redirect loops on `/wp-admin/`
- [ ] OntarioGamers and `api.zuristay.com` still healthy
- [ ] Container under its memory cap; host available RAM > 400 MB under load test
- [ ] Nightly backup ran, restore tested once
- [ ] CI/CD deploy + rollback tested
- [ ] Geo: NJ, PA, TX, non-US produce correct toplists and `/claim/` targets
- [ ] All offers show T&Cs + 21+ + 1-800-GAMBLER; affiliate disclosure visible
- [ ] Sitemaps submitted, robots.txt correct, `/claim/` noindex, schema validated
- [ ] Cookie consent + privacy policy live; GA4 receiving data
- [ ] Admin password rotated, 2FA on for all admins, Limit Login Attempts active

## 10. Risks & mitigations

| Risk | Mitigation |
|---|---|
| Shared 1 vCPU / 2 GB droplet (≈1.2 GB free today) | `mem_limit` on our container, page cache + Cloudflare, lean plugins, no second MySQL; resize to 4 GB (~$24/mo) before Phase 8 or if RAM < 300 MB sustained |
| Shared MySQL & `igaming_default` network | Our DB user has rights only on our DB; never run `down` on OntarioGamers (removes the network) |
| Copyright / brand confusion | We replicate structure & features only; all text, images, logos, reviews are original |
| Gambling-advertising law (state rules, sweepstakes scrutiny) | Only licensed operators per state, geo-gated offers, RG messaging, legal review of sweepstakes pages |
| Affiliate programme approval | Apply to programmes early (Phase 0–2) so live links exist at launch |
| Geo + caching complexity | Cached default + uncached REST swap; `/claim/` re-checks geo |
| Single server = single point of failure | Nightly DB/uploads backups + DigitalOcean droplet backups (~$2.40/mo) |

## 11. Open decisions (need answers before Phase 0)

1. Exact domain + TLD (e.g. `usasmartgamers.com`) and whether it is (or can be moved) on **Cloudflare**.
2. WordPress admin username + email.
3. Brand: logo, colour palette (keep PlayUSA-like indigo/red or our own), font.
4. Verticals for MVP (recommended: online casinos + sweepstakes + slots + news).
5. ACF Pro purchase (recommended) vs free fallback.
6. Which affiliate programmes / operators we already have deals with.
7. Accounts + Play-Perks-style gamification: MVP or Phase 8 (recommended: Phase 8).
8. Approve resizing the droplet to 4 GB (now, or before Phase 8).
