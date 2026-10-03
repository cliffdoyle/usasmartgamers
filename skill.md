---
name: add-wordpress-site
description: Add a NEW WordPress site to the shared DigitalOcean droplet (143.110.219.173) that already hosts OntarioGamers (WordPress) and ZuriStay (hotel API behind Caddy), running it the same way as OntarioGamers — Docker Compose, bind-mounted custom theme/plugin from git, Cloudflare in front, nightly DB + uploads backups. Use when asked to deploy, host, or set up another WordPress app/site on "our server".
---

# Add a new WordPress site to the shared server

You are setting up an additional WordPress site on a production server that ALREADY
serves two live apps. Your #1 rule: **do not break, restart, reconfigure, or touch
the existing apps**. Everything you create must be additive and isolated.

## 0. Before you start — get these from the human

Ask (one at a time) and do not guess:
1. **Domain** of the new site (e.g. `example.ca`) and whether it is on **Cloudflare** (proxied / orange cloud).
2. **Short project name** (lowercase letters/digits only, e.g. `newsite`). Used for the folder, compose project, DB name and container names.
3. **Git repo URL** for the new site's custom theme/plugin (if any). If none, create the site without bind mounts and add them later.
4. **Admin username + email** for the WordPress install.

If the domain is NOT on Cloudflare, read section 4.6 and ask the human how they want
routing/HTTPS done — do not take over ports 80/443.

## 1. Access (from the Windows workstation)

- Machine: Windows, PowerShell. Use Windows paths with backslashes locally.
- **SSH private key:** `C:\Users\v-comollo\.ssh\hetzner_ed25519`
  (named "hetzner" for historical reasons but it IS the DigitalOcean key; no passphrase).
  Other keys in that folder (`deploy_droplet`, `id_ed25519`) are NOT for this server.
- **Server:** `root@143.110.219.173` (DigitalOcean droplet, TOR1 Toronto, static IP).
- Connect:
  ```powershell
  ssh -i "$env:USERPROFILE\.ssh\hetzner_ed25519" root@143.110.219.173
  ```
- Never print, copy, or commit the private key or any `.env` contents. Read secrets on the
  server only; never paste them into chat, git, or files on the workstation.

### Running multi-line scripts over SSH (proven pattern — use it)
PowerShell mangles quotes and `$(...)` in inline ssh strings. Instead base64 the script:
```powershell
$s=@'
set -e
cd /root
echo "hello from $(hostname)"
'@
$env:OG_B64=[Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($s.Replace("`r","")))
ssh -i "$env:USERPROFILE\.ssh\hetzner_ed25519" root@143.110.219.173 "echo $env:OG_B64 | base64 -d | bash"
```
- The here-string MUST be single-quoted (`@'...'@`) so PowerShell does not expand `$vars` locally.
- Always strip `\r` (shown above) or bash will fail on CRLF.
- Never put `$(...)` directly inside a double-quoted ssh string — PowerShell evaluates it locally.
- For large payloads (HTML, files) use `scp -i <key> <file> root@143.110.219.173:/root/...` instead.

### Extra context (optional, read-only)
Prior notes about this server live in VS Code Copilot memory on this machine:
`C:\Users\v-comollo\AppData\Roaming\Code\User\workspaceStorage\e5db052e2fdf1ce187299dc45507142e\GitHub.copilot-chat\memory-tool\memories\repo\`
(`ontariogamers.md`, `backup-and-data-safety.md`). Read them if you need history; do not edit them.

## 2. What is already on the server (DO NOT MODIFY)

Host: Ubuntu 24.04, **1 vCPU, 2 GB RAM + 2 GB swap**, 48 GB disk (~30 GB free).
Docker 29 + Compose v5, runs as root (no `sudo`). `ufw` is inactive.

| App | Folder | Containers | Ports / role |
|---|---|---|---|
| OntarioGamers (WordPress) | `/root/igaming` (git: cliffdoyle/igaming) | `igaming-wordpress-1`, `igaming-db-1` (MySQL 8.0) | **owns host port 80**; Cloudflare (Flexible SSL) → origin :80 for `ontariogamers.ca` |
| ZuriStay (hotel API) | `/root/hotel-management-system/deploy` | `hotel-caddy-1`, `hotel-api-1`, `hotel-redis-1` | Caddy **owns host port 443** (`api.zuristay.com`, `*.zuristay.com`); Caddy is deliberately NOT bound to :80 |

- Docker networks: `igaming_default`, `hotel_hotel`. Volumes: `igaming_db_data`, `igaming_wp_data`, `hotel_*`.
- OntarioGamers secrets: `/root/igaming/.env` (`DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`).
  The MySQL root password is also available inside the DB container as `$MYSQL_ROOT_PASSWORD`.
- Nightly cron (root): `15 3 * * * /root/backup-db.sh` and `30 3 * * * /root/backup-uploads.sh`
  (OntarioGamers only; outputs in `/root/db-backups`, `/root/uploads-backups`).
- Baseline RAM: ~760 MB used, ~1.2 GB available; MySQL ~165 MB, WordPress ~140 MB.

Never run on existing projects: `docker compose down`, `down -v`, `docker volume rm`,
`docker system prune`, edits to `/root/igaming/*`, `/root/hotel-management-system/*`,
the hotel `Caddyfile`, or the existing cron lines. Never restart `igaming-db-1` unless the human approves.

## 3. Architecture for the new site (mirror OntarioGamers, but isolated)

```
Cloudflare (proxied, SSL Flexible) --Origin Rule: port 8082--> droplet :8082 --> <name>-wordpress-1
                                                                                     | (joins network igaming_default)
                                                                                     v
                                                                   igaming-db-1 (shared MySQL), DB "<name>"
```
- **Reuse the existing MySQL server** (`igaming-db-1`) with a NEW database + NEW user. Do not run a
  second MySQL container — the box only has 2 GB RAM.
- **Own WordPress container** on host port **8082** (pick another free port if taken: `ss -tlnp`).
- **Own folder + compose project**: `/root/<name>`, its own named volume for `/var/www/html`.
- **Memory cap** on the new container (`mem_limit: 384m`) so it can never starve OntarioGamers.
- Custom theme/plugin **bind-mounted from git** (same as OntarioGamers) so code deploys are `git pull`.

## 4. Step-by-step

Replace `<name>`, `<domain>`, `<repo-url>`, `<theme>`, `<plugin>` throughout. Verify each step's output before moving on.

### 4.1 Pre-flight checks
```bash
free -h; df -h /; docker ps --format '{{.Names}}\t{{.Ports}}'; ss -tlnp | grep -E ':(80|443|8082)\b' || true
```
Abort and report if available RAM < 600 MB or port 8082 is in use.

### 4.2 Backup first (always)
```bash
/root/backup-db.sh && ls -t /root/db-backups | head -1
```

### 4.3 Create the database + user in the shared MySQL
Generate the password ON THE SERVER, store it only in `/root/<name>/.env`:
```bash
set -e
mkdir -p /root/<name> && cd /root/<name>
NEW_PASS=$(openssl rand -base64 24 | tr -d '/+=')
cat > .env <<EOF
DB_NAME=<name>
DB_USER=<name>_user
DB_PASSWORD=$NEW_PASS
EOF
chmod 600 .env
docker exec -i igaming-db-1 sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD"' <<SQL
CREATE DATABASE IF NOT EXISTS \`<name>\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '<name>_user'@'%' IDENTIFIED BY '$NEW_PASS';
GRANT ALL PRIVILEGES ON \`<name>\`.* TO '<name>_user'@'%';
FLUSH PRIVILEGES;
SQL
```
The new user must have privileges ONLY on its own DB — never grant on `*.*` or on `ontariogamers`.

### 4.4 Code (optional)
```bash
cd /root/<name> && git clone <repo-url> src   # theme at src/wp-content/themes/<theme>, plugin at src/wp-content/plugins/<plugin>
```
If the repo is private, ask the human for a deploy key / token — do not reuse other projects' credentials.

### 4.5 `/root/<name>/docker-compose.prod.yml`
```yaml
name: <name>
services:
  wordpress:
    image: wordpress:latest
    ports:
      - "8082:80"
    environment:
      WORDPRESS_DB_HOST: igaming-db-1
      WORDPRESS_DB_USER: ${DB_USER}
      WORDPRESS_DB_PASSWORD: ${DB_PASSWORD}
      WORDPRESS_DB_NAME: ${DB_NAME}
      WORDPRESS_CONFIG_EXTRA: |
        define('WP_HOME', 'https://<domain>');
        define('WP_SITEURL', 'https://<domain>');
        if (isset($$_SERVER['HTTP_X_FORWARDED_PROTO']) && $$_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $$_SERVER['HTTPS'] = 'on';
        }
    volumes:
      - wp_data:/var/www/html
      # - ./src/wp-content/themes/<theme>:/var/www/html/wp-content/themes/<theme>
      # - ./src/wp-content/plugins/<plugin>:/var/www/html/wp-content/plugins/<plugin>
    networks:
      - igaming_default
    mem_limit: 384m
    restart: unless-stopped

networks:
  igaming_default:
    external: true

volumes:
  wp_data:
```
Notes:
- Use the container name `igaming-db-1` as DB host (unambiguous; don't rely on the `db` alias).
- `$$` is required in compose to emit a literal `$` into wp-config.
- `WORDPRESS_CONFIG_EXTRA` is only applied when wp-config.php is first created (fresh volume). If you
  change the domain later, update `home`/`siteurl` in the DB instead.
- Do not add a `db` service and do not add a `ports:` mapping for MySQL.
- Caveat: if OntarioGamers' compose is ever fully torn down (`down`), the `igaming_default` network is
  removed and this site loses its DB until OntarioGamers is back `up`. Mention this to the human.

Start it:
```bash
cd /root/<name> && docker compose -f docker-compose.prod.yml up -d && docker compose -f docker-compose.prod.yml ps
curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: <domain>' http://127.0.0.1:8082/   # expect 302 (to install.php) or 200
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1/                             # OntarioGamers must still answer (200/301)
```

### 4.6 Cloudflare (human does this in the dashboard — give them exact steps)
1. DNS: `A @ → 143.110.219.173` and `A www → 143.110.219.173`, **Proxied** (orange cloud).
2. SSL/TLS → Overview → **Flexible** (origin speaks HTTP only, same as OntarioGamers).
3. Rules → **Origin Rules** → Create rule: *Hostname equals `<domain>`* OR *Hostname equals `www.<domain>`* →
   **Destination Port → Rewrite to 8082**. Deploy.
4. (Recommended) SSL/TLS → Edge Certificates → **Always Use HTTPS** = On.

Without step 3, Cloudflare would hit port 80 and show OntarioGamers — that is the expected symptom.

If the domain is not on Cloudflare: stop and ask. Options (human decides): move DNS to Cloudflare
(preferred, free), or add a site block to the hotel Caddy on 443 — which touches another project and
needs explicit approval and a backup of the Caddyfile first.

### 4.7 WordPress install (WP-CLI, same pattern as OntarioGamers)
One-off CLI container — `wp` MUST come after the image name, and the `WORDPRESS_DB_*` env vars MUST be passed:
```bash
cd /root/<name> && set -a && . ./.env && set +a
WP="docker run --rm --user 33 -e HOME=/tmp --volumes-from <name>-wordpress-1 --network igaming_default \
  -e WORDPRESS_DB_HOST=igaming-db-1 -e WORDPRESS_DB_USER=$DB_USER -e WORDPRESS_DB_PASSWORD=$DB_PASSWORD -e WORDPRESS_DB_NAME=$DB_NAME \
  wordpress:cli wp"
ADMIN_PASS=$(openssl rand -base64 18)
$WP core install --url=https://<domain> --title="<Site Title>" --admin_user=<admin> --admin_password="$ADMIN_PASS" --admin_email=<email> --skip-email
$WP rewrite structure '/%postname%/' --hard
$WP option update timezone_string 'America/Toronto'
echo "ADMIN PASSWORD (give to human once, do not store elsewhere): $ADMIN_PASS"
```
- Show the generated admin password to the human once; tell them to change it after first login.
- Activate theme/plugin if bind-mounted: `$WP theme activate <theme>`, `$WP plugin activate <plugin>`.
- For PHP snippets use `wp eval-file`: `docker cp` the file into `/var/www/html/` of the WP container
  (NOT `/tmp` — the CLI container doesn't share it), run, then delete it.
- Avoid `-i`/`-t` on `docker run` over SSH (truncates output).

### 4.8 Backups (add new, don't modify existing scripts)
Create `/root/backup-<name>.sh`:
```bash
#!/bin/bash
set -e
STAMP=$(date +%F_%H%M%S)
D=/root/<name>-backups; mkdir -p "$D"
docker exec igaming-db-1 sh -c 'exec mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --quick --routines --events <name>' | gzip > "$D/db_$STAMP.sql.gz"
docker exec <name>-wordpress-1 tar czf - -C /var/www/html/wp-content uploads > "$D/uploads_$STAMP.tar.gz"
ls -1t "$D"/db_*.sql.gz | tail -n +15 | xargs -r rm -f
ls -1t "$D"/uploads_*.tar.gz | tail -n +6 | xargs -r rm -f
echo "$(date '+%F %T') <name> backup OK"
```
`chmod +x`, run it once and verify (`gunzip -t db_*.sql.gz`, `tar tzf uploads_*.tar.gz | head`), then APPEND
to crontab at a different minute:
```bash
(crontab -l; echo '45 3 * * * /root/backup-<name>.sh >> /root/<name>-backups/backup.log 2>&1') | crontab -
crontab -l   # confirm the two existing lines are still there
```

## 5. Verification checklist (all must pass before you report success)
- [ ] `https://<domain>/?nc=<random>` returns 200 and shows the NEW site (not OntarioGamers).
- [ ] `https://<domain>/wp-admin/` loads the login over HTTPS without redirect loops.
- [ ] `https://ontariogamers.ca/` still 200 and unchanged; `https://api.zuristay.com` still responds.
- [ ] `docker stats --no-stream` — new container under its 384 MB cap; host `free -h` available > 400 MB.
- [ ] Backup script ran, outputs valid, new cron line present, existing cron lines intact.
- [ ] No secrets written to the workstation or committed to git.

## 6. Ongoing operations
- Deploy code: `cd /root/<name>/src && git pull` (bind-mounted → live immediately).
- Logs: `cd /root/<name> && docker compose -f docker-compose.prod.yml logs -f --tail=100 wordpress`.
- Restart only this site: `docker compose -f docker-compose.prod.yml restart` inside `/root/<name>`.
- DB restore: `zcat /root/<name>-backups/db_<STAMP>.sql.gz | docker exec -i igaming-db-1 sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <name>'`.

## 7. Capacity / when to escalate
- Fine for a low-traffic new site. If host available RAM stays < 300 MB, swap > 1 GB, or load > 1.0
  sustained, tell the human to resize the droplet to 4 GB (DigitalOcean → Resize, CPU+RAM only, ~$24/mo, data kept).
- Recommend enabling DigitalOcean Droplet Backups (~$2.40/mo) — all three apps share this single server.
- Keep plugins minimal and updated; a compromised plugin shares the host with the other apps.

## 8. Final report to the human
Summarise: URL, admin username (+ password shown once), DB name/user (not password), container
name, host port, backup script + cron time, RAM before/after, and any Cloudflare steps they still need to do.
