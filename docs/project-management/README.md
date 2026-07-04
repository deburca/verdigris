# CMS2 — Project Management Vault

A lightweight, Git-tracked [Obsidian](https://obsidian.md) vault for managing
work on the **CMS2** Drupal multisite platform. Everything here is plain
Markdown, so it version-controls cleanly alongside the code and configuration it
documents.

## Project Overview

**CMS2** is a Drupal 11-based multisite platform hosting independent websites
sharing a common technical foundation. Each site operates with dedicated
databases, site-specific themes, and custom configurations while leveraging
shared Drupal core, contributed modules, and architectural patterns.

### Current Sites

| Hostname | Site Code | Theme | Status | Notes |
|----------|-----------|-------|--------|-------|
| verdigris.nu | `vdg` | zwarte_piet | Active | Default site |
| kragebaekgaard.dk | `kbg` | quick_silver | Active | |
| stutteri-hestehoj.dk | `shh` | hestehoj | In Development | Previously falconi.net |

### Technical Architecture

- **Platform**: Drupal 11.x (Drupal CMS distribution)
- **Development Environment**: DDEV (Docker-based local development)
- **Component Architecture**: Single Directory Components (SDC)
- **Frontend Tooling**: Yarn (Berry), Storybook for component development
- **Database Strategy**: Dedicated database per site within shared MariaDB 10.11
- **PHP Version**: 8.4
- **Node.js Version**: 20

### Shared Foundation

All sites share:
- **Drupal Core**: Common core version across all sites
- **Contributed Modules**: Shared module library (web/modules/contrib/)
- **Composer Dependencies**: Unified dependency management
- **Development Tools**: DDEV configuration, coding standards, testing framework
- **Component Patterns**: SDC architecture for reusable UI components

### Site-Specific Elements

Each site maintains:
- **Custom Theme**: Site-specific theme in web/themes/custom/
- **Site Configuration**: Independent configuration in web/sites/{site_code}/
- **Private Files**: Dedicated private file storage in private_{site_code}/
- **Database**: Isolated database for complete data separation
- **Domain Mapping**: Multiple domains can map to a single site via sites.php

## How to Open

Open either `docs/` **or** `docs/project-management/` as an Obsidian vault — the
dashboard queries are tag-based (`#cms2/...`), so they resolve no matter which
of the two you pick as the vault root:

> Obsidian → *Open folder as vault* → select `docs` (recommended)

Opening `docs/` (rather than the repo root) keeps the vault scoped to
documentation and stops Obsidian from indexing `web/`, `vendor/`, `node_modules/`,
etc. Obsidian writes its own state into a `.obsidian/` folder at whichever
level you open; the volatile parts are git-ignored (see below).

## Structure

The vault lives in the `docs/` directory of the repository:

```
docs/                          ← vault root (recommended)
├── .gitignore                 ← ignores volatile Obsidian state (any depth)
├── .obsidian/                 ← Obsidian config for this vault
└── project-management/
    ├── README.md              ← you are here
    ├── index.md               ← dashboard / map-of-content (open this first)
    ├── templates/             ← copy these when creating new notes
    ├── sites/                 ← site-specific documentation and decisions
    │   ├── verdigris/         ← verdigris.nu documentation
    │   ├── kragebaekgaard/    ← kragebaekgaard.dk documentation
    │   └── hestehoj/          ← stutteri-hestehoj.dk documentation
    ├── projects/              ← multi-task initiatives
    ├── tasks/                 ← atomic units of work (NNNN-slug.md)
    ├── decisions/             ← Architecture Decision Records (ADRs)
    ├── releases/              ← per-version changelog + checklist notes
    ├── infrastructure/        ← DDEV, hosting, deployment documentation
    └── notes/                 ← daily / weekly review notes
```

## Conventions

- **IDs**: tasks and decisions are zero-padded (`0001`, `0002`, …) so they sort
  and link predictably. Never reuse or renumber an ID once committed.
- **Frontmatter**: every note carries YAML frontmatter (`status`, `created`,
  `site`, etc.) so [Dataview](https://github.com/blacksmithgu/obsidian-dataview)
  can build the dashboard automatically.
- **Tags**: every note declares a `cms2/<type>` tag in frontmatter
  (`cms2/task`, `cms2/project`, `cms2/decision`, `cms2/release`, `cms2/site`,
  `cms2/notes`, `cms2/review`).
  The dashboard queries with `FROM #cms2/task` etc. — tag sources are
  independent of the vault root, so the queries work whether you open `docs/`
  or `docs/project-management/`.
- **Site Tagging**: tasks and decisions related to specific sites should include
  a `site` field in frontmatter: `site: vdg`, `site: kbg`, or `site: shh`. Use
  `site: shared` for work affecting all sites.
- **Wikilinks**: cross-reference with real note links like
  `[[0001-multisite-architecture]]` and `[[verdigris-homepage-redesign]]`. Avoid
  leaving placeholder wikilinks in templates, because Obsidian treats them as
  unresolved notes and they clutter graph/backlink views.
- **Status vocabulary**: `backlog` → `todo` → `in-progress` → `review` →
  `done` (or `blocked` / `dropped`). Keep it to these values so queries stay
  reliable.

### Frontmatter Formatting

Keep frontmatter mechanically consistent so Obsidian, Dataview, and simple
repo-wide searches behave predictably.

- Prefer **inline YAML arrays** for short lists:
  - `tags: [cms2/task]`
  - `tags: [cms2/notes, daily, journal]`
  - `depends-on: ["[[0004-sdc-component-library-foundation]]"]`
- Use **exact field values** in frontmatter:
  - `site: vdg` (not `verdigris` or `Verdigris`)
  - `site: kbg` (not `kragebaekgaard`)
  - `site: shh` (not `hestehoj` or `stutteri-hestehoj`)
  - `site: shared` (for cross-site work)
  - `project: "[[homepage-redesign]]"`
  - `status:` only from the vocabulary above
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

The vault lives in the same repository as the codebase, so notes, code, and
configuration travel together.

1. **Branch per task**, naming it after the task ID or feature:
   ```bash
   git checkout -b feature/0001-sdc-component-library
   git checkout -b site/vdg/homepage-redesign
   git checkout -b site/kbg/contact-form
   ```

2. **Reference notes in commits** so history is self-documenting:
   ```bash
   git commit -m "feat(vdg): implement hero component

   Implements docs/project-management/tasks/0001-hero-component-sdc.md
   Site: verdigris.nu (vdg)
   Decision: 0001-sdc-over-traditional-templates — see task for rationale."
   ```

3. **Commit the note with the code it describes.** Updating a task's `status`
   to `done` in the same commit that lands the feature keeps the vault honest.

4. **Site-specific commits**: prefix commit messages with site code when
   appropriate:
   - `feat(vdg): add custom contact form`
   - `fix(kbg): resolve mobile navigation issue`
   - `feat(shared): implement base card component`

5. **Releases**: when deploying to production, finalize the matching
   `releases/YYYY-MM-DD-site-deployment.md` note in the release commit.

## Multisite Development Workflow

### DDEV Local Development

The project uses DDEV with additional hostnames for local multisite development:

```yaml
# .ddev/config.yaml
additional_hostnames: ["verdigris", "kragebaekgaard", "hestehoj"]
```

**Local URLs:**
- Default site (verdigris): https://drupal-cms2.ddev.site
- Verdigris: https://verdigris.ddev.site
- Kragebaekgaard: https://kragebaekgaard.ddev.site
- Hestehoj: https://hestehoj.ddev.site

### Working with Specific Sites

```bash
# Target specific site with Drush using --uri flag
ddev exec drush --uri=verdigris.ddev.site cr
ddev exec drush --uri=kragebaekgaard.ddev.site config:export
ddev exec drush --uri=hestehoj.ddev.site uli

# Generate login link for specific site
ddev exec drush --uri=verdigris.ddev.site uli

# Clear cache for all sites
ddev exec drush --uri=verdigris.ddev.site cr
ddev exec drush --uri=kragebaekgaard.ddev.site cr
ddev exec drush --uri=hestehoj.ddev.site cr
```

### Site Directory Structure

```
web/sites/
├── default/           # Fallback configuration (not used in multisite)
├── vdg/              # verdigris.nu
│   ├── settings.php
│   ├── settings.local.php
│   └── files/
├── kbg/              # kragebaekgaard.dk
│   ├── settings.php
│   ├── settings.local.php
│   └── files/
└── shh/              # stutteri-hestehoj.dk
    ├── settings.php
    ├── settings.local.php
    └── files/
```

### Theme Development

Each site has its own custom theme:

```
web/themes/custom/
├── zwarte_piet/      # Theme for verdigris.nu
├── quick_silver/     # Theme for kragebaekgaard.dk
└── hestehoj/         # Theme for stutteri-hestehoj.dk
```

**Storybook Integration:**
- Storybook server available at: https://drupal-cms2.ddev.site:6006
- Used for developing and documenting SDC components
- Components can be previewed in isolation before integration

## .gitignore

`docs/.gitignore` ignores Obsidian's volatile per-machine state
(`**/.obsidian/workspace.json`, `workspace-mobile.json`, caches, `.trash/`) at
any depth, so it covers a `.obsidian/` folder whether you open `docs/` or
`docs/project-management/`. Shareable config (`app.json`, `appearance.json`,
hotkeys, community-plugin settings) is intentionally tracked so the vault setup
travels with the repo.

## Key Documentation Files

- **AGENTS.md** — AI agent instructions for working with this Drupal project
- **README.md** (root) — General Drupal CMS installation and setup
- **docs/project-management/README.md** — This file
- **docs/project-management/index.md** — Dashboard for all project documentation

## Getting Started

### For New Developers

1. **Clone and setup**:
   ```bash
   git clone <repository-url> cms2
   cd cms2
   ddev start
   ddev composer install
   ddev composer drupal:recipe-unpack
   ```

2. **Import site databases** (obtain from team):
   ```bash
   ddev import-db --database=db --file=vdg-backup.sql.gz
   ddev import-db --database=db_kbg --file=kbg-backup.sql.gz
   ddev import-db --database=db_shh --file=shh-backup.sql.gz
   ```

3. **Access sites**:
   - Verdigris: `ddev launch --uri=verdigris.ddev.site`
   - Kragebaekgaard: `ddev launch --uri=kragebaekgaard.ddev.site`
   - Hestehoj: `ddev launch --uri=hestehoj.ddev.site`

4. **Open the project vault**:
   - Launch Obsidian
   - Open `docs/` as vault
   - Navigate to `project-management/index.md`

### For Project Management

1. **Open the vault** in Obsidian (see "How to Open" above)
2. **Start with the dashboard** at `docs/project-management/index.md`
3. **Create tasks** from `templates/task.md` when planning new work
4. **Document decisions** using `templates/decision.md` for architectural choices
5. **Update status** as work progresses to keep the dashboard current

## Support and Resources

- **Drupal Documentation**: https://www.drupal.org/docs
- **Drupal CMS Guide**: https://project.pages.drupalcode.org/drupal_cms/
- **DDEV Documentation**: https://ddev.readthedocs.io
- **SDC Documentation**: https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components
- **Storybook Documentation**: https://storybook.js.org/docs

## License

This project builds on Drupal CMS and all derivative works are licensed under
the [GNU General Public License, version 2 or later](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html).
