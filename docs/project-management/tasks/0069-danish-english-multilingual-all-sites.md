---
type: task
tags: [cms2/task]
status: in-progress
priority: high
site: vdg, kbg, shh
project:
created: 2026-08-27
updated: 2026-08-27
progress: vdg Phases 1+4 (f997e9b), 2 (0e053c7), 6 (qs 0cb9f7e), 5 (fb1b126), 7 (4861e83), 8 (verification pass — no change) done; next is Phase 9 (rollout)
branch: feature/0069-danish-english-multilingual
---
# Task: Add Danish + English translation to all three sites

## Context
All three sites (`vdg` verdigris.nu, `kbg` kragebaekgaard.dk, `shh`
hestehoj.dk / stutteri-hestehoj.dk) are currently single-language
English builds — none has `language`, `locale`, `content_translation`
or `config_translation` enabled, and `system.site.yml` on each carries
`default_langcode: en`. The client wants each site available in
**Danish and English** from launch.

Config export is the source of truth on every site now (Makefile
`<site>-export` / `<site>-pull`; decision [[0020-shh-config-export-strategy]]
for shh, and `config/{vdg,kbg}/sync` are populated too), so the whole
language setup has to land as reviewed config plus, for interface
strings, committed `.po` files with a deploy step to import them.

### Decisions taken (2026-08-27)
- **Default (fallback) language is per-site:**
  - `vdg` → **English** default, Danish added as the second language.
    Existing content is already English, so this site is purely
    additive.
  - `kbg` → **Danish** default, English second.
  - `shh` → **Danish** default, English second.
- **URL scheme:** prefix the non-default language only. Default
  language served from bare paths; the other language under a path
  prefix:
  - `vdg`: `/` = en, `/da` = Danish
  - `kbg`: `/` = da, `/en` = English
  - `shh`: `/` = da, `/en` = English
  No per-language domains or subdomains — `sites.php` and DNS are
  untouched.

### Open questions (resolve before implementation)
1. **Existing content language on kbg + shh.** Current pages are
   written in English but the site default becomes Danish. Preferred
   approach: leave each existing node/menu-link/block/term as its
   English original translation, author Danish on top as the new
   primary, and rely on language fallback until Danish copy exists.
   Needs the client to supply Danish source copy for kbg + shh
   (homepage, primary nav targets, key landing pages, footer,
   consent text, email templates) — list and hand off separately.
   Do **not** relabel English content as `da`.
2. **Media translation.** Keep `media` entities non-translatable
   (shared file, shared focal point) and translate only alt/caption
   at the field level? Default assumption: yes — confirm.
3. **Canvas translation support** — Phase 3 spike run on vdg (see
   Progress log): `canvas_page` + `components` are translatable, but
   each language keeps its **own** tree with **no layout fallback**,
   so every Canvas page must be rebuilt/copied for Danish. Still to
   confirm: the "Translate" UI tree pre-fill and the
   `translation_sync.inputs` behaviour.
4. **Second-language go-live gate.** Add the language now but keep the
   switcher hidden / language disabled until a minimum content set is
   translated, or launch immediately behind fallback? Assume: ship the
   language, keep the switcher out of the theme until the minimum set
   is done, per site.

## Progress log

### vdg — 2026-08-27 (local only, exported, **not committed**)
Phases 1 and 4 done on the DDEV instance
(`https://verdigris.ddev.site`), config exported to
`config/vdg/sync` (Paddy approved accepting the full export diff).

**Phase 1 — stack + negotiation**
- Enabled `language`, `locale`, `content_translation`,
  `config_translation`.
- Added `da`; **`en` kept as site default** (per-site decision).
- `language.negotiation`: URL path-prefix — `en: ''` (bare),
  `da: da`. `language.types`: interface detection order
  `language-url` → `language-user` → `language-selected`.
- `drush language:add da` pulled Danish interface strings from
  localize.drupal.org — 8,594 added / 1,676 updated. Two strings
  skipped for disallowed HTML (captcha, one PHP-font label) —
  cosmetic.
- Verified over HTTP as anon: `/` → 200, `<html lang="en">`,
  `content-language: en`; `/da` → 200, `<html lang="da">`,
  `content-language: da`; `/da/user/login` shows "Log ind" /
  "Brugernavn" / "Adgangskode". Re-export is **stable** (a second
  `cex` yields zero further changes).

**Phase 4 — content translation config**
- Enabled content translation on `node.page`, `node.blog`, and
  `canvas_page.canvas_page` (the homepage is a `canvas_page`).
- Field translatability set deliberately: translatable —
  `field_content`, `field_description`, `field_seo_title/description/
  analysis`, `field_component_tree` (+ `field_blog__byline` on blog);
  **not** translatable — `field_featured_image`, `field_seo_image`,
  `field_tags` (shared media/taxonomy refs; translate alt on the
  media entity and term labels separately — open questions 1/2).

**Phase 3 spike — Canvas translation (findings)**
- `canvas_page` entity **and** its `components` (component-tree)
  field are both translatable; `content_translation` can be enabled
  for the bundle. Canvas ships
  `core.base_field_override.canvas_page.canvas_page.components` once
  `content_translation` is on, with
  `translation_sync: {inputs: inputs, tree: '0'}`.
- Empirically (created + rendered + removed a `da` translation of
  canvas_page 7): each language holds its **own independent
  component tree** (`en` 30 components, `da` 0). **There is no
  automatic layout fallback** — an untranslated Canvas page renders
  as an empty chrome-only shell (~26 KB vs ~88 KB), not the source
  language's layout.
- **Implication for the plan:** every Canvas page's tree must be
  explicitly copied to Danish and translated in place; there is no
  "translate the text, inherit the layout" mode for standalone
  `canvas_page` entities in this Canvas version. Still to verify in
  the UI: (a) whether the "Translate" form pre-fills the tree from
  the source, (b) what `translation_sync.inputs: inputs` does to
  per-language component text editing (if inputs are synced across
  translations, that setting likely needs flipping).
- **Refined by the Phase 8 pass:** the empty shell only happens when a
  `da` translation *exists* with an empty tree. A `canvas_page` with
  **no** `da` translation at all falls back to the English tree
  cleanly (`/da` front page renders 73 KB of real English content,
  HTTP 200). So the workflow rule is: never save a `da` `canvas_page`
  translation until its component tree is actually built.

**Export diff shape — commit `f997e9b`** (234 files, +2316 / −56): 15
genuine language config files; 104 auto-imported `language/da/*`
community config translations; 55 `core.entity_view_display`
(`langcode` hidden + key-sort normalisation); 43 `canvas.component`
(one-time `active_version` re-versioning); 12
`core.entity_form_display` (`langcode` select widget); 3
`field.field`; `core.extension`; `canvas.folder`.

### vdg — 2026-08-27, Phase 2 (interface-translation deploy plumbing)
`cex`/`cim` do not carry `{locales_target}` rows, so the ~8.6k Danish
UI strings imported by `drush language:add da` had no route to
testing/production. Fixed with a small vdg-scoped module.

**New module `web/modules/custom/vdg_multilingual`** (`shh_*`-style:
info.yml + focused hooks, no config/schema of its own; enabled on vdg
only via `core.extension`):
- `translations/da.po` — committed snapshot, `drush locale:export da
  --types=customized,not-customized`, 816 KB / 8,594 strings.
- `vdg_multilingual_import_interface_translations()` in `.module` —
  `Gettext::fileToDatabase()` of that file as `LOCALE_NOT_CUSTOMIZED`
  with `overwrite_options` `not_customized: true / customized: false`
  (a human-edited string is never clobbered; a later `locale:update`
  can still layer newer community strings on top), then
  `_locale_rebuild_js('da')` + invalidate `rendered`/`locale` tags.
  Idempotent; no-op if `da` or the file is absent.
- `hook_install()` calls it — covers the module being enabled by
  `cim` on a fresh target.
- `vdg_multilingual.deploy.php` →
  `vdg_multilingual_deploy_import_danish_translations()` for `drush
  deploy:hook` (last step of the Makefile `vdg-deploy`).

**Run-once semantics:** `drush deploy:hook` auto-baselines a deploy
hook when the module is first installed (same as
`hook_post_update_NAME`). To push a refreshed `da.po` to
already-deployed environments you must re-export the file **and** add
the next numbered function (`…_danish_translations_3`, …); editing the
`.po` alone re-imports nowhere.

**First-deploy fix — commit `ec926cd` (2026-08-27).** The first
`make vdg-pull` imported **nothing**: `config:import` runs
`processExtensions` (installing all five modules) to completion
*before* `processConfigurations` creates `language.entity.da`, so
`hook_install()` ran while `da` did not exist (no-op), and in that
same step Drush auto-baselined the `…_danish_translations` deploy
hook → `drush deploy:hook` then reported "No pending deploy hooks".
Fix:
- `hook_ENTITY_TYPE_insert()` for `configurable_language` — imports
  the `.po` the moment `da` is created (during that cim, or later
  via `language:add da` / the UI). This is the fresh-install path.
- `hook_install()` still imports for the "`da` already exists" case.
- `…_danish_translations_2()` — post-dates environments already
  deployed with the baselined `_1`, so it runs once there on the
  next `make vdg-pull` to back-fill. Auto-baselined on fresh
  installs (insert hook covers those). Idempotent everywhere.
Immediate manual fix for a stuck target:
`drush ev 'echo vdg_multilingual_import_interface_translations();'`.

**Verified:** clean uninstall + re-enable imports 8,594 rows via
`hook_install`; `deploy:hook` run in isolation reports "0 added,
8594 updated"; `/da/user/login` renders Danish. Export delta is one
line — `core.extension.yml` gains `vdg_multilingual: 0`.

**Custom-string audit (vdg):** the `quick_silver` theme is the only
custom user-facing code (no vdg custom modules). All visible strings
in its templates and `src/Hook/ThemeHooks.php` already go through
`|t` / `t()` / `{% trans %}` — nothing to fix. The handful of
theme-provided source strings (e.g. "Color scheme") are translatable
but not on localize.drupal.org; translating them is Phase 5 /
translation-phase content work.

### vdg — 2026-08-27, Phase 6 (theme + SEO)

**No cms2 config change** — vdg's theme `quick_silver` is its **own
git repo** (`github.com/deburca/quick_silver`, own `main`), explicitly
`.gitignore`d by cms2 at `.gitignore:85` (unlike `zwarte_piet` /
`hestehoj`, which are tracked inside cms2). Theme work for vdg lands
there, not here.

**Language switcher — committed to the quick_silver repo (`0cb9f7e`),
not pushed:**
- `templates/navigation/links--language-block.html.twig` — override of
  core `links.html.twig` for `links__language_block`: inline
  horizontal list, 44px targets, `aria-label="Language"`, active
  language marked. The `<a>` is left to core so the language-prefixed
  href + `hreflang`/`lang` attributes stay intact. Verified it
  compiles and is picked up (`✅ links--language-block.html.twig`).
- `src/theme.css` +~35 lines — scoped `.language-switcher*` rules on
  existing CSS vars; **no `build/main.min.css` rebuild** (per the
  hestehoj-era toolchain rule, which applies here too).
- **Deliberately not placed in a region.** Go-live = drop the
  `language_block:language_interface` component into the header or
  footer Canvas page region (open question 4). A raw
  `block.block` placement in the `header` region was tested and does
  **not** render — quick_silver's front-end header/footer are owned
  by `canvas.page_region.quick_silver.{header,footer}`, so the
  switcher must go into that Canvas tree.
- Native-name label polish (show "Dansk" not "Danish" in the
  switcher) deferred — would edit `language.entity.da` label.

**SEO / i18n plumbing audit — all already correct, no change made:**
- `simple_sitemap.settings`: `skip_untranslated: true`,
  `disable_language_hreflang: false`, `default_hreflang` sitemap type
  active — per-language sitemap with hreflang comes for free once
  translations exist.
- `pathauto.settings`: `transliterate: true` → Danish å/ø/æ collapse
  to ASCII in aliases (standard). Keeping native chars in URLs would
  be a deliberate change; not made.
- Fonts: quick_silver's `latin` subset (`U+0000–00FF`) already covers
  å ø æ Å Ø Æ for both Outfit and Inter.
- `<html lang>` dynamic via `html_attributes` — verified `en`/`da`.

**SEO items that are real but belong to Phase 5 (metatag
localisation), not the theme:**
- No `og:locale` / `og:locale:alternate` in `metatag.metatag_defaults.*`.
- `og:url` not language-aware (renders `…/home` on both `/` and
  `/da`).
- Pre-translation, `/da` `rel=canonical` → `/` and only
  `hreflang="en"` is emitted — correct fallback behaviour,
  self-corrects when the Danish homepage exists; core never emits
  `x-default`, add via metatag if wanted.

### vdg — 2026-08-27, Phase 5 (config-string translation — mechanical + structure only)

Scope agreed with Paddy: translate only the safe/mechanical
visitor-facing UI strings and add the `og:locale` structure; leave all
editorial copy for a client / translator hand-off (below). Nothing
editorial was invented.

**`da` config overrides written** (12 files: 2 modified, 10 new under
`config/vdg/sync/language/da/`; re-export stable):
- `klaro.klaro_purpose.*` labels (7): Analyse, Funktionel, Indlejret
  eksternt indhold, Live chat, Annoncering og markedsføring —
  `security`/`styling` already carried the community values
  (Sikkerhed / Styling), left as-is.
- `views.view.search`: page title "Søgning", submit "Søg",
  placeholder "Indtast søgeord", result summary "Viser @start – @end
  af @total resultater", no-results text.
- `views.view.blog`: "Blog", "Seneste blogindlæg", "Relaterede
  blogindlæg", empty text.
- `views.view.related_tags`: "Relaterede tags".
- `webform.webform.contact_form`: title "Kontakt" + element `#title`s
  (Navn / E-mail / Besked). `webform.webform.newsletter_signup`:
  title "Tilmeld nyhedsbrev", email label, submit "Tilmeld".
- `metatag.metatag_defaults.global`: **base** gains `og_locale:
  en_US` (a new tag on English pages too — standard, benign); `da`
  override sets `og_locale: da_DK`. Verified: `/` → `og:locale
  en_US`, `/da` → `da_DK`.

Note: the exposed-form generic buttons ("Apply", "Reset", "Sort by",
etc.) are translated by *interface* translation at render, so they
were not given config overrides. `og:locale:alternate` was attempted
but `og_locale_alternate` is **not a defined metatag tag** in this
install — reverted (no dead config); needs a custom tag or another
module if wanted.

**Editorial Danish still needed — client / translator hand-off:**
1. `klaro.klaro_app.*` (~25) — one-sentence purpose description each.
2. `klaro.texts` — any consent-notice body beyond the community
   button strings already imported.
3. `easy_email.easy_email_type.*` (10) + `easy_email_override.*` —
   subject + body of every transactional email (user activation,
   password recovery, approval flow, cancellation, …).
4. `webform.webform.contact_form` — `settings.confirmation_title`
   ("Thank you") + `confirmation_message` ("<h2>Thanks for
   contacting us!</h2>…"); `newsletter_signup` — `confirmation_title`
   ("Thanks") + any confirmation body.
5. `metatag.metatag_defaults.{front,node,global}` — Danish
   title/description **patterns** (the tokenised templates), plus a
   decision on `x-default` / `og:url` language-awareness.
6. `system.site` slogan — currently empty; supply if the site should
   have a Danish tagline.
7. Any Canvas/SDC component example strings that surface as real UI
   (none found in the vdg audit, but re-check once Danish pages are
   built).

### vdg — 2026-08-27, Phase 7 (existing-content handling)

**No langcode rewrite needed and none done** — English stays the vdg
default, and every content entity is already `en`: 14 nodes, 9
`canvas_page`, 20 `menu_link_content`, 8 taxonomy terms, 42 media
(`path_alias` has 8 `und` = language-neutral, correct;
`block_content` module not installed). Phase 7's "no bulk langcode
rewrite" is trivially satisfied for vdg. **No config/code change in
this phase** — the output is the translation inventory + two
decisions below.

**Danish content inventory (for the client / translator):**

| Set | Count | Route to translate | Notes |
|---|---|---|---|
| `canvas_page` | 9 | Canvas UI, per page | **Each needs its full component tree rebuilt in `da`** — no layout fallback (Phase 3 spike). Home (30 comps), Features (32), Pricing (29), Consultancy (28), Contact (11), Careers (11), Resources (9), About (9), "Page not found"/404 (2). |
| nodes — legal | 2 | standard node translation form | Privacy policy, Terms of service — legal text, translate carefully. |
| nodes — blog | 12 | standard node translation form | Editorial; decide which posts get a Danish version (probably not all). All are plain `field_content` bodies — no Canvas complexity. |
| `menu_link_content` | ~9 visitor-facing | translation tab per link | main: Features/Contact/Resources/Pricing/Consultancy; secondary: About/Careers; footer: Privacy policy/Terms of service. `social` links are brand names (no translation); `top-tasks` menu is admin-only. |
| taxonomy `tags` | 8 | term translation form | compliance, GrapheneOS, digital sovereignty, self hosting, data privacy, linux, AI, mobile phones. |

**Decisions needed before the content work:**
1. **Per-language image alt text.** `media/*` content translation is
   OFF and `field_featured_image` / `field_seo_image` are
   non-translatable, so alt text is currently shared across
   languages. If Danish pages need Danish alt text, enable content
   translation on `media/image` (config change) — otherwise the
   English alt is reused. Recommend: leave off unless the client
   asks.
2. **Blog scope.** Which of the 12 posts (if any) get a Danish
   translation — they're privacy/tech editorial, not core
   marketing pages.

### vdg — 2026-08-27, Phase 8 (verification pass)

Run on the DDEV instance, anonymous unless noted. **No config/code
change.**

**Pass:**
- `drush config:status`: only `canvas.content_template.node.blog.full`
  shows "Different" — a **pre-existing** Canvas serialisation quirk
  (byte-identical on `cex`, predates task 0069). Active config ==
  committed, 0 real diffs.
- `<html lang>` = `en` on every bare path, `da` on every `/da`
  response including 404 / 403; `content-language` header matches.
- **No cross-language cache bleed** — 3× rapid `/` ↔ `/da`
  alternation, each URL holds its language. URL-keyed page cache
  (`vary: Cookie,Accept-Encoding`, `X-Drupal-Cache: HIT` per URL)
  makes bleed structurally impossible.
- Untranslated **front page** under `/da` → clean English fallback
  (HTTP 200, ~73 KB, real navbar/hero/footer components).
- `/admin` + `/da/admin` → 403; `/nonexistent` + `/da/nonexistent` →
  404; the `/da` variants render the Danish 403/404 page.
- Webform: submissions created in both `en` and `da` contexts store
  the right `langcode` and fire the email handler (test submissions
  removed, Mailpit cleared afterwards).
- EN regression: no Danish string leakage; the only new `<head>`
  tags on English pages are `og:locale: en_US` and a self-referential
  `hreflang="en"` — both standard multilingual output, benign.
- `user.preferred_langcode` + `user.preferred_admin_langcode` base
  fields present; `language-user` is in the interface detection
  chain, so editor language preference is wired.
- gin admin: Danish community strings imported with the language;
  admin routes negotiate to `da` under `/da` (403 page was
  `lang="da"`). Full logged-in admin walkthrough deferred to a real
  browser session (scripted `uli` sessions are unreliable here).

**Findings — documented, not blockers:**
- **A. `/da/<aliased-path>` 404s** for any page without a Danish
  translation (`/da/features`, `/da/pricing`, …). Path aliases are
  per-language: `/features` exists only for `en`, so `/da/features`
  matches no route. The front page is exempt (not alias-routed).
  Aliased pages begin resolving under `/da` once they get a Danish
  translation (which creates a `da` alias). Acceptable pre-launch;
  it also means the language switcher, when placed, must use core's
  "drop the link if no translation" behaviour — another reason it's
  gated (Phase 6).
- **B.** Canvas fallback refinement — see the Phase 3 spike note:
  missing `da` translation → clean English fallback; only an
  *empty-tree* `da` translation renders the broken shell.
- **C.** `/privacy-policy` 404s in **both** languages — **pre-existing**,
  node 1 is unpublished (draft), and the footer menu links to it.
  Not an i18n issue; flag for the vdg content backlog.
- **D.** Notification / confirmation emails for `da` submissions are
  still English until the `easy_email` / webform templates are
  translated (editorial hand-off list).

**Not yet done for vdg:** Phase 9 (rollout — `make vdg-pull` to
testing then production). `quick_silver` commit `0cb9f7e` pushed
2026-08-27.

## Acceptance criteria
Per site (`vdg`, then `kbg`, then `shh`):
- [ ] `language`, `locale`, `content_translation`, `config_translation`
      enabled; captured in `core.extension.yml`.
- [ ] `da` and `en` both present; correct site default per the
      decision above; `language.negotiation.yml` / `language.types.yml`
      set to URL-prefix negotiation with the prefixes above; interface
      detection order URL → user → selected; content + URL language
      from URL.
- [ ] Danish interface strings for core + every enabled contrib module
      imported from localize.drupal.org; committed as a per-site `.po`
      and imported by a deploy hook (`drush locale:import`), because
      `cex`/`cim` does **not** carry interface translations.
- [ ] Custom-code UI strings (custom modules incl. `shh_common`, and
      the site theme templates) all run through `t()` /
      `{{ …|t }}` / `{% trans %}`; no hardcoded English left in
      user-facing chrome. New/changed strings translated in the
      committed `.po`.
- [ ] Content translation enabled on the right bundles with the right
      fields translatable (see Phase 4); `language.content_settings.*`
      and `translatable:` flags exported.
- [ ] Config strings translated via `config_translation` for: site
      slogan, menus, custom blocks, every `views.view.*` (exposed
      labels, titles, empty text), `klaro.klaro_app.*` +
      `klaro.klaro_purpose.*` consent copy, `easy_email.easy_email_type.*`
      templates, and (shh) `webform.webform.*`. Exported under
      `config/<site>/sync/language/da/…` (and `…/en/…` where the
      default is Danish).
- [ ] Language switcher present and styled in the site theme
      (`quick_silver` / `zwarte_piet` / `hestehoj`); switching
      preserves the current page and its query; switcher hidden until
      the go-live gate (open question 4) is met.
- [ ] `<html lang>` reflects the served language on every page (no
      hardcoded `lang="en"` in `html.html.twig`); `hreflang`
      alternates and `rel=canonical` correct on translated and
      untranslated pages; `og:locale` / `og:locale:alternate` set.
- [ ] XML sitemap is per-language with `hreflang` entries (shh:
      `simple_sitemap` language settings; vdg/kbg: whichever sitemap
      module is enabled, or add one).
- [ ] Danish `core.date_format.*` / number formatting verified;
      å/ø/æ render correctly in every theme's webfont subset and in
      generated path aliases (pathauto transliteration).
- [ ] Verified end to end over real HTTP as a non-admin (house style):
      bare path = default language, prefixed path = other language,
      switcher round-trips, forms submit and send the
      correctly-localised email (Mailpit), 403/404 pages localised,
      no cross-language cache bleed (page cache varies on language),
      `drush config:status` clean after export, gin admin still
      usable.
- [ ] `vdg` English behaviour unchanged where no Danish translation
      exists (pure regression check).
- [ ] Task file + (shh only) `projects/shh-stables-platform.md` status
      narrative updated in house style in the same change; per-site
      commits `feat(<site>): …` with the `Site: <domain> (<site>)`
      body line; deploy via `make <site>-pull` to testing, then
      production.

## Implementation notes

### Sequencing
Do **`vdg` first** as the reference implementation — English stays the
default, existing content is already the right language, so the work
is purely additive and the lowest-risk place to nail down the config
pattern (negotiation, content settings, `language/da/*` overrides,
`.po` deploy hook, theme switcher). Then apply the same pattern to
`kbg` and `shh`, where Danish becomes the default and the
existing-content question (open question 1) also applies.

One feature branch, but a separate config export + commit + deploy per
site (never a combined `all-push` for this — the diffs are large and
decision 0020 wants every changed line explained per site).

### Phase 1 — Enable the multilingual stack (per site)
- `ddev drush -l <site-ddev> en language locale content_translation config_translation -y`
- `ddev drush -l <site-ddev> language:add da`  (and `language:add en`
  is already the built-in default; keep it)
- Set the site default: kbg + shh → `da`; vdg stays `en`. Via UI
  (`/admin/config/regional/language`) or `drush cset system.site
  default_langcode …`, then re-export.
- Configure negotiation at
  `/admin/config/language/detection`:
  - URL method = path prefix; default language prefix **empty**,
    other language prefix `da` (vdg) or `en` (kbg/shh).
  - Interface detection order: URL → Account → Selected language.
  - Content language + URL language: from URL.
  - Optionally pin "Account administration pages" to a single
    language so the admin UI doesn't flip under editors.
- `ddev drush ... cex` and review: expect `core.extension.yml`,
  `language.entity.da.yml`, `language.entity.en.yml`,
  `language.negotiation.yml`, `language.types.yml`,
  `language.mappings.yml`, `system.site.yml`
  (`default_langcode`, `langcode`).

### Phase 2 — Interface (UI string) translation
- `ddev drush ... locale:check && ddev drush ... locale:update` to
  pull `da` translations for core + every enabled contrib project
  from localize.drupal.org.
- Audit custom code for untranslatable strings:
  `grep` custom modules and the site theme's `templates/` for bare
  English in markup / `#markup` / `->addMessage(` not wrapped in
  `t()` / `TranslatableMarkup` / `{% trans %}`. Fix in place. Note
  the shh theme build constraints from prior tasks (no
  `main.min.css` rebuild, plain component CSS only) — string changes
  in `.twig` are fine, no build step.
- **Deploy gap:** `cim` does not import interface translations
  (they live in the `locales_*` tables, not config). So:
  1. Export a per-site custom `.po`
     (`ddev drush ... locale:export da --types=customized`) plus keep
     the community `.po` cache, commit under
     e.g. `translations/<site>/da.po`.
  2. Add a `hook_deploy_NAME()` (in a small shared install/deploy
     module, or per-site profile) that runs `locale:import` of the
     committed `.po` on `drush deploy` — the Makefile `<site>-deploy`
     already runs `deploy:hook`, so this slots in with no Makefile
     change.
  3. Document the round-trip in the deploy procedure note.

### Phase 3 — Canvas translation spike (before Phase 4 content work)
Canvas holds the page layout / component tree as a field on the host
entity (decision [[0003-canvas-for-page-building]],
[[0019-canvas-content-templates-for-structured-content]]). Determine:
- Does Canvas support a per-language component tree (translate the
  Canvas field like any other translatable field), or is the tree
  shared across translations with only referenced content
  translated?
- How do `canvas.component.*` / `canvas.folder` / `canvas.page_region`
  config strings get translated (config_translation coverage)?
- What happens to a Canvas page when its translation is incomplete —
  clean fallback, or broken render?
Outcome decides the launch scope for page-built content. If Canvas
can't do per-language trees yet, the interim is: translate the
structured field content that Canvas references, accept a shared
layout, and only hand-build separate second-language pages for the
few where layout must differ. Record the finding as a decision note.

### Phase 4 — Content translation configuration (per site)
Enable translation on bundles and set field translatability at
`/admin/config/regional/content-language`, then `cex`:
- **Nodes:** `page` (all), `blog` (vdg), `news` + `bookable_facility`
  (shh). Translatable: title, body/summary, SEO/metatag fields,
  image *alt* (not the file reference), CTA text, Canvas field
  pending Phase 3. Non-translatable: dates, taxonomy refs, media
  refs, booking/commerce plumbing on `bookable_facility`.
- **Menus:** `menu_link_content` custom links translatable; menu
  container labels via config_translation.
- **Blocks:** `block_content` translatable; block placement config
  (title, visibility) via config_translation.
- **Taxonomy:** any vocabularies (check shh) — terms translatable.
- **Media:** per open question 2 — assume entity non-translatable,
  translate alt/caption at field level.
- **Users:** keep `user` non-translatable; expose a preferred
  language on the account (core does this once `language` is on).
- **Commerce (shh):** product/variation titles + descriptions
  translatable; prices, SKUs, stores non-translatable. Order emails
  handled via easy_email translations. Confirm the disabled modules
  from [[shh-phased-launch-disabled-modules]] stay disabled — no
  translation work for cart/checkout/booking_hold/etc. now.

### Phase 5 — Config string translation (per site)
Work through `config_translation` (`/admin/config/regional/config-translation`)
for every user-visible config string and `cex`. Expect a large
`config/<site>/sync/language/da/…` tree (and `…/en/…` where the
default is Danish). Cover at least:
- `system.site` slogan (keep the site name identical unless the
  client wants it localised).
- Every `system.menu.*` label and `views.view.*` (exposed filter
  labels, page/block titles, empty-result text, pager, header/footer).
- `klaro.klaro_app.*` + `klaro.klaro_purpose.*` — full consent-banner
  copy in both languages (decision [[0010-spam-protection-strategy]] /
  klaro).
- `easy_email.easy_email_type.*` (10 per site) + any
  `easy_email_override.*` — subject + body per language.
- `webform.webform.*` and `webform.webform_options.*` (shh) — element
  labels, descriptions, confirmation + validation messages, email
  handlers.
- `core.date_format.*` — Danish patterns.
- `field.field.*` / form-display help text that renders on public
  forms.
- Metatag defaults (decision [[0011-seo-optimization-stack]]) per
  language; `og:locale` + alternates.

### Phase 6 — Theme + SEO (per theme)
- Add a **language switcher** (core language block, or an SDC
  wrapper) to the header or footer region of `quick_silver` /
  `zwarte_piet` / `hestehoj`; style per each theme's existing system
  (shh: component-scoped CSS + only Tailwind utilities already in the
  committed build — grep first; no `npm run format`, no
  `main.min.css` rebuild). Keep it out of the rendered markup until
  the per-site go-live gate is met.
- Verify `html.html.twig` in each theme uses the dynamic
  `{{ html_attributes }}` / `language` and has no hardcoded
  `lang="en"` / `dir`.
- Confirm core emits `<link rel="alternate" hreflang="…">` for each
  translation and a correct `rel="canonical"`; add the x-default.
- `simple_sitemap` (shh): enable multilingual sitemap variants with
  hreflang. vdg/kbg: check for a sitemap module; add
  `simple_sitemap` if missing (separate small task if it grows).
- Check webfont subsetting covers å ø æ Å Ø Æ in every theme.
- pathauto: confirm aliases are generated per-language and
  transliteration handles Danish characters; consider per-language
  patterns if the client wants Danish slugs.

### Phase 7 — Existing-content handling (kbg + shh only)
Per open question 1: keep each existing entity as its English
original, add Danish as the new primary translation as copy arrives,
rely on fallback meanwhile. Produce the hand-off list of what needs
Danish source copy (homepage, nav targets, landing pages, footer,
consent, emails). No bulk `langcode` rewrite of English content.

### Phase 8 — Verification (per site, real HTTP, non-admin)
- Bare path renders the default language; prefixed path renders the
  other; a missing translation falls back cleanly (no fatal, no
  half-rendered Canvas).
- Language switcher round-trips on several page types and preserves
  path + query.
- `<html lang>`, `hreflang` set, `canonical` correct on translated
  and untranslated pages.
- Contact / webform submits in each language and the notification +
  confirmation emails arrive localised (Mailpit API on the web:8025
  mapping). For logged-in form fetches use the `big_pipe_nojs=1`
  cookie (house note).
- 403 / 404 / maintenance pages localised.
- Page cache varies on language — hit each URL twice, swap language,
  confirm no stale cross-language HTML; check `X-Drupal-Cache` and
  cache tags.
- `drush config:status` clean after `cex`; `git diff config/<site>`
  fully explainable (decision 0020).
- gin admin usable in both languages; editor language preference
  respected.
- vdg: English pages with no Danish translation behave exactly as
  before (regression).

### Phase 9 — Rollout
- Per site: `make <site>-pull SHH_URI=… ` to the testing instance
  first, smoke-test Phase 8 there, then production. Watch the
  destructive-import caveat ([[drops-destructive-db-import]]) if
  drops is used instead of the Makefile.
- Flip the switcher on (remove the theme guard) per site once the
  minimum translated set is signed off.
- Announce nothing publicly / no new sitemap submission until the
  second language has its minimum content.

## Risks / watch-items
- **Interface translations aren't in `cex`** — the `.po` + deploy-hook
  step (Phase 2) is new plumbing; if it's skipped, testing/production
  come up with English UI chrome.
- **Canvas** may not support per-language component trees yet
  (Phase 3) — could cap launch scope for page-built content.
- **Config diff size** — `language/da/*` overrides add hundreds of
  files; budget review time, commit per site, keep each line
  explainable.
- **Content volume** — klaro consent, easy_email, webforms, menus,
  homepage/landing copy in Danish is a real content project, not just
  toggles; depends on client-supplied copy for kbg + shh.
- **shh theme toolchain** constraints (no `main.min.css` rebuild, no
  repo-wide `npm run format`, Tailwind utilities limited to the
  committed build) — style the switcher within those limits.
- **SEO duplicate content** — untranslated pages served under both
  `/` and the prefix must carry correct `hreflang` + `canonical`;
  prefer not exposing empty translations over exposing thin ones.
- **Cache** — verify no anonymous page-cache bleed between languages
  after negotiation changes.
- **gin / Canvas admin UI** Danish coverage is community-maintained;
  minor gaps are acceptable, report anything broken upstream.

## Related
- [[0001-multisite-architecture]]
- [[0003-canvas-for-page-building]] · [[0019-canvas-content-templates-for-structured-content]]
- [[0010-spam-protection-strategy]] · [[0011-seo-optimization-stack]]
- [[0020-shh-config-export-strategy]]
- [[shh-stables-platform]]
- infrastructure/shh-deployment-procedure.md — add the `.po` import step
- Commits::
