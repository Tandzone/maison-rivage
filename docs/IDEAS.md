# Ideas and open questions

These items are proposals, not approved decisions. Move accepted choices to `DECISIONS.md` and keep this list current.

## Editorial and imagery follow-up

- Three distinct garden/balcony/suite visuals are generated following user approval (D23); review results and integrate the selected images with responsive crops.

- Maison Rivage's story and target guest profile; the hotel name is approved.
- Review generated hall/event drafts before publication; see `VISUAL-REFERENCE.md`.
- Use licensed real destination photographs if portraying specific real places.

## Proposed technical direction

- Twenty Twenty-Five child theme and Docker Compose foundation are implemented; see D13 and README.
- Room inventory, category metadata, media and five journal stories are seeded. Event stories, schedules and occupancy are implemented in chantier 7.
- WordPress hosting with HTTPS, backups and migration support; provider TBD.

## Approved site structure

See `site-architecture.md` and D12 for approved pages, navigation and journeys.

## Detailed workflow and deferred features

- Simple category rates are approved at 95/135/195 EUR; seasonal rates and promotions are deferred. Event listings remain without ticketing/registration.
- No external booking-platform synchronization.
- One language at launch.
- Room assignment and category availability display are implemented in chantier 6.
- Notification triggers and 30-minute verification expiry are implemented; production SMTP provider remains undecided.
- Timed events use the approved one-hour buffers and the same occupancy intervals as overnight stays; see D20 and `events-workflow.md`.

## Public demo and portfolio

- Clear fictional-project and generated-imagery disclosure.
- Submission rate limits, honeypot and idempotency are implemented; review public-demo abuse controls before deployment.
- Consider resetting demo reservations regularly; mechanism and frequency not decided.
- Desktop/mobile screenshots, short walkthrough and English case study.
- Four events and five journal articles are seeded. Review dates before deployment; repeat setup deliberately preserves them.
