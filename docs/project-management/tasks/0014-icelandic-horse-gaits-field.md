---
type: task
tags: [cms2/task]
status: todo
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-05
updated: 2026-07-05
---
# Task: Add Icelandic horse gaits field; correct sample content

## Description
Stutteri Hestehøj sells **only Icelandic horses** — no other breed. This wasn't
captured when the `horse` product variation type was built in
[[0011-shh-entity-content-type-modeling]], which has two consequences to fix:

1. **Missing field.** Icelandic horses are the classic five-gaited breed: walk,
   trot, and canter/gallop are the baseline three gaits any horse has; whether a
   horse additionally has **tölt** and/or **flying pace** (skeið) is one of the
   most important facts a buyer looks for (it's the difference between a
   "three/four-gaited" and "five-gaited" horse, and directly affects value and
   suitability). There is currently no field for this — `field_discipline`
   (dressage/show jumping/eventing/trail/other) does not capture it and
   shouldn't be overloaded to.
2. **Wrong sample content.** The sample horse product created in 0011 ("Freja —
   Danish Warmblood mare") is factually inconsistent with the business — it
   must be replaced with an Icelandic horse, gaits populated.

## Acceptance criteria
- [ ] New field `field_gaits` on the `commerce_product_variation.horse` bundle:
      multi-value `list_string`, options `walk`, `trot`, `canter_gallop`,
      `tolt`, `flying_pace` (label: "Flying pace (skeið)"). No default value
      (must be explicitly set per horse — do not assume all 5).
      **Apply the `clearCachedFieldDefinitions()` workaround immediately after
      the storage save** (see [[0011-shh-entity-content-type-modeling]]
      "Resolution" — this is now a well-understood, cheap, recurring
      requirement on this BAT/BEE-adjacent Commerce build, not optional).
- [ ] Form + view display components added for `field_gaits` (checkboxes
      widget; a formatter that reads clearly, e.g. comma-separated list)
- [ ] Consider whether `field_breed` should be removed/hidden (constant value,
      arguably redundant for a single-breed catalog) or simply defaulted to
      "Icelandic Horse" and left as-is for potential future-proofing — decide
      and document the choice here, don't leave it silently ambiguous
- [ ] Existing sample product ("Freja — Danish Warmblood mare", SKU
      `HORSE-0001`) replaced with an Icelandic horse: correct breed value,
      gaits populated (e.g. a five-gaited example and, ideally, a second
      sample that is *not* five-gaited, so the field's purpose is visibly
      demonstrated)
- [ ] Verified end to end: product page displays gaits clearly to an
      anonymous/prospective-buyer view (not just in the admin edit form)

## Related
- [[shh-stables-platform]]
- [[shh-stables-platform-model]]
- [[0011-shh-entity-content-type-modeling]]
</content>
