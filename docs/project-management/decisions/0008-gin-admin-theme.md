---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-01-01
site: shared
deciders: [Architecture Team, Content Team]
---

# 0008: Gin as Shared Admin Theme

## Status

accepted

## Context

Drupal's default admin experience (using core's Claro theme) is functional but basic. For a multisite installation with content creators working across multiple sites, we needed to decide on the admin theme strategy:

1. Use default Claro theme
2. Use Gin theme (modern admin experience)
3. Use Seven theme (legacy admin theme)
4. Site-specific admin themes

Key considerations:
- Content creators work across multiple sites
- Consistency in admin experience reduces training
- Modern, user-friendly interface improves productivity
- Integration with Drupal CMS and Navigation module
- Active development and community support

Gin theme provides:
- Modern, clean admin interface
- Improved usability and accessibility
- Dark mode support
- Better mobile experience
- Integration with Navigation module
- Part of Drupal CMS recommended stack

## Decision

Use Gin admin theme (version 5.x) as the shared admin theme across all sites in the multisite installation.

**Implementation:**
- Gin theme enabled for all sites
- Gin Login module for branded login pages
- Gin Toolbar module for improved navigation
- Consistent admin experience across vdg, kbg, and shh sites
- Site-specific frontend themes remain independent

## Consequences

### Positive

- **Consistent UX**: Content creators have same interface on all sites
- **Reduced training**: Learn admin UI once, use everywhere
- **Modern interface**: Better than default Claro theme
- **Active development**: Part of Drupal CMS, well-maintained
- **Better navigation**: Integrates with core Navigation module
- **Mobile friendly**: Works well on tablets and phones
- **Accessibility**: Meets modern accessibility standards
- **Dark mode**: Optional dark theme reduces eye strain
- **Customizable**: Gin settings allow branding adjustments

### Negative

- **Dependency**: All sites tied to Gin update cycle
- **Breaking changes**: Gin updates affect all sites simultaneously
- **Performance**: Slightly more CSS/JS than Claro (minimal impact)
- **Learning curve**: Team needs to learn Gin-specific patterns

### Neutral

- **Shared experience**: Same admin theme despite different frontend themes
- **Site independence**: Frontend themes still completely independent
- **Configuration**: Gin settings could be site-specific if needed

## Alternatives Considered

### Alternative 1: Default Claro Theme

Use Drupal Core's default admin theme.

**Rejected because:**
- Less modern interface
- Basic functionality only
- Not as user-friendly for content creators
- No integration with advanced features
- Drupal CMS recommends Gin

### Alternative 2: Site-Specific Admin Themes

Different admin theme for each site.

**Rejected because:**
- Inconsistent experience for content creators
- More training required
- Harder to maintain multiple admin themes
- No benefit for this use case
- More complex configuration

### Alternative 3: Seven Theme

Use older Seven admin theme.

**Rejected because:**
- Deprecated in Drupal 9+
- Not maintained for Drupal 11
- Less modern than Gin or Claro
- No reason to use legacy theme

## Implementation Notes

**Theme configuration (system.theme):**
```yaml
admin: gin
default: [site-specific: quick_silver, zwarte_piet, or hestehoj]
```

**Related modules:**
- `drupal/gin`: ^5.0.15 (main admin theme)
- `drupal/gin_login`: ^2.1.4 (styled login pages)
- `drupal/gin_toolbar`: ^3.0.3 (enhanced toolbar)
- `drupal/navigation_extra_tools`: ^1.3.2 (navigation enhancements)

**Gin integrates with:**
- Core Navigation module (Drupal 11+)
- Content Moderation
- Layout Builder
- Media Library
- All standard Drupal admin interfaces

**Gin Login:**
Provides branded login pages for each site while maintaining consistent admin experience after login.

**Navigation module:**
Drupal 11's new Navigation module works seamlessly with Gin, providing modern admin navigation experience.

**Customization:**
Gin settings available at `/admin/appearance/settings/gin` for:
- Logo customization per site
- Color scheme preferences
- Layout density
- Dark mode toggle
- Navigation position

**User preferences:**
Individual users can customize their Gin experience (dark mode, density) while maintaining overall consistency.

## References

- Gin Theme: https://www.drupal.org/project/gin
- Gin Documentation: https://www.drupal.org/docs/contributed-themes/gin
- Drupal CMS Admin UI recipe: recipes/drupal_cms_admin_ui
- Related: [[0002-drupal-cms-as-base-platform]]
- Related: [[0007-site-specific-custom-themes]]
