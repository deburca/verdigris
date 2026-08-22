---
type: task
tags: [cms2/task]
status: done
priority: low
site: shh
project: "[[shh-stables-platform]]"
created: 2026-08-22
updated: 2026-08-22
---
# Task: News/blog section

## Description
[[0051-homepage-content-plan]] considered and explicitly deferred a
News/blog section: "a homepage carrying three-year-old 'latest news'
is worse than none — revisit only if someone will own it." Recorded
here so it's tracked in the backlog rather than forgotten, consistent
with this project's other deliberately-deferred items (e.g.
[[0041-gallery-lightbox]] before it was approved).

Approved 2026-08-22: confirmed someone will own ongoing content.

## Resolution (2026-08-22)
New content type **News** (`node.type.news`), fields reused from the
site's existing `page` bundle rather than introducing a parallel
convention: `field_description` (required, short — doubles as the
card teaser and the real, author-written meta description),
`field_featured_image` (single image, reusing the shared
`field.storage.node.field_featured_image`), `field_content`
(`text_long`, `content_format` — the post body). Deliberately no
`content_moderation`/`scheduler` third-party settings `page` carries —
out of scope, keeps this closer to `bookable_facility`'s simpler
model. URLs via a new pathauto pattern, `/news/[node:title]`.

New module `shh_news`, following the exact established pattern from
`shh_horse_catalog`/`shh_facilities_overview`/`shh_feed_catalog`
(task 0051 sections 3–5): `NewsCardBuilder` (query + card, shared
between the listing page and the homepage teaser so they can't drift
apart), `NewsController` (`/news`), `FeaturedNewsBlock`
(`shh_featured_news`, 3 most recent posts, renders nothing when
there's no news — same as the other featured blocks). Meta
description/Open Graph tags on both the listing page and each post
(task 0054's `shh_common_attach_meta_tags()` pattern) — the post page
is the only one of these with a real author-written description to
use directly rather than one computed from structured fields.

**Homepage placement**: inserted as a new section — wrapping
`sdc.hestehoj.section` + `sdc.hestehoj.heading` + the block, matching
sections 3–5's exact structure exactly — directly before the closing
CTA, via a careful in-place edit of the Canvas page's `components`
field (content, not config; there was no existing script for this in
the repo to reuse, so it was built from first inspection of the field
structure). Verified byte-for-byte: all 60 pre-existing components
preserved in original order before and after, only the 3 new rows
added. Confirmed one existing platform behavior isn't news-specific:
the wrapping section's heading renders even when the block itself is
empty (same true of horses/facilities/feed — the section's `header_slot`
isn't conditional on `main_slot`'s content), not a regression
introduced here.

Sitemap ([[0058-xml-sitemap-missing]]): `node.news` bundle indexing
enabled, `/news` added as a custom link.

One real seed post left in place (matching the site's existing sample-content
pattern, e.g. task 0039's placeholder photos) — the client should
replace or remove it, not treat it as real content.

Verified over real HTTP: listing page, individual post page (meta
tags, content), homepage teaser section (card, "See all news" link),
and the sitemap. Config exported.

## Acceptance criteria
- [x] Client confirmed someone will own ongoing content
- [x] Content type/module approach decided (plain node type, not
      Canvas ContentTemplate — matches decision 0019/0030's site-wide
      pattern) and built
- [x] Homepage teaser section following the pattern of sections 3–5
- [x] Verified over real HTTP; config exported

## Related
- [[shh-stables-platform]]
- [[0051-homepage-content-plan]] — where this was deferred
