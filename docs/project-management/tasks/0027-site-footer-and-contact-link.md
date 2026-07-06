---
type: task
tags: [cms2/task]
status: backlog
priority: high
site: shh
project: "[[shh-stables-platform]]"
created: 2026-07-06
updated: 2026-07-06
---
# Task: Site footer with a "Contact us" link

## Description
Found via [[shh-account-access-gap-analysis]]: no footer renders
anywhere on the site. A `footer` menu already exists in config
("Privacy policy", "My privacy settings") but — the same gap
`shh_main_navigation` found and fixed for the `main` menu in
[[0019-horse-catalog-page]] — nothing has ever placed a block to
display it. This also means the pre-existing `/form/contact` webform
(decision 0009) is never linked from anywhere on the site at all,
leaving a visitor with no way to ask staff a question, including —
critically — a prospective rider turned away at the account/login wall
found in [[0026-rider-account-access-policy]].

## Acceptance criteria
- [ ] Place a "Footer" system menu block (or equivalent) in the
      hestehoj theme's `footer` region, mirroring
      `shh_main_navigation`'s approach for the `header` region
- [ ] Add a "Contact us" link (to `/form/contact`) to the `footer` menu
- [ ] Verify over real HTTP that the footer — and the contact link —
      actually render on a real page, not just exist in menu config

## Related
- [[shh-stables-platform]]
- [[shh-account-access-gap-analysis]]
- [[0019-horse-catalog-page]]
- [[0026-rider-account-access-policy]]
- [[0009-webform-for-forms]]
</content>
