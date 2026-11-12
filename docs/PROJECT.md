# Maison Rivage — Project reference

Last updated: 2026-11-12

## Purpose

Build a fictional hotel website for an Upwork portfolio. Demonstrate visual design, WordPress customization, usable administration and a reservation workflow. Website and administration labels are in English; project discussion is in French.

Primary priority: demonstrate WordPress development skills through a reviewable implementation and meaningful custom functionality.

## Approved concept

- Hotel name: **Maison Rivage** (fictional portfolio establishment).
- Boutique hotel with an upscale, intimate positioning.
- Fictional coastal setting inspired by Madagascar.
- Hotel imagery can be generated; visual consistency must be reviewed before finalizing the art direction.
- Rooms, reservation requests, events and news are the main features.
- Six physical rooms: two Garden Rooms, two Ocean Balcony Rooms and two Rivage Suites. Guests choose a category; room assignment happens at confirmation.
- Events can take place in the hall or a particular room.
- Docker-based local development; aim for an easy deployment path.
- Custom WordPress business plugin for rooms, availability, reservations, events and notifications, independent of the theme.

See [decisions](DECISIONS.md) for reservation rules and [ideas](IDEAS.md) for proposals that still need validation.

## Work areas (chantiers)

| # | Chantier | Status |
|---|---|---|
| 1 | Concept and scope | Complete — approved brief |
| 2 | Site architecture | Complete — architecture approved |
| 3 | Local development environment | Complete — running and verified |
| 4 | Theme and interface | Not started |
| 5 | Content and administration | Not started |
| 6 | Reservation workflow | Not started |
| 7 | Events and news | Not started |
| 8 | Verification and polish | Not started |
| 9 | Deployment | Not started |
| 10 | Upwork portfolio presentation | Not started |

The outline is a planning reference, not an implementation approval for every proposed feature.

## Current state

Local WordPress is running at `http://localhost:8080`, administration at `/wp-admin/`, and Mailpit at `http://localhost:8025`. Docker services, repeatable setup, starter pages, a Twenty Twenty-Five child theme and business plugin scaffold are in place. Credentials are in ignored local `.env`. Reservation features and final styling are not implemented. See `README.md` for commands and `VISUAL-REFERENCE.md` for imagery.

## Next — chantier 4: theme and interface

Chantiers 1–3 are complete. Next: design and implement the theme's global styles, header/footer and public page layouts using the approved visual direction. Business features remain for chantiers 5–7. Source is published at https://github.com/Tandzone/maison-rivage on `main`; this is source publication, not production website deployment.

## Memory map

- `PROJECT.md`: short current-state reference and next steps.
- `DECISIONS.md`: approved decisions and their history.
- `IDEAS.md`: proposals, questions and possible later features.
- `CHANGELOG.md`: meaningful progress and changes.
- `VISUAL-REFERENCE.md`: approved visual anchor, image register and consistency rules.
- `project-brief.md`: English V1 concept, scope, boundaries and delivery criteria.
- `site-architecture.md`: approved sitemap and visitor/admin journeys.
