---
tags: [cms2/infrastructure, cms2/notes]
status: active
created: 2026-06-30
site: shared
priority: medium
---

# Missing Configurations and Recommended Patterns

This document identifies configuration items, patterns, and best practices that are currently missing or could be improved in the CMS2 multisite platform.

## Critical Missing Items

### 1. Configuration Management Strategy

**Status:** ⚠️ Partially configured but inconsistent

**Current state:**
- Configuration sync directory set to `sites/default/files/sync`
- Configuration exists in sync directory
- `.gitignore` has exceptions for sync directories
- No site-specific config directories
- Config is out of sync (`drush config:status` shows differences)

**Issues:**
- Multisite installations typically need per-site config management
- Current approach treats `default` site as primary, but sites use vdg/kbg/shh directories
- Configuration sync location should be outside web-accessible area
- No documented config workflow for multisite

**Recommendations:**

1. **Per-site configuration directories:**
```bash
# Create config directories outside web root
mkdir -p config/vdg/sync
mkdir -p config/kbg/sync
mkdir -p config/shh/sync
```

2. **Update settings.php for each site:**
```php
// web/sites/vdg/settings.php
$settings['config_sync_directory'] = '../../../config/vdg/sync';

// web/sites/kbg/settings.php
$settings['config_sync_directory'] = '../../../config/kbg/sync';

// web/sites/shh/settings.php
$settings['config_sync_directory'] = '../../../config/shh/sync';
```

3. **Update .gitignore:**
```gitignore
# Remove these lines:
!files/sync
!files/sync/*
!sites/*/files/sync
!sites/*/files/sync/*

# Add config directories (KEEP these in version control):
!/config/
!/config/*/
!/config/*/sync/
!/config/*/sync/*.yml
```

4. **Export configuration per site:**
```bash
ddev exec drush --uri=verdigris.ddev.site config:export
ddev exec drush --uri=kragebaekgaard.ddev.site config:export
ddev exec drush --uri=hestehoj.ddev.site config:export
```

5. **Document workflow:**
- Export config after significant changes
- Commit config changes with code changes
- Import config on deployment
- Use config splits for environment-specific settings

**Priority:** 🔴 High - Essential for proper deployment and version control

---

### 2. CI/CD Pipeline

**Status:** ❌ Not configured

**Current state:**
- `.gitignore` excludes `.github/workflows/` directory
- No continuous integration configured
- No automated testing
- No automated deployment

**Issues:**
- Code quality not enforced automatically
- No automated testing before merge
- Manual deployment increases error risk
- No automated security scanning

**Recommendations:**

1. **Create GitHub Actions workflow:**
```yaml
# .github/workflows/ci.yml
name: CI

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  phpcs:
    name: Code Standards
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - name: Install dependencies
        run: composer install
      - name: Run PHPCS
        run: vendor/bin/phpcs --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml web/modules/custom web/themes/custom

  phpunit:
    name: PHPUnit Tests
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mariadb:10.11
        env:
          MYSQL_ROOT_PASSWORD: drupal
          MYSQL_DATABASE: drupal
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: gd, pdo_mysql
      - name: Install dependencies
        run: composer install
      - name: Run PHPUnit
        run: vendor/bin/phpunit

  security:
    name: Security Audit
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - name: Install dependencies
        run: composer install
      - name: Security audit
        run: composer audit
```

2. **Update .gitignore:**
```gitignore
# Remove this line:
# .github/workflows/

# Instead, keep CI/CD workflows in version control
```

3. **Add deployment workflow:**
Create separate workflows for staging and production deployments.

**Priority:** 🔴 High - Essential for code quality and safe deployments

---

### 3. Automated Testing Infrastructure

**Status:** ❌ Not configured

**Current state:**
- PHPUnit available in composer.json (`phpunit/phpunit: ^11.5`)
- No test files exist
- `phpunit.xml` excluded by .gitignore
- No test coverage tracking

**Issues:**
- No automated regression testing
- Changes can break existing functionality unnoticed
- No way to verify multisite compatibility

**Recommendations:**

1. **Create PHPUnit configuration:**
```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="web/core/tests/bootstrap.php"
         colors="true"
         beStrictAboutTestsThatDoNotTestAnything="true"
         beStrictAboutOutputDuringTests="true"
         beStrictAboutChangesToGlobalState="true">
  <php>
    <ini name="error_reporting" value="32767"/>
    <env name="SIMPLETEST_BASE_URL" value="http://drupal-cms2.ddev.site"/>
    <env name="SIMPLETEST_DB" value="mysql://db:db@db/db"/>
    <env name="BROWSERTEST_OUTPUT_DIRECTORY" value="web/sites/simpletest/browser_output"/>
  </php>
  <testsuites>
    <testsuite name="unit">
      <directory>./web/modules/custom/*/tests/src/Unit</directory>
      <directory>./web/themes/custom/*/tests/src/Unit</directory>
    </testsuite>
    <testsuite name="kernel">
      <directory>./web/modules/custom/*/tests/src/Kernel</directory>
    </testsuite>
    <testsuite name="functional">
      <directory>./web/modules/custom/*/tests/src/Functional</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

2. **Create test directory structure:**
```bash
# For any custom modules (when created)
mkdir -p web/modules/custom/example_module/tests/src/{Unit,Kernel,Functional}
```

3. **Add sample tests:**
Document testing patterns and create example tests for common scenarios.

**Priority:** 🟡 Medium - Important for long-term maintainability

---

### 4. Environment-Specific Configuration Management

**Status:** ⚠️ Partially configured

**Current state:**
- `.env` files in .gitignore (good)
- `settings.local.php` in .gitignore (good)
- DDEV provides some environment variables
- No clear environment variable documentation

**Issues:**
- No documented environment variables
- No example `.env.example` file
- API keys storage not documented
- No clear dev/staging/production config split

**Recommendations:**

1. **Create `.env.example`:**
```bash
# .env.example
# Copy to .env and fill in your values

# Environment
ENVIRONMENT=development

# API Keys (use Key module for production)
ANTHROPIC_API_KEY=your_key_here
FRIENDLY_CAPTCHA_SITE_KEY=your_site_key
FRIENDLY_CAPTCHA_SECRET=your_secret

# External services
GOOGLE_TAG_ID=
GOOGLE_ANALYTICS_ID=

# Mail configuration
SMTP_HOST=
SMTP_PORT=
SMTP_USER=
SMTP_PASS=
```

2. **Document environment setup in README:**
Add section explaining environment configuration for new developers.

3. **Use Config Split module:**
```bash
ddev composer require drupal/config_split
```

Then create splits for:
- Development (devel, webprofiler, etc.)
- Staging (some debugging tools)
- Production (minimal modules, performance optimized)

**Priority:** 🟡 Medium - Important for deployment consistency

---

### 5. Database Backup and Disaster Recovery

**Status:** ⚠️ Manual only

**Current state:**
- DDEV snapshots available (`ddev snapshot`)
- `.sql` files in .gitignore (good - no accidental commits)
- No documented backup strategy
- No automated backups

**Issues:**
- No regular backup schedule
- No documented recovery procedures
- No backup verification
- No off-site backup storage

**Recommendations:**

1. **Document backup procedures:**
```bash
# Create backup directory (outside git)
mkdir -p backups

# Backup script for all sites
#!/bin/bash
DATE=$(date +%Y%m%d-%H%M%S)

# Backup each site
ddev exec drush --uri=verdigris.ddev.site sql:dump --gzip > backups/vdg-$DATE.sql.gz
ddev exec drush --uri=kragebaekgaard.ddev.site sql:dump --gzip > backups/kbg-$DATE.sql.gz
ddev exec drush --uri=hestehoj.ddev.site sql:dump --gzip > backups/shh-$DATE.sql.gz

# Export configuration
ddev exec drush --uri=verdigris.ddev.site config:export
ddev exec drush --uri=kragebaekgaard.ddev.site config:export
ddev exec drush --uri=hestehoj.ddev.site config:export

# Backup files (if needed)
tar -czf backups/files-vdg-$DATE.tar.gz web/sites/vdg/files/
tar -czf backups/files-kbg-$DATE.tar.gz web/sites/kbg/files/
tar -czf backups/files-shh-$DATE.tar.gz web/sites/shh/files/
```

2. **Add to crontab or scheduled task:**
Run backup script daily, keep last 7 days, weekly for 4 weeks, monthly for 6 months.

3. **Document recovery:**
Create step-by-step recovery procedures for each scenario:
- Single site corruption
- Complete database loss
- File system corruption
- Complete platform rebuild

**Priority:** 🔴 High - Critical for business continuity

---

## Moderate Priority Items

### 6. Performance Monitoring and Optimization

**Status:** ⚠️ Limited

**Current state:**
- BigPipe enabled (good)
- No performance monitoring
- No caching layer beyond Drupal internal cache
- No CDN configuration

**Recommendations:**

1. **Install performance modules:**
```bash
ddev composer require drupal/memcache drupal/redis
```

2. **Configure caching:**
- Redis for cache backend
- Memcache as alternative
- Varnish or Cloudflare CDN for production

3. **Add performance monitoring:**
```bash
ddev composer require drupal/webprofiler
# Enable only in development
```

4. **Document performance baselines:**
- Page load times
- Time to first byte (TTFB)
- Database query counts
- Cache hit rates

**Priority:** 🟡 Medium

---

### 7. Security Headers and Hardening

**Status:** ⚠️ Partially configured

**Current state:**
- Seckit module installed (`drupal/seckit: ^2.0`)
- No documented security header configuration
- `.htaccess` modifications tracked
- **Fixed 2026-07-05:** `display_errors` was `On` for the PHP-FPM (web) SAPI —
  raw, unstyled PHP warnings/deprecation notices (e.g. the many implicit-nullable-
  parameter deprecations throughout the `bat`/`bee` contrib codebase, surfaced by
  enabling those modules per [[0010-enable-shh-commerce-bat-bee-modules]]) were
  being written directly into HTML response bodies instead of going through
  Drupal's own themed/logged error handling (`system.logging.error_level`, which
  was already correctly set to `hide` but doesn't govern raw PHP-level
  `display_errors` output for `E_DEPRECATED`). Fixed via
  `.ddev/php/error-display.ini` (`display_errors = stderr`,
  `display_startup_errors = Off`); `log_errors` remains on so nothing is lost —
  errors now go to the container log instead of the page. This is a DDEV/php.ini
  setting shared by all three sites (vdg, kbg, shh), not shh-specific.

**Recommendations:**

1. **Configure Seckit module:**
- Content Security Policy (CSP)
- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy
- Permissions-Policy

2. **Review and harden .htaccess:**
- Ensure security headers present
- Disable directory browsing
- Protect sensitive files

3. **Regular security audits:**
```bash
# Check for security updates
ddev composer audit

# Check module status
ddev exec drush pm:security
```

**Priority:** 🔴 High - Security is critical

---

### 8. Documentation Structure

**Status:** ⚠️ Basic structure in place

**Current state:**
- README.md exists (basic Drupal CMS info)
- AGENTS.md exists (AI agent instructions)
- Project management vault started
- Missing comprehensive documentation

**Recommendations:**

1. **Create documentation structure:**
```
docs/
├── project-management/    # This vault
├── architecture/          # Architecture diagrams and decisions
├── development/           # Development guides
├── deployment/            # Deployment procedures
├── operations/            # Operations runbooks
└── api/                  # API documentation (if applicable)
```

2. **Essential documentation needed:**
- Onboarding guide for new developers
- Deployment checklist
- Troubleshooting guide
- Architecture diagrams
- Component library documentation
- API documentation (for any custom APIs)

**Priority:** 🟡 Medium - Important for team collaboration

---

### 9. Logging and Monitoring

**Status:** ⚠️ Basic only

**Current state:**
- Database logging (dblog) enabled
- No centralized logging
- No application monitoring
- No uptime monitoring

**Recommendations:**

1. **Install logging modules:**
```bash
ddev composer require drupal/syslog drupal/monolog
```

2. **Configure syslog** for production (instead of dblog)

3. **Set up monitoring:**
- Uptime monitoring (UptimeRobot, Pingdom, etc.)
- Application monitoring (New Relic, Datadog, etc.)
- Error tracking (Sentry, Rollbar, etc.)

4. **Log aggregation:**
- Centralized logging (ELK stack, Splunk, etc.)
- Log retention policies
- Alert thresholds

**Priority:** 🟡 Medium

---

### 10. Development Workflow Documentation

**Status:** ⚠️ Minimal

**Current state:**
- Git repository configured
- No branching strategy documented
- No code review process documented
- No deployment workflow documented

**Recommendations:**

1. **Document branching strategy:**
```
main         - Production-ready code
├── staging  - Staging environment
└── develop  - Development integration
    ├── feature/NNNN-feature-name
    ├── bugfix/NNNN-bug-description
    └── hotfix/NNNN-critical-fix
```

2. **Code review process:**
- Pull request requirements
- Review checklist
- Testing requirements
- Approval workflow

3. **Deployment workflow:**
- Development → Staging → Production
- Deployment checklist
- Rollback procedures
- Post-deployment verification

**Priority:** 🟡 Medium - Important for team coordination

---

## Lower Priority Enhancements

### 11. Accessibility Testing

- Automated accessibility testing in CI
- Editoria11y module already installed (good!)
- Regular manual accessibility audits
- WCAG 2.1 AA compliance verification

**Priority:** 🟢 Low - Enhancement

---

### 12. Internationalization Setup

- Locale module (if needed)
- Content translation workflows
- Interface translation management
- Multi-language URL patterns

**Priority:** 🟢 Low - Only if needed

---

### 13. Advanced Media Management

- Media Library Bulk Upload installed (good!)
- Image optimization pipeline
- Responsive image configuration
- Video transcoding (if needed)
- CDN integration for media

**Priority:** 🟢 Low - Enhancement

---

## Summary Checklist

### Critical (Do First)
- [ ] Configure per-site configuration management
- [ ] Set up CI/CD pipeline
- [ ] Implement automated testing
- [ ] Document backup and recovery procedures
- [ ] Configure security headers and hardening

### Important (Do Soon)
- [ ] Create environment-specific configuration
- [ ] Set up performance monitoring
- [ ] Enhance documentation structure
- [ ] Configure logging and monitoring
- [ ] Document development workflow

### Nice to Have (When Time Permits)
- [ ] Accessibility testing automation
- [ ] Internationalization setup (if needed)
- [ ] Advanced media management
- [ ] Additional performance optimizations

---

## Notes

- Review this document quarterly and update based on project evolution
- Prioritize items based on current project phase and business needs
- Consider creating tasks in project management vault for each item
- Link completed items to relevant ADRs when implemented

**Last updated:** 2026-06-30
**Next review:** 2026-09-30
