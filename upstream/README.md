# upstream/ — drupal/cms template baseline

This project's `composer.json` and `.gitignore` began as copies of the
[`drupal/cms`](https://git.drupalcode.org/project/cms) project template,
then diverged heavily (extra contrib, `patches`, `minimum-stability:
stable`, scaffold overrides, 28 custom modules' worth of requires).

`composer update drupal/core-*` keeps *core and contrib* current but
never surfaces changes the Drupal CMS maintainers make to the **template
itself** — `require` floor bumps, `extra.drupal-scaffold.file-mapping`,
`config.allow-plugins`, `installer-paths`, `repositories`, the recipe
list. This directory + `make upstream-diff` is how those get reviewed.

## Files

| File | What it is |
|------|------------|
| `BASELINE` | The `drupal/cms` tag this project last reconciled with. |
| `composer.json` | Verbatim upstream `composer.json` at that tag. |
| `.gitignore` | Verbatim upstream `.gitignore` at that tag. |

The `composer.json` / `.gitignore` here are **reference copies, not
active config** — nothing builds from them. They exist only to give
`git diff` / `diff -u` a local anchor so your intentional divergence
doesn't drown the signal every time.

## Workflow (each Drupal CMS release)

```sh
make upstream-diff                 # vs latest release
make upstream-diff CMS_VERSION=2.1.3
```

Two diffs per tracked file:

- **[1] baseline → target** — what upstream changed since you forked.
  This is the one to read and act on.
- **[2] yours → target** — everything still differing: your deliberate
  changes plus any upstream change you haven't adopted. Noisy by design.

Hand-apply the wanted parts of [1] to the real `composer.json` /
`.gitignore`, run `composer update` for anything whose constraint moved,
test the site. Then advance the baseline:

```sh
make upstream-promote CMS_VERSION=2.1.3   # re-vendors upstream/*, bumps BASELINE
```

Review and commit the `upstream/` changes together with your reconciled
`composer.json`.

## What to scan for in diff [1]

- `require` — constraint bumps on packages this project also carries
  (usually a security floor)
- `extra.drupal-scaffold.file-mapping` — new scaffolded files, or files
  newly set to `false`
- `config.allow-plugins` — plugins upstream newly trusts
- `extra.installer-paths` — new type mappings
- `repositories` — new package sources
- `minimum-stability` / `conflict`
- the `drupal/drupal_cms_*` recipe list in `require`

Ignore anything that is purely this project's divergence (the extra
`drupal/*` requires, `patches`, `minimum-stability: stable` vs upstream
`dev`, the vendored `jquery/*` / library `repositories`).

## Adding another tracked file

Edit the `FILES=( … )` array in
`scripts/upstream-diff/upstream-diff.sh` — entries are
`repo-path:upstream-repo-path` — then run `make upstream-promote` once to
seed its baseline copy.
