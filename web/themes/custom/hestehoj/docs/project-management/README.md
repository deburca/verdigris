# Hestehoj Theme — Project Management Vault

A lightweight, Git-tracked [Obsidian](https://obsidian.md) vault for managing
work on the **Hestehoj** theme for [stutteri-hestehoj.dk](https://stutteri-hestehoj.dk).
Everything here is plain Markdown, so it version-controls cleanly alongside the
theme code it documents.

## Theme Overview

**Hestehoj** (`shh`) is a custom Drupal 11 theme built for stutteri-hestehoj.dk,
a Danish horse stud and riding school website. The theme lives in
`web/themes/custom/hestehoj/` within the CMS2 multisite platform.

### Technical Architecture

- **Platform**: Drupal 11.x (CMS2 multisite, site code `shh`)
- **Development Environment**: DDEV — local URL: https://hestehoj.ddev.site
- **Component Architecture**: Single Directory Components (SDC)
- **Styling**: Tailwind CSS with CVA (Class Variant Authority) for variant management
- **Frontend Tooling**: Yarn (Berry), Storybook for component development
- **Page Building**: Canvas visual page builder
- **PHP Version**: 8.4 | **Node.js Version**: 20

### Key Conventions

- **CVA for all conditional classes** — never use inline Twig conditionals in
  `class` attributes; use `html_cva()` with `yes`/`no` variant keys instead.
- **SDC components** — all UI elements are Single Directory Components with a
  `component.yml` schema, a Twig template, and optional JS/CSS.
- **Always run after changes**: `npm run format && npm run build` from the
  theme directory.
- **Include isolation**: always use `with_context: false` (function syntax) or
  `only` (tag syntax) when including components.

## How to Open

Open `docs/` **or** `docs/project-management/` as an Obsidian vault — the
dashboard queries are tag-based (`#hestehoj/...`), so they resolve no matter
which of the two you pick as the vault root:

> Obsidian → *Open folder as vault* → select `docs` (recommended)

Opening `docs/` keeps the vault scoped to documentation and stops Obsidian
from indexing source files. Obsidian writes its own state into a `.obsidian/`
folder; the volatile parts are git-ignored (see below).

## Structure

The vault lives in `docs/` within the theme directory:

```
web/themes/custom/hestehoj/
├── AGENTS.md              ← AI agent coding rules for this theme
├── docs/
│   ├── .gitignore         ← ignores volatile Obsidian state (any depth)
│   ├── .obsidian/         ← Obsidian config for this vault
│   └── project-management/
│       ├── README.md      ← you are here
│       ├── index.md       ← dashboard / map-of-content (open this first)
│       ├── templates/     ← copy these when creating new notes
│       ├── projects/      ← multi-task initiatives
│       ├── tasks/         ← atomic units of work (NNNN-slug.md)
│       ├── decisions/     ← Architecture Decision Records (ADRs)
│       ├── releases/      ← per-version changelog + checklist notes
│       ├── infrastructure/ ← hosting, deployment, environment documentation
│       └── notes/         ← daily / weekly review notes
```

## Conventions

- **IDs**: tasks and decisions are zero-padded (`0001`, `0002`, …) so they sort
  and link predictably. Never reuse or renumber an ID once committed.
- **Frontmatter**: every note carries YAML frontmatter (`status`, `created`,
  etc.) so [Dataview](https://github.com/blacksmithgu/obsidian-dataview) can
  build the dashboard automatically.
- **Tags**: every note declares a `hestehoj/<type>` tag in frontmatter
  (`hestehoj/task`, `hestehoj/project`, `hestehoj/decision`,
  `hestehoj/release`, `hestehoj/notes`, `hestehoj/review`).
- **Wikilinks**: cross-reference with real note links like
  `[[0001-card-component-sdc]]`. Avoid placeholder wikilinks in templates —
  Obsidian treats them as unresolved notes and clutters the graph view.
- **Status vocabulary**: `backlog` → `todo` → `in-progress` → `review` →
  `done` (or `blocked` / `dropped`). Keep it to these values so queries stay
  reliable.

### Frontmatter Formatting

Keep frontmatter mechanically consistent so Obsidian, Dataview, and repo-wide
searches behave predictably.

- Prefer **inline YAML arrays** for short lists:
  - `tags: [hestehoj/task]`
  - `tags: [hestehoj/notes, daily]`
  - `depends-on: ["[[0001-card-component-sdc]]"]`
- Use **exact field values** in frontmatter:
  - `status:` only from the vocabulary above
  - `project: "[[project-slug]]"`
  - `priority: low | medium | high`
- Prefer **blank values over placeholders** when something is not known yet:
  - `target:`
  - `release:`
  - `blocked-by:`
  - do **not** leave fake links like `[[project-note]]` or `[[NNNN-...]]`
- When editing an existing note, preserve the current field order unless there
  is a strong reason to change it.

## Recommended Obsidian Plugins

- **Dataview** — powers the dashboard tables in `index.md`.
- **Tasks** — query `- [ ]` checkboxes across notes.
- **Templater** (or core *Templates*) — instantiate `templates/*.md`.
- **Git** — auto-commit the vault on an interval if you like.

## Git Workflow

The vault lives inside the theme directory, which is part of the CMS2
repository, so notes and code travel together.

1. **Branch per task**, naming it after the task ID or feature:
   ```bash
   git checkout -b feature/0001-hero-component
   git checkout -b feature/0002-card-variants
   git checkout -b fix/0003-mobile-nav
   ```

2. **Reference notes in commits** so history is self-documenting:
   ```bash
   git commit -m "feat(shh): implement hero SDC component

   Implements web/themes/custom/hestehoj/docs/project-management/tasks/0001-hero-component.md
   Decision: 0001-sdc-component-architecture"
   ```

3. **Commit the note with the code it describes.** Updating a task's `status`
   to `done` in the same commit that lands the feature keeps the vault honest.

4. **Prefix commit messages** with the site code:
   - `feat(shh): add hero component`
   - `fix(shh): resolve mobile navigation alignment`
   - `refactor(shh): migrate card to SDC`

5. **Releases**: when deploying to production, finalize the matching
   `releases/YYYY-MM-DD-deployment.md` note in the release commit.

## Theme Development Workflow

### DDEV Local Development

```bash
# Start DDEV environment (from CMS2 repo root)
ddev start

# Access the Hestehoj site
ddev launch --uri=hestehoj.ddev.site

# Drush commands for the shh site
ddev exec drush --uri=hestehoj.ddev.site cr
ddev exec drush --uri=hestehoj.ddev.site uli
ddev exec drush --uri=hestehoj.ddev.site config:export
```

### Frontend Build

Run these commands from `web/themes/custom/hestehoj/`:

```bash
# Install dependencies
yarn install

# Format all code (run after every change)
npm run format

# Build compiled CSS / JS assets (run after every change)
npm run build

# Watch for changes during development
npm run watch

# Storybook component development
npm run storybook
```

**Storybook:** available at https://drupal-cms2.ddev.site:6006 — use it to
develop and preview SDC components in isolation before integrating them.

### Theme Directory Structure

```
web/themes/custom/hestehoj/
├── AGENTS.md                  ← AI agent coding rules
├── components/                ← SDC components (each in own directory)
│   ├── button/
│   │   ├── button.component.yml
│   │   ├── button.twig
│   │   └── button.css
│   └── ...
├── docs/                      ← this vault
├── templates/                 ← Drupal template overrides
├── hestehoj.info.yml
├── hestehoj.libraries.yml
├── hestehoj.theme
├── package.json
└── tailwind.config.js
```

## .gitignore

`docs/.gitignore` ignores Obsidian's volatile per-machine state
(`**/.obsidian/workspace.json`, `workspace-mobile.json`, caches, `.trash/`) at
any depth. Shareable config (`app.json`, `appearance.json`, hotkeys,
community-plugin settings) is intentionally tracked so the vault setup travels
with the repo.

## Key Documentation Files

- **[AGENTS.md](../../AGENTS.md)** — AI agent coding rules for this theme
- **[docs/project-management/README.md](README.md)** — This file
- **[docs/project-management/index.md](index.md)** — Project dashboard

## Getting Started

### For Theme Developers

1. **Set up the CMS2 environment** (from repo root):
   ```bash
   ddev start
   ddev composer install
   ```

2. **Import the shh database** (obtain from team):
   ```bash
   ddev import-db --database=db_shh --file=shh-backup.sql.gz
   ```

3. **Install theme dependencies** (from theme directory):
   ```bash
   cd web/themes/custom/hestehoj
   yarn install
   npm run build
   ```

4. **Access the site**: https://hestehoj.ddev.site

5. **Open this vault** in Obsidian:
   - Launch Obsidian → *Open folder as vault* → select `docs/`
   - Navigate to `project-management/index.md`

### For Project Management

1. **Open the vault** in Obsidian (see "How to Open" above)
2. **Start with the dashboard** at `docs/project-management/index.md`
3. **Create tasks** from `templates/task.md` when planning new work
4. **Document decisions** using `templates/decision.md` for architectural choices
5. **Update status** as work progresses to keep the dashboard current

## Resources

- **Drupal SDC**: https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components
- **Tailwind CSS**: https://tailwindcss.com/docs
- **Storybook**: https://storybook.js.org/docs
- **CVA (Class Variance Authority)**: https://cva.style
- **DDEV**: https://ddev.readthedocs.io
- **Canvas**: https://www.drupal.org/project/canvas
- **Drupal API**: https://api.drupal.org

## License

This project builds on Drupal CMS and all derivative works are licensed under
the [GNU General Public License, version 2 or later](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html).
