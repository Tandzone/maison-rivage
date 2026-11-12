# Maison Rivage

A fictional Madagascar-inspired boutique hotel website, built as a WordPress development portfolio project. Public content and administration labels are in English.

**Current stage:** approved V2 interface with an operational room catalogue and editorial administration. The plugin manages category details, rates and six private physical rooms; the site renders those values dynamically. Events and journal content are operational; see [events workflow](docs/events-workflow.md). The reservation workflow is operational: availability search, email verification, manual room assignment, overlap prevention and guest notifications. See [booking workflow](docs/booking-workflow.md). See [project reference](docs/PROJECT.md), [approved brief](docs/project-brief.md), [site architecture](docs/site-architecture.md) and [content model](docs/content-model.md).

The second visual pass adds a coastal wordmark, an asymmetric inside/outside opening, alternating room layouts and dedicated room facts. See [design direction](docs/design-direction.md) for the rationale. Images still use the approved atmosphere reference; category-specific galleries remain to be created.

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

The initial image downloads can take several minutes. `setup` waits for the services, installs WordPress if needed, installs the parent theme, activates our child theme and plugin, and creates pages, hotel inventory, media and journal stories. Rerunning it preserves existing content and administrator credentials. Reservation records are preserved on repeat setup.

The first design seeds only untouched starter pages (plus the original untouched WordPress privacy draft) and creates the category preview pages. Subsequent page edits belong to the editor and are preserved. Patterns are available in the block inserter, and templates/header/footer in the Site Editor. The untouched WordPress “Hello world!” sample post is moved to Trash to leave an honest journal empty state. Public room pages now act as the editable category records used by the hotel catalogue.

The V2 design migration upgrades only the five untouched V1 home/room previews, checking content hashes normalized for the site URL. It preserves edited pages and stores the previous preview in `_rivage_design_v1_content` post metadata. Rerunning setup does not overwrite V2 editor content.

The booking form is active as a fictional demonstration; the contact form remains inactive. Generated hall/event imagery and category illustrations are local previews, with category-specific galleries still to be prepared. User-approved fictional rates are 95 / 135 / 195 EUR per room, per night.

## Editing hotel content

Open **Maison Rivage → Room Types** in administration. Edit a category's page title, excerpt, Featured image and block content; use its **Maison Rivage — Room Type Details** meta box for guest limits, outdoor space and rate. In the block editor, expand the **Meta Boxes** pane if it is collapsed.

**Maison Rivage → Rooms** manages the six private physical records and their category/operational status. Filters help inspect one category or rooms out of service. **Overview** shows current inventory counts. Hotel data management is restricted to administrators. Journal stories use ordinary **Posts**. **Events** provides native block editing; its **Schedule & Venue** panel opens the controlled schedule form. Publishing a story and reserving its venue are separate operations.

The catalogue and room facts are dynamic shortcodes in native Shortcode blocks. Existing edited preview pages are preserved; insert the shortcodes listed in [content model](docs/content-model.md) manually if setup reports that it skipped a customized page. Prices are stored in integer cents, and the plugin has no dependency on the parent theme's business logic.

`init` does not overwrite an existing `.env`. Change ports in `.env` before the first setup if needed; keep `WP_URL` consistent with `WORDPRESS_PORT`. Changing the site address after installation also requires updating WordPress URLs and existing content appropriately. Changing environment passwords does not rotate credentials already stored in existing database volumes.

## Commands

```bash
./bin/dev up                  # Start existing services and wait until ready
./bin/dev down                # Stop; preserve database, uploads and installed core
./bin/dev logs                # Recent service logs
./bin/dev wp plugin list      # Run WP-CLI against the shared install
./bin/dev wp theme list
./bin/dev check               # Core checksums, active theme/plugin, PHP lint, SMTP smoke test
./bin/dev wp eval-file /workspace/scripts/tests/catalogue.php  # Local catalogue integration checks
./bin/dev wp eval-file /workspace/scripts/tests/events.php     # Event occupancy and schedule checks
python3 scripts/tests/event-booking-race.py                  # Concurrent booking/event conflict check
```

Database and WordPress runtime files live in named Docker volumes. Source folders are mounted read-only into containers: edit them in this repository. WordPress core, parent theme and uploaded media are not committed. `docker compose down -v` **deletes local WordPress and database volumes**; use it only when deliberately discarding local data.

## Services and source layout

| Component | Role |
|---|---|
| WordPress / PHP 8.3 / Apache | Local web application |
| MariaDB 11.4 | Persistent local database; no host database port |
| WP-CLI / PHP 8.3 | Installation and maintenance commands, same files and environment |
| Scheduler / WP-CLI | Runs due WordPress tasks every minute, including mail retries and retention cleanup |
| Mailpit | Local SMTP capture; messages are not delivered to real recipients |
| `wp-content/themes/maison-rivage/` | Child theme of Twenty Twenty-Five |
| `wp-content/plugins/maison-rivage-core/` | Room catalogue, private inventory and reservations |
| `wp-content/mu-plugins/rivage-local-mail.php` | Local-only SMTP routing |
| `scripts/` | Repeatable setup, starter pages and smoke checks |
| `assets/concepts/` | Generated visual references and drafts |
| `docs/` | Brief, architecture, decisions and progress |

WP-CLI runs as UID/GID 33 to match the Debian WordPress volume owner. Its image is based on Alpine, whose default user otherwise differs. Both services receive the same WordPress configuration.

Container images are pinned by digest in `compose.yaml` so a fresh setup uses the tested artifacts. Twenty Twenty-Five is installed at version 1.5 when absent. Update these deliberately and rerun checks when maintaining the project.

The local scheduler replaces visitor-triggered WP-Cron. Local automatic core updates are disabled to preserve the tested installation; update pinned versions deliberately. Production needs a scheduled cron job and a real SMTP provider.

## Email and environment boundaries

Mail routing is enabled only when `WP_ENVIRONMENT_TYPE` is `local` and an SMTP host is configured. Local WordPress binds to loopback, uses debug logging with error display disabled, and discourages indexing. This Compose configuration is for local development; production hosting, HTTPS, actual email delivery and backups are covered in the deployment chantier.

The smoke check sends a message titled **Maison Rivage local smoke test** to `guest@example.test`; verify it appears in Mailpit. It does not delete previously captured messages. Mailpit storage is temporary and may reset when its container is recreated.

## Portfolio scope

Implemented: six private physical rooms, three editable public category pages, nightly rates, guest limits, category/status administration filters, dynamic room collections and journal demo content. Also implemented: category-based requests, email verification, manual confirmation, physical room assignment, overlap prevention and notifications. Events now support native block editing, transactional hall/suite scheduling, buffered occupation, cancellation and upcoming/past public listings. Four events and five journal stories are seeded. See [events workflow](docs/events-workflow.md).

Hotel images are AI-generated concepts for a fictional property. Follow [the visual reference guide](docs/VISUAL-REFERENCE.md) and review individual drafts before publishing them.

## Git and configuration

Commit source, documentation and reference assets. Never commit `.env`, credentials, database dumps or runtime data. `.env.example` contains placeholders only. No production deployment happens when this repository is pushed to GitHub.

## Documentation references

- [WordPress Docker image](https://github.com/docker-library/docs/tree/master/wordpress)
- [WordPress child themes](https://developer.wordpress.org/themes/advanced-topics/child-themes/)
- [Mailpit](https://mailpit.axllent.org/)
