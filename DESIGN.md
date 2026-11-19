# SLFA design system

This document records the visual rules for the public site. It follows the DESIGN.md pattern in VoltAgent's awesome-design-md collection and the Vercel Web Interface Guidelines.

## Identity

- Voice: welcoming, credible, community led. Prefer concrete statements over grand claims.
- Primary ink: `#0b101c`; maroon: `#800000`; gold: `#D4AF37`; warm paper: `#f5f1e9`.
- Serif headings: Abhaya Libre. Interface and body copy: Lato.
- Contrast: gold on dark surfaces, deep ink on light surfaces. Avoid pale gold body text on white.
- Shape: open editorial sections, thin rules, broad spacing. Reserve boxed surfaces for forms and controls that truly need containment.
- Photography: use existing culture and event images; apply a navy gradient for text legibility.

## Page rhythm

1. Dark image led hero with one H1, concise message, and a clear primary action.
2. Warm paper editorial section for explanation, with ruled text columns.
3. Dark navy or photographic section for activities, evidence, or featured events.
4. Warm paper for details and next steps.
5. Maroon or navy closing action.

This pattern carries the premium hero feel through the full scroll without making every section identical. Keep section padding roughly 4rem on mobile and 5rem to 6rem on desktop; reduce it for short content.

## Shared components

- Public header: brand mark at left, a single News & Events link, membership action at right.
- Content: favor open rows, restrained lines, and image led stories over repeated rounded cards.
- Buttons: gold filled primary, outlined secondary; all have visible focus states.
- Images: meaningful images have alt text; decorative images use empty alt or CSS backgrounds. Lazy load below the fold.
- Motion: crossfade decorative background photos at a calm pace; obey reduced motion and keep all essential content static and readable.

## Rollout after this update

- Use the official SLFA crest and wordmark assets now included in `public/images` across the site.
- Carry the same photo, type, and ruled row language through member profile and dashboard pages as a later pass.
- Audit old `/storage/legacy` download links and media and publish only files that are actually available.
- Replace illustrative archive covers with the exact historic event photos when they are available; admins can now upload news covers.
- Review at 390px, 768px, and 1440px, including keyboard navigation, forms, and loading states.
