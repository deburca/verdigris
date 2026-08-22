---
type: task
tags: [cms2/task]
status: in-progress
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: Lunge Ring facility page shows a horse's photo, not the facility

## Description
`/lunge-ring`'s hero image (`field_media`) has `alt="Freja grazing in
the home pasture"` — a horse-sale photo, not a picture of the Lunge
Ring arena. This was a client test edit made during
[[0043-bee-price-frequency-form-reset]]'s verification (2026-07-11) and
was explicitly left as "a content decision left to the client" in that
task's closing note. It's still live today (confirmed via the facility
page's hero image carousel, task 0053-era homepage audit, 2026-08-22).

It's actively misleading, not just a placeholder gap: a visitor booking
the Lunge Ring sees a horse in a field, not the arena they're paying
for. It also means the "zero-image degrade" case (`hestehoj:image-carousel`
skipping the hero when `field_media` is empty) hasn't been genuinely
exercised on this facility since 0043, only masked by a wrong photo.

## Progress (2026-08-22)
The wrong photo is removed, on both dev and production: node 5's
`field_media` cleared (was a single reference to media 3, "Freja -
pasture" — confirmed shared with Freja's own product variation 1,
which was left untouched; only the Lunge Ring node's reference was
removed). `/lunge-ring` now shows no hero image at all rather than the
wrong one — reverting to the deliberate zero-image state 0040
originally shipped for this facility, before 0043's test edit
overwrote it. Verified on both environments: `field_media` count 0 on
Lunge Ring, still 3 on Freja.

Content only (no config change), so this had to be applied to
production directly rather than travel through the usual
export/commit/deploy pipeline.

**Still open**: no real Lunge Ring photo exists yet — that part needs
the client.

## Acceptance criteria
- [x] Wrong photo removed from `field_media` on the Lunge Ring node
      (dev and production) — no longer actively misleading
- [ ] Client supplies one or more real photos of the Lunge Ring arena
- [ ] Real photo(s) added to `field_media`
- [ ] Verified over real HTTP: `/lunge-ring`'s hero carousel shows the
      Lunge Ring, not a placeholder/empty state

## Related
- [[shh-stables-platform]]
- [[0043-bee-price-frequency-form-reset]] — where the wrong photo was introduced
- [[0040-facility-images-featured-and-gallery]] — the facility photo model
