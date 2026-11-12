# Maison Rivage

A fictional Madagascar-inspired boutique hotel website, built as a WordPress development portfolio project. Public content and administration labels are in English.

**Current stage:** first local visual preview, awaiting design review. The child theme includes native block templates, reusable patterns, local fonts and responsive layouts. The reservation engine is not implemented yet. See [project reference](docs/PROJECT.md), [approved brief](docs/project-brief.md) and [site architecture](docs/site-architecture.md).

## Local development

Requirements: Docker Engine or Docker Desktop with Docker Compose v2+, Python 3 for generating local configuration, and Bash. No host PHP, database or Node installation is needed.

```bash
./bin/dev init
./bin/dev setup
./bin/dev check
```

- Website: [localhost:8080](http://localhost:8080)
- Administration: [localhost:8080/wp-admin](http://localhost:8080/wp-admin/)
- Captured email: [localhost:8025](http://localhost:8025)
- Admin username and password: `WP_ADMIN_USER` and `WP_ADMIN_PASSWORD` in your generated `.env` file.

The initial image downloads can take several minutes. `setup` waits for the services, installs WordPress if needed, installs the parent theme, activates our child theme and plugin, and creates starter/design pages. Rerunning it preserves existing page content and existing administrator credentials. It does not yet seed business room/reservation records.

The first design seeds only untouched starter pages (plus the original untouched WordPress privacy draft) and creates the category preview pages. Subsequent page edits belong to the editor and are preserved. Patterns are available in the block inserter, and templates/header/footer in the Site Editor. The untouched WordPress “Hello world!” sample post is moved to Trash to leave an honest journal empty state. Public room pages are presentation placeholders until the content chantier defines their business model.

Booking and contact forms are not active. The booking page explicitly states that reservations are unavailable. Generated hall/event imagery and category illustrations are local design previews, pending individual review and final image preparation. Rates have not been invented.

`init` does not overwrite an existing `.env`. Change ports in `.env` before the first setup if needed; keep `WP_URL` consistent with `WORDPRESS_PORT`. Changing the site address after installation also requires updating WordPress URLs and existing content appropriately. Changing environment passwords does not rotate credentials already stored in existing database volumes.

## Commands

```bash
./bin/dev up                  # Start existing services and wait until ready
./bin/dev down                # Stop; preserve database, uploads and installed core
./bin/dev logs                # Recent service logs
./bin/dev wp plugin list      # Run WP-CLI against the shared install
./bin/dev wp theme list
./bin/dev check               # Core checksums, active theme/plugin, PHP lint, SMTP smoke test
```

Database and WordPress runtime files live in named Docker volumes. Source folders are mounted read-only into containers: edit them in this repository. WordPress core, parent theme and uploaded media are not committed. `docker compose down -v` **deletes local WordPress and database volumes**; use it only when deliberately discarding local data.

## Services and source layout

| Component | Role |
|---|---|
| WordPress / PHP 8.3 / Apache | Local web application |
| MariaDB 11.4 | Persistent local database; no host database port |
| WP-CLI / PHP 8.3 | Installation and maintenance commands, same files and environment |
| Mailpit | Local SMTP capture; messages are not delivered to real recipients |
| `wp-content/themes/maison-rivage/` | Child theme of Twenty Twenty-Five |
| `wp-content/plugins/maison-rivage-core/` | Custom business plugin foundation |
| `wp-content/mu-plugins/rivage-local-mail.php` | Local-only SMTP routing |
| `scripts/` | Repeatable setup, starter pages and smoke checks |
| `assets/concepts/` | Generated visual references and drafts |
| `docs/` | Brief, architecture, decisions and progress |

WP-CLI runs as UID/GID 33 to match the Debian WordPress volume owner. Its image is based on Alpine, whose default user otherwise differs. Both services receive the same WordPress configuration.

Container images are pinned by digest in `compose.yaml` so a fresh setup uses the tested artifacts. Twenty Twenty-Five is installed at version 1.5 when absent. Update these deliberately and rerun checks when maintaining the project.

## Email and environment boundaries

Mail routing is enabled only when `WP_ENVIRONMENT_TYPE` is `local` and an SMTP host is configured. Local WordPress binds to loopback, uses debug logging with error display disabled, and discourages indexing. This Compose configuration is for local development; production hosting, HTTPS, actual email delivery and backups are covered in the deployment chantier.

The smoke check sends a message titled **Maison Rivage local smoke test** to `guest@example.test`; verify it appears in Mailpit. It does not delete previously captured messages. Mailpit storage is temporary and may reset when its container is recreated.

## Portfolio scope

The planned custom plugin covers six physical rooms across three categories, category-based requests, email verification, manual confirmation, physical room assignment, overlap prevention, notifications and room-occupying events. These business features belong to later chantiers.

Hotel images are AI-generated concepts for a fictional property. Follow [the visual reference guide](docs/VISUAL-REFERENCE.md) and review individual drafts before publishing them.

## Git and configuration

Commit source, documentation and reference assets. Never commit `.env`, credentials, database dumps or runtime data. `.env.example` contains placeholders only. No production deployment happens when this repository is pushed to GitHub.

## Documentation references

- [WordPress Docker image](https://github.com/docker-library/docs/tree/master/wordpress)
- [WordPress child themes](https://developer.wordpress.org/themes/advanced-topics/child-themes/)
- [Mailpit](https://mailpit.axllent.org/)
