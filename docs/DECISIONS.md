# Approved decisions

Recorded on 2026-11-12 from the project discussion.

## D01 — Purpose and language

The project is a fictional hotel website for an Upwork portfolio. Site content and administration labels will be in English.

The user emphasized that demonstrating WordPress development skills is the primary goal. Favor a reviewable implementation with meaningful custom functionality; the custom-plugin approach is approved (D10).

## D02 — Hotel concept

An upscale boutique hotel in a fictional coastal setting inspired by Madagascar. The approved name is **Maison Rivage**. Detailed identity remains open. The name was confirmed on 2026-11-12 for this fictional portfolio project.

## D03 — Development and delivery goal

Use Docker for local development and favor a straightforward deployment path. Hosting provider and production workflow are not selected.

## D04 — Functional scope

Present rooms, handle reservation requests, display events in the hall or a particular room, and publish news.

## D05 — V1 reservations

- Reservations are requests, confirmed manually by the hotel.
- No online payment in V1.
- Proposed and accepted workflow includes email verification before administrative review.
- Viewing availability and filling a form do not block inventory.
- Pending requests do not permanently reserve a room; confirmed reservations block their dates.
- The visitor must clearly see that a request is awaiting confirmation.

## D06 — Conflict checks and notifications

The user requested automatic overlap checks and administrator notifications. The discussed implementation must:

- Recheck availability at each confirmation attempt.
- Include confirmed bookings and room-occupying events in conflict checks.
- Reject incompatible confirmations and identify the conflicting record.
- Flag overlapping pending requests for administrative review.
- Flag affected pending requests when another booking is confirmed.
- Prevent simultaneous confirmations from double-booking the same room.
- Leave alternative-room proposals and cancellation of competing requests to the hotel.
- Allow checkout and check-in on the same date for overnight stays.

Notification channels are approved in D11, room assignment is approved in D09, and detailed event timing rules remain to be designed. A warning alone must not replace conflict prevention.

## D07 — Terminology

Major work areas are called “chantiers”; each can contain tasks and acceptance criteria.

## D08 — Approved visual direction and image consistency

The user approved the first balcony and bedroom visual direction and requested reference-based consistency for future imagery, particularly the hall. Use `assets/concepts/maison-rivage-direction-v1.png` as the master image reference. Follow `VISUAL-REFERENCE.md`; pass the actual reference image to generation and review new views before publication.

The user subsequently approved the proposed hall design brief: timber and pale stone reception, woven lounge furniture with green cushions, matching roof structure, garden/ocean opening and flexible space for intimate events. A first image has been generated; approval of the resulting image is pending.

## D09 — Room inventory and assignment

The user approved six physical rooms across three categories:

| Category | Physical rooms | Capacity per room | Feature |
|---|---|---|---|
| Garden Room | 2 | 2 guests | Terrace overlooking tropical garden |
| Ocean Balcony Room | 2 | 2 guests | Ocean-view balcony, matching the visual reference |
| Rivage Suite | 2 | 2 adults and 1 child | Lounge area and ocean-view terrace |

Guests request a category; the hotel assigns an available physical room when confirming. Availability checks must evaluate the selected physical room and category inventory. Private room events are limited to Rivage Suites; event capacities and hours remain to be defined.

## D10 — Custom business plugin

The user approved a custom WordPress business plugin for rooms, availability, reservation requests, events and notifications. Keep business functionality independent of the theme. Provide a usable administration interface, reproducible local development, documentation and tests for important reservation rules. Plugin internals and final parent theme choice remain to be designed.

## D11 — Final V1 boundaries

The user approved simple nightly category rates without seasons or promotions, event listings without ticketing or registration, administration and email notifications for requests/confirmations/cancellations/conflicts, and booking requests without customer accounts. V1 targets demonstrable WordPress development skills. The English scope brief is `project-brief.md`; chantier 1 is complete. Detailed recipients, triggers, prices and technical implementation remain for later chantiers.

## D12 — Site architecture and guest follow-up

The user accepted the architecture in `site-architecture.md`, including category pages, the public navigation, administration screens and email-based guest follow-up without a customer portal. The hotel confirms in administration; the system rechecks availability, records the booking and blocks dates, then emails the guest. Email verification does not itself confirm a reservation. Chantier 2 is complete; next is chantier 3, the local development environment.

## D13 — Development foundation and source control

The user authorized chantier 3 and selected a public GitHub repository on the connected Tandzone account. Repository name: `maison-rivage`.

Implementation baseline: WordPress 7.1.4 on PHP 8.3/Apache, MariaDB 11.4, WP-CLI on PHP 8.3, Mailpit 1.33.1. Container artifacts are pinned by digest in `compose.yaml`. Twenty Twenty-Five 1.5 is the selected parent for the child theme. Business plugin is a scaffold, with features deferred to later chantiers. Setup creates English starter pages and preserves them on repeat runs. Local secrets and runtime data stay out of Git. Website and Mailpit listen only on loopback; Compose is local-development configuration.

## D14 — Retain standard WordPress

After discussing Bedrock and hosting constraints, the user explicitly chose to continue with standard WordPress. No Bedrock migration. Hosting provider, budget commitment and production email provider remain undecided; investigate multi-site hosting for several portfolio demos before choosing an offer.

## D15 — Strengthen the child theme identity

The user requested a stronger visual identity so the website feels less like the parent theme, and approved a second design pass on the home and room pages. Keep Twenty Twenty-Five as the parent, native WordPress editing and the established photographic direction. The resulting visual proposal remains pending user review; no production publication or new room imagery is implied.

The user subsequently approved the second version (“c'est beaucoup plus joli, continuons”). Chantier 4's interface direction is accepted; category-specific photographs and production image review remain tracked for final content/polish.

## D16 — Demo rates

The user approved EUR nightly rates: Garden Room 95 €, Ocean Balcony Room 135 €, Rivage Suite 195 €. Rates are per room, per night. Tax presentation and cancellation policy are not finalized.

## D17 — Content implementation

Implementation choice for chantier 5: keep room types as the existing public category Pages (with hotel metadata) and use a nonpublic `rivage_room` post type for physical inventory. This preserves URLs and block editing without duplicate category records. Plugin shortcodes render collections and facts from centralized data. Hotel field changes require administrative capabilities and nonces. Journal uses standard WordPress Posts. Booking and event operations remain for chantiers 6–7.

## D18 — Approved reservation rules

The user approved arrival from 14:00 and departure before 11:00 (Madagascar); at least one adult, adults aged 12+, children aged 0–11; category guest limits; a 30-minute email verification link with resend; pending requests never hold inventory; manual confirmation rechecks availability. Demo totals are nightly rate × nights with no supplements or payment. Admin cancellation releases dates. Verification emails go to guests, verified requests notify the admin, and confirmation/decline/cancellation notify guests. Conflicting confirmations are blocked and alert the admin.

## D19 — Booking implementation defaults

Implementation choices, distinct from user-approved business rules: stays of 1–30 nights with arrival within one year; transactional InnoDB occupancy with per-room locks; signed form idempotency keys; hashed verification tokens; transactional mail outbox and bounded retries. Local scheduler runs every minute. Unverified requests are removed after 24 hours, other booking records 90 days after departure. These demo defaults are disclosed in the site policies and can be revised before production. See `booking-workflow.md`.
