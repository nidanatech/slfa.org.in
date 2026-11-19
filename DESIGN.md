# SLFA design system

This document records the visual rules for the public site. It follows the DESIGN.md pattern in VoltAgent's awesome-design-md collection and the Vercel Web Interface Guidelines.

## Identity

- Voice: welcoming, credible, community led. Prefer concrete statements over grand claims.
- Primary ink: `#0b101c`; maroon: `#800000`; gold: `#D4AF37`; warm paper: `#f5f1e9`.
- Serif headings: Abhaya Libre. Interface and body copy: Lato.
- Contrast: gold on dark surfaces, deep ink on light surfaces. Avoid pale gold body text on white.
- Shape: large but consistent `1.5rem` to `2rem` card radii, restrained borders, broad editorial spacing.
- Photography: use existing culture and event images; apply a navy gradient for text legibility.

## Page rhythm

1. Dark image led hero with one H1, concise message, and a clear primary action.
2. Warm paper editorial section for explanation and readable cards.
3. Dark navy section for activities, evidence, or featured events.
4. Warm paper for details and next steps.
5. Maroon or navy closing action.

This pattern carries the premium hero feel through the full scroll without making every section identical. Keep section padding roughly 4rem on mobile and 5rem to 6rem on desktop; reduce it for short content.

## Shared components

- Public header: brand mark at left, a single News & Events link, membership action at right.
- Cards: one clear title, enough contrast for body text, subtle border and shadow on light surfaces; translucent fill and gold border on dark surfaces.
- Buttons: gold filled primary, outlined secondary; all have visible focus states.
- Images: meaningful images have alt text; decorative images use empty alt or CSS backgrounds. Lazy load below the fold.
- Motion: use small opacity and transform reveals only; obey reduced motion, and avoid autoplay carousels for essential content.

## Rollout after this update

- Replace the provisional brand mark with the official transparent SLFA crest once the source asset is supplied.
- Apply the same hero, surface, and card rules to Gallery, Contact, Membership, and member profile pages.
- Audit old `/storage/legacy` download links and media and publish only files that are actually available.
- Add real event photos and updated committee portraits through the admin tools as content becomes available.
- Review at 390px, 768px, and 1440px, including keyboard navigation, forms, and loading states.
