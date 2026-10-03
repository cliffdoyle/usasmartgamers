# Operations runbook

## Live setup
| Item | Value |
|---|---|
| Site | https://usasmartgamers.com (Cloudflare → origin `143.110.219.173:8082`) |
| Container | `usasmartgamers-wordpress-1` (`/root/usasmartgamers`, `mem_limit: 384m`) |
| Database | `usasmartgamers` on shared `igaming-db-1`, user `usasmartgamers_user` (rights on this DB only); password in `/root/usasmartgamers/.env` on the server only |
| Code on server | `/root/usasmartgamers/src/wp-content/{themes/usasmartgamers,plugins/usasmartgamers-core}` (bind-mounted, live immediately) |
| Backups | `/root/backup-usasmartgamers.sh`, cron `45 3 * * *` → `/root/usasmartgamers-backups` (14 DB dumps, 5 uploads archives) |
| Search engines | `blog_public = 0` (discouraged) until launch — flip in Settings → Reading at launch |

## Deploy pipeline
- Push / PR on any branch → **CI** (`.github/workflows/ci.yml`): PHP lint (+ npm build once `package.json` exists).
- Push to `main` (or *Actions → Deploy → Run workflow*) → **Deploy** (`.github/workflows/deploy.yml`):
  CI → stamp `build-version.txt` with the commit SHA → rsync theme + plugin → purge Cloudflare (if secrets set)
  → smoke test `/?rest_route=/usg/v1/health` must report the new SHA.
- Rollback: `git revert <bad-commit> && git push` (redeploys the previous code).

### Secrets (GitHub → Settings → Secrets and variables → Actions)
| Secret | Purpose |
|---|---|
| `DEPLOY_SSH_KEY` | Private half of a dedicated deploy key. On the server it is pinned in `authorized_keys` to `command="/usr/bin/rrsync /root/usasmartgamers/src",restrict` — it can only rsync inside that folder (no shell, no other apps). |
| `DEPLOY_HOST` | Droplet IP |
| `DEPLOY_KNOWN_HOSTS` | Server host keys (`ssh-keyscan`) |
| `CF_API_TOKEN`, `CF_ZONE_ID` | Optional — zone-scoped token with *Cache Purge* permission only |

To rotate the deploy key: generate a new ed25519 key, replace the `usasmartgamers-github-deploy` line in
`/root/.ssh/authorized_keys` (keep the `command=…,restrict` prefix), update `DEPLOY_SSH_KEY`.

## Content / DB changes
The pipeline ships **code only**. Content lives in the database (edited in wp-admin). Schema/option changes ship
as idempotent plugin upgrade routines; one-off tasks use WP-CLI on the server (pattern in [skill.md](../skill.md) §4.7).

### Seeder
`wp usg seed` builds the site **structure** only: US states + legal status, payment methods, 26 news categories,
eight empty toplists (casinos, sweepstakes, sportsbooks, homepage, poker, lottery, prediction markets, social casinos),
~51 section pages, menus and settings. No operators, offers, slots, news or rewards are created — every section without
data shows **"Coming soon"** until editors add content in wp-admin.

- `wp usg seed` — **safe to re-run**: only creates what's missing; never changes pages, authors or templates editors own.
- `wp usg seed --menus` — same, plus rebuilds the four menus from code (overwrites menu edits made in wp-admin).
- `wp usg seed --update` — overwrites ALL seeded pages, menus, toplists and settings. **Don't use once editors have changed content.**

### Adding real content (what makes "Coming soon" disappear)
1. **Operators & Offers → Operators**: add each casino/sportsbook (logo, score, availability by state, facts, pros/cons).
2. **Operators & Offers → Offers**: add its welcome offer (headline, promo code, T&Cs, affiliate URL).
3. **Operators & Offers → Toplists**: add operators to the four toplists — hubs, state pages and the homepage fill automatically.
4. Write a review page (e.g. `/online-casinos/brand-name/`) with the Review summary, Pros & cons, Claim button and User reviews blocks, and set it as the operator's *Review page*.
5. **Slots** (+ Providers), **Posts** (news), **Insights**, **Smart Rewards → Reward catalogue** as needed.
6. Verify each state's legal status under **Operators & Offers → US States**.

### Team
| User | Role | Writes | Fact-checks |
|---|---|---|---|
| philimorevanessa (Vanessa Phillimore) | Administrator | Casino hubs/reviews/state pages, slots, editorial & legal pages | George's content |
| georgeowens (George Owens) | Administrator | Sports, sweepstakes, news, Insights, bill tracker, betting tools | Vanessa's content |
| Kevo (Kevin Lanogwa) | Administrator | — (site management) | — |
| cliffdoyle | Administrator | — | — |

Author profiles (photo, job title, expertise, bio, LinkedIn) are edited under **Users → Profile → Author profile**.
Only filled-in social links are shown on the site (LinkedIn first).

### Plugins
Yoast SEO, Limit Login Attempts Reloaded, Two-Factor (each admin should enable 2FA under their profile) +
our `usasmartgamers-core`. Keep the list short — the droplet has 2 GB RAM shared with two other apps.

## Common commands (on the server)
```bash
cd /root/usasmartgamers
docker compose -f docker-compose.prod.yml logs -f --tail=100 wordpress   # logs
docker compose -f docker-compose.prod.yml restart                         # restart this site only
/root/backup-usasmartgamers.sh                                            # manual backup
zcat /root/usasmartgamers-backups/db_<STAMP>.sql.gz | docker exec -i igaming-db-1 sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" usasmartgamers'  # restore
```
Never run `docker compose down` in `/root/igaming` — it removes the `igaming_default` network this site's DB connection relies on.
