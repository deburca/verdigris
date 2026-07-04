---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-02-01
site: shared
deciders: [Frontend Team]
---

# 0005: Tailwind CSS as Styling Framework

## Status

accepted

## Context

The project requires a consistent, maintainable approach to CSS that works well with component-based architecture. Traditional CSS approaches include:

1. Custom CSS written from scratch
2. Bootstrap or Foundation frameworks
3. Utility-first frameworks (Tailwind CSS)
4. CSS-in-JS solutions

Key requirements:
- Fast development velocity
- Consistency across components
- Small production CSS footprint
- Good integration with SDC components
- Support for component variants
- Responsive design patterns

Tailwind CSS is a utility-first CSS framework that provides:
- Comprehensive utility classes
- PurgeCSS for small production builds
- Design system through configuration
- Excellent component support
- JIT (Just-In-Time) compiler for development

## Decision

Adopt Tailwind CSS as the primary styling framework for all custom themes, integrated with CVA (Class Variance Authority) for component variant management.

**Implementation approach:**
- Tailwind CSS for all custom theme styling
- CVA module for managing component variants
- Tailwind configuration scans all component directories
- Custom theme extensions via tailwind.config.js
- PostCSS processing pipeline
- Site-specific theme customizations possible

## Consequences

### Positive

- **Rapid development**: Utility classes speed up styling
- **Consistency**: Design system enforced through configuration
- **Small bundle size**: PurgeCSS removes unused styles
- **Responsive by default**: Mobile-first responsive utilities
- **Component variants**: CVA provides clean variant management
- **Low learning curve**: Utility classes are intuitive
- **Customizable**: Extensive configuration options
- **Active ecosystem**: Large community and plugin ecosystem
- **Modern workflow**: Integrates with modern build tools

### Negative

- **Verbose HTML**: Many utility classes in templates can be hard to read
- **Build step required**: Need Node.js and build process
- **Framework dependency**: Sites depend on Tailwind ecosystem
- **Initial learning**: Team needs to learn Tailwind conventions
- **Canvas compatibility issues**: Current Canvas code components have styling conflicts (workaround exists)

### Neutral

- **Opinionated defaults**: Tailwind's design decisions may not suit all preferences
- **Class naming**: Different from traditional BEM or SMACSS approaches
- **Purging configuration**: Requires proper content paths in config

## Alternatives Considered

### Alternative 1: Custom CSS

Write all CSS from scratch using modern CSS features.

**Rejected because:**
- Slower development velocity
- Risk of inconsistent patterns across sites
- Need to build own design system
- Larger CSS footprints without optimization
- More maintenance burden

### Alternative 2: Bootstrap

Use Bootstrap framework for components and utilities.

**Rejected because:**
- More opinionated component styles
- Harder to create custom designs
- Larger default bundle size
- jQuery dependency (older versions)
- Less suited to utility-first approach
- Harder to integrate with SDC patterns

### Alternative 3: CSS Modules

Use CSS Modules with component-scoped styles.

**Rejected because:**
- Requires JavaScript build tooling
- More complex setup for Drupal
- Less intuitive for backend developers
- Doesn't solve design system consistency
- Smaller ecosystem than Tailwind

### Alternative 4: Styled Components / CSS-in-JS

Use runtime CSS-in-JS solutions.

**Rejected because:**
- Requires JavaScript runtime
- Performance overhead
- SEO challenges
- Not compatible with Drupal's server-side rendering
- Overkill for Drupal's template system

## Implementation Notes

**Dependencies:**
```json
{
  "devDependencies": {
    "tailwindcss": "^3.x",
    "postcss": "^8.x",
    "autoprefixer": "^10.x"
  }
}
```

**Tailwind configuration (tailwind.config.js):**
```javascript
export default {
  content: [
    // SDC component templates, scripts, and styles
    "./components/**/*.{twig,js,css}",
    "./templates/**/*.twig",
    "./lib/**/*.js",
    "./src/**/*.css",
    // Components from contrib and custom modules
    "../../../modules/contrib/*/components/**/*.{twig,js,css}",
    "../../../modules/custom/*/components/**/*.{twig,js,css}",
  ],
  theme: {
    extend: {
      // Custom theme variables here
    },
  },
  plugins: [],
}
```

**CVA integration for variants:**
```twig
{%
  set button = html_cva(
    base: 'px-4 py-2 rounded font-semibold',
    variants: {
      color: {
        primary: 'bg-blue-600 text-white hover:bg-blue-700',
        secondary: 'bg-gray-200 text-gray-900 hover:bg-gray-300',
      },
      size: {
        sm: 'text-sm',
        md: 'text-base',
        lg: 'text-lg',
      }
    }
  )
%}

<button class="{{ button.apply({color: 'primary', size: 'md'}, class) }}">
  {{ button_text }}
</button>
```

**Build process:**
Theme build processes handle Tailwind compilation, typically integrated into theme development workflow.

**Current sites using Tailwind:**
- Zwarte Piet (verdigris.nu) - Custom Tailwind theme
- Quick Silver (kragebaekgaard.dk) - Custom Tailwind theme  
- Hestehoj (stutteri-hestehoj.dk) - Custom Tailwind theme

**Known issue:**
Canvas code components have styling conflicts with Tailwind. Workaround documented in theme READMEs involves copying theme CSS into Canvas Global CSS editor.

## References

- Tailwind CSS: https://tailwindcss.com
- CVA Module: https://www.drupal.org/project/cva
- Twig html_cva function: https://twig.symfony.com/doc/3.x/functions/html_cva.html
- Related: [[0004-sdc-component-architecture]]
- Related: [[0007-site-specific-custom-themes]]
- Related: [[0003-canvas-for-page-building]]
