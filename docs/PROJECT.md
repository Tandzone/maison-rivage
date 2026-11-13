# Maison Rivage — Project reference

Last updated: 2026-11-13

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
- Standard WordPress retained after evaluating Bedrock; hosting and production email provider remain undecided.
- Custom WordPress business plugin for rooms, availability, reservations, events and notifications, independent of the theme.

See [decisions](DECISIONS.md) for reservation rules and [ideas](IDEAS.md) for proposals that still need validation.

## Work areas (chantiers)

| # | Chantier | Status |
|---|---|---|
| 1 | Concept and scope | Complete — approved brief |
| 2 | Site architecture | Complete — architecture approved |
| 3 | Local development environment | Complete — running and verified |
| 4 | Theme and interface | Complete — V2 visual direction approved |
| 5 | Content and administration | Complete — room catalogue and journal administration |
| 6 | Reservation workflow | Complete — verified locally |
| 7 | Events and news | Complete — verified locally |
| 8 | Verification and polish | In progress — functional checks passed; browser review pending |
| 9 | Deployment | Not started |
| 10 | Upwork portfolio presentation | Not started |

The outline is a planning reference, not an implementation approval for every proposed feature.

## Current state

Local WordPress is running at `http://localhost:8080`, administration at `/wp-admin/`, and Mailpit at `http://localhost:8025`. V2 design is approved. The plugin now manages three category Pages, six private physical rooms, guest limits and approved EUR rates (95/135/195). Room collections/facts are dynamic, five journal stories and media are seeded, and native administration has inventory filters and safe field validation. Reservations now support availability search, email verification, manual confirmation/assignment, conflict prevention, cancellation and email notifications. Contact now queues administrator emails with validation, duplicate protection and retry. All mail is captured locally in Mailpit. WebP derivatives reduce the three full-size image files by 90%; three distinct category images have now been generated from the master reference. See `booking-workflow.md`.

## Current — chantier 8: verification and polish

Functional checks pass: catalogue, bookings, events, contact, concurrent occupancy, anonymous HTTP barriers and a 21-page public crawl. Contact/privacy starter pages are migrated without replacing edited pages. Read `quality-review.md` for evidence and limits.

**Still open:** final visual/responsive/keyboard review. The browser access host was unavailable on 2026-11-13, so no new browser validation is claimed. The user approved three distinct category images; they are generated and saved in `assets/concepts/`, with prompts in `room-category-generation-v1.json`. Image review and site integration remain; the live previews still use the prior reference. Resume these checks before marking chantier 8 complete.

Production hosting/SMTP/HTTPS/cron/backups remain chantier 9; the Upwork case study belongs to chantier 10. Repository: https://github.com/Tandzone/maison-rivage (`main`); source publication is not production deployment.

## Memory map

- `PROJECT.md`: short current-state reference and next steps.
- `DECISIONS.md`: approved decisions and their history.
- `IDEAS.md`: proposals, questions and possible later features.
- `CHANGELOG.md`: meaningful progress and changes.
- `VISUAL-REFERENCE.md`: approved visual anchor, image register and consistency rules.
- `project-brief.md`: English V1 concept, scope, boundaries and delivery criteria.
- `site-architecture.md`: approved sitemap and visitor/admin journeys.
- `design-direction.md`: design tokens, layout rationale and preview limitations.
- `content-model.md`: editable category Pages, private rooms, rates, administration and verification.

- `events-workflow.md`: editorial/schedule separation, event occupancy, public listings and verification.

- `quality-review.md`: chantier 8 changes, checks and remaining browser/imagery review.
