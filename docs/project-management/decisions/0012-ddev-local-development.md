---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-01-01
site: shared
deciders: [DevOps Team, Architecture Team]
---

# 0012: DDEV for Local Development Environment

## Status

accepted

## Context

Development team needed a consistent, reproducible local development environment that:

- Works across different operating systems (macOS, Windows, Linux)
- Supports Drupal 11 requirements (PHP 8.4, MariaDB 10.11)
- Handles multisite architecture with multiple databases
- Provides isolated environments per developer
- Easy to setup and maintain
- Supports modern tooling (Composer, Drush, Node.js, Yarn)
- Fast performance for daily development

Available options:
1. Native LAMP/MAMP/XAMPP stack
2. DDEV (Docker-based)
3. Lando (Docker-based)
4. Docksal (Docker-based)
5. Custom Docker Compose setup

## Decision

Adopt DDEV as the standard local development environment for all developers working on the CMS2 project.

**Configuration:**
- Project type: drupal11
- PHP version: 8.4
- Database: MariaDB 10.11
- Node.js: 20
- Web server: Apache-FPM
- Additional hostnames for multisite
- Storybook support on extra port

## Consequences

### Positive

- **Consistency**: All developers have identical environments
- **Multisite support**: Easy hostname configuration for each site
- **Cross-platform**: Works on macOS, Windows, Linux
- **Isolated**: Each project has own containers
- **Fast setup**: `ddev start` and ready to go
- **Database management**: Easy snapshots, import/export
- **Modern tooling**: Composer, Drush, Node, Yarn pre-configured
- **Mailhog integration**: Catch outgoing emails locally
- **Active development**: Well-maintained with strong community
- **Documentation**: Excellent official documentation
- **Drupal-optimized**: Drupal-specific optimizations built-in
- **Xdebug support**: Easy debugging with IDE integration
- **Performance**: Good performance with Mutagen on macOS

### Negative

- **Docker dependency**: Requires Docker Desktop (or alternatives)
- **Resource usage**: Docker containers use memory and CPU
- **Learning curve**: Team needs to learn DDEV commands
- **Disk space**: Container images and volumes use disk space
- **Network issues**: Occasional port conflicts or DNS issues
- **macOS performance**: File sync can be slow without Mutagen

### Neutral

- **Updates**: DDEV updates occasionally require project restart
- **Configuration**: Can be customized but defaults work well
- **Backups**: Need to backup `.ddev` configuration

## Alternatives Considered

### Alternative 1: Native LAMP Stack

Install PHP, MySQL, Apache directly on development machines.

**Rejected because:**
- Different configurations per developer
- Hard to maintain consistency
- Version conflicts with other projects
- OS-specific issues
- No isolation between projects
- Difficult to match production environment

### Alternative 2: Lando

Another Docker-based development environment.

**Rejected because:**
- Less Drupal-focused than DDEV
- More complex configuration
- Smaller community than DDEV
- DDEV has better Drupal integration
- Team already familiar with DDEV

### Alternative 3: Docksal

Docker-based environment with focus on Drupal/WordPress.

**Rejected because:**
- Less widely adopted than DDEV
- More opinionated than DDEV
- Smaller community
- DDEV momentum in Drupal community
- No significant advantages over DDEV

### Alternative 4: Custom Docker Compose

Build custom Docker Compose setup.

**Rejected because:**
- Significant setup and maintenance time
- Reinventing what DDEV provides
- Each developer might customize differently
- No built-in Drupal optimizations
- Loss of DDEV's convenience features

### Alternative 5: Platform.sh / Acquia Dev Desktop

Use hosting provider's local development tools.

**Rejected because:**
- Not hosting-agnostic
- Less flexible than DDEV
- Some are deprecated (Acquia Dev Desktop)
- Vendor lock-in concerns
- DDEV more actively developed

## Implementation Notes

**DDEV Configuration (.ddev/config.yaml):**
```yaml
name: drupal-cms2
type: drupal11
docroot: web
php_version: "8.4"
nodejs_version: "20"
webserver_type: apache-fpm
database:
  type: mariadb
  version: "10.11"

# Multisite support
additional_hostnames:
  - verdigris
  - kragebaekgaard
  - hestehoj

# Storybook integration
web_extra_exposed_ports:
  - name: storybook
    container_port: 6006
    http_port: 6007
    https_port: 6006

web_extra_daemons:
  - name: node.js
    command: "tail -F package.json > /dev/null"
    directory: /var/www/html

# Post-start hook
hooks:
  post-start:
    - exec: yarn set version berry
```

**Local URLs:**
- Main site: https://drupal-cms2.ddev.site
- Verdigris: https://verdigris.ddev.site
- Kragebaekgaard: https://kragebaekgaard.ddev.site
- Hestehoj: https://hestehoj.ddev.site
- Storybook: https://drupal-cms2.ddev.site:6006

**Common commands:**
```bash
# Start environment
ddev start

# Execute Drush commands
ddev exec drush cr
ddev exec drush --uri=verdigris.ddev.site uli

# Composer commands
ddev composer install
ddev composer require drupal/module_name

# Database operations
ddev snapshot
ddev import-db --file=backup.sql.gz
ddev export-db > backup.sql

# SSH into container
ddev ssh

# View logs
ddev logs -f
```

**Database strategy:**
- Primary database: `db` (mapped to vdg site)
- Additional databases created as needed for kbg, shh
- Each site connects to its own database via settings.php

**Performance optimization:**
- Mutagen sync available on macOS (optional)
- NFS mounting option for better performance
- Performance mode configurable per developer preference

**PHP extensions:**
Extra PHP packages installed via config:
```yaml
webimage_extra_packages:
  - php8.4-yaml
  - php8.4-tidy
  - php8.4-gmp
```

**Xdebug:**
Enable when needed:
```bash
ddev xdebug on   # Enable
ddev xdebug off  # Disable (better performance)
```

**Yarn Berry:**
Post-start hook sets Yarn to Berry version (4.16.0) for modern package management.

**Mailhog:**
All outgoing emails caught at: https://drupal-cms2.ddev.site:8026

**Workflow:**
1. Clone repository
2. `ddev start`
3. `ddev composer install`
4. Import site databases
5. Development ready

## References

- DDEV: https://ddev.com
- DDEV Documentation: https://ddev.readthedocs.io
- DDEV Drupal Guide: https://ddev.readthedocs.io/en/stable/users/topics/drupal/
- Related: [[0001-multisite-architecture]]
- Related: [[AGENTS.md]] for AI agent DDEV usage
