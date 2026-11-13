# Chantier 8 — quality review

Review date: 2026-11-13. Functional and source checks passed locally. **Final browser review remains pending:** the computer-use browser host was unavailable in this session. This report is not an accessibility certification or a measured page-speed score.

## Changes delivered

- Contact is operational through `[rivage_contact]`. Name, email and message go to the administrator mail outbox, with an explicit fictional-demo acknowledgement. No guest auto-reply or reservation is created. The public response says “Message queued”, not “delivered”.
- Validation rejects invalid email/header injection and oversized/missing fields. Forms use nonces, signed two-hour submission keys, a honeypot and five attempts per IP/hour. Unique outbox keys make concurrent retries idempotent. The HTTP test caught and fixed a collision between a form field called `name` and WordPress's public query variables; the form now uses `guest_name`.
- Contact delivery uses the existing transport retries and administrator Notifications screen. Body content is cleared after SMTP acceptance; contact queue records expire after 24 hours. Test-mailbox copies have a separate lifecycle. The shared outbox now has an immutable `created_at` epoch (booking schema version 2), so retry timing cannot accidentally shorten contact retention.
- The privacy page accurately describes the local minute scheduler, contact handling and test mailbox. Migrations preserve customized pages and retain prior starter content in `_rivage_pre_polish_content`.
- Three original PNGs total 8,922,095 bytes; equivalent full-size WebP derivatives total 894,506 bytes: **90.0% smaller by file size**. PNG originals remain intact. Additional 640px/1200px derivatives support untouched seeded media. Replaced/edited attachments keep WordPress's normal rendering. This is an encoding improvement, not new hotel imagery.
- Focus outlines use the dark brand colour on light surfaces, with a contrasting treatment for buttons. Existing reduced-motion styles remain. Calculated brand-text contrast on ivory is 11.0:1 for ink and 4.68:1 for leaf; this does not replace a full rendered contrast audit.

## Checks completed

- Core checksums, PHP syntax, active theme/plugin and local SMTP smoke check.
- Catalogue, reservation, event and contact integration suites.
- Independent-process booking/booking and booking/event races: one success and one conflict, with one occupancy.
- Anonymous booking HTTP checks: forged nonce, invalid date/capacity/token, cache/referrer headers and private admin barrier.
- Contact HTTP/SMTP: forged nonce rejected, valid submission redirects to an opaque receipt, repeat submission creates one Mailpit message. Only fictional test data was used.
- Read-only public HTTP crawl: 21 linked pages, local image sources/derivatives, one H1 and main landmark per page, image alt attributes present, no visible PHP errors and a genuine 404. This static check does not establish the quality of image descriptions, keyboard focus order, JavaScript behaviour or responsive rendering.
- Repeat setup preserves page-content and event-schedule snapshots.

Commands:

```bash
./bin/dev check
./bin/dev wp eval-file /workspace/scripts/tests/catalogue.php
./bin/dev wp eval-file /workspace/scripts/tests/bookings.php
./bin/dev wp eval-file /workspace/scripts/tests/events.php
./bin/dev wp eval-file /workspace/scripts/tests/contact.php
python3 scripts/tests/booking-race.py
python3 scripts/tests/event-booking-race.py
python3 scripts/tests/booking-http.py
python3 scripts/tests/contact-http.py
python3 scripts/tests/site-http.py
```

Contact HTTP checks leave a disposable captured email in Mailpit and consume the normal submission rate limit. Avoid repeatedly running them against the same local IP within one hour.

## Remaining before closing chantier 8

1. Reopen browser access and inspect home, catalogue and each category, booking/results/errors, contact/success/error, event listing/detail, journal/article, policies and 404 at desktop, 768px, 390px and 320px.
2. Use Tab/Shift+Tab/Enter/Escape to review skip links, mobile navigation, form focus/error feedback and buttons; check zoom and reduced-motion rendering. Recheck console/network errors and rendered contrast.
3. Decide whether to retain the current reference imagery or generate distinct garden/balcony/suite compositions. The optional question is pending; no new imagery has been generated or silently approved.
4. Review fictional content and event dates before portfolio screenshots. Hosting, HTTPS, production SMTP, cron and backups belong to chantier 9.
