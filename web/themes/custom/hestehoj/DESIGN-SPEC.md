Hestehoj Design System

Version: 1.0
Color Model: OKLCH
Accessibility Target: WCAG AA minimum, AAA where practical

⸻

# Brand Overview

## Brand Personality
-	Icelandic-inspired clarity
-	Modern craft with Nordic restraint
-	Warm but disciplined
-	Technical without sterility
-	Calm confidence over loud tech energy

## Visual Intent

This system balances:
-	Deep Icelandic blue (trust, clarity, intelligence)
-	Icelandic red (emphasis, urgency, attention)
-	Warm sand neutrals (softness and approachability)
-	Cool blue-tinted surfaces (structure and depth)

The result is a product that feels:

> Engineered with taste. Icelandic at heart.

⸻

# Color Foundations (OKLCH)

All colors use `oklch(L C H)`. Hestehoj uses semantic design tokens rather than
numbered scales. Each token has a foreground counterpart for text/icon contrast.

⸻

## Primary — Icelandic Blue

A deep, saturated blue inspired by Icelandic waters and sky. Hue sits around 256,
with high chroma for confident, authoritative presence.

Used for:
-	Primary buttons
-	Links
-	Focus rings
-	Active controls

```css
/* Light mode */
--primary:            oklch(0.428 0.146 256.1);
--primary-foreground:  oklch(0.984 0.004 248.2);
--ring:               oklch(0.428 0.146 256.1);

/* Dark mode */
--primary:            oklch(0.559 0.196 256.8);
--primary-foreground:  oklch(0.984 0.004 248.2);
--ring:               oklch(0.634 0.196 254.9);
```

Usage Rules
-	Light mode primary button → `--primary` at L 0.428
-	Dark mode primary button → `--primary` at L 0.559 (lighter for dark surfaces)
-	Focus ring → `--ring` (matches primary in light; slightly brighter in dark)
-	Hover → reduce opacity to 85% via Tailwind modifier (`bg-primary/85`)

⸻

## Destructive — Icelandic Red

A bold, warm red drawn from the Icelandic flag. Hue ~21 in light, ~16 in dark,
with high chroma for urgency.

Used for:
-	Destructive actions
-	Error states
-	Highlight emphasis
-	Badges

```css
/* Light mode */
--destructive:            oklch(0.573 0.216 21.1);
--destructive-foreground:  oklch(0.984 0.004 248.2);

/* Dark mode */
--destructive:            oklch(0.635 0.188 15.6);
--destructive-foreground:  oklch(0.984 0.004 248.2);
```

Usage Rules
-	Destructive button → `--destructive`
-	Error text/border → `--destructive`
-	Hover → reduce opacity to 85%

⸻

## Secondary — Warm Neutral

A soft, warm sand tone that provides approachability against the cool primary.

```css
/* Light mode */
--secondary:            oklch(0.956 0.005 67.5);
--secondary-foreground:  oklch(0.372 0.014 55.9);

/* Dark mode */
--secondary:            oklch(0.291 0.018 264.2);
--secondary-foreground:  oklch(0.968 0.007 248.1);
```

⸻

## Accent — Cool Blue-Violet

A subtle blue-violet tint used for accented surfaces and secondary buttons.

```css
/* Light mode */
--accent:            oklch(0.932 0.032 255.585);
--accent-foreground:  var(--foreground);

/* Dark mode */
--accent:            oklch(0.932 0.032 255.585);
--accent-foreground:  oklch(0.13 0.043 265.132);
```

⸻

# Surface System

Surfaces use cool blue-tinted neutrals in both modes, creating a cohesive
Icelandic atmosphere. All values in OKLCH.

## Light Mode

```text
Layer                Token                              Value
App Background       --background                       oklch(1 0 0)
Card                 --card                             oklch(0.984 0.004 248.2)
Muted Surface        --muted                            oklch(0.961 0.004 248.2)
Accented Surface     --accent                           oklch(0.932 0.032 255.585)
Border               --border                           oklch(0.926 0.013 255.1)
Popover              --popover                          oklch(1 0 0)
```

Foreground tokens:

```text
Token                              Value
--foreground                       oklch(0.137 0.036 258.3)
--card-foreground                  oklch(0.137 0.036 258.3)
--muted-foreground                 oklch(0.556 0.04 256.8)
--popover-foreground               oklch(0.137 0.036 258.3)
```

## Dark Mode

```text
Layer                Token                              Value
App Background       --background                       oklch(0.137 0.036 258.3)
Card                 --card                             oklch(0.206 0.039 265.6)
Muted Surface        --muted                            oklch(0.291 0.018 264.2)
Accented Surface     --accent                           oklch(0.932 0.032 255.585)
Border               --border                           oklch(0.373 0.031 259.733)
Popover              --popover                          oklch(0.206 0.039 265.6)
```

Foreground tokens:

```text
Token                              Value
--foreground                       oklch(0.984 0.004 248.2)
--card-foreground                  oklch(0.984 0.004 248.2)
--muted-foreground                 oklch(0.71 0.035 256.8)
--popover-foreground               oklch(0.984 0.004 248.2)
```

⸻

# Interaction Guidelines

Hover

Use Tailwind opacity modifiers to darken on hover. This keeps chroma stable
and avoids saturation spikes.

Light mode:

```css
hover:bg-primary/85
```

Dark mode:

```css
hover:bg-primary/85
```

> Never increase chroma on hover.
> Only adjust lightness via opacity modifiers.

⸻

Focus
-	3px ring via Tailwind's `ring` utility
-	Uses `--ring` token
-	Must remain visible on all surfaces
-	Ring at 50% opacity to avoid overwhelming the control

Example (applied via Tailwind classes):

```css
focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:border-ring
```

⸻

Active
-	5–8% darker than hover
-	No saturation spike
-	Maintain minimum 4.5:1 contrast

⸻

Disabled
-	Reduce opacity to 50% (`disabled:opacity-50`)
-	Disable pointer events (`disabled:pointer-events-none`)
-	Maintain readable foreground

⸻

# Accessibility Standards
-	Body text: ≥ 4.5:1 (AA)
-	UI text where possible: ≥ 7:1 (AAA)
-	Buttons: must pass 4.5:1 minimum
-	Do not rely on color alone for meaning
-	Always pair semantic color with icon or label

⸻

# Typography

Primary font: **Outfit** — a geometric sans-serif with clean, modern proportions
that complement the Icelandic aesthetic. Variable weight (100–900) via self-hosted
woff2 files with latin and latin-ext subsets.

```css
--font-sans: "Outfit", "Helvetica Neue", Arial, Helvetica, sans-serif;
--font-serif: ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
--font-mono: "Fira Mono", "Menlo", "Consolas", "Liberation Mono", monospace;
```

Avoid:
-	Overly playful display fonts
-	Ultra-thin weights for body text

Weight Hierarchy:

```text
Role                 Weight       Tailwind Class
Page Title           600          font-semibold
Section Heading      600          font-semibold
Body                 400          font-normal
Medium emphasis      500          font-medium
Caption              400          font-normal
```

⸻

# Design Principles
1.	Contrast over decoration
2.	Nordic warmth without noise
3.	Structure first, flourish second
4.	Interaction clarity above aesthetic novelty
5.	Every color must earn its place

⸻

# Implementation Stack
-	Tailwind CSS v4
-	Drupal Single Directory Components (SDC)
-	Class Variant Authority (CVA) via the `cva` Drupal module
-	Twig templates (not React/JSX)
-	OKLCH color space throughout
-	CSS custom properties for theming (`theme.css`)
-	Tailwind opacity modifiers for interaction states
-	Class-based dark mode (`.dark`)

⸻

# Tone of UI

This system should feel:
-	Calm
-	Assured
-	Thoughtfully engineered
-	Slightly premium
-	Icelandic in character

Not:
-	Neon
-	Playful startup chaos
-	Over-animated
-	Hyper-saturated

⸻

