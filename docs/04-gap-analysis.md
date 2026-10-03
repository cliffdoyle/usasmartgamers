# Gap analysis vs PlayUSA.com + user-data report

Checked live on 2026-10-03 against PlayUSA's sitemaps (616 pages, 23 news categories) and our live site.

## 1. Section-by-section: what PlayUSA has that we don't (yet)

Legend: ✅ built · 🟡 template/structure built, content/sub-pages missing · ❌ not built

### Verticals (whole sections)
| PlayUSA section | Their pages | Us | Notes |
|---|---|---|---|
| `/online-casinos/` | 142 | 🟡 hub + NJ/PA/MI + bonus page | Missing: ~13 brand reviews, ~22 more state pages (incl. non-legal states CA, TX, FL, NY… which rank well), state bonus-code pages (`/nj/bonus-codes/`), 10 bonus sub-pages (no-deposit, free-spins, wagering-requirements…), casino-rewards (7), apps (best-apps, iphone, android, mobile), live-dealer, new casinos, fastest-paying, best-payouts, minimum-deposit, customer-support, complaints, sign-up-process, terms-conditions, gambling-age, wire-act, unregulated/offshore |
| `/sweepstakes-casinos/` | 147 | 🟡 hub | Missing: ~100 brand reviews, 18 "sites like X" comparisons, redemptions, coins explained, laws, apps, new, no-deposit, fish-table & scratch games |
| `/slots/` | 82 + 576 slots + 53 providers | 🟡 hub + slot & provider templates | Missing: content, plus free, new, A–Z index, megaways, how-they-work, are-slots-rigged, progressive, bonus-buy, high-limit, tournaments, Atlantic City slots |
| `/sports-betting/` + `/how-to-bet/` + `/missouri/` | 11 | 🟡 hub | Missing: bonus page, league guides (NFL, college basketball, World Cup), state sportsbook pages (they currently push Missouri) |
| `/payments/` | 19 | 🟡 hub (uses finder) | Missing: 18 method pages (PayPal, Venmo, Play+, Skrill, Apple Pay, PayNearMe, cash at cage, bitcoin…) — our `usg_payment` taxonomy already has a "guide page" field for them |
| `/online-poker/` | 21 | ❌ | Brand + state pages (WSOP, BetMGM, PokerStars, GGPoker…) |
| `/online-lottery/` | 29 | ❌ | State lottery pages, Powerball, Mega Millions, Jackpocket, scratch-offs |
| `/prediction-markets/` | 19 | ❌ | Kalshi, Polymarket, PredictIt, Crypto.com, PrizePicks, Underdog… (our "prediction market" vertical exists in the data model) |
| `/blackjack/` `/roulette/` `/craps/` `/video-poker/` `/casino-games/` | 63 | ❌ | Rules, strategy, odds, free games, live dealer, variants (baccarat, pai gow, 3-card poker…) — evergreen SEO traffic |
| `/social-casinos/` | 2 | ❌ | Separate from sweepstakes |
| `/online-bingo/` | 1 | ❌ | |
| `/parimutuel-betting/` + `/kentucky-derby/` + `/preakness-stakes/` | 5 | ❌ | Horse-racing / historical-racing games |
| `/taxes/` `/revenue/` `/tribal-casinos/` | 3 | ❌ | Linked from their footer |
| `/exclusive-offers/` | 1 | ❌ | Members-only offers (part of Play Perks) |
| `/play-perks/` (rewards) | 39 | ✅ page · ❌ help centre | They have 33 support articles + rules/terms (4) + shop |
| `/responsible-gambling/` | 8 | 🟡 1 page | Missing: help a loved one, identify a problem, self-exclusion, services & support, tips, tools |
| `/about/` | 5 | 🟡 about, how-we-rate, editorial | Missing: careers, media/press |
| `/news/`, `/press-play/` (blog), `/learn-hub/`, `/tools/` (5), `/casino-finder/`, `/casino-bill-tracker/`, `/author-hub/`, `/profile/`, `/contact-us/`, `/disclaimer/`, `/privacy-policy/`, terms | — | ✅ | Equivalent pages built (`/insights/` = Press Play, `/our-team/` = author hub, `/account/` = profile, `/rewards/` = Play Perks). Our 5 calculators match theirs exactly. |

### News categories
| PlayUSA (23) | Ours (7) |
|---|---|
| legislation, industry, promo, commercial-gaming, federal, financial, las-vegas, mergers-and-acquisitions, tribal-gaming, sportsbetting, views (opinion), casino-cat, + states: AL, CA, FL, IL, MD, MN, MO, NV, NY, TX, VA | legislation, industry, promotions, guides, + NJ, PA, MI |

Missing categories: **commercial gaming, federal, financial, Las Vegas, mergers & acquisitions, tribal gaming, sports betting, opinion/views**, and state categories for **AL, CA, FL, IL, MD, MN, MO, NV, NY, TX, VA**. (Adding a category is 1 minute in Posts → Categories.)

### Features PlayUSA has that we haven't built
| Feature | Effort | Recommendation |
|---|---|---|
| Live slot demos via a demo provider (SlotsLaunch) | M + paid API | Our slot template already has a demo-iframe field; add when you sign a provider |
| Bot protection on forms (Cloudflare Turnstile) | S | **Add** — free; protects join/contact forms (we currently use honeypot + rate limits) |
| Block registration outside the US (they redirect non-US visitors to `#restricted`) | S | **Add** — reduces fraud on rewards |
| Rewards help centre (33 articles) + rewards T&Cs | Content | Write before enabling rewards publicly |
| Multi-step registration with extra fields | M | Only if needed for KYC / lead-gen (see §2) |
| "As seen on" press logos, newsletter/marketing opt-in | S | Later |
| Automated internal-link engine | M | Later (Phase 9) |
| Google News publisher listing | Process | Our `/news-sitemap.xml` is ready |

### Things we built that PlayUSA does differently
- **Visible state picker** in the header (PlayUSA geo-targets silently). Added so visitors (and testers) can switch state; can be hidden if not wanted.
- **Analytics only after cookie consent** (PlayUSA loads Google Analytics immediately).
- **Contact messages stored in wp-admin** (no email server needed).

### Why the "Where are online casinos legal?" map
It **is** a PlayUSA feature: their bill tracker has an "Online casino legalization map" section, and a `state-map`
component appears on their operator reviews (e.g. BetMGM) and on the sweepstakes hub. Ours is the same idea as a
tile map, driven by **Operators & Offers → US States** (legal status per state), so updating a state's status updates
every map, table and the bill tracker at once. It answers the #1 user question ("can I play in my state?") and links
each state to its state page.

## 2. User data

### What PlayUSA collects (from its live site + Privacy Policy, effective 2 Apr 2026)
- **Data controller:** Catena Operations Ltd, Malta (Catena Media). Policy has a US section (incl. California rights) and an EEA/UK section.
- **Accounts:** User Registration Pro (multi-part form, conditional fields) for Play Perks; coins, tasks, referrals, reward redemptions (GamiPress).
- **Identifiers the policy says they may collect:** name, **government ID copy, SSN, date of birth**, phone, postal address (for promotions / lead-generation partners).
- **Marketing data:** campaign data, click-throughs, preferences, email/SMS/phone consent.
- **Location:** approximated from IP (their affiliate links carry `geo=` codes).
- **Tracking:** cookies & pixels, Google Analytics + GTM. Their GA events (seen live) send login status, gamification user ID, coin values, device memory/CPU cores, mouse-movement, keystroke and scroll statistics.
- **Sharing:** service providers, marketing partners, ad/audience modelling, business transfers.
- **Reviews:** Site Reviews plugin (name + review + ratings).
- **Non-US visitors** cannot register (redirected to `#restricted`).

### What our site collects and where it is stored
**Location:** DigitalOcean droplet in **Toronto, Canada (TOR1)** → MySQL database `usasmartgamers` on the shared
`igaming-db-1` container (data volume `igaming_db_data`). Traffic passes through **Cloudflare** (US/global edge).

| Data | Where (table) | Collected when |
|---|---|---|
| Username, email, **hashed** password, display/first name | `wp_users`, `wp_usermeta` | Join form |
| US state, favourite game, referral (who invited), terms-accepted timestamp | `wp_usermeta` (`_usg_*`) | Join / profile |
| Coin ledger (amount, action, note, date) and balance | `wp_usg_coins`, `wp_usermeta` | Rewards activity |
| Player reviews (text, star ratings, author name/email) | `wp_comments`, `wp_commentmeta` | Review form (moderated) |
| Reward redemptions (user, reward, cost, status) | `wp_posts` (type `usg_redemption`) | Redeem button |
| Contact messages (name, email, topic, message) | `wp_posts` (type `usg_message`, private) | Contact form |
| Affiliate clicks (operator, offer, placement, page path, state, user id) — **no IP address** | `wp_usg_clicks` | `/claim/` links |
| Failed-login IPs (security) | Limit Login Attempts tables/options | Failed logins |
| Rate-limit counters (hashed IP, 1-hour expiry) | `wp_options` transients | Join / contact forms |
| Cookies: `usg_state` (chosen state), `usg_consent`, WordPress login cookies; GA cookies only after consent | Visitor's browser | — |
| Nightly DB dumps (contain all of the above) | `/root/usasmartgamers-backups` on the **same droplet**, 14 days | 03:45 cron |

We do **not** collect date of birth, SSN, ID documents, phone or address, and we don't send marketing emails.

### If we go further (KYC / gift-card rewards / marketing)
1. **Before launch:** final Privacy Policy & Terms (lawyer), cookie banner live (set GTM ID), "delete my account / export my data" process (WordPress has Tools → Export/Erase Personal Data).
2. **Backups off the server:** copy nightly dumps (encrypted) to DigitalOcean Spaces or another provider, and enable DigitalOcean Droplet Backups.
3. **Restrict origin port 8082 to Cloudflare IPs** so visitor-IP headers can't be spoofed and the site can't be reached around Cloudflare.
4. **Transactional email (SMTP)** for verification/password resets — needed before collecting more.
5. If you need **SSN/ID/DOB** (KYC for cash-value rewards), **don't store it in WordPress**: use a KYC provider (e.g. Persona, Veriff) and keep only a pass/fail flag. That keeps the shared server out of scope for sensitive-data rules.
6. Marketing opt-in: separate, unticked consent checkbox; store consent timestamp; use an email platform (e.g. Brevo/Mailchimp) rather than WordPress.
7. Consider restricting registration to US visitors (PlayUSA does) and Cloudflare Turnstile on forms.

## 3. Fixes made during this check
- Header: on 1281–1439px screens the "Join" button was being pushed off-screen for logged-out visitors → compacted; hamburger menu now below 1280px.
- Login protection: Limit Login Attempts was seeing Cloudflare's IP instead of the visitor's (one attacker could have locked out everyone on that Cloudflare node) → now reads `CF-Connecting-IP`.
