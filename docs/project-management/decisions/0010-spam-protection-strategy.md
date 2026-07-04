---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-01-01
site: shared
deciders: [Architecture Team, Security Team]
---

# 0010: Multi-Layered Spam Protection Strategy

## Status

accepted

## Context

Public-facing websites with forms are constant targets for spam bots and malicious submissions. The project needed a comprehensive spam protection strategy that:

- Blocks automated bot submissions
- Doesn't frustrate legitimate users
- Provides multiple layers of defense
- Integrates with Webform module
- Meets accessibility requirements
- Complies with privacy regulations (GDPR)

Available approaches:
1. Google reCAPTCHA (v2/v3)
2. Friendly Captcha (privacy-focused)
3. Honeypot (invisible fields)
4. Traditional CAPTCHA
5. Akismet or similar spam filtering services
6. Custom bot detection

## Decision

Implement a multi-layered spam protection strategy combining:
1. **Honeypot** as primary invisible protection
2. **Friendly Captcha** for user-facing challenges when needed
3. **CAPTCHA module** as fallback framework

**Implementation:**
- Honeypot on all forms by default (invisible to users)
- Friendly Captcha for high-risk forms (contact, registration)
- CAPTCHA module provides framework and fallbacks
- Integration with Webform for flexible form-specific configuration
- No Google reCAPTCHA (privacy concerns)

## Consequences

### Positive

- **Privacy-first**: Friendly Captcha is GDPR-compliant, no tracking
- **User-friendly**: Honeypot invisible to legitimate users
- **Multi-layered**: Multiple defenses catch different bot types
- **Accessibility**: Both solutions are accessible (WCAG 2.1 AA compliant)
- **Flexible**: Can configure per-form based on risk level
- **No external dependencies**: No Google tracking or data sharing
- **Cost effective**: Friendly Captcha free tier sufficient for traffic volume
- **Bot effectiveness**: Catches most automated spam

### Negative

- **Not foolproof**: Sophisticated bots may bypass honeypot
- **Maintenance**: Multiple modules to keep updated
- **Complexity**: More complex than single solution
- **Form builder knowledge**: Admins need to understand options
- **False positives**: Honeypot can rarely catch legitimate users with autocomplete

### Neutral

- **Configuration per site**: Can adjust settings per site's needs
- **Friendly Captcha keys**: Requires registration and API keys
- **Multiple options**: Form builders choose appropriate protection

## Alternatives Considered

### Alternative 1: Google reCAPTCHA Only

Use Google's popular reCAPTCHA service exclusively.

**Rejected because:**
- Privacy concerns (Google tracking)
- Not GDPR-friendly without consent
- External dependency on Google
- "I'm not a robot" checkbox frustrates users
- reCAPTCHA v3 scores can be unpredictable
- Preference for privacy-focused solutions

### Alternative 2: Akismet or Mollom

Use comment spam filtering services.

**Rejected because:**
- Subscription costs
- External service dependency
- Designed for comments, not forms
- Shares data with third parties
- Mollom shut down
- Overkill for form protection

### Alternative 3: Honeypot Only

Rely solely on Honeypot invisible fields.

**Rejected because:**
- Not sufficient for high-risk forms
- Sophisticated bots can detect honeypots
- No fallback for when honeypot fails
- Single point of failure

### Alternative 4: Custom Bot Detection

Build custom spam detection system.

**Rejected because:**
- High development cost
- Maintenance burden
- Reinventing solved problems
- Unlikely to outperform established solutions
- Team time better spent elsewhere

## Implementation Notes

**Installed modules:**
```json
{
  "require": {
    "drupal/captcha": "^2.0.10",
    "drupal/friendlycaptcha": "^1.1.4",
    "drupal/friendly_captcha_challenge": "^0.9",
    "drupal/honeypot": "^2.2.2"
  }
}
```

**Honeypot configuration:**
- Enabled globally: `/admin/config/content/honeypot`
- Time restriction: Configurable minimum form submission time
- Field name: Randomized to avoid detection
- Applies to: User registration, contact forms, webforms
- Log attempts: Optional logging of caught spam

**Friendly Captcha configuration:**
- API keys: Stored in Key module
- Puzzle difficulty: Adjustable per form
- Language support: Auto-detect user language
- Fallback: Traditional CAPTCHA if JavaScript disabled
- Free tier: Sufficient for current traffic

**CAPTCHA module framework:**
- Provides base infrastructure
- Supports multiple challenge types
- Per-form type configuration
- Integration with Webform
- Math challenges as fallback

**Webform integration:**
Settings available per webform:
```
Form settings → Third party settings
- Honeypot protection
- CAPTCHA challenge type
- Friendly Captcha
```

**Form protection by type:**

| Form Type | Honeypot | Friendly Captcha | Notes |
|-----------|----------|------------------|-------|
| Contact forms | Yes | Yes | High spam risk |
| User registration | Yes | Yes | Account creation protection |
| Simple webforms | Yes | Optional | Based on spam levels |
| Comments | Yes | No | Honeypot sufficient |
| Admin forms | No | No | Authenticated users only |

**Performance considerations:**
- Honeypot: No performance impact (pure PHP)
- Friendly Captcha: Client-side JavaScript puzzle
- CAPTCHA: Minimal server-side processing

**Accessibility:**
Both Honeypot and Friendly Captcha are WCAG 2.1 Level AA compliant:
- Honeypot: Completely invisible, no user interaction
- Friendly Captcha: Accessible puzzle interface, keyboard navigable

**Privacy compliance:**
- Friendly Captcha: GDPR compliant, EU-based
- Honeypot: No external data sharing
- No tracking or profiling of users
- Cookie consent not required for spam protection

## References

- Honeypot Module: https://www.drupal.org/project/honeypot
- Friendly Captcha: https://www.drupal.org/project/friendlycaptcha
- Friendly Captcha Service: https://friendlycaptcha.com
- CAPTCHA Module: https://www.drupal.org/project/captcha
- Related: [[0009-webform-for-forms]]
- Related: [[0012-key-module-for-api-credentials]]
