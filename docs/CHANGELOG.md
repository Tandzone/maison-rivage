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
