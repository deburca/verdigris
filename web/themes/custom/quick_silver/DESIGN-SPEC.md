Verdigris Design System

Version: 1.0
Color Model: OKLCH
Accessibility Target: WCAG AA minimum, AAA where practical

⸻

# Brand Overview

## Brand Personality

- Modern craft
- Architectural precision
- Warm but disciplined
- Technical without sterility
- Calm confidence over loud tech energy

## Visual Intent

This system balances:

- Cool structural teal (trust, clarity, intelligence)
- Warm rust/terracotta (humanity, emphasis, attention)
- Sand neutrals (softness and approachability)

The result is a product that feels:

> Engineered with taste.

⸻

# Color Foundations (OKLCH)

All colors use oklch(L C H).

⸻

## Primary Scale — Teal

Used for:

- Primary buttons
- Links
- Focus states
- Active controls

```css
--teal-50: oklch(0.97 0.02 200);
--teal-100: oklch(0.93 0.03 200);
--teal-200: oklch(0.87 0.05 200);
--teal-300: oklch(0.8 0.07 200);
--teal-400: oklch(0.7 0.09 205);
--teal-500: oklch(0.62 0.1 205);
--teal-600: oklch(0.55 0.11 210);
--teal-700: oklch(0.48 0.11 210);
--teal-800: oklch(0.38 0.09 210);
--teal-900: oklch(0.3 0.07 210);
--teal-950: oklch(0.22 0.05 210);
```

Usage Rules

- Light mode primary button → `teal-700`
- Dark mode primary button → `teal-500`
- Focus ring → `teal-600`
- Background tint → `teal-50` or `teal-100`

⸻

## Accent Scale — Rust

Used for:

- Accent CTAs
- Highlight states
- Destructive emphasis
- Badges

```css
--rust-50: oklch(0.97 0.02 40);
--rust-100: oklch(0.93 0.04 38);
--rust-200: oklch(0.86 0.07 38);
--rust-300: oklch(0.78 0.1 38);
--rust-400: oklch(0.7 0.13 38);
--rust-500: oklch(0.62 0.16 38);
--rust-600: oklch(0.55 0.17 35);
--rust-700: oklch(0.48 0.16 32);
--rust-800: oklch(0.4 0.13 30);
--rust-900: oklch(0.32 0.1 28);
--rust-950: oklch(0.25 0.07 28);
```

Usage Rules

- Accent button → `rust-600`
- Badge background → `rust-200`
- Destructive button → `rust-700`
- Hover → mix toward black 5–12%

⸻

# Semantic State Tokens

⸻

## Success

Derived from teal, shifted greener.

```css
--success-100: oklch(0.92 0.04 170);
--success-500: oklch(0.6 0.11 170);
--success-700: oklch(0.45 0.11 170);
```

Usage:

- Background → `success-100`
- Button → `success-600`
- Text on light → `success-700`

⸻

## Warning

Derived from rust, shifted amber.

```css
--warning-100: oklch(0.95 0.04 70);
--warning-500: oklch(0.65 0.17 50);
--warning-700: oklch(0.5 0.17 40);
```

Usage:

- Banner background → `warning-100`
- Button → `warning-600`
- Text → `warning-700`

⸻

## Info

Cool teal-neutral variant.

```css
--info-100: oklch(0.92 0.04 210);
--info-500: oklch(0.62 0.1 210);
--info-700: oklch(0.48 0.1 210);
```

Usage:

- Informational panels
- System status
- Neutral notifications

⸻

# Surface System

## Light Mode

```text
Layer				Token
App Background		oklch(0.94 0.05 90)
Card				white
Muted Surface		oklch(0.88 0.03 90)
Border				oklch(0.85 0.03 90)
```

## Dark Mode

```text
Layer				Token
App Background		oklch(0.28 0.06 210)
Card				oklch(0.32 0.05 210)
Muted Surface		oklch(0.36 0.04 210)
Border				oklch(0.40 0.04 210)
```

⸻

# Interaction Guidelines

Hover

Use `color-mix()`.

Light mode:

```css
background: color-mix(in oklch, var(--primary) 88%, black);
```

Dark mode:

```css
background: color-mix(in oklch, var(--primary) 85%, white);
```

> Never increase chroma on hover.
> Only adjust lightness via mixing.

⸻

Focus

- 3px ring
- Uses `--ring`
- Must remain visible on all surfaces

Example:

```css
box-shadow: 0 0 0 3px var(--ring);
```

⸻

Active

- 5–8% darker than hover
- No saturation spike
- Maintain minimum 4.5:1 contrast

⸻

Disabled

- Reduce chroma
- Maintain readable foreground
- Avoid opacity-only solutions

⸻

# Accessibility Standards

- Body text: ≥ 4.5:1 (AA)
- UI text where possible: ≥ 7:1 (AAA)
- Buttons: must pass 4.5:1 minimum
- Do not rely on color alone for meaning
- Always pair semantic color with icon or label

⸻

# Typography Guidance

Recommended type families:

- Geist
- Inter
- IBM Plex Sans
  • Söhne-style grotesk

Avoid:

- Overly playful display fonts
- Ultra-thin weights for body text

Hierarchy:

```text
Role				Weight
Page Title			600–700
Section Heading		600
Body				400–500
Caption				400
```

⸻

# Design Principles

1. Contrast over decoration
2. Warmth without noise
3. Structure first, flourish second
4. Interaction clarity above aesthetic novelty
5. Every color must earn its place

⸻

# Implementation Stack

- Tailwind + shadcn/ui
- OKLCH color space
- CSS variables for theming
- `color-mix()` for interaction states
- Class-based dark mode

⸻

# Tone of UI

This system should feel:

- Calm
- Assured
- Thoughtfully engineered
- Slightly premium
- Designed by adults

Not:

- Neon
- Playful startup chaos
- Over-animated
- Hyper-saturated

⸻
