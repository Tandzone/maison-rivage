# Maison Rivage — Project brief

Status: V1 concept and functional scope approved. Chantier 1 complete.

## Goal

Create an English-language WordPress website for Maison Rivage, a fictional upscale coastal boutique hotel inspired by Madagascar. The project is an Upwork portfolio demonstration of WordPress development: custom presentation, business logic, usable administration and a reproducible delivery process.

## Hotel and visual identity

Maison Rivage is an intimate six-room property with tropical gardens, ocean views and small cultural events. The approved visual direction combines warm timber, woven materials, ivory plaster, pale stone and muted green textiles. Use the master image and consistency workflow in `VISUAL-REFERENCE.md` for all hotel imagery. Individual draft images still require review before publication. Detailed brand story and editorial personas can be developed during content work.

## Room inventory

| Category | Rooms | Capacity per room | Main feature |
|---|---|---|---|
| Garden Room | 2 | 2 guests | Garden terrace |
| Ocean Balcony Room | 2 | 2 guests | Ocean-view balcony |
| Rivage Suite | 2 | 2 adults and 1 child | Lounge and ocean-view terrace |

## Visitor experience

- Browse room categories, photographs, amenities, capacities and simple nightly category rates.
- Search availability by dates and party size, then request a room category without creating an account.
- Verify the request email address and receive clear pending-confirmation messaging.
- Browse events and hotel news; events have no ticketing or registration in V1.

## Reservation rules

- No online payment in V1. The hotel confirms requests manually and assigns a physical room.
- Browsing, form entry and pending requests do not reserve inventory. Multiple requests may compete for the same dates.
- Confirmation rechecks the assigned room against confirmed stays and room-occupying events, then blocks its dates.
- Incompatible confirmations are rejected with an actionable conflict message. Simultaneous operations must not double-book a room.
- Competing pending requests are flagged, not automatically cancelled. Competition at category level must account for the two available physical rooms; overlapping requests are not necessarily impossible to fulfill.
- Checkout and check-in may occur on the same date. Cancellation releases the confirmed stay's occupancy.
- Event occupation and overnight stay times must be defined before implementation.

## Administration and notifications

- Manage categories, physical rooms, requests, assignments, confirmations and cancellations.
- Manage hall events and private events limited to Rivage Suites; define event capacity and hours later.
- Publish news using WordPress articles.
- Provide administration and email notifications for requests, confirmations, cancellations and conflicts. Specify recipients and triggers in the detailed workflow; a blocked confirmation must not send a success notification.
- Keep labels, statuses and explanatory messages in English.

## Implementation direction

- Build a custom WordPress business plugin for rooms, availability, reservations, events and notifications, independently of the theme.
- Use a customized presentation layer; a Twenty Twenty-Five child theme is the current proposal, with parent choice to be finalized.
- Docker-based local development, documented setup, reproducible demo content and meaningful tests for reservation rules.
- Choose a straightforward WordPress deployment route in chantier 9.

## V1 boundaries

Included: category-based nightly rates, availability, email verification, manual confirmation, conflict prevention, notifications, event listings and news.

Deferred: online payment, seasonal pricing, promotions, event ticketing/registration, external booking-platform synchronization, multilingual content and customer accounts.

## Evidence of completion

- Responsive public site with room, event and news content.
- Demonstrable request-to-confirmation journey and understandable administration.
- Conflict and simultaneous-confirmation checks verified with meaningful tests.
- Notifications verified locally and on the deployed demo as appropriate.
- Documented local setup and deployment, consistent imagery and fictional-project disclosure.
- Portfolio screenshots and a walkthrough explaining the implemented WordPress work.

## Next work

Chantier 2 defines the sitemap, navigation, page contents and visitor/admin journeys. Technical models, timing rules, security details, notification recipients, prices and production hosting will be decided in their respective chantiers; they do not reopen the approved V1 scope.
