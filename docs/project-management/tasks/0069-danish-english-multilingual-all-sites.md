---
type: task
tags: [cms2/task]
status: in-progress
priority: high
site: vdg, kbg, shh
project:
created: 2026-08-27
updated: 2026-08-27
progress: vdg Phases 1 + 4 done locally (unexported→exported, not committed); Canvas spike findings below
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

**Not yet done for vdg:** Phase 2 (`.po` commit + `locale:import`
deploy hook — the 8,594 Danish strings currently live only in the
local DB), Phase 5 (config-string translation via
`config_translation`), Phase 6 (theme language switcher + SEO
hreflang/canonical review), Phases 7–9. Not committed — awaiting
Paddy's `git diff config/vdg` review and `make vdg-commit`.

**Export diff shape** (234 files, +2316 / −56): 15 genuine language
config files; 104 auto-imported `language/da/*` community config
translations; 55 `core.entity_view_display` (`langcode` hidden +
key-sort normalisation); 43 `canvas.component` (one-time
`active_version` re-versioning); 12 `core.entity_form_display`
(`langcode` select widget); 3 `field.field`; `core.extension`;
`canvas.folder`.

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
