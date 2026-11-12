# Maison Rivage — Content and administration

Room catalogue from chantier 5, extended with operational reservations in chantier 6; see [booking workflow](booking-workflow.md). Event management belongs to chantier 7.

## Editorial categories and private physical rooms

Room types are the three existing WordPress Pages under `/rooms/`. This preserves the approved URLs, block layouts and revision history. The plugin marks them with `_rivage_room_type`; there is no duplicate category post type with a second public URL.

- Page title: category name, also used in listings and the category page's native Post Title block.
- Page excerpt: short listing description.
- Featured image: photograph used in room collections; detailed galleries remain editable page blocks.
- Block content: editorial description, layout and gallery.
- Hotel Details meta box: capacity, adult/child maxima, outdoor space and optional nightly rate.

Physical rooms use the nonpublic `rivage_room` post type. They have internal identifiers, a category page ID and `active` / `out_of_service` operational status. They have no public permalink, archive or REST endpoint. A published category and valid assignment are required for a room to count as active inventory. Operational status is not date-based availability.

## Seeded inventory and rates

| Category | Internal identifiers | Total guests | Adult / child maxima | Nightly EUR |
|---|---|---|---|---|
| Garden Room | Garden 01, Garden 02 | 2 | 2 / 2, subject to total capacity | 95 |
| Ocean Balcony Room | Ocean 01, Ocean 02 | 2 | 2 / 2, subject to total capacity | 135 |
| Rivage Suite | Suite 01, Suite 02 | 3 | 2 / 1 | 195 |

Adult and child limits are separate maxima, not a sum that can exceed the total. Child-age rules and minimum adult requirements are not finalized; define them before implementing the booking flow. Private room-event eligibility is marked only on Rivage Suite.

Rates are stored as integer cents, parsed from decimal input without floating-point conversion. Blank means hidden, not zero/free. Negative, zero, excessive-precision or malformed amounts are rejected. Prices are demo rates; tax presentation and final booking-policy details remain to be specified before booking calculations.

## Administration

**Maison Rivage → Overview** shows category, physical-room and active-room counts. **Room Types** links to the public pages and hotel fields. **Rooms** provides native WordPress editing, columns and filters for category/operational status. Creation and changes to hotel details are restricted to administrators through `manage_options`, with nonce and object-capability checks. Ordinary WordPress editorial permissions continue to govern page/article editing.

Invalid custom-field submissions preserve previously saved hotel fields and show an error; page editorial changes may still save independently. New physical records without valid configuration do not become active inventory.

## Frontend integration

- `[rivage_rooms layout="cards"]`: homepage collection.
- `[rivage_rooms layout="catalogue"]`: full category listing.
- `[rivage_room_facts]`: current category facts, with optional `type="ocean-balcony-room"` for explicit use elsewhere.

The plugin queries published categories and escapes output. Its collection template is readable without the child theme; a theme can override it with `hotel/room-collection.php`. Styling stays in the theme. Dynamic collections reflect category edits rather than duplicated hardcoded prices.

The native Query block supplies recent journal articles on Home. The Journal uses standard Posts and categories, with article URLs under `/journal/{slug}/`. Three seeded stories describe the fictional architecture, hall and recital concept; none announces a scheduled event. Media are imported once into the WordPress library, preserving alt text and normal responsive image delivery.

## Setup and preservation

`seed-hotel.php` creates missing demo records and preserves existing edits. Seed markers avoid duplicates and do not resurrect trashed room/article records. The V2-content migration transforms only recognized untouched preview bodies; otherwise it leaves the page intact and reports that dynamic blocks can be inserted manually. Previous V2 bodies are retained in `_rivage_design_v2_content` metadata. Fresh installations already use the dynamic patterns.

## Verification

Run `./bin/dev wp eval-file /workspace/scripts/tests/catalogue.php` locally. It checks price/capacity validation, save authorization/nonces, rejection of partial invalid changes, operational fields, dynamic rendering/escaping and inventory REST privacy. Temporary test mutations are restored. `./bin/dev check` also covers PHP syntax, core integrity and SMTP.
