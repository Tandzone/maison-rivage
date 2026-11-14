# Project progress log

## 2026-11-12

- Established the project goal and the ten-chantier planning outline.
- Agreed on an English-language, fictional upscale coastal boutique hotel inspired by Madagascar.
- Discussed and accepted a manual reservation confirmation workflow without online payment in V1.
- Added automatic availability checks, conflict prevention and administrator notifications to the reservation requirements.
- Created repository-based project memory, separating approved decisions from open proposals.
- Approved **Maison Rivage** as the fictional hotel name.
- Next: finish chantier 1, starting with the visual identity and sample imagery.
- Generated and saved the first visual direction diptych (balcony and bedroom) at `assets/concepts/maison-rivage-direction-v1.png`: warm timber, ivory, woven materials and muted green. Awaiting user validation.
- User approved the balcony/bedroom direction and requested reference-based consistency for all future hotel visuals. Added `VISUAL-REFERENCE.md`, registered the master image and added the workflow to `AGENTS.md`. Hall generation remains pending.
- User approved the hall design brief. Generated `assets/concepts/maison-rivage-hall-v1.png` using the approved master image. Visual review shows matching materials, palette and coastal outlook; awaiting user approval of this image.
- Tested a second hall camera angle using hall-v1 as an image reference. Saved `assets/concepts/maison-rivage-hall-angle-2-v1.png`. Identity is recognizable, but layout/proportion drift remains; draft pending user review.
- Created a requested event concept: adult cellist with an intimate audience in the rearranged hall lounge, from the reception side. Saved `assets/concepts/maison-rivage-cello-evening-v1.png`, using hall-v1 as reference; awaiting approval.
- Approved six physical rooms in three categories, category-based booking requests, assignment at confirmation, and private room events limited to Rivage Suites. User emphasized demonstrating WordPress development skills as the primary portfolio objective. Custom plugin remains a recommendation to discuss.
- User approved the custom WordPress business plugin for rooms, availability, reservations, events and notifications, independent of the theme.
- User approved final V1 boundaries: simple nightly rates, event listings without registration, admin/email notifications and no customer accounts. Created English `project-brief.md` and completed chantier 1. Next: chantier 2, site architecture and journeys.
- Started chantier 2 and drafted `site-architecture.md`: public navigation, page responsibilities, category-based booking journey, administrative screens, conflict states and review criteria. Architecture is proposed, awaiting user review.
- User accepted chantier 2 architecture after clarifying administrative confirmation and guest email notification. Marked chantier 2 complete; next is chantier 3, local development environment.
- Implemented chantier 3: pinned Docker services, ignored generated local secrets, WP-CLI setup, English starter pages, Twenty Twenty-Five child theme, custom plugin scaffold and local-only SMTP routing to Mailpit.
- Verified Compose configuration, running services, WordPress checksums, PHP syntax, front page and pretty permalinks, REST endpoint, administrator login, captured SMTP message and setup idempotency (existing page contents/count preserved).
- Fixed local mail sender validation (`wordpress@localhost`) before PHPMailer initialization. No real recipient mail was sent.
- Initialized Git on `main`. User authorized a public `Tandzone/maison-rivage` GitHub repository; publication follows source review. Chantier 3 complete; next: theme and interface.
- Reviewed staged files for local passwords and ignored `.env`, then committed and pushed the project to the public repository https://github.com/Tandzone/maison-rivage (`main`). Runtime volumes and local credentials are not published.
- User chose to retain standard WordPress after discussing Bedrock. Hosting and production mail remain undecided; discussing shared hosting for multiple portfolio demos.
- Started chantier 4: implemented first local visual preview using native block templates/patterns, global design tokens, local Literata/Manrope fonts, responsive navigation and home/room/about/event/booking/contact/policy/journal/404 layouts. Existing editable content is preserved by repeatable design seeding.
- Kept business functionality deferred: no invented prices, live availability or active guest forms. Room pages use reference imagery; hall/event drafts remain for local review.
- Verified all 13 patterns and 11 seeded page contents with WordPress's actual client block validator (no validation failures after image markup correction). Temporary validation instrumentation removed. Verified public links, one H1 per page, 404 response, mobile navigation and room-to-booking journey. Awaiting user visual feedback; chantier 4 remains in progress.
- Final preview checks passed: no broken images or horizontal overflow at 320, 390, 768 and desktop viewport; PHP/core/SMTP smoke checks and repeat setup preservation passed. Desktop/mobile captures saved locally under ignored `artifacts/previews/`. First visual version ready for user review.
- User approved a second visual pass to strengthen Maison Rivage's identity while retaining the parent theme. Added vector coastal wordmark, asymmetric home opening, staggered home room previews, green hall feature, alternating room catalogue and room detail facts/terrace layouts.
- Added safe V1-to-V2 design migration using normalized content hashes, recoverable previous-content snapshots and preservation of editor modifications. Validated all patterns/pages with the WordPress block validator and tested repeat seeding plus edited-content preservation. Visual review pending.
- V2 responsive checks passed for home, catalogue and room detail at 320/390/768 px (one H1, no horizontal overflow or broken images); checked desktop composition and mobile menu Escape/focus return. Saved V2 desktop/mobile captures under `artifacts/previews/`; removed temporary QA instrumentation. PHP/core/SMTP checks passed.
- User approved the V2 visual direction and authorized continuing. Completed chantier 4's interface work; final category imagery remains tracked.
- User approved fictional nightly rates in EUR: 95 (Garden), 135 (Ocean Balcony), 195 (Suite).
- Implemented chantier 5: editable category Pages with hotel metadata, six nonpublic physical-room records, administrative overview/category table/filters, validated and authorized saves, integer-cent rates and dynamic collection/facts shortcodes. Imported media and seeded three fictional journal articles with recent-post display.
- Verified price changes through the actual WordPress editor reflected on the public page, then restored the approved 135 EUR rate. Verified category filter returns two rooms, inventory is not exposed via public room IDs or REST, malformed input/unauthorized saves are rejected and reseeding preserves existing records without duplication. Fixed first-run journal permalink initialization. Next: reservation workflow decisions and chantier 6.
- Final frontend verification: all homepage links and journal article URLs respond, homepage shows the three approved rates and three journal stories, and the dynamic layout has no horizontal overflow at mobile width. Admin category table/filtered inventory captures saved in ignored `artifacts/previews/`. Room/category integration checks, PHP/core integrity and SMTP checks passed.

## 2026-11-12 — Chantier 6: reservation workflow

- Added category availability search, server-side pricing, verified requests and admin room assignment/confirmation, decline and cancellation.
- Added InnoDB occupancy and shared room locks, preserving pending requests without holding inventory and preventing overlapping confirmations under concurrent workers.
- Added private booking list, 14-night inventory calendar, overlapping-request warnings and email outbox monitoring/retry. Conflicts show an immediate administrator notice.
- Added token expiry/resend, signed submission idempotency, rate limits, privacy headers and retention cleanup. Updated public booking/privacy policies with demo limits.
- Added local Docker scheduler for retries/cleanup without visitors; kept core updates deliberate for reproducibility.
- Verified core checksums, PHP lint, SMTP, catalogue tests, booking integration tests, independent-process confirmation race and anonymous HTTP barriers. Browser exercised request → verification → confirmation → calendar → cancellation with Mailpit messages; cancellation released occupancy. Checked 390px and 320px and fixed narrow search-grid overflow. Repeat setup preserved existing content.
- Event management remains chantier 7. Production SMTP/hosting remain chantier 9. No production website was deployed.

## 2026-11-12 — Chantier 7: events and news

- Recorded user approval for one-hour preparation/reset buffers around suite events; hall events do not block rooms.
- Added public event posts with native block editing, images, excerpts and revisions, plus a separate administrator schedule form with nonce/capability and stale-revision checks.
- Added transactional event schedules using shared room locks and occupancy. Conflicting venue/time edits roll back, cancellation releases inventory, and trash/restore cannot silently revive a room occupation.
- Added upcoming/past paginated listings, public detail/status information, home teasers, calendar event links and an administrator Overview summary.
- Seeded four events and two more journal stories (five total); added related stories. Reused existing concept imagery. Repeat setup preserved a hash of all editorial records, schedules and occupations.
- Passed event integration tests and booking regression tests. Independent booking/event workers produced one success, one conflict and one occupation. Core checksums, PHP lint and local SMTP passed. Browser verified native editor, schedule save, calendar, public details, related stories, home teasers and 320px rendering without overflow.
- Updated the workflow documentation and project memory. No production deployment; final review/polish is chantier 8.

## 2026-11-13 — Chantier 8 functional review and polish (in progress)

- Added contact validation, nonce/honeypot/rate limits, transactional-outbox reuse, idempotent queueing, failure retry and 24-hour retention. Fixed WordPress query-variable collision exposed by the HTTP contact test.
- Added immutable outbox creation times (schema version 2) to make retention independent of retry scheduling. Updated truthful contact success/privacy wording and local scheduler description.
- Added non-destructive WebP derivatives (90% reduction across the three full-size image files), responsive seeded-media delivery and clearer focus outlines.
- Passed catalogue, booking, event and contact suites, both occupancy races, anonymous booking checks and contact HTTP/SMTP. Public crawl verifies 21 pages and local image sources; repeat setup preserves page/schedule snapshots.
- Browser host was unavailable on inventory and direct-open attempts. Final visual, responsive and keyboard checks remain open, as does the optional category-imagery choice. No new screenshot, accessibility certification or page-speed score is claimed. See `quality-review.md`.

## 2026-11-13 — Three distinct room-category image proposals

- Recorded explicit user approval to generate Garden Room, Ocean Balcony Room and Rivage Suite visuals.
- Used built-in image generation with the actual approved master image for all three; preserved full prompts and generation mode in `assets/concepts/room-category-generation-v1.json`.
- Saved three new 1536×1024 PNGs and reviewed garden/ocean/lounge differentiation, natural materials, muted green palette and coastline consistency. Preserved original/live imagery; result approval and site integration remain open.

## 2026-11-13 — Fix assets through private Tailscale Serve

- Confirmed screenshot failure: the HTTPS preview page referenced loopback CSS/images/fonts, causing connection-refused and mixed-content errors remotely.
- Added explicit preview-origin configuration before WordPress bootstrap, exact-host HTTPS detection and local-only content URL normalization. Preserved localhost access and database content; no global URL replacement.
- Enabled the existing private Serve origin only in ignored `.env`, recreated local services and verified both origins. Added a reusable HTTPS preview check covering pages/assets and CSS dependencies. Tailscale network access and Funnel settings were not changed.

## 2026-11-13 — Open date calendars from the whole field

- User requested date selection instead of typing. Retained native date inputs and added progressive `showPicker()` activation when clicking anywhere in the search date fields; the calendar icon and keyboard entry remain available when unsupported or blocked.
- Verified JavaScript/PHP syntax and script delivery on home/booking through localhost and the private HTTPS preview. Native popup interaction still belongs to the pending browser review.

## 2026-11-13 — Separate Typesense portfolio idea

- Recorded the user's interest in a separate search-focused site with freely available data indexed into an owned Typesense collection, reflecting their existing professional experience. Dataset/licensing, scope and stack remain to discuss; Maison Rivage's scope is unchanged.

## 2026-11-14 — Complete chantier 8: room galleries and browser review

- User authorized bathroom/outdoor photography and editable/fullscreen galleries, with explicit desktop/mobile, keyboard and calendar checks. Generated six reference-based views, preserved full prompts, and integrated all nine photographs as WebP Media Library assets.
- Added protected administrator media selection/ordering and dynamic gallery/outdoor shortcodes. Starter migrations preserve customized content and Featured images; repeat setup preserved content/gallery/media snapshots.
- Added native-dialog fullscreen viewing, thumbnails, arrow/Enter/Space navigation, explicit Tab containment, Escape/focus restoration and pointer swipes, without autoplay. Fixed the focus escape discovered by browser testing. Increased header controls to 44px minimum height.
- Passed 42 layout checks across 14 pages at 1280/768/320px, inspected additional 390px/screenshots, verified menu/skip links, selected real dates in native calendars and obtained availability results. Verified native media selection/reorder/Page save and HTTPS Tailscale gallery operation without console errors or loopback URLs.
- Passed gallery permission/data tests, catalogue/bookings/events regressions, public/preview HTTP asset crawls, core/PHP checks and local SMTP. Practical browser verification is complete; no exhaustive accessibility certification is claimed. Next: chantier 9 deployment.
