# Best Practice — Flyer & Résumé Creation (Social Services, National)

**Audience:** workers, sole traders / contractors, and organisations creating self-promotion assets
(résumés, service flyers, job flyers) on Shuffles. **Scope:** Australia-wide — NDIS, aged care, allied
health and broader social services.

> **Single source of truth.** This document is the authoritative best-practice guidance. It is also
> surfaced **in-app**: today as a Guides panel — *"Best practice: creating a flyer or résumé (social
> services)"* — via the `[sssj_guides]` shortcode and **Settings → Guides** (`Shuffles_SSJ_Guides::sections()`,
> id `asset-best-practice`). When the **asset creator (Workstream E)** is built, these rules must appear
> **inline, right where the member creates the asset** (tips beside each step, sensible defaults, and an
> accessibility check before download/share). Keep the doc, the Guides section and the wizard copy aligned.

---

## Why this matters
Assets in the care-and-support sector are read by busy participants, families, support coordinators and
employers across the whole country — many with low vision, limited English, neurodiversity, or simply very
little time. **Clear, respectful and honest beats flashy, every time.** Good assets also reflect the values
of the sector: dignity, choice, safety and inclusion.

---

## The 8 rules (built into the creator)

1. **Location + services at the very top.** The first two questions any reader has are *"where are you?"*
   and *"what do you do?"*. Lead with the suburb / service area and a short, scannable list of services
   before anything else. (On Shuffles these are already structured fields, so the template can place them
   automatically.)

2. **Plain, Easy-Read English.** Short sentences, one idea per line, everyday words, **no acronyms or
   jargon**. Target ~age-12 reading level. This is a national, culturally and linguistically diverse (CALD)
   audience; many readers use translation or read-aloud tools, so simple text helps everyone. (Pairs with
   the plugin's CALD/accessibility layer.)

3. **Person-centred, strengths-based language.** Describe what you help people *do and achieve* — *"I support
   people to get out and about, cook, and stay independent"* — not clinical labels or deficits. Lead with
   dignity and choice.

4. **Show safety credentials clearly.** Trust is the sector's currency. State the checks held — **WWCC /
   Blue Card, NDIS Worker Screening, Police Check, First Aid / CPR** — and the **ABN** for contractors. On
   Shuffles these can surface as a **✓ Verified** badge once an admin confirms them; never claim a check you
   don't hold.

5. **Be specific about who you help and when.** Real availability (*"evenings & weekends"*), the supports
   you're genuinely good at, and any specialisms — complex care, mental health, ageing, **Auslan**, a
   language you speak, **LGBTQIA+ friendly**, First Nations cultural safety. Specific gets matched; generic
   gets skipped.

6. **Protect privacy and dignity.** **Never** name or identify a participant on a flyer. **Never** use a
   photo of anyone without their **written consent**. Participant-facing assets must be **private,
   relay-safe links — never public or search-indexed** (this is a hard rule; participant needs are always
   `noindex` and pseudonymous).

7. **Accessible by design.** Large text (**12pt+**), high contrast, a clean sans-serif font, generous white
   space, and **meaningful descriptions for every image**. An accessible flyer reaches the widest audience
   and models the sector's values. The creator should run a quick accessibility check (contrast + text size)
   before download/share.

8. **One clear next step.** End with a single, obvious call to action — *how to get in touch or apply* —
   routed through the **safe Shuffles relay** wherever privacy matters. One clear ask beats several.

---

## Quick tips
- **One page is plenty.** White space and short lines make it readable, not empty.
- **Real, consented photos** that reflect actual work; avoid stock that misrepresents.
- **Inclusive language and imagery** (CALD, First Nations, disability dignity, LGBTQIA+) widen reach.
- **Keep it current** — availability, rates and credentials should never read as out of date.
- **On-brand** — reuse the Shuffles design tokens (Style Studio) so every shared asset carries the brand.

---

## Asset-type specifics (for Workstream E build)
- **Sole trader / worker résumé:** location + services header → about (strengths-based) → experience →
  credentials/verification → availability + rate → contact CTA.
- **Sole trader / worker service flyer:** big service headline + suburb/radius → 3–5 service bullets →
  credentials badges → friendly photo (consented) → one CTA.
- **Employer job flyer:** role + suburb + basis (TFN/ABN) → what the day looks like → must-have credentials →
  rate range → org logo → "how to apply" CTA. (Public listings already emit JobPosting/Org SEO.)
- **Participant asset:** a **private, relay-safe share link** + ready copy-paste "how to respond" text.
  **No public SEO, no real name, no identifying detail** — pseudonymous + `noindex` always.

---

## Build approach (E)
HTML/CSS templates using the design tokens → **browser print-to-PDF** (no server PDF library), a
downloadable graphic, plus **copy-paste text**. Surface rules 1–8 inline as tips/defaults, and gate
download/share behind a lightweight accessibility check (contrast, min text size, image descriptions,
no-participant-PII confirmation for participant assets).

---

*Maintenance: keep this doc, the `asset-best-practice` Guides section, and the eventual asset-wizard copy in
sync. Last updated: 2026-06-07.*
