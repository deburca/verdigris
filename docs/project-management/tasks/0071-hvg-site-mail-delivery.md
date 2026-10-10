---
type: task
tags: [cms2/task]
status: review
priority: high
site: hvg
project: "[[hivelog-eu-demo-site]]"
created: 2026-10-10
updated: 2026-10-10
---
# Task: Make hvg site mail reach the inbox (iCloud SMTP)

## Description
Notifications sent from the OVH web host as `info@hivelog.eu` to
`info@hivelog.eu` land in iCloud Junk: the relay uses its own bounce domain
as envelope sender (SPF passes but is not aligned with `From`), adds no
DKIM signature, and hivelog.eu has no DMARC record. Decision and test
evidence: [[0022-hivelog-eu-demo-site-architecture]] (mail section). The
code is merged (`symfony_mailer_lite` plus an `icloud` SMTP transport with
empty credentials); what remains is production configuration and proof.

## Acceptance criteria
- [x] App-specific password created for the Apple ID that owns
      `info@hivelog.eu`
- [x] Production `web/sites/hvg/settings.php` sets `default_transport` to
      `icloud` and the user/pass overrides (never committed)
- [x] `make hvg-pull` run so the two modules and transports are imported
- [x] An interest-form submission arrives in the inbox (not Junk) with
      `dkim=pass` in its headers (verified 2026-10-10, see Result)
- [x] `_dmarc` TXT record `v=DMARC1; p=none;` added at EuroDNS
- [ ] Note the iCloud account that holds the credential somewhere other
      than the repo (who must renew it if it is revoked)

## Result (2026-10-10)
Two production tests of the interest-form notification, both sent through
the iCloud SMTP transport:

| | Before DMARC record | After DMARC record |
|---|---|---|
| Folder | Junk | **Inbox** |
| DKIM | pass, `d=hivelog.eu` (aligned) | pass |
| DMARC | none (no policy) | **pass** |
| SPF | softfail | softfail |
| iCloud spam score | 4.43 | 2.43 |

The SPF softfail is an artefact: iCloud checks SPF on its own internal
relay address (`100.x`) when an iCloud-hosted domain mails itself, and the
standard `include:icloud.com` record is the one iCloud documents for custom
domains. It is not worth changing, because DMARC passes on the aligned DKIM
signature alone.

Kept in `review` only for the last criterion, which is housekeeping.

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
- Command-line `sendmail` is refused for the production shell user, so
  test mail through the web (interest form) or, after this change,
  through drush.
