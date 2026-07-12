---
tags: [cms2/index]
created: 2026-06-30
updated: 2026-06-30
---

# CMS2 Project Management Dashboard

Welcome to the CMS2 multisite platform project management vault. This dashboard provides an overview of all project documentation, architectural decisions, tasks, and notes.

## Quick Links

- **[README](README.md)** - Project management vault guide
- **[Architecture Decisions](#architecture-decisions)** - ADRs documenting key technical decisions
- **[Missing Configurations](#missing-configurations)** - Items needing attention
- **[Templates](#templates)** - Templates for creating new documentation

## Project Overview

**CMS2** is a Drupal 11-based multisite platform hosting multiple independent websites:

| Site                 | Code  | Status         | Theme        |
| -------------------- | ----- | -------------- | ------------ |
| verdigris.nu         | `vdg` | Active         | zwarte_piet  |
| kragebaekgaard.dk    | `kbg` | Active         | quick_silver |
| stutteri-hestehoj.dk | `shh` | In Development | hestehoj     |

**Platform:** Drupal CMS 2.0.0 on Drupal Core 11.3+  
**Development:** DDEV with PHP 8.4, MariaDB 10.11, Node.js 20  
**Architecture:** Multisite with dedicated databases, SDC components, Canvas page building

## Architecture Decisions

Architectural Decision Records (ADRs) documenting key technical choices:

### Platform & Infrastructure

- **[[decisions/0001-multisite-architecture]]** - Multisite with dedicated databases
- **[[decisions/0002-drupal-cms-as-base-platform]]** - Using Drupal CMS distribution
- **[[decisions/0012-ddev-local-development]]** - DDEV for development environment
- **[[decisions/0006-composer-patch-management]]** - Composer patches workflow

### Frontend & Theming

- **[[decisions/0003-canvas-for-page-building]]** - Canvas for visual page building
- **[[decisions/0004-sdc-component-architecture]]** - Single Directory Components
- **[[decisions/0005-tailwind-css-framework]]** - Tailwind CSS for styling
- **[[decisions/0007-site-specific-custom-themes]]** - Independent themes per site
- **[[decisions/0008-gin-admin-theme]]** - Gin as shared admin theme

### Features & Functionality

- **[[decisions/0009-webform-for-forms]]** - Webform module for form building
- **[[decisions/0010-spam-protection-strategy]]** - Multi-layered spam protection
- **[[decisions/0011-seo-optimization-stack]]** - Comprehensive SEO module stack

## Missing Configurations & Improvements

**[See infrastructure/missing-configurations.md for full details](infrastructure/missing-configurations.md)**

### Critical Priorities 🔴

1. **Configuration Management** - Set up per-site config directories
2. **CI/CD Pipeline** - Automated testing and deployment
3. **Automated Testing** - PHPUnit test infrastructure
4. **Backup Strategy** - Documented backup and recovery procedures
5. **Security Hardening** - Security headers and Seckit configuration

### Important Priorities 🟡

6. **Environment Configuration** - `.env.example` and config splits
7. **Performance Monitoring** - Caching and performance tools
8. **Documentation** - Comprehensive developer and operations guides
9. **Logging & Monitoring** - Centralized logging and alerting
10. **Development Workflow** - Branching strategy and deployment docs

## Current Module Stack

### Core Platform

- Drupal Core 11.3.13
- Drupal CMS Helper 2.x
- PHP 8.4, MariaDB 10.11

### Content & Page Building

- Canvas 1.x - Visual page building
- Webform 6.3.x - Form builder
- Layout Builder - Layouts
- Media Library - Asset management

### SEO & Marketing

- Pathauto 1.13 - URL aliases
- Metatag 2.2 - Meta tags with Open Graph
- Simple Sitemap 4.2 - XML sitemaps
- Redirect 1.10 - 301/302 redirects
- Yoast SEO 2.1 - Content optimization
- Google Tag 2.0 - Analytics

### Security & Spam Protection

- Seckit 2.0 - Security headers
- Honeypot 2.2 - Invisible spam protection
- Friendly Captcha 1.1 - Privacy-friendly CAPTCHA
- CAPTCHA 2.0 - CAPTCHA framework

### Frontend & Theming

- Gin 5.0 - Admin theme
- CVA 1.0 - Tailwind variant management
- UI Icons 1.1 - Icon system

### Developer Tools

- Coffee 2.0 - Admin navigation
- Devel (dev) - Development tools
- Examples (dev) - API reference code

### Workflow & Content

- Content Moderation - Editorial workflow
- Scheduler 2.2 - Scheduled publishing
- Autosave Form 1.11 - Auto-save drafts

## Templates

Use these templates when creating new documentation:

- **[templates/decision.md](templates/decision.md)** - Architecture Decision Record (ADR)
- **templates/task.md** - Task documentation (to be created)
- **templates/project.md** - Project initiative (to be created)

## Convention Reference

### Frontmatter Fields

```yaml
tags: [cms2/type] # Type: task, project, decision, notes, etc.
status: pending # pending, in_progress, review, done, blocked
created: YYYY-MM-DD # Creation date
updated: YYYY-MM-DD # Last update date
site: vdg # vdg, kbg, shh, or shared
priority: medium # low, medium, high
```

### Site Codes

- `vdg` - verdigris.nu
- `kbg` - kragebaekgaard.dk
- `shh` - stutteri-hestehoj.dk
- `shared` - Affects all sites

### Status Values

- `backlog` - Not yet scheduled
- `todo` - Scheduled but not started
- `in_progress` - Currently being worked on
- `review` - Awaiting review
- `done` - Completed
- `blocked` - Blocked by external dependency
- `dropped` - No longer relevant

## Development Workflow

### Local Development

```bash
# Start DDEV environment
ddev start

# Access sites
ddev launch --uri=verdigris.ddev.site
ddev launch --uri=kragebaekgaard.ddev.site
ddev launch --uri=hestehoj.ddev.site

# Site-specific commands
ddev exec drush --uri=verdigris.ddev.site cr
ddev exec drush --uri=kragebaekgaard.ddev.site config:export
ddev exec drush --uri=hestehoj.ddev.site uli
```

### Branch Naming

```
feature/NNNN-description     # New features
bugfix/NNNN-description      # Bug fixes
hotfix/NNNN-description      # Critical production fixes
site/vdg/description         # Site-specific work
site/kbg/description
site/shh/description
```

### Commit Messages

```
feat(vdg): add hero component

Implements docs/project-management/tasks/0001-hero-component.md
Site: verdigris.nu (vdg)
```

## Key Documentation Files

- **[README.md](README.md)** - This project management vault
- **[AGENTS.md](../../AGENTS.md)** - AI agent development instructions
- **[Root README.md](../../README.md)** - Main project README

## Resources

- **Drupal CMS**: https://www.drupal.org/project/cms
- **DDEV**: https://ddev.com
- **Canvas**: https://www.drupal.org/project/canvas
- **SDC**: https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components
- **Drupal.org**: https://www.drupal.org

## Maintenance

- **Review ADRs** - Quarterly review of decisions
- **Update this dashboard** - As project evolves
- **Archive completed items** - Keep dashboard focused
- **Link new decisions** - Cross-reference related ADRs

---

**Last updated:** 2026-06-30  
**Vault location:** `docs/project-management/`  
**Open in Obsidian:** Select `docs/` as vault root
