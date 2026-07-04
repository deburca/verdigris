---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-02-01
site: shared
deciders: [Architecture Team, Frontend Team]
---

# 0003: Canvas for Visual Page Building

## Status

accepted

## Context

Content creators and site builders need an intuitive way to build custom landing pages and compose complex layouts without developer intervention. Traditional approaches include:

1. Layout Builder (Drupal core)
2. Paragraphs module
3. Custom content types with field-based layouts
4. Drupal Canvas with SDC components

The team needed a solution that:
- Empowers content creators with visual building tools
- Integrates well with Single Directory Components (SDC)
- Provides browser-based editing experience
- Maintains clean, maintainable code
- Supports both structured content and free-form pages

## Decision

Adopt Drupal Canvas as the primary page building and content composition tool, integrated with Single Directory Components (SDC).

**Implementation approach:**
- Canvas module for visual editing
- SDC components for reusable UI elements
- Site-specific component libraries in custom themes
- Canvas integration with Media Library for images
- Browser-based code editing for advanced users

## Consequences

### Positive

- **Content creator empowerment**: Non-technical users can build complex layouts visually
- **Component reusability**: SDC components work across Canvas and traditional templates
- **Modern UX**: Drag-and-drop interface with live preview
- **Code quality**: Components generate clean, maintainable markup
- **Flexibility**: Supports both visual building and code-based customization
- **Media integration**: Seamless integration with Drupal Media Library
- **No vendor lock-in**: Components are standard Twig/SDC, not proprietary
- **Performance**: Renders as standard Drupal content, no client-side framework overhead

### Negative

- **Learning curve**: Content creators need training on Canvas interface
- **Complexity**: More moving parts than simple field-based content types
- **Code component limitations**: Current Canvas version has issues with Tailwind themes (workaround documented)
- **Module maturity**: Canvas is relatively new, may have edge cases
- **Developer setup**: Requires understanding of both Canvas and SDC patterns

### Neutral

- **Component maintenance**: Need to maintain component library across themes
- **Version dependency**: Tied to Canvas module update cycle
- **Theme compatibility**: Works best with SDC-based themes (using custom themes, not Byte)

## Alternatives Considered

### Alternative 1: Layout Builder

Drupal Core's built-in layout and block system.

**Rejected because:**
- More developer-centric, less intuitive for content creators
- Block-based paradigm less flexible than component composition
- Limited visual editing capabilities
- Harder to create reusable component patterns
- Less modern UX compared to Canvas

### Alternative 2: Paragraphs Module

Popular contributed module for structured content composition.

**Rejected because:**
- Field-based approach less visual than Canvas
- Doesn't integrate as well with modern component architectures
- More complex content type configuration
- Less intuitive for non-technical users
- Harder to preview changes before saving

### Alternative 3: Custom Field-Based Layouts

Traditional content types with field regions and view modes.

**Rejected because:**
- Requires significant developer time for each layout
- Not flexible for content creators
- Difficult to create one-off page designs
- Poor content creator experience
- High maintenance burden

### Alternative 4: Gutenberg

WordPress-style block editor for Drupal.

**Rejected because:**
- Less integrated with Drupal's architecture
- Different paradigm from SDC components
- Less mature in Drupal ecosystem than Canvas
- Doesn't align with CMS distribution direction

## Implementation Notes

**Module dependencies:**
```json
"drupal/canvas": "^1",
"drupal/media": "*",
"drupal/media_library": "*"
```

**Theme integration:**
Each custom theme includes SDC components in `/components` directory that are automatically available in Canvas.

**Known issue - Tailwind compatibility:**
Canvas code components currently have styling conflicts with Tailwind-based themes. Workaround documented in theme README:

1. Open Canvas Global CSS tab
2. Paste theme.css contents at top
3. Paste main.css contents (remove @imports) after theme.css
4. Save global CSS

**Component structure example:**
```
web/themes/custom/zwarte_piet/components/
├── card/
│   ├── card.component.yml
│   ├── card.twig
│   └── card.css
├── hero-billboard/
│   ├── hero-billboard.component.yml
│   ├── hero-billboard.twig
│   └── hero-billboard.css
└── ...
```

**Canvas integration:**
- Components automatically discovered from theme directories
- Props defined in component.yml configure Canvas UI
- JSON schema validation ensures correct prop usage
- Media fields integrate with Drupal Media Library

## References

- Canvas Project: https://www.drupal.org/project/canvas
- Canvas Documentation: https://www.drupal.org/docs/contributed-modules/canvas
- SDC Documentation: https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components
- Canvas Issue #3549628 (Tailwind compatibility): https://www.drupal.org/node/3549628
- Related: [[0004-sdc-component-architecture]]
- Related: [[0007-site-specific-custom-themes]]
