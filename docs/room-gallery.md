# Room galleries — chantier 8

The user authorized three photographs per category (bedroom/lounge, outdoor space, ensuite bathroom), fullscreen viewing and administrator editing. Implementation completed on 2026-11-14.

## Editing in WordPress

Open **Maison Rivage → Room Types**, edit a category Page and expand the editor's **Meta Boxes** pane. **Maison Rivage — Room Gallery** opens the native Media Library, preselects existing photographs and lets an administrator choose up to 12 images. Use the named Move up/Move down/Remove buttons to order them, then save the Page. Edit alt text and captions directly in Media Library. The **Featured image** separately controls the catalogue card. The second gallery photo also illustrates the outdoor section of the seeded layout.

Gallery attachment IDs are private `_rivage_gallery` metadata. Saves check a page-specific nonce, `manage_options`, object edit permission and autosave/revision guards. IDs are deduplicated, capped at 12 and filtered to nontrashed image attachments. No external gallery service or page-builder dependency is required.

`[rivage_room_gallery]` renders the main photograph, thumbnails and captions. `[rivage_room_outdoor]` renders the secondary outdoor photograph. The plugin provides fallback styling independently of the child theme. Empty galleries show an honest empty state.

## Viewer behaviour

- Thumbnails select the main photo; clicking the main photo opens a native modal dialog.
- Enter/Space activate gallery controls; Previous/Next and Left/Right change images with wrapping.
- Escape or Close dismisses the viewer and returns focus to its opener.
- Tab/Shift+Tab remain within the three viewer controls. Other page content is inert through the modal dialog.
- Horizontal pointer swipes/drags change photographs; vertical movement and pinch zoom are retained. No autoplay or animation is added.
- Without supported JavaScript/dialog APIs, image links remain usable. Alt text, captions, image counts and the current-thumbnail indication come from the media records.

## Image sources and delivery

All nine live photographs are WebP images imported into Media Library with WordPress-generated responsive sizes. Raw generated PNGs are preserved under `assets/concepts/`. The original master reference remains unchanged.

| Category | Bedroom source | Outdoor source | Bathroom source |
|---|---|---|---|
| Garden Room | `assets/concepts/maison-rivage-garden-room-v1.png` | `assets/concepts/maison-rivage-garden-room-terrace-v1.png` | `assets/concepts/maison-rivage-garden-room-bathroom-v1.png` |
| Ocean Balcony Room | `assets/concepts/maison-rivage-ocean-balcony-room-v1.png` | `assets/concepts/maison-rivage-ocean-balcony-room-terrace-v1.png` | `assets/concepts/maison-rivage-ocean-balcony-room-bathroom-v1.png` |
| Rivage Suite | `assets/concepts/maison-rivage-rivage-suite-v1.png` | `assets/concepts/maison-rivage-rivage-suite-terrace-v1.png` | `assets/concepts/maison-rivage-rivage-suite-bathroom-v1.png` |

Serving files are `wp-content/themes/maison-rivage/assets/images/{category}-{bedroom|terrace|bathroom}.webp`. Setup imports them once and preserves subsequent image order, media captions/alt text, deliberately cleared galleries and customized Featured images. Untouched starter room Pages are migrated by content hash, backing up content in `_rivage_pre_gallery_content`; edited Pages are preserved with a warning to insert the shortcodes manually. Repeating setup was checked against category-content/gallery/Featured-image/media-ID snapshots.

Generation used the built-in imagegen tool. Each new image received the actual master reference plus its category bedroom image. Complete prompts/reference paths/output paths are recorded in `assets/concepts/gallery-generation-v1.json`; the earlier bedroom prompts are in `assets/concepts/room-category-generation-v1.json`. The agent reviewed shared timber, stone, woven textures, muted-green palette and category differentiation before integration. These are fictional spatial concepts, not measured architectural reconstructions.

## Verification

```bash
./bin/dev check
./bin/dev wp eval-file /workspace/scripts/tests/gallery.php
python3 scripts/tests/site-http.py
python3 scripts/tests/preview-http.py https://your-machine.your-tailnet.ts.net
```

Gallery integration tests check all three categories, nine images/alt texts, Featured images, invalid IDs, deduplication, nonce/capability protection and persisted ordering. Catalogue, booking and event regression suites also pass.

Browser checks covered selection/reordering/save in the native editor, desktop and mobile gallery display, thumbnail selection, Enter/Space opening, next/previous/arrow navigation, Tab/Shift+Tab containment, Escape and returned focus, horizontal pointer dragging, the mobile navigation menu and skip link. Calendar clicks opened the native popup; actual dates were selected with arrows/Enter on desktop and at 390px, followed by a successful availability search. HTTPS Tailscale gallery interactions worked without loopback asset references or console errors.

The layout matrix covered 14 public pages at 1280×900, 768×900 and 320×900 (42 checks), with no horizontal overflow, missing main headings or broken visible images. Additional 390px screenshots were inspected. These are browser viewport tests, not physical-device or exhaustive assistive-technology certification.
