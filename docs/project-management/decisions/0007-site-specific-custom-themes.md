---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-02-01
site: shared
deciders: [Architecture Team, Design Team]
---

# 0007: Site-Specific Custom Themes Instead of Shared Theme

## Status

accepted

## Context

In a multisite architecture, there's a fundamental decision about theme strategy:

1. **Single shared theme**: All sites use one theme with configuration overrides
2. **Site-specific themes**: Each site has its own custom theme
3. **Hybrid approach**: Shared base theme with site-specific sub-themes

Each site has distinct requirements:
- **verdigris.nu (vdg)**: Personal/professional site with unique design
- **kragebaekgaard.dk (kbg)**: Different branding and color scheme
- **stutteri-hestehoj.dk (shh)**: Equestrian business with specific requirements

The sites share:
- Common component patterns (cards, heroes, navigation)
- Tailwind CSS framework
- SDC component architecture
- Canvas integration

But differ in:
- Branding (colors, fonts, logos)
- Component variants and styling
- Layout preferences
- Specific custom components

## Decision

Implement site-specific custom themes rather than a shared theme, while maintaining shared component patterns and architectural decisions.

**Implementation:**
- Each site has dedicated custom theme in `web/themes/custom/`
  - `zwarte_piet/` for verdigris.nu
  - `quick_silver/` for kragebaekgaard.dk
  - `hestehoj/` for stutteri-hestehoj.dk
- All themes follow same architecture (Tailwind + SDC + Canvas)
- Common components can be copied/adapted between themes
- Each theme has independent component library
- Themes can evolve independently

## Consequences

### Positive

- **Design freedom**: Each site can have completely unique design
- **Independence**: Changes to one site don't affect others
- **Clear separation**: No complex configuration to manage site differences
- **Simpler logic**: No conditional CSS or template overrides
- **Easier debugging**: Issues isolated to specific theme
- **Flexibility**: Can use different versions of components per site
- **Brand identity**: Each site maintains distinct visual identity
- **Performance**: No unused CSS from other sites

### Negative

- **Code duplication**: Similar components repeated across themes
- **Update burden**: Common improvements must be applied to multiple themes
- **Consistency challenges**: Shared patterns may drift apart over time
- **More maintenance**: Three themes to maintain vs one
- **Learning curve**: Developers need to know which theme for which site
- **Component drift**: Same component may evolve differently per site

### Neutral

- **Component sharing**: Can still share components through copy/paste
- **Pattern library**: Need documentation of common patterns
- **Theme size**: Each theme contains only its components (smaller)

## Alternatives Considered

### Alternative 1: Single Shared Theme with Configuration

One theme with extensive configuration for site-specific variations.

**Rejected because:**
- Complex configuration management
- Conditional logic throughout templates
- Harder to make site-specific changes
- CSS bloat from all site variations
- Risk of breaking one site when changing another
- Difficult to maintain distinct brand identities

### Alternative 2: Base Theme with Sub-Themes

Shared base theme in `/themes/custom/base_theme` with site-specific sub-themes.

**Rejected because:**
- Added complexity of theme inheritance
- Sub-themes can't easily override base components
- Still risk of changes affecting multiple sites
- Base theme becomes bottleneck for changes
- Harder to understand component source
- Theme inheritance adds debugging complexity

### Alternative 3: Shared Component Module

Components in custom module, themes just style them.

**Rejected because:**
- Components tightly coupled to visual design
- Module pattern not ideal for presentational code
- Harder to make site-specific component variants
- Doesn't align with SDC theme-based pattern
- More complex to maintain than theme components

## Implementation Notes

**Theme directory structure:**
```
web/themes/custom/
├── zwarte_piet/          # verdigris.nu theme
│   ├── components/       # 26 SDC components
│   ├── tailwind.config.js
│   ├── zwarte_piet.info.yml
│   └── ...
├── quick_silver/         # kragebaekgaard.dk theme
│   ├── components/
│   ├── tailwind.config.js
│   ├── quick_silver.info.yml
│   └── ...
└── hestehoj/            # stutteri-hestehoj.dk theme
    ├── components/
    ├── tailwind.config.js
    ├── hestehoj.info.yml
    └── ...
```

**Shared architecture across all themes:**
- Tailwind CSS framework
- SDC component architecture
- CVA for variant management
- Canvas integration
- No base theme (independent themes)

**Current components in Zwarte Piet (example):**
- accordion, accordion-container
- anchor, badge, blockquote, button
- card (multiple variants)
- cta, footer, group, heading
- hero-billboard, hero-blog, hero-side-by-side
- icon, image, navbar
- related-tags, section, text

**Admin theme (shared):**
All sites use Gin admin theme for consistent admin experience:
```yaml
admin: gin
default: [site-specific-theme]
```

**Theme generation:**
Themes generated using Mercury theme generator (1.0.4) then customized per site.

**Component reuse strategy:**
When a component pattern works well, copy to other themes and adapt styling/variants as needed. No formal sharing mechanism to avoid coupling.

## References

- Mercury Theme: https://www.drupal.org/project/mercury
- Theme generator used: mercury:1.0.4
- Related: [[0004-sdc-component-architecture]]
- Related: [[0005-tailwind-css-framework]]
- Related: [[0008-gin-admin-theme]]
