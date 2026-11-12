# Project working instructions

## Persistent project memory

- At the start of work, read `docs/PROJECT.md` and `docs/DECISIONS.md` before making project choices. Read `docs/IDEAS.md` when planning scope.
- Treat these files as the shared project reference. Distinguish approved decisions from proposals; do not silently promote an idea to an approved decision.
- After meaningful work or a user decision, update the affected memory files and append a concise dated entry to `docs/CHANGELOG.md`.
- Keep `docs/PROJECT.md` concise, with current status and concrete next steps. Preserve decision history in `docs/DECISIONS.md` when a decision changes.
- Discuss project choices with the user in French. Public website content and WordPress administration labels are in English.
- Call major work areas “chantiers”.
- Before generating hotel imagery, read `docs/VISUAL-REFERENCE.md` and inspect its master reference image. Supply that image as an actual generation reference, not just a text description. Review new views for consistency before using them.

## Documentation

Use Context7 MCP for current library, framework, SDK, API, CLI and cloud-service documentation. Resolve the library ID first unless an exact ID is supplied, then query one concept at a time. This is not required for general programming, business logic, refactoring or code review that does not depend on platform APIs.
