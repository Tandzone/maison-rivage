# Maison Rivage — Site architecture

Status: approved architecture. Chantier 2 complete. Implements the approved V1 scope; does not add a customer portal, payments or event registration.

## Navigation

Header: logo linking to Home; Rooms & Suites; Experiences & Events; About; Journal; Contact. Primary action: **Book Your Stay**, leading to the availability form. That action starts a request journey; all submission and status messages explicitly explain manual confirmation.

Mobile: same links in a collapsible menu and an accessible booking action.

Footer: contact/location, room and event links, Booking Policy, Privacy Policy and a clear fictional portfolio demo notice. Do not invent real contact details or map coordinates for the fictional hotel.

## Public sitemap and page responsibilities

| Page/template | Proposed path | Main sections | Main action |
|---|---|---|---|
| Home | `/` | Hero, date/guest search, hotel introduction, 3 room categories, coastal setting, upcoming events, recent journal articles | Check Availability |
| Rooms & Suites | `/rooms/` | Category cards, capacities, key amenities, indicative nightly rates; distinguish room category from physical inventory | View Room / Check Availability |
| Room category | `/rooms/{category}/` | Gallery, description, capacity, amenities, nightly rate, policies summary, category-specific date search | Check Availability |
| Booking | `/booking/` | Date/guest search, matching categories, category selection, guest details, estimated total, request review | Request a Reservation |
| Request submitted | `/booking/request-received/` | Check-your-email instruction, explanation that no room is held, next steps | Return to Rooms |
| Email verification | `/booking/verify/` | Valid, already-used, expired and invalid-link states; success explains pending hotel review | Continue Browsing |
| Experiences & Events | `/events/` | Upcoming events, date/location labels, separate past-events section | View Event |
| Event details | `/events/{event}/` | Image, description, date/time, venue and attendance information; no booking widget for event tickets | Explore Rooms |
| About | `/about/` | Hotel concept, materials, setting, hospitality and services | Explore Rooms |
| Journal | `/journal/` | Article cards, categories and pagination when needed | Read Story |
| Article | `/journal/{article}/` | Title, image, publication date, content and related articles | Read Related Story |
| Contact & Location | `/contact/` | Fictional destination description, arrival guidance, demo contact information and contact form | Send Message |
| Booking Policy | `/booking-policy/` | Request/confirmation distinction, arrival/departure rules, cancellation process, no online payment | Check Availability |
| Privacy Policy | `/privacy-policy/` | Explain actual form data handling, recipients and retention once implementation is defined | — |
| Not found | Unmatched URL | Helpful message and routes back to rooms/home | Return Home |

Six physical rooms do not create six near-identical public pages: use three category pages. Physical identifiers remain administrative. Do not expose reservation records or guest details through public archives or predictable links.

## Booking journey

1. From Home, a category page or the header, enter check-in, check-out, adults and children.
2. Validate dates and capacity; list categories with at least one physical room available for the entire stay. Preserve valid dates and guest counts between pages.
3. Select a category. Show nightly rate, number of nights and an estimated stay total; explain exactly what it includes once pricing details are set.
4. Enter name and email, with an optional message. Further contact fields are to be decided based on operational need.
5. Review the request and accept the booking policy. Submit with **Request a Reservation**, explicitly stating that no payment or confirmed booking occurs here.
6. Show **Check your email**. Verification establishes email ownership; it does not confirm availability or hold a room.
7. After verification, show **Your request is awaiting confirmation**. Notify the hotel according to the detailed notification workflow.
8. Administrator assigns and confirms an available physical room. Email the guest the outcome and stay details. No customer account or ongoing public booking portal in V1.

### Essential states

- Missing/invalid dates, invalid email, capacity mismatch and past arrival.
- No availability: preserve search values and offer changing dates or category.
- Availability changed since search: explain and offer another search; recheck again at confirmation regardless of earlier results.
- Submitting, successful submission and recoverable submission failure; prevent accidental duplicate requests.
- Verification link valid, expired, invalid or already used; implemented with 30-minute expiry and token rotation on resend in chantier 6.
- Pending review, confirmed, declined and cancelled outcomes. Implemented statuses: unverified, pending, confirmed, declined, cancelled. See `booking-workflow.md`.

## Administration architecture

Proposed custom menu: **Maison Rivage**. Use native WordPress administration conventions and reusable controls rather than a separate application.

| Screen | Responsibility |
|---|---|
| Overview | Pending verified requests, conflicts needing attention, upcoming arrivals and events |
| Bookings | Filter by status, date and category; open a request, review details, assign a room, confirm, decline or cancel |
| Availability | Calendar of physical rooms with confirmed stays and room-occupying events; clearly distinguish non-blocking pending requests |
| Room Types | Manage the three public category pages, amenities, capacities and rates |
| Rooms | Manage six physical identifiers, their categories and operational availability rules |
| Events | Manage public listing details, location and room occupation when applicable |
| Notifications | Review actionable notices and their linked booking/event records; read/unread behavior to be specified |
| Settings | Operational contact/notification settings and agreed policy values; avoid unnecessary customization |

Journal remains under WordPress **Posts**; standard pages remain under **Pages**. Detailed storage, permissions and notification delivery architecture are deferred to implementation design.

## Administrator confirmation journey

1. Open a verified request and inspect category, dates, party size and warnings.
2. View eligible rooms; choose one available for the complete stay.
3. Click **Confirm Booking**. Recheck all occupancy conflicts as part of a safe simultaneous-operation strategy.
4. On success, reserve the physical room and trigger confirmation notifications.
5. On conflict, retain the pending request, identify the blocker and offer another eligible room. Never send a confirmation email for this failed attempt.
6. Refresh warnings on other affected requests. Overlap alone is not a category-level conflict if another physical room can accommodate the stay.
7. Cancellation releases that booking's occupancy and triggers the relevant notifications.

## Events journey

Public visitors browse events and view details; no event registration or automatic suite booking follows from viewing an event. Only public editorial information is published, never private guest or booking data.

Administrators select Hall or a specific Rivage Suite. A room-occupying event must be checked against existing occupancy and block its defined time interval. Editing event dates or venue also needs conflict checks. Exact time buffers and stay/event interaction rules will be designed in chantier 6/7.

## Acceptance criteria for chantier 2

- User accepts the proposed sitemap, navigation and category-only public room pages.
- Public reservation states distinguish requests from confirmed bookings.
- Booking and administrative journeys support physical room assignment and conflict handling.
- Page responsibilities fit the approved V1 scope.
- Open implementation details are recorded without prematurely choosing storage or adding features.

## Review outcome

The user accepted the architecture after clarifying confirmation behavior: the hotel confirms in administration; the system checks availability, records the confirmed booking and blocks dates, then sends the guest a confirmation email. Email verification is a separate earlier step and does not confirm a booking. Guest follow-up is by email/contact without an account or public booking portal in V1.
