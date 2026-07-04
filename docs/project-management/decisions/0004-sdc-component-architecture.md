---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-02-01
site: shared
deciders: [Architecture Team, Frontend Team]
---

# 0004: Single Directory Components (SDC) Architecture

## Status

accepted

## Context

Modern frontend development emphasizes component-based architecture for reusability, maintainability, and scalability. Drupal 10+ introduced Single Directory Components (SDC) as a core pattern for organizing component code.

Traditional Drupal theming approaches:
1. Template files scattered across `/templates` directory
2. CSS in theme libraries with manual organization
3. JavaScript loosely coupled to templates
4. No standardized component structure

SDC provides:
- Co-located template, styles, and scripts
- Component metadata and prop definitions
- JSON schema validation
- Automatic discovery and registration
- Integration with modern tools (Storybook, Canvas)

The team needed to decide between traditional template organization and SDC for building reusable UI components.

## Decision

Adopt Single Directory Components (SDC) as the primary architecture pattern for all custom theme development.

**Implementation approach:**
- All reusable UI elements built as SDC components
- Components organized in `/components` directory per theme
- Component metadata defined in `.component.yml` files
- Props validated using JSON Schema
- Integration with Canvas for visual editing
- Integration with CVA (Class Variance Authority) for Tailwind variant management

## Consequences

### Positive

- **Co-location**: Templates, styles, scripts, and metadata in single directory
- **Discoverability**: Components automatically registered and available
- **Reusability**: Components easily shared across pages and contexts
- **Type safety**: JSON Schema validation prevents prop errors
- **Modern tooling**: Compatible with Storybook, Canvas, and other modern tools
- **Maintainability**: Clear component boundaries and dependencies
- **Canvas integration**: Components automatically available in Canvas UI
- **Standard pattern**: Follows Drupal core recommendations
- **Portability**: Components can be moved between projects

### Negative

- **Learning curve**: Team must learn SDC patterns and conventions
- **Migration effort**: Existing non-SDC code requires refactoring
- **Tooling requirements**: Need build tools for CSS/JS processing
- **Complexity**: More structure than simple template files
- **Documentation needs**: Components need proper documentation

### Neutral

- **File organization**: More directories but clearer structure
- **Naming conventions**: Must follow component naming standards
- **Schema maintenance**: Component schemas need updating when props change

## Alternatives Considered

### Alternative 1: Traditional Template Files

Continue using Drupal's traditional template organization.

**Rejected because:**
- No standardized component structure
- Difficult to manage component variants
- Poor integration with modern tools like Canvas
- Templates, CSS, and JS scattered across theme
- No prop validation or type safety
- Harder to create reusable patterns

### Alternative 2: Custom Component System

Build a proprietary component architecture.

**Rejected because:**
- Reinventing functionality provided by core
- No community support or documentation
- Team would maintain custom system
- Not compatible with Canvas or other tools
- Would diverge from Drupal standards

### Alternative 3: React/Vue Components

Use JavaScript framework for components.

**Rejected because:**
- Additional complexity and build requirements
- Performance overhead of client-side rendering
- Decoupled from Drupal's server-side rendering
- Accessibility challenges
- SEO considerations
- Team expertise primarily in PHP/Twig

## Implementation Notes

**Component structure:**
```
components/
├── card/
│   ├── card.component.yml    # Metadata and prop definitions
│   ├── card.twig              # Template
│   ├── card.css               # Styles
│   └── card.js                # Optional JavaScript
├── hero-billboard/
│   ├── hero-billboard.component.yml
│   ├── hero-billboard.twig
│   └── hero-billboard.css
└── ...
```

**Component metadata example (card.component.yml):**
```yaml
"$schema": "https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json"
name: Image card
group: Card
props:
  type: object
  required:
    - heading_text
    - orientation
  properties:
    heading_text:
      type: string
      title: Heading
    orientation:
      type: string
      enum: [vertical, horizontal]
    media:
      $ref: json-schema-definitions://canvas.module/image
```

**Using components in templates:**
```twig
{% include 'themename:card' with {
  heading_text: 'Card Title',
  orientation: 'vertical',
  media: {
    src: 'path/to/image.jpg',
    alt: 'Description'
  }
} %}
```

**CVA integration for Tailwind variants:**
Theme uses CVA module to manage Tailwind class variants within components:

```yaml
# zwarte_piet.info.yml
dependencies:
  - "cva:cva"
```

**Tailwind configuration:**
Configured to scan all component directories:
```javascript
content: [
  "./components/**/*.{twig,js,css}",
  "../../../modules/contrib/*/components/**/*.{twig,js,css}",
  "../../../modules/custom/*/components/**/*.{twig,js,css}",
]
```

## References

- SDC Documentation: https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components
- SDC Core Module: core/modules/sdc
- CVA Module: https://www.drupal.org/project/cva
- Canvas Integration: https://www.drupal.org/docs/contributed-modules/canvas
- Related: [[0003-canvas-for-page-building]]
- Related: [[0005-tailwind-css-framework]]
- Related: [[0007-site-specific-custom-themes]]
