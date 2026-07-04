---
tags: [cms2/decision]
status: accepted
created: 2026-06-30
decided: 2024-01-01
site: shared
deciders: [Architecture Team]
---

# 0009: Webform Module for Form Building

## Status

accepted

## Context

The sites need flexible form building capabilities for:
- Contact forms
- Registration forms
- Surveys and questionnaires
- Application forms
- User feedback forms

Requirements:
- Visual form builder for content creators
- Conditional logic support
- Multiple submission handlers
- Data export capabilities
- Email notifications
- Spam protection integration
- Accessibility compliance

Options available:
1. Webform module (most popular)
2. Entity forms with Form API
3. Contact module (core)
4. Custom form development

## Decision

Adopt Webform module (version 6.3.x) as the standard form building solution across all sites.

**Implementation:**
- Webform 6.3.0-rc2 with UI module enabled
- Extensive library dependencies managed through Composer
- Integration with spam protection (CAPTCHA, Honeypot)
- Integration with email system (Easy Email)
- Shared form patterns available to all sites

## Consequences

### Positive

- **Visual builder**: Content creators can build forms without code
- **Feature rich**: Comprehensive form element types and handlers
- **Conditional logic**: Show/hide fields based on other inputs
- **Spam protection**: Integrates with CAPTCHA and Honeypot
- **Data management**: Export submissions to CSV/Excel
- **Email handling**: Flexible email notification system
- **Accessibility**: WCAG 2.0 compliant forms
- **Multipage forms**: Step-by-step form wizards
- **Templates**: Reusable form templates
- **Integrations**: Works with other contrib modules
- **Active development**: Well-maintained with large community

### Negative

- **Complex UI**: Powerful but can be overwhelming
- **Learning curve**: Content creators need training
- **Many dependencies**: Requires multiple JavaScript libraries
- **Performance**: Complex forms can be heavy
- **Overkill**: Simple forms might not need Webform power
- **Database tables**: Adds several custom tables

### Neutral

- **JavaScript libraries**: Many external dependencies (managed through Composer)
- **UI styling**: Forms may need custom theme integration
- **Submission storage**: Can store in database or send to external services

## Alternatives Considered

### Alternative 1: Core Contact Module

Use Drupal's built-in Contact module.

**Rejected because:**
- Limited to basic contact forms
- No conditional logic
- No multi-step forms
- Limited customization
- No advanced submission handling
- Too basic for complex requirements

### Alternative 2: Custom Form API Forms

Build all forms using Drupal Form API.

**Rejected because:**
- Requires developer for every form
- No visual builder for content creators
- High development cost
- Harder to maintain
- No WYSIWYG form building

### Alternative 3: Entity Forms

Use custom content types as forms with custom handling.

**Rejected because:**
- Not designed for forms
- Awkward user experience
- Complex to implement conditional logic
- No form-specific features
- Submissions would be content entities (not ideal)

### Alternative 4: Third-party Form Services

Use external services like Google Forms, Typeform, etc.

**Rejected because:**
- Data hosted externally
- Less integration with Drupal
- Subscription costs
- Branding limitations
- No offline capability

## Implementation Notes

**Composer dependencies:**
```json
{
  "require": {
    "drupal/webform": "^6.3-beta3",
    "codemirror/codemirror": "*",
    "jquery/inputmask": "*",
    "jquery/intl-tel-input": "*",
    "jquery/rateit": "*",
    "jquery/select2": "*",
    "jquery/textcounter": "*",
    "jquery/timepicker": "*",
    "popperjs/popperjs": "*",
    "progress-tracker/progress-tracker": "*",
    "signature_pad/signature_pad": "*",
    "tabby/tabby": "*",
    "tippyjs/tippyjs": "*"
  }
}
```

**JavaScript library repositories:**
Custom package repositories defined in composer.json for Webform's JavaScript dependencies.

**Enabled modules:**
- `webform`: Main Webform module
- `webform_ui`: Visual form builder interface

**Integration with spam protection:**
- `captcha`: ^2.0.10
- `friendlycaptcha`: ^1.1.4  
- `honeypot`: ^2.2.2

Webforms can use any of these spam protection methods.

**Integration with email:**
- `easy_email`: ^3.0.8
- `mailsystem`: ^4.5

Webform emails can be processed through Easy Email templates.

**Common form elements available:**
- Text fields, textareas, select lists
- Date/time pickers with jquery.timepicker
- Phone number fields with intl-tel-input
- Rating fields with rateit
- Signature fields with signature_pad
- Advanced select with select2
- Conditional logic with custom handlers
- File uploads integrated with Drupal Media
- Address fields with postal code validation

**Access control:**
Webform has granular permissions for:
- Creating/editing webforms
- Viewing submissions
- Exporting data
- Deleting submissions

## References

- Webform Project: https://www.drupal.org/project/webform
- Webform Documentation: https://www.drupal.org/docs/contributed-modules/webform
- Related: [[0010-spam-protection-strategy]]
- Related: [[0011-easy-email-for-transactional-mail]]
