---
tags:
  - cms2/decision
status: accepted
created: 2026-10-10
updated: 2026-10-10
decided: 2026-10-09
site: hvg
deciders:
  - Paddy de Burca
---

# 0022: hivelog.eu (hvg) — public demo site: design and implementation

## Status

accepted — implemented and live in production since 2026-10-09. One part,
the iCloud SMTP mail transport, is merged but not yet proven in production
(see [[0071-hvg-site-mail-delivery]]).

## Context

HiveLog (the beekeeping module, own repo `deburca/hivelog`) and the Viculum
iOS app need a public face. The requirements as stated:

- a **landing page**, built with Drupal Canvas;
- a **working demo** of the HiveLog module that anyone can try;
- later, **renting** use of the app, and **marketing hive sensors**.

For the demo, visitors choose their own username and password. Each account
is placed in a "Demo" group and a "HiveLog" group that may only use the
HiveLog module, and demo accounts with their content are deleted after
seven days (the period is to be revised).

The platform already runs three sites (`vdg`, `kbg`, `shh`) as a Drupal CMS
multisite ([[0001-multisite-architecture]],
[[0002-drupal-cms-as-base-platform]]) with Canvas for page building
([[0003-canvas-for-page-building]]) and config export as the source of
truth ([[0020-shh-config-export-strategy]]). hvg has to fit that model and be
deployable to the shared OVH host without special handling.

## Decision

1. **A fourth multisite, `hvg`**, for `hivelog.eu` (and `www.`, plus
   `hivelog.ddev.site` locally). Own database, config store
   `config/hvg/sync` (git-tracked), private files `../private/hvg`.
   `hvg-*` Makefile targets mirror the other sites; hvg is deliberately
   **not** in `all-push` / `all-pull` until it exists on every server.

2. **Theme: Beeswax** (`deburca/beeswax`, a Mercury-derived Canvas theme with
   HiveLog integration), installed from Composer rather than written as a
   site-specific theme under `web/themes/custom` as for the other sites
   ([[0007-site-specific-custom-themes]]). Theme fixes are released from the
   Beeswax repo and shipped by updating `composer.lock`.

3. **HiveLog and its dependencies** (geofield, leaflet) are installed as
   ordinary Composer packages and enabled on hvg only.

4. **The landing page is a Canvas page, seeded by code.** `canvas_page`
   entities are content, so config export never carries them. The module
   `hvg_landing` holds a PHP builder (`LandingPage`) that creates the page at
   `/home` from Beeswax components and ships the screenshots, logo and a
   small **carousel** component. Seeding runs from a `ConfigEvents::IMPORT`
   subscriber, once, as the superuser, logging and retrying rather than
   failing the import; an idempotent deploy hook (`hvg_landing.deploy.php`)
   covers anything the subscriber missed and later reseeds. After the first
   seed the page is edited in Canvas, which is the source of truth.
   The carousel is a module-provided Single Directory Component (CSS
   scroll-snap plus a little JavaScript for buttons, dots and arrow keys)
   because Beeswax documents that Canvas code components break its Tailwind
   styling.

5. **Demo accounts, as roles rather than Groups.** The Group module is not
   installed and is not needed. Module `hvg_demo` provides:
   - roles `demo` (a marker with no permissions) and `hivelog_user` (the
     HiveLog "own" permissions only: no "any" and no administer), so visitors
     cannot see each other's data;
   - `/demo/start`: username + password (entered twice, at least 8
     characters), **no email address** collected, honeypot with a time
     check, and a per-IP flood limit (default 5 per hour);
   - login and redirect to `/hivelog`, including on later logins (a hook
     ordered after the Dashboard module's own login redirect);
   - **cron cleanup:** accounts with the `demo` role older than
     `lifetime_days` (default **7**, config `hvg_demo.settings`) are deleted
     together with every entity they own across all 16 HiveLog entity types
     and their uploaded files, because HiveLog has no user-deletion
     handling and core's cancel methods leave dangling owners.

6. **Authenticated role locked down.** Drupal CMS grants `authenticated` the
   administration theme, navigation, dashboard, coffee, contextual links and
   the honeypot/CAPTCHA bypasses. With open sign-up that is wrong, so hvg's
   exported config removes them.

7. **Interest form** (`interest` webform) for hosting and sensors, shown on
   the landing page through the Canvas webform block. Confirmation type is
   **inline**: the default "message" confirmation redirects after submit,
   and inside a Canvas block that redirect is thrown as an exception during
   rendering and replaces the section with an error box. Submissions are
   stored and also emailed to the site address, with Reply-To set to the
   visitor.

8. **Mail stack:** `symfony_mailer_lite` + `mailsystem` (as on vdg/kbg/shh)
   with an **`icloud` SMTP transport** (`smtp.mail.me.com:587`). The
   committed config keeps the default transport `native` and empty
   credentials; production selects `icloud` and supplies the Apple ID and an
   app-specific password from its own `settings.php`. Reason: mail relayed
   by the OVH host used its own bounce domain as envelope sender, had no
   DKIM and the domain has no DMARC record, so iCloud (which hosts
   `info@hivelog.eu`) filed notifications as Junk. iCloud already publishes
   a DKIM key for hivelog.eu, so mail submitted through it is signed with an
   aligned signature.

9. **Deployment is config-first.** A new environment is created with
   `drush site:install --existing-config` on an **empty** database from
   `config/hvg/sync`; routine deploys use `make hvg-pull`
   (`git pull`, `composer install --no-dev`, `updb`, `cim`, `cr`,
   `deploy:hook`). The first-deploy path is rehearsed on a throw-away local
   site before production. `web/sites/hvg/settings.php`, the files directory
   and `private/hvg` live outside git and are set up per server.

## Consequences

### Positive

- The whole site, apart from page content and secrets, is reproducible from
  git: an empty database plus the sync directory yields a working site,
  verified by a rehearsal install and a clean re-export.
- Demo visitors are isolated from each other and from admin features, and
  hold no personal data beyond a chosen username.
- Expiry is automatic and complete (data and files), so the demo does not
  accumulate abandoned data.
- Nothing secret is committed; the mail credential exists only on the
  server.

### Negative

- No email on demo accounts means **no password recovery**; a forgotten
  password means a new demo account.
- Outgoing mail depends on an Apple ID app-specific password held on the
  server and tied to a personal account; if it is revoked mail fails.
- The seeded page does not follow later changes to `LandingPage.php`; content
  changes after launch are made in Canvas and are not in git.
- A new demo account sees the module's seeded 31-entry calendar as many
  "overdue" items, and a Denmark-specific CBR prompt (task
  [[0074-hvg-demo-first-run-experience]]).
- Contrib code is not in git (`web/modules/contrib` is ignored), so a
  production tree can silently be incomplete: the first install failed with
  geofield's backend plugin directory empty.
- hvg's admin hardening must be maintained against future Drupal CMS
  recipe updates that re-grant permissions.

### Neutral

- hvg is a separate site from the other three but shares their Makefile
  pattern, and is excluded from the "all sites" targets for now.
- The Beeswax theme has its own release cycle: shipping a theme fix means
  tagging a release and updating `composer.lock`.

## Alternatives Considered

### Alternative 1: Drupal Group module for the "Demo" and "HiveLog" groups

Rejected for now. Two roles give the same access boundary without a new
contrib dependency, and HiveLog already scopes data by owner. Revisit if
group-level features (shared apiaries, teams) become a requirement,
especially for the rental offering ([[0078-hvg-hosted-hivelog-rental-scoping]]).

### Alternative 2: Core registration form with email verification

Rejected. It collects an email address and needs working mail to complete.
Username + password only keeps the demo friction-free and data-light, at the
cost of password recovery.

### Alternative 3: Seed the landing page only from a deploy hook

Rejected. With `site:install --existing-config` the import runs inside the
install, where deploy hooks do not run, and the module installs before the
Beeswax components exist. A rehearsal install also showed the import runs
as the anonymous user before role permissions exist, so seeding must run as
the superuser. The hook is kept as a safety net and for reseeds.

### Alternative 4: Webform "message" or "page" confirmation

Rejected for the embedded form. A redirect after submit is thrown inside the
Canvas block render and surfaces as a visible render error. Inline
confirmation avoids the redirect.

### Alternative 5: Drupal core's Symfony mailer plugin

Rejected. It is marked experimental and flattens HTML to plain text.
`symfony_mailer_lite` is already the house standard on the other sites and
handles HTML and Webform.

### Alternative 6: Send through Open-Xchange SMTP

Considered. It would need an OX SPF include and an OX DKIM key published at
EuroDNS and relies on an account that may be legacy, since mail for
hivelog.eu is received at iCloud. iCloud needs no DNS changes because its
SPF and DKIM are already in place.

### Alternative 7: Fix the OVH relay with SPF/DKIM/DMARC records only

Not enough on its own: the relay's envelope sender is its own bounce domain,
so SPF cannot align with `hivelog.eu`, and OVH signing for a custom domain
was not confirmed. A DMARC record is still worth adding (task 0071).

### Alternative 8: Copy a development database to production

Rejected. It risks site-UUID and Canvas component-UUID collisions on import
and the deployment tooling's database import step drops all production
tables with no backup. An empty database plus `--existing-config` has none of
these.

## Implementation Notes

| Part | Where |
|---|---|
| Site registration | `web/sites/sites.php`, `.ddev/config.yaml`, `Makefile` (`hvg-*`) |
| Config | `config/hvg/sync` |
| Landing page, carousel, logo, screenshots | `web/modules/custom/hvg_landing/` |
| Demo accounts, roles, cleanup | `web/modules/custom/hvg_demo/` |
| Theme | `deburca/beeswax` (1.0.4 includes fixes found on hvg) |
| Mail transports | `config/hvg/sync/symfony_mailer_lite.*`, `mailsystem.settings.yml` |
| Per-server secrets | `web/sites/hvg/settings.php` (not in git) |

Production `settings.php` additions for mail:

```php
$config['symfony_mailer_lite.settings']['default_transport'] = 'icloud';
$config['symfony_mailer_lite.symfony_mailer_lite_transport.icloud']['configuration']['user'] = '<apple id>';
$config['symfony_mailer_lite.symfony_mailer_lite_transport.icloud']['configuration']['pass'] = '<app-specific password>';
```

Verification done: rehearsal install onto an empty database (site UUID,
theme, seeded page, roles, signup, cleanup, second import a no-op, clean
re-export); browser checks at desktop and mobile width; interest-form email
through DDEV Mailpit, including through the SMTP transport. Not verified:
real delivery to the iCloud inbox from production.

Incidents met in production and what they taught:
- Geofield's `GeofieldBackend/` directory was empty, so HiveLog's tables
  could not be created (`geofield_backend_default` plugin missing); fixed
  with `composer reinstall`. If it recurs, compare file counts against
  dev before suspecting the repo.
- Submitting the form inside the Canvas block showed a render error
  (Decision 7).
- Command-line `sendmail` is refused for the shell user on the host, so mail
  must be tested through the web or, now, the SMTP transport.

## Follow-up tasks

- [[0070-hvg-initial-build-and-launch]] — what shipped (done)
- [[0071-hvg-site-mail-delivery]] — finish and prove the mail fix
- [[0072-hvg-operations-integration]] — drops config, monitoring, backups
- [[0073-hvg-privacy-and-data-protection]] — policy, consent, retention
- [[0074-hvg-demo-first-run-experience]]
- [[0075-hvg-viculum-app-section]]
- [[0076-hvg-seo-and-landing-polish]]
- [[0077-hvg-danish-translation]]
- [[0078-hvg-hosted-hivelog-rental-scoping]]
- [[0079-hvg-hive-sensor-offering]]

## References

- Project: [[hivelog-eu-demo-site]]
- Pull request: https://github.com/deburca/verdigris/pull/6 (merge `4daba6b`),
  later fixes `3cce52c`, `0dbac0a`, `53cee2e`, `73b780e` on `main`
- Related decisions: [[0001-multisite-architecture]],
  [[0003-canvas-for-page-building]], [[0009-webform-for-forms]],
  [[0010-spam-protection-strategy]], [[0020-shh-config-export-strategy]]
