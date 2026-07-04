---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-01-01
site: shared
deciders: [DevOps Team, Architecture Team]
---

# 0006: Composer Patches with cweagans/composer-patches v2

## Status

accepted

## Context

In Drupal development, it's common to need patches for contributed modules to fix bugs or add features before official releases. The project needed a reliable, version-controlled way to manage patches across all sites.

Key requirements:
- Apply patches automatically during composer install
- Track which patches are applied
- Version control patch definitions
- Prevent deployment if patches fail
- Support patches from drupal.org and custom patches

Options available:
1. Manual patch application (error-prone, not repeatable)
2. cweagans/composer-patches v1 (older version)
3. cweagans/composer-patches v2 (current, with lock file)
4. Composer patches plugin alternatives

## Decision

Use cweagans/composer-patches version 2 with lock file tracking for all patch management across the multisite installation.

**Implementation approach:**
- Patches defined in composer.json under `extra.patches`
- Patch state tracked in `patches.lock.json`
- Custom patches stored in `/patches` directory
- Exit on patch failure to prevent broken deployments
- Patches applied to contrib modules only (not core)

## Consequences

### Positive

- **Automatic application**: Patches applied during `composer install`
- **Version controlled**: All patch definitions in git
- **Lock file tracking**: `patches.lock.json` ensures consistent patch state
- **Failure protection**: Build fails if patches can't be applied
- **Team visibility**: Everyone knows which patches are active
- **Reproducible builds**: Same patches on all environments
- **Easy updates**: `composer reinstall` to reapply after changes
- **Documentation**: Patch purpose documented in composer.json

### Negative

- **Manual reinstall needed**: Changes require `composer reinstall <package>`
- **Merge conflicts**: patches.lock.json can cause git conflicts
- **Upstream tracking**: Must monitor for when patches are merged upstream
- **Patch rot**: Patches may fail on module updates
- **Git diffs**: Patches stored as files add to repository size

### Neutral

- **Two-file system**: Both composer.json and patches.lock.json must be committed
- **Format requirements**: Patches must use proper git diff format with package-relative paths

## Alternatives Considered

### Alternative 1: Manual Patch Application

Apply patches manually to vendor directory after composer install.

**Rejected because:**
- Not reproducible across environments
- Easy to forget patches
- No version control
- Team members may have different patches applied
- Patches lost on composer install

### Alternative 2: Forking Modules

Fork contributed modules and maintain patched versions.

**Rejected because:**
- High maintenance burden
- Must track upstream updates manually
- Diverges from community versions
- Harder to contribute fixes back
- Complicates updates

### Alternative 3: cweagans/composer-patches v1

Use older version without lock file.

**Rejected because:**
- Less reliable state tracking
- Harder to debug patch issues
- Version 2 is current standard
- Missing newer features
- Less community support

## Implementation Notes

**Composer configuration:**
```json
{
  "require": {
    "cweagans/composer-patches": "^2.0"
  },
  "config": {
    "allow-plugins": {
      "cweagans/composer-patches": true
    }
  },
  "extra": {
    "enable-patching-from-dependencies": true,
    "patches": {
      "drupal/byte_theme": {
        "Custom Byte Theme main.css": "patches/byte_theme_main.patch"
      },
      "drupal/canvas": {
        "Fix TypeError in ComponentMetadataRequirementsChecker": "patches/canvas-component-metadata-closure-type-fix.patch"
      }
    }
  }
}
```

**Current patches applied:**

| Module | Patch File | Purpose |
|--------|------------|---------|
| drupal/byte_theme | patches/byte_theme_main.patch | Custom background styles for front-page hero |
| drupal/canvas | patches/canvas-component-metadata-closure-type-fix.patch | Fix TypeError when example items aren't arrays |
| drupal/geofield | patches/geofield-drupal-11.2-plugin-manager-fix.patch | Drupal 11.2 compatibility fix |
| drupal/geocoder | patches/geocoder-geofield-proximity-source-attributes.patch | Proximity source attribute fixes |

**Applying patches after changes:**
```bash
# Reinstall specific package to reapply patches
ddev exec composer reinstall drupal/canvas

# Or reinstall all to be safe
ddev exec composer install
```

**Patch format requirements:**
- Must use `git diff` format (not plain `diff`)
- Paths must be package-relative (not absolute)
- Include proper diff headers

**Previous issues resolved:**
- Commit 707651f: Removed duplicate byte_theme_theme.patch
- Commit b95ae5f: Purged stale entry from patches.lock.json
- Fixed malformed diff headers causing "No available patcher" errors

## References

- cweagans/composer-patches: https://github.com/cweagans/composer-patches
- Drupal.org patch documentation: https://www.drupal.org/patch
- Related: [[0002-drupal-cms-as-base-platform]]
