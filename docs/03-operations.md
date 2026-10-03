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
`wp usg seed` builds the site structure (US states + legal status, payment methods, providers, operators, offers,
toplists, slots, ~37 pages, news, Insights, rewards, menus, settings). It is idempotent; `wp usg seed --update`
overwrites seeded items (pages, menus, operators…) — **don't run `--update` once editors have changed content.**
Operators, offers, slots, providers, news and state legal statuses are **SAMPLE data** — replace before launch.

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
