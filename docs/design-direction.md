# Maison Rivage — Interface direction

Second local design preview; user review pending. The approved photographic direction remains the reference.

## Subject and purpose

An intimate six-room coastal hotel inspired by Madagascar. The home page should make the setting tangible and lead visitors to compare rooms and plan a stay.

## Tokens

- Lagoon ink `#183E35`: navigation, text, buttons and quiet full-width sections.
- Ivory plaster `#F8F7F2`: page background, drawn from the architecture.
- Sandstone `#E7E1D4`: secondary surfaces and dividers.
- Leaf `#617461`: restrained secondary color.
- White linen `#FFFFFF`: image captions and reverse text.
- Warm charcoal `#282D29`: body copy.

Literata regular/italic for editorial headings; Manrope for readable body and navigation. Fonts are served locally, with licenses retained. No external font requests.

## Initial layout exploration

A full-bleed balcony/bedroom diptych opens the site; the two views express the relationship between the room and its landscape. A deep-green caption strip anchors it. Follow with spacious asymmetric introduction, three room categories, and a hall/event story. Keep cards quiet, avoid decorative counters, ratings or invented testimonials.

```text
wordmark                  navigation                  booking
-------------------------------------------------------------
quiet headline                          short destination note
-------------------------------------------------------------
balcony photograph                  | bedroom photograph
-------------------------------------------------------------
deep-green coastal caption                   explore rooms
-------------------------------------------------------------
introduction heading          hospitality copy + about link
-------------------------------------------------------------
three room categories and honest descriptive labels
-------------------------------------------------------------
hall image                    cultural-events story
-------------------------------------------------------------
journal query / honest empty state
footer + fictional portfolio disclosure
```

The ivory/serif direction is intentional because it matches the approved plaster, textiles and quiet hotel identity. The distinguishing element is the paired inside/outside photograph, rather than unrelated decorative flourishes. Keep motion limited to subtle hover transitions; respect reduced motion.

## Second pass — stronger Maison Rivage identity

Following user feedback about resemblance to the parent theme, retain the approved palette and materials but replace the opening diptych layout with an asymmetric composition: tall balcony photograph on the left, a quiet headline on the right, and an inset bedroom image below it. These are CSS-framed views of the existing reference, not newly generated photographs.

The wordmark gains a simple vector M above two coastal wave lines. Room previews on the home page have a controlled stagger; the hall story uses a deep-green panel. The room catalogue alternates image and copy. Category pages pair a large bedroom view with capacity/outdoor-space facts and a stay action, followed by a terrace story. No unapproved rates or amenities are introduced.

All changes remain in the child theme. The parent theme identity remains discoverable in source; the goal is a distinctive, usable hotel interface rather than hiding its technical foundation. The local preview still requires user review.

## Implementation and review boundaries

Use native blocks, PHP patterns and child theme templates, with scoped styles shared by editor and frontend. Preserve existing editor content when seeding layout. Hotel inventory, booking logic and event data stay in the business plugin in later chantiers. For now, online booking has an explicit unavailable state, rates are omitted until agreed, and no availability is invented.

Hall and recital images can be shown in this local preview for review; they are not approved for production publication yet. Reusing the suite reference for room-category illustration does not establish three different finished room designs. More category-specific imagery will be needed.
