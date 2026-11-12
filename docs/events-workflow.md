# Events and journal — chantier 7

## Editing and scheduling

**Maison Rivage → Events** opens native WordPress event editing. Edit title, excerpt, featured image and block content, with revisions. Events have public URLs under `/events/{slug}/`. Only administrators can manage event content or schedules. The journal remains standard **Posts**, with categories, excerpts, images, archive pagination and related stories from the same category.

The event editor's **Schedule & Venue** panel links to a dedicated screen. Save start/end times in Madagascar time (UTC+3), select Hall or Rivage Suite, and select a physical suite when applicable. Saving uses a nonce, administrator capability and optimistic revision check. A stale tab must reload rather than silently overwrite someone else's schedule.

Editorial publishing and operational scheduling are deliberately separate:

- Published + scheduled events appear in the catalogue and home teaser.
- Unscheduled published stories show “Dates to be announced”.
- Draft/private event stories stay out of public listings, while their scheduled suite occupancy remains effective. Unpublishing an announcement does not cancel the hotel operation.
- **Cancel Event & Release Suite** removes occupancy and removes the event from active listings. A published detail page retains its cancellation label and previous dates.
- Trashing/deleting archives the schedule and releases occupation. A locked tombstone prevents a concurrent save from reviving it during removal. Restoring a story leaves the schedule cancelled; reschedule explicitly.

The administration Overview lists the next five scheduled events, including nonpublic operational events. The existing 14-night availability calendar links occupied nights to event schedules. Its columns describe overnight availability, not a minute-by-minute day planner.

## Occupancy rules

The user approved a one-hour preparation buffer and one-hour reset buffer for suite events. Hall events create no room occupation. Only active private-event-eligible Rivage Suites can be selected. No event capacity is sold or registered; all dates and attendance content are fictional.

Times are strictly validated and stored as UTC. As an implementation default, an event must have positive duration and last at most 24 hours; past dates are allowed for editorial archives. Occupancy uses the complete buffered interval, including when it crosses midnight. Exact touching boundaries are allowed: after an 11:00 checkout, a 12:00 event has a preparation interval starting at 11:00 and can fit; an 11:59 event cannot.

The `rivage_events` InnoDB table stores the operational schedule (one row per event post), status and revision. Room occupations use the existing shared `rivage_occupancy` table. A save locks the event row, then all old/new room locks in numeric order, rechecks bookings and other events, and commits schedule replacement and occupancy together. Conflict or storage failure rolls back the complete edit, preserving the old room and times. Bookings use the same physical-room lock and see event occupations.

Hall schedules may overlap: they do not reserve exclusive hall inventory in this V1. Event conflict feedback is an immediate administrator notice; there are no attendee accounts, ticket sales, guest mailing lists or automatic event announcement emails.

## Public pages and demo content

`[rivage_events]` lists upcoming/current events ordered by start time. `[rivage_events period="past"]` lists past events newest first. Each list has its own pagination, up to 12 cards per page. `[rivage_events limit="2"]` is the home teaser. Cancelled, archived and unpublished events are excluded. Detail pages show local date/time, general venue, status and fictional-event disclosure; physical room identifiers and booking data remain private.

Setup creates four event stories (three upcoming, one past) and two additional journal stories, bringing the seeded journal to five. Dates are relative to the first setup only. Rerunning setup preserves edited stories, schedules, cancellations and dates. It does not move past demo events into the future. Review dates before producing portfolio screenshots or deploying.

The events/home migration updates only untouched starter content, saves a backup in `_rivage_pre_events_content`, and leaves customized pages alone. If skipped, insert the appropriate shortcode manually. Existing AI concept imagery is reused; no new generated views were introduced.

## Verification

```bash
./bin/dev check
./bin/dev wp eval-file /workspace/scripts/tests/events.php
./bin/dev wp eval-file /workspace/scripts/tests/bookings.php
python3 scripts/tests/event-booking-race.py
```

Event tests cover permissions, strict dates, suite eligibility, time buffers, stale revisions, event/event conflicts, event/booking conflicts in both directions, rollback of room/time edits, exact checkout boundaries, cancellation, trash/restore and unpublished-story privacy. The race test runs independent booking-confirmation and event-scheduling processes competing for the same suite and asserts one success, one conflict and one occupation. Fixtures are local and clean themselves up.

Browser verification covers native block editing, schedule save and success notice, event occupancy in the room calendar, public upcoming/past lists, event detail and related journal stories. The event list was checked at 320px with no horizontal overflow.
