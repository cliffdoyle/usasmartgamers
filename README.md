# USA Smart Gamers

WordPress affiliate site for the US gambling market, modelled on the feature set of PlayUSA.com.

- [docs/01-playusa-blueprint.md](docs/01-playusa-blueprint.md) — full teardown of PlayUSA: features, design system, architecture, page templates, components.
- [docs/02-build-plan.md](docs/02-build-plan.md) — our architecture, data model, plugin stack, CI/CD pipeline and phased roadmap.
- [docs/03-operations.md](docs/03-operations.md) — live server setup, deploy pipeline, secrets, backups, rollback.
- [skill.md](skill.md) — runbook for hosting this site on the shared DigitalOcean droplet.

## Local development
1. `copy .env.example .env`
2. `docker compose up -d` → http://localhost:8080 (phpMyAdmin on :8081)
3. Edit `wp-content/themes/usasmartgamers` and `wp-content/plugins/usasmartgamers-core`; push to `main` to deploy.
