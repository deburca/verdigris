---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-01-01
site: shared
deciders: [Architecture Team]
---

# 0002: Drupal CMS as Base Platform

## Status

accepted

## Context

The project needed a robust, enterprise-grade content management platform with modern developer experience, strong security, and extensive ecosystem. We needed to choose between building from Drupal Core or using the Drupal CMS distribution.

Drupal CMS is a ready-to-use platform built on Drupal Core that offers:
- Smart defaults and opinionated structure
- Pre-configured recipes for common functionality
- Modern admin experience with Gin theme
- Built-in support for AI, Canvas, and modern tooling
- Strong focus on content creator experience

## Decision

Use Drupal CMS (version 2.0.0) as the base platform instead of vanilla Drupal Core.

**Key components included:**
- Drupal Core 11.3+ with PHP 8.4
- Drupal CMS recipes and helper modules
- Gin admin theme with modern UI
- Canvas module for visual page building
- Pre-configured SEO, accessibility, and anti-spam tools
- AI integration capabilities
- Modern navigation with Navigation module

## Consequences

### Positive

- **Faster initial setup**: Smart defaults reduce configuration time
- **Modern admin UX**: Gin theme and Navigation module provide excellent admin experience
- **Recipe system**: Modular functionality through Drupal recipes
- **AI capabilities**: Built-in AI module integration for content optimization
- **Canvas integration**: Visual page building out of the box
- **Active development**: Part of Drupal.org official project with ongoing support
- **Best practices**: Follows Drupal community standards and recommendations
- **Security focus**: Includes security modules (seckit, honeypot, captcha)
- **SEO ready**: Pre-configured with metatag, pathauto, redirect, simple_sitemap

### Negative

- **Opinionated structure**: More modules and configuration than minimal Drupal Core
- **Learning curve**: Team needs to understand CMS-specific patterns and recipes
- **Update complexity**: Must follow Drupal CMS update path, not just core
- **Larger footprint**: More dependencies than minimal installation
- **Some unused features**: Not all CMS features may be relevant to all sites

### Neutral

- **Recipe dependencies**: Sites can selectively use CMS recipes or create custom ones
- **Theme independence**: Can still use custom themes (not required to use Byte)
- **Module flexibility**: Can disable unused CMS modules while keeping core functionality

## Alternatives Considered

### Alternative 1: Vanilla Drupal Core

Start with minimal Drupal Core and add only required modules.

**Rejected because:**
- Significant configuration time to reach same functionality
- Need to make many architectural decisions already solved by CMS
- Miss out on integrated recipe system
- Less cohesive admin experience without Gin setup
- More maintenance burden for common features

### Alternative 2: Custom Distribution

Build our own Drupal distribution with selected modules.

**Rejected because:**
- Significant development and maintenance overhead
- Reinventing solutions already provided by Drupal CMS
- No community support for custom distribution
- Team would need to maintain upgrade path
- Limited resources for maintaining a distribution

### Alternative 3: Acquia Site Studio or Similar

Use a commercial Drupal-based platform.

**Rejected because:**
- License costs for multiple sites
- Vendor lock-in concerns
- Less flexibility for customization
- Open source preference for this project
- Drupal CMS provides sufficient functionality

## Implementation Notes

**Composer base:**
```json
{
  "name": "drupal/cms",
  "version": "2.0.0",
  "require": {
    "drupal/core": "^11.3",
    "drupal/drupal_cms_helper": "^2",
    "drupal/gin": "^5",
    "drupal/navigation_extra_tools": "^1.3",
    ...
  }
}
```

**Recipe usage:**
```bash
ddev composer drupal:recipe-unpack
```

**Enabled CMS recipes:**
- drupal_cms_admin_ui
- drupal_cms_media
- drupal_cms_forms
- drupal_cms_seo_tools
- drupal_cms_accessibility_tools
- drupal_cms_anti_spam
- drupal_cms_ai (for AI-assisted content features)

## References

- Drupal CMS Project: https://www.drupal.org/project/cms
- Drupal CMS Documentation: https://project.pages.drupalcode.org/drupal_cms/
- Related: [[0001-multisite-architecture]]
- Related: [[0003-canvas-for-page-building]]
