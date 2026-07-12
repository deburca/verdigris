/**
 * WCAG AA contrast verification — Hestehoj Icelandic flag palette.
 * Task: 0005-accessibility-and-contrast-verification
 *
 * Converts oklch → oklab → linear sRGB → relative luminance, then computes
 * WCAG 2.1 contrast ratios. All thresholds:
 *   AA normal text  : 4.5:1
 *   AA large text   : 3.0:1  (≥ 18pt / 14pt bold)
 *   AA UI component : 3.0:1  (WCAG SC 1.4.11)
 *   AAA normal text : 7.0:1
 */

// ── oklch → oklab ─────────────────────────────────────────────────────────────
function oklchToOklab(L, C, hDeg) {
  const h = (hDeg * Math.PI) / 180;
  return { L, a: C * Math.cos(h), b: C * Math.sin(h) };
}

// ── oklab → linear sRGB ───────────────────────────────────────────────────────
function oklabToLinearSRGB({ L, a, b }) {
  const l_ = L + 0.3963377774 * a + 0.2158037573 * b;
  const m_ = L - 0.1055613458 * a - 0.0638541728 * b;
  const s_ = L - 0.0894841775 * a - 1.291485548 * b;

  const l = l_ ** 3;
  const m = m_ ** 3;
  const s = s_ ** 3;

  return {
    r: +4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s,
    g: -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s,
    b_: -0.0041960863 * l - 0.7034186147 * m + 1.6956086611 * s,
  };
}

// ── relative luminance (WCAG 2.1 definition) ───────────────────────────────────
function relativeLuminance(L, C, hDeg) {
  const lab = oklchToOklab(L, C, hDeg);
  const { r, g, b_ } = oklabToLinearSRGB(lab);
  // Clamp to [0,1] to handle floating-point overshoot
  const clamp = (v) => Math.max(0, Math.min(1, v));
  return 0.2126 * clamp(r) + 0.7152 * clamp(g) + 0.0722 * clamp(b_);
}

// ── contrast ratio ─────────────────────────────────────────────────────────────
function contrast(L1, L2) {
  const [li, da] = L1 > L2 ? [L1, L2] : [L2, L1];
  return (li + 0.05) / (da + 0.05);
}

function grade(ratio, { text = false, ui = false } = {}) {
  if (text) {
    if (ratio >= 7.0) return "AAA ✅";
    if (ratio >= 4.5) return "AA ✅";
    if (ratio >= 3.0) return "AA large ⚠️ ";
    return "FAIL ❌";
  }
  if (ui) {
    if (ratio >= 3.0) return "AA ✅";
    return "FAIL ❌";
  }
  return ratio >= 4.5 ? "AA ✅" : "FAIL ❌";
}

// ── Palette ────────────────────────────────────────────────────────────────────
// src/theme.css values
const colours = {
  // Brand tokens
  "iceland-red (light)":       [0.573, 0.216,  21.1],
  "iceland-blue (light)":      [0.428, 0.146, 256.1],
  "iceland-red (dark)":        [0.573, 0.216,  21.1],  // same as light: task 0005
  "iceland-blue (dark)":       [0.559, 0.196, 256.8],
  // Foreground tokens
  "foreground/white":          [0.984, 0.004, 248.2],
  "foreground (dark) ":        [0.984, 0.004, 248.2],  // same near-white
  // Page & card backgrounds
  "bg-background (light)":     [1.000, 0.000,   0.0],
  "bg-background (dark)":      [0.137, 0.036, 258.3],
  "bg-card (dark)":            [0.206, 0.039, 265.6],
  // Ring (focus indicator)
  "ring (light)":              [0.428, 0.146, 256.1],  // same as iceland-blue light
  "ring (dark)":               [0.634, 0.196, 254.9],
  // Muted foreground (informational text)
  "muted-foreground (dark)":   [0.710, 0.035, 256.8],
  // Link colour tokens (task 0006) — lightened for dark mode
  "link-color (dark) ":        [0.680, 0.130, 256.1],
  "link-hover (dark) ":        [0.680, 0.150,  21.1],
  // Error text token in dark mode (same lightened red as link-hover)
  "error-text (dark) ":        [0.680, 0.150,  21.1],
};

const lum = {};
for (const [name, [L, C, h]] of Object.entries(colours)) {
  lum[name] = relativeLuminance(L, C, h);
}

// ── Checks ─────────────────────────────────────────────────────────────────────
const checks = [
  // ── Light mode ────────────────────────────────────────────────────────────
  {
    label: "LIGHT — white text on primary-btn (red)",
    fg: "foreground/white", bg: "iceland-red (light)", mode: "text",
  },
  {
    label: "LIGHT — white text on accent-btn (blue)",
    fg: "foreground/white", bg: "iceland-blue (light)", mode: "text",
  },
  {
    label: "LIGHT — blue link text on page background",
    fg: "iceland-blue (light)", bg: "bg-background (light)", mode: "text",
  },
  {
    label: "LIGHT — red link hover on page background",
    fg: "iceland-red (light)", bg: "bg-background (light)", mode: "text",
  },
  {
    label: "LIGHT — focus ring (blue) vs page background",
    fg: "ring (light)", bg: "bg-background (light)", mode: "ui",
  },
  // Ring-offset fix: ring is separated from button by a background-coloured gap.
  // The meaningful checks are ring vs background (above, 8.32:1) and
  // ring-offset-gap vs button surface (below). Ring directly on button is superseded.
  {
    label: "LIGHT — focus ring offset (bg) vs red btn surface (ring-offset fix)",
    fg: "bg-background (light)", bg: "iceland-red (light)", mode: "ui",
  },
  // ── Dark mode ─────────────────────────────────────────────────────────────
  {
    label: "DARK  — white text on primary-btn (red)",
    fg: "foreground/white", bg: "iceland-red (dark)", mode: "text",
  },
  {
    label: "DARK  — white text on accent-btn (blue)",
    fg: "foreground/white", bg: "iceland-blue (dark)", mode: "text",
  },
  {
    label: "DARK  — focus ring vs dark page background (3:1 UI)",
    fg: "ring (dark)", bg: "bg-background (dark)", mode: "ui",
  },
  // Same ring-offset logic in dark mode.
  {
    label: "DARK  — red primary btn vs dark page background (3:1 UI)",
    fg: "iceland-red (dark)", bg: "bg-background (dark)", mode: "ui",
  },
  {
    label: "DARK  — blue accent btn vs dark page background (3:1 UI)",
    fg: "iceland-blue (dark)", bg: "bg-background (dark)", mode: "ui",
  },
  {
    label: "DARK  — focus ring offset (bg) vs red btn surface (3:1 UI, ring-offset fix)",
    fg: "bg-background (dark)", bg: "iceland-red (dark)", mode: "ui",
  },
  // ── Dark mode secondary elements ─────────────────────────────────────────
  // Link colours now use dedicated --link-color / --link-color-hover tokens
  // in dark mode (oklch 0.680 lightened tints); error text still uses --destructive.
  {
    label: "DARK  — link colour (blue tint) on page background",
    fg: "link-color (dark) ", bg: "bg-background (dark)", mode: "text",
  },
  {
    label: "DARK  — link hover colour (red tint) on page background",
    fg: "link-hover (dark) ", bg: "bg-background (dark)", mode: "text",
  },
  {
    label: "DARK  — link colour on card background",
    fg: "link-color (dark) ", bg: "bg-card (dark)", mode: "text",
  },
  {
    label: "DARK  — error text (--error-text, lightened red) on page background",
    fg: "error-text (dark) ", bg: "bg-background (dark)", mode: "text",
  },
  {
    label: "DARK  — form focus border (blue/accent) vs page background",
    fg: "iceland-blue (dark)", bg: "bg-background (dark)", mode: "ui",
  },
  {
    label: "DARK  — muted-foreground text on page background",
    fg: "muted-foreground (dark)", bg: "bg-background (dark)", mode: "text",
  },
];

console.log("\n=== Hestehoj — WCAG Contrast Report (task 0005) ===\n");
console.log(
  "Thresholds: AA text ≥ 4.5:1 | AA large/UI ≥ 3.0:1 | AAA text ≥ 7.0:1\n"
);

let failCount = 0;
const findings = [];
for (const { label, fg, bg, mode } of checks) {
  const ratio = contrast(lum[fg], lum[bg]);
  const isText = mode === "text";
  const isUi   = mode === "ui";
  const result = grade(ratio, { text: isText, ui: isUi });
  const pass = !result.includes("FAIL");
  if (!pass) failCount++;
  const row = `  ${result}  ${ratio.toFixed(2)}:1  ${label}`;
  console.log(row);
  findings.push({ label, ratio: ratio.toFixed(2), result, pass });
}

console.log(`\n${failCount === 0 ? "All checks passed." : `${failCount} check(s) FAILED — see findings above.`}\n`);

// ── Luminance reference ────────────────────────────────────────────────────────
console.log("--- Relative luminance reference ---");
for (const [name, l] of Object.entries(lum)) {
  console.log(`  ${name.padEnd(32)} L = ${l.toFixed(5)}`);
}
console.log();
