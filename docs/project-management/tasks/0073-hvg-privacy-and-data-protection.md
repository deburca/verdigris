---
type: task
tags: [cms2/task]
status: todo
priority: high
site: hvg
project: "[[hivelog-eu-demo-site]]"
created: 2026-10-10
updated: 2026-10-10
---
# Task: Privacy policy, consent and retention for hvg

## Description
hvg collects personal data: interest-form submissions (name, email, hive
count, message) stored in Drupal and emailed to `info@hivelog.eu`, and demo
accounts (username, password hash, and any apiary or inspection data a
visitor enters, which can include locations). The shh site already has a
privacy policy (`PRIVACY POLICY.md`, written for Stutteri Hestehøj); hvg has
none, and the interest form's consent checkbox points at no policy.

## Acceptance criteria
- [ ] Privacy policy page for hivelog.eu covering both data sets, who the
      controller is, retention and how to request deletion
- [ ] Link the policy from the footer and from the interest form's consent
      text
- [ ] Decide cookie/consent handling (the Klaro module is installed; check
      what the site actually sets)
- [ ] Retention for interest submissions (they currently never expire) and
      a documented process for deleting one on request
- [ ] Confirm demo-account deletion also removes uploaded files and is
      described in the policy (cron cleanup is built; see ADR)
- [ ] Review whether apiary locations entered in the demo need a warning
      ("do not enter real addresses")

## Related
- Project: [[hivelog-eu-demo-site]]
- Decisions: [[0022-hivelog-eu-demo-site-architecture]]
