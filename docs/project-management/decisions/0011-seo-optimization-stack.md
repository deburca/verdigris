---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-01-01
site: shared
deciders: [Architecture Team, Marketing Team]
---

# 0011: SEO Optimization Module Stack

## Status

accepted

## Context

All sites need strong search engine optimization to improve discoverability and ranking. The project needed a comprehensive SEO strategy covering:

- Meta tags (title, description, Open Graph, Twitter Cards)
- Clean URLs with automatic path aliases
- XML sitemaps for search engines
- Redirect management for URL changes
- 404 error tracking
- Structured data and schema.org markup
- Content optimization tools

Drupal has mature SEO ecosystem with multiple specialized modules. Need to select optimal combination that works together without conflicts.

## Decision

Implement comprehensive SEO stack using proven Drupal modules:

**Core SEO modules:**
- **Pathauto**: Automatic URL alias generation
- **Metatag**: Meta tag management with Open Graph & Twitter Cards
- **Simple Sitemap**: XML sitemap generation
- **Redirect**: 301/302 redirect management with 404 tracking
- **Token**: Token system for dynamic values

**Enhanced SEO tools:**
- **Yoast SEO**: Content optimization analysis
- **SEO Checklist**: Implementation tracking
- **Google Tag**: Analytics and tag management
- **Easy Breadcrumb**: Breadcrumb navigation for UX and SEO

## Consequences

### Positive

- **Comprehensive coverage**: All major SEO needs addressed
- **Proven modules**: Battle-tested modules with large user bases
- **Content creator tools**: Yoast provides real-time optimization feedback
- **Automatic features**: Pathauto and sitemap work automatically
- **Redirect handling**: 404s tracked, redirects managed
- **Social sharing**: Open Graph and Twitter Cards for rich previews
- **Structured approach**: SEO Checklist ensures nothing missed
- **Analytics ready**: Google Tag Manager integration
- **User experience**: Breadcrumbs improve navigation
- **Token integration**: Dynamic meta tags based on content

### Negative

- **Module count**: Multiple modules to maintain
- **Configuration complexity**: Many settings across modules
- **Potential conflicts**: Must configure carefully to avoid overlaps
- **Performance**: Multiple modules add processing overhead (minimal)
- **Training needed**: Content creators need Yoast training

### Neutral

- **Per-site tuning**: Each site can configure differently
- **Token learning curve**: Tokens powerful but require learning
- **Redirect growth**: Redirect table grows over time (needs monitoring)

## Alternatives Considered

### Alternative 1: Minimal SEO (Pathauto + Metatag Only)

Use only basic Pathauto and Metatag modules.

**Rejected because:**
- No sitemap generation
- No redirect management
- No 404 tracking
- No content optimization tools
- Missing important SEO features
- Incomplete SEO strategy

### Alternative 2: All-in-One SEO Module

Use hypothetical single SEO module if one existed.

**Not available because:**
- No comprehensive single module exists
- Drupal ecosystem favors specialized modules
- Best-of-breed approach more flexible

### Alternative 3: Custom SEO Solution

Build custom SEO implementation.

**Rejected because:**
- Reinventing solved problems
- High development cost
- Maintenance burden
- Unlikely to match established modules
- No community support

### Alternative 4: External SEO Services

Use external services like SEMrush, Moz, etc. exclusively.

**Rejected because:**
- Still need on-site SEO implementation
- External tools complement but don't replace core modules
- Subscription costs
- Services are for analysis, not implementation

## Implementation Notes

**Installed modules and versions:**
```json
{
  "require": {
    "drupal/pathauto": "^1.13",
    "drupal/metatag": "^2.2.0",
    "drupal/simple_sitemap": "^4.2.2",
    "drupal/redirect": "^1.10",
    "drupal/token": "^1.16",
    "drupal/token_filter": "^2.2",
    "drupal/token_or": "^2.2",
    "drupal/token_entity_render": "^2.0",
    "drupal/yoast_seo": "^2.1",
    "drupal/seo_checklist": "^5.2.1",
    "drupal/google_tag": "^2.0.7",
    "drupal/easy_breadcrumb": "^2.0.9"
  }
}
```

**Pathauto configuration:**
- Automatic URL aliases for all content types
- Pattern: `[node:content-type]/[node:title]`
- Pattern per content type possible
- Updates preserve old URLs (with redirects)
- Token-based patterns for flexibility

**Metatag configuration:**
- Default meta tags at site level
- Override per content type
- Override per individual node
- Modules enabled:
  - `metatag_open_graph`: Facebook sharing
  - `metatag_twitter_cards`: Twitter sharing
- Token support for dynamic values

**Simple Sitemap:**
- XML sitemap at `/sitemap.xml`
- Automatic inclusion of content
- Per-content-type configuration
- Priority and change frequency settings
- Multiple sitemap variants possible

**Redirect module:**
- Automatic redirects when URL changes
- Manual redirect creation
- 404 tracking at `/admin/config/search/redirect/404`
- Bulk import of redirects
- Path matching with wildcards

**Yoast SEO:**
- Real-time content analysis
- SEO score and suggestions
- Readability analysis
- Focus keyword optimization
- Integrated with content editing

**SEO Checklist:**
- Implementation tracking at `/admin/config/search/seo-checklist`
- Checklist based on Drupal best practices
- Tracks completion of SEO tasks
- Helps ensure nothing overlooked

**Google Tag:**
- Google Analytics integration
- Google Tag Manager support
- Event tracking configuration
- Privacy-compliant implementation (with Klaro consent)

**Easy Breadcrumb:**
- Automatic breadcrumb generation
- Based on menu hierarchy or URL structure
- Improves UX and SEO
- Schema.org BreadcrumbList markup

**Token system:**
Enhanced with additional token modules:
- `token_filter`: Use tokens in text fields
- `token_or`: Fallback token values
- `token_entity_render`: Render entity fields as tokens

**SEO workflow for content creators:**
1. Create/edit content
2. Yoast SEO panel provides real-time feedback
3. Optimize based on suggestions
4. Meta tags auto-generated (can override)
5. URL alias auto-generated (can override)
6. Sitemap automatically updated
7. Breadcrumbs automatically generated

**Per-site configuration:**
Each site can configure:
- Custom Pathauto patterns
- Site-specific meta tag defaults
- Different Google Analytics properties
- Site-specific redirect handling

## References

- Pathauto: https://www.drupal.org/project/pathauto
- Metatag: https://www.drupal.org/project/metatag
- Simple Sitemap: https://www.drupal.org/project/simple_sitemap
- Redirect: https://www.drupal.org/project/redirect
- Yoast SEO: https://www.drupal.org/project/yoast_seo
- SEO Checklist: https://www.drupal.org/project/seo_checklist
- Google Tag: https://www.drupal.org/project/google_tag
- Related: [[0002-drupal-cms-as-base-platform]]
- Related: [[0013-privacy-and-consent-management]]
