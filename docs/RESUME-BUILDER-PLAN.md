# Résumé and Graphics Builder, Plan (Workstream E)

**Positioning:** turn any member profile or listing into a clean, readable, on-brand résumé, flyer or social graphic in one click, so the sector stops sharing cluttered, hard-to-read images.

**Last updated: 2026-06-07. Status: planning / workshop (not yet built).**

> Writing rules for this and all project docs: no em dashes, and member-facing copy never names third-party tools. This is an internal engineering plan, so it does name candidate dev tools to support a build decision.

---

## 1. The problem we are solving

Support workers, sole traders and small providers in the Facebook groups share résumés and flyers that are hard to read and hard to digest: tiny text, low contrast, walls of words, rainbow colours, no clear "what do you do and where". Good people get scrolled past. The platform already holds clean, structured profile data, so we can generate far better assets for them automatically.

## 2. Principles

1. **Locked templates, not a free canvas.** We craft a small number of strong layouts once. Members pour their data in and confirm. They cannot create clutter because the layout, type, spacing and colour are fixed.
2. **One click from existing data.** Pull name, suburb and service area, services, availability, verified checks, photo and a short blurb from the member's profile or listing.
3. **Readability guaranteed by the system.** A fixed type scale, an 8pt spacing grid, a 2 to 3 colour palette from brand tokens, location and services leading, one clear call to action, one page.
4. **Accessibility and sector best practice baked in** (from docs/BEST-PRACTICE-ASSETS.md): plain Easy-Read wording, person-centred language, safety credentials shown clearly, meaningful images, never identifying a participant.
5. **Privacy first.** Participant-facing assets are private, relay-safe links, never public and never indexed. Photos require consent (aligns with the Advertising and Media Production policy: no children by default, fair pay, informed consent).
6. **Standalone-first.** The whole thing works at zero running cost using the browser. A server-side renderer is an optional fidelity upgrade, runtime-detected, with a graceful fallback. No hard dependency.

## 3. The assets and their outputs

| Asset | Source data | Print | Image | Notes |
|---|---|---|---|---|
| **Worker / sole-trader résumé** | worker profile | A4 PDF | 1080 x 1350 | The main FB-group pain point. Built first. |
| **Sole-trader service flyer** | worker profile (+ ABN) | A4 PDF | 1080 x 1080 | "What I offer and where", with a call to action. |
| **Employer job flyer** | job listing (+ org) | A4 PDF | 1080 x 1080 | Clean, shareable advert for one role. |
| **Social graphic** | any of the above | n/a | 1080 x 1080 square + 1080 x 1920 story | Plus copy-paste caption text for the post. |

Every asset produces, from one set of content: a print-ready PDF, a download image at the sizes above, and copy-paste caption text. Participant assets also produce a private, relay-safe share link instead of a public one.

## 4. The design and readability system

- **Type:** one clean, legible sans family. Fixed scale: body 16pt or larger, clear heading steps, generous line height.
- **Grid and spacing:** 8pt grid, consistent margins, plenty of white space. One page.
- **Colour:** 2 to 3 colours drawn from the site's brand tokens (the same tokens the rest of the plugin uses), high contrast (WCAG AA), no rainbow soup.
- **Layout rules:** **location (suburb plus service area) sits in the header, near the name, always visible and high contrast** (it is the first question a reader has and is never buried at the bottom). Services lead next, then a short strengths-based blurb, safety credentials as a clear row, one obvious call to action, real consented photo only.
- **Pre-download readability check:** before any download we verify contrast, that text fields are within length limits, that location is present and in the header, and that a photo is present where expected. Anything that would read as cluttered is blocked with a friendly nudge.

### What we are fixing (from real samples shared from the groups)

Two real examples showed the exact problems the locked templates remove:
- **Location buried or missing.** One flyer put "Based in Palmview, servicing surrounding areas" in tiny text at the very bottom; another showed no service area at all. A reader cannot tell where the person works at a glance. **Our templates always put location in the header.**
- **Low contrast.** Pale taupe text on a light background looks elegant but is hard to read. **Our templates enforce WCAG AA contrast.**
- **Dense walls of text.** Three long "about me" paragraphs that nobody scans. **Our templates cap the blurb length and lead with scannable services and facts.**
- **No single clear next step.** **Our templates end with one call to action** (through the relay for participant-facing assets).
- The good parts seen in those samples (a clear photo, a credentials row with icons, a stated availability) are kept and made consistent.

## 5. Rendering architecture (decided: HTML/CSS templates plus server-side fidelity, with a $0 browser fallback)

**Single source of truth:** every asset is one HTML/CSS template, styled with the existing design tokens. The same template drives the on-screen preview, the print output and the raster image, so there is never a mismatch.

**Backend A, default and $0 (always available, standalone):**
- Print-ready PDF via the browser, using a dedicated print stylesheet with CSS paged-media rules. Selectable text, crisp vector, no server.
- Download image via a small client-side rasteriser (for example html-to-image) that snapshots the template to a PNG at the exact social size.
- Copy-paste caption text generated from the content.

**Backend B, fidelity upgrade, opt-in (the chosen direction):**
- A server-side headless-browser renderer (Chrome via Puppeteer or Playwright) returns pixel-perfect PDF and PNG at exact dimensions, with web fonts and full CSS support, identical on every device.
- **Where it runs (decision still open, see section 12):**
  1. A small Node plus Puppeteer service on a separate Cloudways Node application (full control, predictable monthly cost).
  2. A serverless function (for example a Lambda with a headless Chromium layer), pay per render.
  3. A configured external HTML-to-PDF/image rendering API (fastest to wire, a per-render or subscription cost, and a third-party dependency).
- **Integration:** a renderer abstraction, `Shuffles_SSJ_Asset_Renderer`, with two backends behind one interface. The server endpoint URL and a shared secret live in Settings. The plugin detects whether a renderer is configured and reachable; if not, it falls back to Backend A automatically. This keeps the standalone-first promise.
- **Security:** requests are authenticated with a shared secret or HMAC and carry a short-lived signed token that references the asset, never participant PII in a URL. The renderer returns the file to the member through an authenticated handler. Requests are rate-limited. Participant content is never rendered to a public URL.

**Why both:** members always get a good asset for free; when the fidelity renderer is configured they get a flawless one. We are never blocked by, or dependent on, the renderer.

## 6. Member journey (the "Create an asset" wizard)

1. **Pick the asset** (résumé, service flyer, job flyer, social graphic).
2. **Choose a template** from the 2 to 3 on offer for that asset (live thumbnail preview).
3. **Confirm and edit the content** that was pulled in from the profile or listing, in guided fields with character limits and a live preview. Nothing is free-form layout, only content.
4. **Readability check** runs and shows a green tick or a friendly fix-this note.
5. **Download or share:** PDF, image (at the right sizes), copy-paste caption, and for participants a private relay-safe link.

Entry points: a "Create an asset" tab in My dashboard, the `[sssj_create_asset]` shortcode, and contextual buttons ("Make a flyer for this job", "Make my résumé").

## 7. Data sources

- **Résumé and service flyer:** sssj_worker (name, suburb and service area, services, availability, years, rate where shown, languages and culture, verified credentials, photo, blurb, ABN for the flyer).
- **Job flyer:** sssj_job (title, basis, suburb, hours, pay, must-haves, closing date) plus the linked organisation (logo, name unless anonymous).
- **Social graphic and caption:** derived from whichever asset is chosen.
- Masked or members-only fields are respected. Anonymous and participant rules are respected.

## 8. Privacy and safety

- Participant assets are private and relay-safe, never public and never indexed.
- Photo use follows the Advertising and Media Production policy: no children by default, informed consent for anyone shown, and fair pay where the content is commercial. The wizard reminds the member and records the consent confirmation.
- No participant is named or identified on any asset.

## 9. Accessibility

- WCAG AA contrast, large readable type, sensible reading order, alt text on generated images where they are used on the site, and clean print output. The accessibility layer settings still apply to the wizard UI.

## 10. Technical implementation sketch

- `includes/class-assets.php`: asset definitions, data mapping from each entity, the readability checks, and the single source for the best-practice rules.
- `templates/assets/*.php`: the locked HTML templates (one per template per asset).
- `includes/class-asset-renderer.php`: the backend abstraction (browser vs server), endpoint config, auth, fallback.
- `[sssj_create_asset]` shortcode plus a "Create an asset" dashboard tab.
- `public/assets/js/sssj-assets.js`: live preview, client-side PNG, print trigger, copy caption.
- `public/assets/css/sssj-assets.css`: template styles and the print stylesheet, built on the existing tokens.
- Settings: an "Assets" tab (enable, renderer endpoint and secret, default paper size, default templates).
- Tests: a "Résumé and graphics builder" suite. FEATURES, ROADMAP and Business Logic updated when built.

## 11. Phased rollout

- **Phase 1 ($0, standalone):** the template system plus Backend A (browser PDF, client PNG, copy text). Ship the **worker / sole-trader résumé** first with 2 to 3 templates.
- **Phase 2 (fidelity):** the `Shuffles_SSJ_Asset_Renderer` abstraction plus Backend B (server-side headless rendering), with automatic fallback.
- **Phase 3 (breadth):** service flyer, job flyer, social graphic, and the caption generator.
- **Phase 4 (polish):** more templates, smarter readability hints, optional monetised premium template packs.

## 12. Open decisions to lock (the workshop)

> **Locked 2026-06-07:** (1) Renderer = the **$0 browser path is the everyday default** (print-to-PDF + client image + copy text). For pixel-perfect output the recommended Phase 2 backend is a **self-hosted Chromium render service (Gotenberg)**, with a configurable **SaaS HTML-to-PDF API** as a drop-in alternative. Both sit behind the `Shuffles_SSJ_Asset_Renderer` abstraction, runtime-detected, with automatic fallback to the browser path. **Participant-facing assets are never sent to a third-party service** (browser or self-host only). (5) Visual direction = **the refined v2 blend** (`docs/mockups/resume-v2.html`): clean white structure and generous spacing (from A), a soft tinted header with a ringed rounded avatar and friendly call to action (from B), and a strong scannable facts row with short accent bars on section headers (from C). Calm blue-teal primary plus one warm micro-accent, with the real builder pulling the site's brand-token colours. The earlier comparison mockups (resume-clean, resume-warm, resume-bold) are kept in `docs/mockups/` for reference. This v2 style is the launch look for the worker résumé and the other three assets follow it.


1. **Server renderer host:** Cloudways Node app, serverless function, or external rendering API. Trade-off is control vs ops vs cost.
2. **Acceptable running cost** for Phase 2 (sets the choice above).
3. **Paper size default:** A4 (Australia) confirmed, plus whether to offer Letter.
4. **Templates per asset at launch:** suggest 2 to 3.
5. **Visual direction(s):** clean and professional, warm and human, or bold and modern (we can offer one of each).
6. **Photo and consent handling** for social graphics (consent gate wording).
7. **Monetisation:** is the builder free for everyone, or are premium template packs a paid add-on.

## 13. Cost summary

Phase 1 is $0 to build and run (browser-based). Phase 2 adds a small running cost: a Node service or per-render API. Everything else reuses what the plugin already has (design tokens, profile data, the relay, the policies).

*Maintenance: keep this plan in step with FEATURES.md (Workstream E), docs/ROADMAP.md and docs/BEST-PRACTICE-ASSETS.md as the build proceeds. Last updated: 2026-06-07.*
