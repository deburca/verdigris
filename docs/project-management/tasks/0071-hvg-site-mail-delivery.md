---
type: task
tags: [cms2/task]
status: in-progress
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
- [ ] App-specific password created for the Apple ID that owns
      `info@hivelog.eu`
- [ ] Production `web/sites/hvg/settings.php` sets `default_transport` to
      `icloud` and the user/pass overrides (never committed)
- [ ] `make hvg-pull` run so the two modules and transports are imported
- [ ] An interest-form submission arrives in the inbox (not Junk) with
      `dkim=pass` in its headers
- [ ] Decide on a `_dmarc` TXT record (`p=none` first) and add it at
      EuroDNS if wanted
- [ ] Note the iCloud account that holds the credential somewhere other
      than the repo (who must renew it if it is revoked)

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
- Command-line `sendmail` is refused for the production shell user, so
  test mail through the web (interest form) or, after this change,
  through drush.
