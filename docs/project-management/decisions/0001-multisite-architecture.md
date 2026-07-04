---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-01-01
site: shared
deciders: [Architecture Team]
---

# 0001: Multisite Architecture with Dedicated Databases

## Status

accepted

## Context

The project needs to host multiple independent websites (verdigris.nu, kragebaekgaard.dk, stutteri-hestehoj.dk) that share similar functionality but require complete data isolation and site-specific customization. We needed to decide between:

1. Separate Drupal installations for each site
2. Drupal multisite with shared database
3. Drupal multisite with dedicated databases per site

Key considerations:
- Each site has distinct branding and design requirements
- Sites need independent content and configuration
- All sites can benefit from shared module updates and security patches
- Development efficiency through code reuse
- Complete data isolation for security and independence

## Decision

Implement Drupal 11 multisite architecture with dedicated databases for each site.

**Implementation details:**
- Single codebase in `/web` directory
- Shared Drupal core and contributed modules
- Site-specific directories under `/web/sites/`:
  - `vdg/` for verdigris.nu
  - `kbg/` for kragebaekgaard.dk
  - `shh/` for stutteri-hestehoj.dk
- Dedicated database per site (db, db_kbg, db_shh)
- Domain mapping via `sites.php`
- Site-specific private file storage (`private/`, `private_kbg/`)

## Consequences

### Positive

- **Code sharing**: Single codebase means updates, security patches, and new modules benefit all sites simultaneously
- **Data isolation**: Each site has its own database ensuring complete data separation
- **Independent configuration**: Sites can have different modules enabled, content types, and configurations
- **Cost efficient**: Single hosting environment, single DDEV setup
- **Development efficiency**: Common patterns and components can be developed once and adapted per site
- **Unified dependency management**: Single `composer.json` for all sites

### Negative

- **Complexity**: More complex than single-site setup, requires understanding of multisite architecture
- **Drush commands**: Must specify `--uri` flag for site-specific operations
- **Testing overhead**: Changes must be tested across multiple sites
- **Database management**: Multiple databases to backup, restore, and maintain
- **Deployment complexity**: Need to manage multiple site deployments

### Neutral

- **Shared modules**: All sites share the same module versions (can't have different versions per site)
- **Core updates**: All sites must update Drupal core together
- **DDEV configuration**: Requires additional hostname configuration but provides local URLs per site

## Alternatives Considered

### Alternative 1: Separate Drupal Installations

Each site would have its own complete Drupal installation with separate codebases.

**Rejected because:**
- Significant code duplication
- Module and security updates must be applied three times
- No code sharing between sites
- Higher maintenance burden
- More complex deployment pipeline

### Alternative 2: Multisite with Shared Database

Use table prefixes to separate site data within a single database.

**Rejected because:**
- Less data isolation (security concern)
- Performance impact from large shared database
- More complex database queries
- Difficult to backup/restore individual sites
- Risk of cross-site data leaks

## Implementation Notes

**DDEV Configuration:**
```yaml
additional_hostnames: ["verdigris", "kragebaekgaard", "hestehoj"]
```

**Domain Mapping in sites.php:**
```php
$sites = [
  'verdigris.nu' => 'vdg',
  'verdigris.ddev.site' => 'vdg',
  'kragebaekgaard.dk' => 'kbg',
  'kragebaekgaard.ddev.site' => 'kbg',
  'stutteri-hestehoj.dk' => 'shh',
  'hestehoj.ddev.site' => 'shh',
];
```

**Drush Usage:**
```bash
ddev exec drush --uri=verdigris.ddev.site cr
ddev exec drush --uri=kragebaekgaard.ddev.site config:export
```

## References

- Drupal Multisite Documentation: https://www.drupal.org/docs/getting-started/multisite-drupal
- DDEV Multisite Support: https://ddev.readthedocs.io/en/stable/users/topics/drupal/
- Related: [[0002-drupal-cms-as-base-platform]]
