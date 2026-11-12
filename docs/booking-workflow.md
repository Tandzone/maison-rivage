# Reservation workflow — chantier 6

This is a fictional, nonbinding demonstration. No payment or real accommodation is offered. Website forms and administration use English.

## Visitor and administrator journey

1. Search by dates and party size. Categories show a server-calculated nightly rate and total. At least one adult is required; category adult, child and total capacity limits apply.
2. Submit name, email and optional message. An unverified request is created without holding a room.
3. Open the verification email and explicitly press Verify Email. A GET alone never consumes the link. The token expires after 30 minutes; resend rotates it. A verified request becomes pending and notifies the WordPress administrator email.
4. In **Maison Rivage → Bookings**, assign an available physical room and confirm, or decline. Confirmation rechecks availability and capacity and records occupancy before queuing the guest email.
5. Admin cancellation releases occupancy and queues a guest email. Pending requests do not block rooms. Repeating a successful transition does not create another notification.

**Availability** displays 14 nights for each physical room. **Notifications** displays delivery status and supports retrying failed mail. Admin capability and nonces protect mutations; reservations are not public posts or REST resources.

## Dates and pricing

Arrival is 14:00 and departure 11:00 in `Indian/Antananarivo`; occupancy is stored as UTC timestamps. Overlap uses `existing_start < new_end AND existing_end > new_start`. Adjacent stays are allowed. Implementation defaults limit stays to 1–30 nights and arrival within one year.

Rates are snapshotted in integer cents when requested. A later catalogue change does not alter an existing quote. Totals are rate × nights, with no extra charges or online payment in this demo.

## Concurrent confirmations and future events

The plugin creates five InnoDB tables: bookings, room_locks, occupancy, mail and rate_limits (with the WordPress prefix). Confirmation locks the booking row and the shared physical-room lock inside a transaction, rechecks occupancy and inserts the confirmed occupation before committing. Two simultaneous confirmations for the same room cannot both succeed. Conflicts leave the request pending and alert the administrator.

Future event writers must acquire the same room lock and check the same occupancy intervals in a transaction. Occupancy already supports a source type and source ID for events; event administration belongs to chantier 7. A room's operational status is separate from date availability.

## Mail delivery and scheduled work

State changes and outbox insertion commit together. SMTP failure does not undo a confirmed reservation. Dispatch claims queued rows, tracks attempts, recovers abandoned claims after five minutes, and retries with exponential delays up to five attempts. Failed items can be retried by an administrator. Expired or replaced verification emails become obsolete.

Delivery is **at least once**: a process crash after SMTP acceptance but before recording success can cause a duplicate. A successful `wp_mail` means SMTP accepted the message, not proof that a real recipient received it.

Locally all messages go to Mailpit at http://localhost:8025. The Docker scheduler runs due WordPress cron tasks every minute without visitors. Visitor-triggered WP-Cron is disabled locally. Production must configure a real scheduled cron job, SMTP provider, sender identity and HTTPS in chantier 9; the local mail MU plugin does not route production mail.

## Public demo protections and retention

Forms use nonces, a honeypot, signed two-hour idempotency keys and server-side validation. Submission rate limits use salted hashed buckets rather than raw IP storage: five requests per IP/hour, 30 verification attempts per IP/hour, three resends per email/30 minutes. These controls reduce abuse but are not a complete public-demo anti-spam service.

Only token hashes are stored on booking records. Unsent outbox bodies temporarily contain verification links; sent bodies are cleared. Booking responses are uncached, noindex and use a no-referrer policy. The receipt uses an opaque temporary identifier.

Scheduled cleanup removes unverified requests after 24 hours and other booking records 90 days after departure, including related outbox and occupancy. This is a demo retention default, disclosed in the privacy page; production policy needs review for the chosen operation.

## Verification

```bash
./bin/dev check
./bin/dev wp eval-file /workspace/scripts/tests/catalogue.php
./bin/dev wp eval-file /workspace/scripts/tests/bookings.php
python3 scripts/tests/booking-race.py
python3 scripts/tests/booking-http.py
```

Integration tests cover token expiry/rotation, guest limits, immutable quotes, conflicts, adjacent stays, event occupation, cancellation and mail failure/retry. The race test uses two independent processes attempting to confirm the same physical room. HTTP tests cover anonymous validation, forged nonces, privacy headers and admin access barriers. Fixtures clean themselves up and require a local environment.

The browser journey was also exercised through request, verification, confirmation, calendar inspection and cancellation, with the corresponding messages captured in Mailpit. Responsive results were checked at 390px and 320px.

Setup upgrades only untouched booking-related starter pages; customized content is preserved. If a customized booking page is skipped, insert `[rivage_booking]` manually. The verification and receipt child pages use `[rivage_verify]` and `[rivage_received]`; home search uses `[rivage_search]`.
