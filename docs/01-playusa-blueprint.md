# PlayUSA.com — Reverse-Engineered Blueprint

> Research date: 2026-10-03. Source: live crawl of https://www.playusa.com (homepage, all 12 sitemaps,
> 34 representative pages, main CSS bundle, mobile screenshots in [`reference-screens/`](./reference-screens)).
> Purpose: the **blueprint** for USA Smart Gamers. We copy *features, structure and UX patterns* —
> never their text, logos, images, reviews or brand assets. All content/data on our site is our own.

---

## 1. What the site is

An **affiliate media site for the US gambling market** (owned by Catena Media). It earns money when
readers click a tracked "Claim Offer" link and sign up at a licensed operator. Everything on the site
exists to (a) rank in Google for high-intent gambling keywords, (b) build trust (E-E-A-T: named experts,
"fact-checked", "how we test"), and (c) push the reader to a geo-appropriate offer.

Verticals covered:

| Vertical | Hub URL | Notes |
|---|---|---|
| Real-money online casinos | `/online-casinos/` | Only legal in ~7 states → heavy state pages |
| Sweepstakes / social casinos | `/sweepstakes-casinos/`, `/social-casinos/` | Legal in most states → biggest catalog (147 pages, 35+ brand reviews) |
| Slots (reviews + free demos) | `/slots/` | 576 slot pages, 53 provider pages |
| Table games guides | `/blackjack/`, `/roulette/`, `/craps/`, `/online-poker/`, `/video-poker/`, `/casino-games/` | Evergreen guides + toplists |
| Sports betting | `/sports-betting/`, `/missouri/...`, `/how-to-bet/` | Smaller section |
| Prediction markets | `/prediction-markets/` | New vertical (Kalshi etc.) |
| Online lottery, bingo, parimutuel | `/online-lottery/`, `/online-bingo/`, `/parimutuel-betting/` | Niche |
| Payments | `/payments/`, `/payments/paypal/` … | "X casinos" pages per method |
| News | `/news/` | ~4,800 posts, Google News publisher |
| Blog / lifestyle | `/press-play/` | 216 posts (separate CPT) |
| Tools | `/tools/…` | 5 calculators |
| Legislation | `/casino-bill-tracker/` | State-by-state status tables + map |
| Rewards program | `/play-perks/` | Coins, tasks, redeem gift cards / offers |

## 2. Technology / architecture (observed)

| Layer | What PlayUSA uses | Evidence |
|---|---|---|
| CMS | **WordPress** (Gutenberg block editor) | `wp-content`, `wp-block-*` classes, `/wp-json/` |
| Theme | **`catena-unicorn`** — custom in-house theme shared across Catena sites (`site-playusa` body class = per-site skin) | asset paths |
| Page templates | `page-template-evergreen-template` (hubs/reviews), default single post, `single-feed-slots`, `single-catena-blog`, author & category archives | body classes |
| Custom post types | `post` (news), `page` (evergreen), `feed-slots` (slot games), `feed-providers` (slot studios), `catena-blog` (Press Play) | sitemaps |
| SEO | **Yoast SEO** (+ Yoast News SEO): sitemaps, breadcrumbs, schema graph | `sitemap_index.xml`, `news-sitemap.xml`, `breadcrumb_last` |
| Tables | **TablePress** (52 tables on bill tracker page alone) | `tablepress-id-*` |
| User reviews / star ratings | **Site Reviews** (`glsr-*`) with sub-ratings | review pages |
| Accounts | **User Registration Pro** (+ advanced fields, conditional logic, multi-part, content restriction) | plugin assets |
| Gamification | **GamiPress** wrapped in custom "LevelUp" module: coins, tasks, streaks, referral, notifications bell | `levelup/assets/gamipress.js`, `referral.js`, `gamification-header.js` |
| Slot demos | **SlotsLaunch** API ("Loading demo from SlotsLaunch…") | slot page |
| Affiliate links | Cloaked `/claim/<operator>/?cid=…&tag=<placement>&aid=…&geo=<CC-REGION>` with `rel="nofollow noindex sponsored noopener"`; also `/visit/`, `/recommends/` (all disallowed in robots.txt) | homepage links |
| Geo-targeting | Server-side: toplists change by visitor location (we saw `geo=KE-*` from Kenya → international brands; US visitors get state-legal brands) | link params |
| Internal linking | `nexus.linkengine.io` (automated internal-link engine) | script |
| Performance | Autoptimize (combined CSS), nginx-helper cache purge, lazy images | asset paths, robots.txt |
| Analytics | GTM + GA4 | `GTM-`, `G-PX8K4QCJY7` |
| JS | jQuery + small vanilla modules (mega-nav, collapse, sticky footer, read-more, notifications) | scripts |

## 3. Information architecture & URL scheme

Content volume (from sitemaps): **616 pages, ~4,814 news posts, 576 slots, 53 providers, 216 blog posts,
51 authors, 23 news categories.**

```
/                                       Home (evergreen template)
/online-casinos/                        Vertical hub (toplist + long guide + FAQ)
/online-casinos/{brand}/                Operator review (betmgm, fanduel, …)
/online-casinos/{state}/                State hub (nj, pa, michigan, west-virginia, + "not legal yet" states)
/online-casinos/{state}/bonus-codes/    State promo page
/online-casinos/{bonus-type}/           bonus, no-deposit, free-spins, fastest-paying, best-payouts, minimum-deposit-casinos
/sweepstakes-casinos/  …/{brand}/  …/new/  …/no-deposit/  …/apps/
/slots/  /slots/free/  /slots/new/  /slots/a-z/  /slots/megaways/
/slots/{provider}/                      Provider page (CPT feed-providers)
/slots/{provider}/{slot}/               Slot review + demo (CPT feed-slots)
/payments/  /payments/{method}/
/blackjack/ /roulette/ /craps/ /online-poker/ /video-poker/ /casino-games/  (+ sub-guides)
/sports-betting/  /how-to-bet/{league}/  /{state}/sports-betting/
/prediction-markets/  /online-lottery/  /parimutuel-betting/
/news/                                  News landing (sections per category/state)
/news/{slug}/                           News article
/{category}/                            Category archive (legislation, financial, alabama, …)
/press-play/  /press-play/{slug}/       Blog CPT
/author/{name}/                         Author profile
/author-hub/                            Team page
/tools/  /tools/{calculator}/
/casino-bill-tracker/  /casino-finder/  /learn-hub/  /play-perks/  /exclusive-offers/  /profile/
/about/ (review-process, editorial-guidelines, media, career)  /responsible-gambling/  /disclaimer/
/privacy-policy/  /contact-us/  /taxes/  /revenue/  /tribal-casinos/
/claim/{brand}/  /visit/  /recommends/   (affiliate redirects — noindex, robots-disallowed)
```

## 4. Global design system

| Token | Value |
|---|---|
| Font | **DM Sans** (all text), monospace Fira Code for code |
| Brand primary / nav / surfaces-primary | `#3A3984` (indigo) |
| Surfaces secondary (hero gradient end) | `#29285E` |
| Footer | `#181837` (near-black navy) |
| CTA primary ("CLAIM OFFER") | `#B22435` (crimson) |
| CTA alt (in-review "CLAIM BONUS!") | `#1BB56E` green with dark 3-D bottom border `#024126` |
| Text primary / secondary | `#0F0E0D` / `#494745` |
| Links / hover | `#3568D4` / `#2A4EB8` |
| Borders / inactive | `#E8E8E8` |
| Stars / highlights | `#FFC107` / `#FFD04A` |
| Radii | 8px cards, 4px small, 999px pill buttons |
| Breakpoints | ~480 / 768 / 1024 / 1200 / 1440 px |
| Style | Clean white content area, indigo header, hero with indigo→navy gradient, rounded cards with thin grey borders, bold uppercase CTA buttons |

Reference screenshots (mobile width): [`home-top`](./reference-screens/home-top.png),
[`casinos-top`](./reference-screens/casinos-top.png), [`review-top`](./reference-screens/review-top.png),
[`state_toplist`](./reference-screens/state_toplist.png), [`review_user_reviews_and_section`](./reference-screens/review_user_reviews_and_section.png),
[`sticky_footer_cta`](./reference-screens/sticky_footer_cta.png), [`slot-top`](./reference-screens/slot-top.png),
[`perks-top`](./reference-screens/perks-top.png), [`news-top`](./reference-screens/news-top.png), [`calc-top`](./reference-screens/calc-top.png).

## 5. Global chrome (every page)

1. **Header** — indigo bar: logo left; notifications bell with unread badge (gamification); hamburger/
   account; **mega-nav** with 3 levels (columns, small brand logos per item, "Trending/Hot/New" pills).
   Top-level groups: Online Casinos (brands / states / bonuses / payments), Sweepstakes (≈35 brands),
   Slots (lists, top titles, Megaways), Casino Games, Parimutuel, News & Resources (news, blog, bill tracker,
   learn hub, tools), Sports Betting.
2. **Sticky in-page section nav** (horizontal scroller under header) built from the page's H2s
   ("Our Verdict", "Bonus Code", "Games", "Payments" …) — acts as a table of contents.
3. **Breadcrumbs** (Yoast) above H1.
4. **Byline**: avatar, "Written by {author}", "Last Updated {date}", ✓ "Fact-checked (by {editor})".
5. **Sticky footer CTA bar** on money pages: "Up to 1,000 Bonus Spins — CLAIM 100 BONUS SPINS ↗".
6. **Back-to-top** button.
7. **Footer** (navy): affiliate disclosure paragraph; link columns (US State Gambling Guides, Popular Pages,
   About Us); search box; RG line "Bet with your head… Call 1-800-GAMBLER… 21+"; copyright; social icons
   (FB, X, IG, TikTok, YouTube, Reddit, Google News).
8. **Author box** at the bottom of evergreen pages (bio, socials, expertise categories, "view all").

## 6. Reusable content components (Gutenberg blocks / shortcodes)

| Component | What it shows | Used on |
|---|---|---|
| **Toplist / CTA list (tabbed v3)** | Rank #, logo, brand, ★ rating, bonus headline, description, highlight pill, CTA; tabs (e.g. Casinos / Sweeps / Prediction) | Home, hubs |
| **CTA card (state/list skin)** | Logo tile + bonus headline header, "{Brand} Review" link, ★ x/5, bullet list, **full T&C/RG text**, dashed **promo-code copy box** ("Copy promo code USASLOTS" / "No code needed"), red **CLAIM OFFER** | State pages, bonus pages, guides |
| **Bonus card** | Single highlighted offer, promo code, claim button | Inline in articles |
| **Review summary box** | Overall Editorial Score (4.6), key facts (payout speed, win rate/RTP, methods), 6 sub-grades with one-line justification, "trusted & legal in NJ, PA, MI, WV", "will suit players who…", claim + code | Operator reviews |
| **Review section headers** | "BetMGM Casino games \| 5 / 5" + green 3-D CTA | Operator reviews |
| **Expert insight** | Quote box from a named expert | Everywhere |
| **Pros / Cons box** | Two-column list | Reviews, guides |
| **FAQ accordion** (+ FAQPage schema) | Q/A collapsible, 1 or 2 columns | Almost all pages |
| **"How we test" block** | Methodology + affiliate disclosure | Home, hubs |
| **Comparison tables** | TablePress responsive tables | Hubs, tracker |
| **State availability map** | Clickable US map coloured by legal status | Reviews, sweeps hub, tracker |
| **Game cards / slot navigator** | Slot tile, RTP, volatility, "Play demo" / "Play for real" | Home, slots, bonus pages |
| **Post cards / filtered news** | Latest news filtered by category/state | Home, hubs, news page |
| **Team grid / author cards** | Avatar, name, title, socials | Home, author-hub |
| **"As seen on" logos** | Press mentions | Home |
| **CTA banner** | Promo banner for Play Perks / sign-up | Many |
| **Tabs** | Generic tabbed content | Provider, state pages |
| **User reviews widget** | Site Reviews: list, sub-rating bars, filter, "write your own", pagination; incentivised with coins | Reviews, slots |
| **Plain-text read-more** | Collapses long intros | Hubs |

## 7. Page templates in detail

### 7.1 Homepage
Hero (gradient, H1, intro, horizontal pill buttons to verticals + scroll indicator) → tabbed top-rated toplist
→ Play Perks promo (3 steps: complete tasks / collect coins / redeem) → "Sharing objective reviews" (3 vertical
teasers) → How we test + affiliate disclosure → "Why us" (3 USPs) → resources grid → popular slot demos →
"Keeping you safe" (legal-only, RG) → latest 4 news → editorial guidelines / "trusted by news agencies" →
As Seen On → Meet our team.

### 7.2 Vertical hub (e.g. `/online-casinos/`, ~9k words)
Sticky TOC → H1 + byline → intro → toplist (top 10) → how we rank → how we test → full list table →
latest updates (news feed) → how to sign up → legal states (map/table) → next states → alternatives →
RG → offer types → bonus terms → evaluating bonuses → games → RTP table → payment methods → legal vs offshore → FAQ.

### 7.3 Operator review (e.g. `/online-casinos/betmgm/`, ~7k words) — the money page
Sticky TOC → H1 "My honest {brand} review & bonus code" + byline (writer + fact-checker) → **review summary box**
→ verdict → grades → evidence of testing (screenshots) → **user reviews** ("write your own" → coins) →
one section per graded criterion with score in H2 + CTA → availability (state map) → legit & safe → what
players say (app store ratings) → vs competition table → FAQ → final thoughts. Schema: Product + Review +
AggregateRating + Offer + Brand + FAQPage + Article.

### 7.4 State hub (`/online-casinos/nj/`) & state promo page (`/nj/bonus-codes/`)
Toplist of CTA cards for brands legal in that state → how we rate → editor's picks (tabs) → legality →
how to start → full list → app-store ratings table → geolocation troubleshooting → state news → RG (state
helplines) → FAQ → related links. Promo page adds **wagering calculator** embed and "how to cash out".

### 7.5 Sweepstakes review (`/sweepstakes-casinos/chumba/`)
Same skeleton as operator review, criteria adapted (promo code, prize likelihood, existing-player promos,
games, app, support), pros/cons, comparison, legality.

### 7.6 Slot page (CPT, `/slots/{provider}/{slot}/`)
Hero: breadcrumbs, title, **live demo player** (lazy-loaded iframe, "Play for real" + "refresh credits"),
trust badges, feature chips (free spins, wilds…), side CTAs for 3 operators → spec box (RTP, volatility, max
win, bet range, paylines, reels) + ★ rating with count + "Rate it" → "Earn coins every time you play" → popular
slots carousel → byline → user reviews with sub-ratings (bonus features, payout potential, graphics, volatility)
→ where to play → highlights → how to play → similar slots → bonus rounds → jackpot → series → final thoughts →
more from provider. Schema: VideoGame + AggregateRating.

### 7.7 Provider page (`/slots/igt/`)
Free-play tabs → most popular games grid → studio info tables → game types → where to play → similar studios.

### 7.8 News
Landing: sections per category (Legislation, Commercial Gaming, Financial, Industry, Promos) and per state.
Article: H1, byline with read-time + share buttons, body, author. Schema NewsArticle; Google News sitemap.

### 7.9 Other templates
Blog (Press Play) single = BlogPosting; Author profile (bio, badges, socials, posts grid, "Getting to know",
fun facts, Q&A accordion; ProfilePage schema); Author hub (team stats + cards + editorial policy); Category
archive (post list + pagination); Learn hub (guides grouped by game); Tools hub + calculators; Bill tracker
(watchlist, latest developments, legalization map, 40+ state tables grouped by status); Casino Finder (JS app:
search/filter 156 casinos); About/editorial/media/careers; Contact (form + FAQ); Responsible gambling.

## 8. Interactive features

| Feature | Behaviour |
|---|---|
| Geo-targeted toplists | Operator list/links depend on visitor country + US state |
| Promo-code copy | Click-to-copy with feedback |
| Affiliate redirect + tracking | `/claim/{brand}` with campaign/placement/geo params → 302 to operator |
| Casino bonus / wagering calculator | Deposit or no-deposit mode → playthrough & value |
| Odds, implied probability, hedge, Martingale calculators | Pure JS tools |
| Casino Finder | Client-side search/filter over operator database |
| Slot demos | Lazy iframe from demo provider |
| User reviews & ratings | Logged-in users rate casinos/slots (moderated) |
| Accounts | Register / login / profile, multi-part form, pending-registration notice |
| Play Perks gamification | Coins for: register 500, complete profile 500, daily login 25, 7-day streak 200, review 150 (3/day), rating 50 (3/day), slot demo 25 (3/day), referral 500; redeem for gift cards / exclusive offers; notifications bell |
| News filtering | Category/state filters on news landing |
| Mega-nav, sticky TOC, sticky footer CTA, read-more, accordions, tabs | Small JS modules |

## 9. SEO, trust & compliance patterns

- Titles always carry **month/year** ("for October 2026") — updated automatically.
- Every money page: named author + fact-checker + last-updated date; author pages with credentials.
- Schema graph on every page (WebSite + SearchAction, Organization, BreadcrumbList, Person) plus per-template types.
- XML sitemaps per type, Google News sitemap, robots blocking redirect paths and `wp-json`.
- Affiliate links `rel="nofollow sponsored noopener"`; affiliate disclosure in footer and in "How we test".
- **Responsible gambling**: 21+ statements, 1-800-GAMBLER, per-operator T&Cs printed under every offer, RG
  page, state helplines; "only legal/regulated operators" positioning.
- Hub ↔ review ↔ state ↔ news dense internal linking (and an automated link engine).
