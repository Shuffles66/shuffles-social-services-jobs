# Canva templated assets — scope (grounded in the real Canva Connect API, June 2026)

A possible **future upgrade** to the asset builder (Workstream E): instead of (or alongside) our locked
HTML templates rendered to PDF, generate **designer-quality, on-brand assets from Canva Brand Templates**
by autofilling a member's profile data, then exporting to PDF/PNG.

This is a scope, not a commitment — there are real cost and privacy gates that make it a business decision.

---

## What's actually possible with the Canva Connect API

| Capability | Real? | Notes |
|---|---|---|
| **Autofill a Brand Template** with data (name, location, services, photo) | ✅ Yes | `POST /autofills` against a Brand Template's dataset (`Get brand template dataset` lists the fields). |
| **Export a design** to PDF / PNG / JPG / PPTX / MP4 | ✅ Yes | `POST /exports`. Rate limits are generous (750 / 5 min, 5,000 / day per integration). |
| **Upload an asset** (e.g. a member's photo) | ✅ Yes | Asset upload endpoint, then reference it in the autofill. |
| **Auth** | ✅ Yes | OAuth 2.0 Authorization Code + PKCE (SHA-256). |

### 🚩 The hard gate: Brand Templates + Autofill require **Canva Enterprise**
Per Canva's own docs: *"To use the Brand template and Autofill APIs, your integration must act on behalf
of a user that's a member of a Canva Enterprise organization,"* and the developer account must also be in
a Canva Enterprise org (with MFA). A non-Enterprise dev can *request* dev-only access, but production
autofill needs Enterprise.

**Implication:** members (workers / sole traders) are **not** Canva Enterprise users, so we cannot have
each member connect their own Canva. The only workable model is:

> **The site holds ONE Canva Enterprise account.** All autofill + export runs server-side under that single
> account's token. Members never touch Canva — they click "Make a designed PDF" and get a file back.

So the real prerequisite is **the business pays for Canva Enterprise.**

---

## Privacy (decisive constraint)

Autofilling sends the member's data + photo to **Canva (a third party)**. That runs straight into our
standing invariant: **participant-derived content must never go to a third party.**

- ✅ Allowed (with consent + disclosure): a **worker's / advertiser's own** résumé, flyer, job flyer —
  it's their own data, they chose to make a shareable asset.
- ❌ Never: anything **participant-derived** (`sssj_need`). The same self-hosted guard we built for the
  Gotenberg renderer applies — **Canva is a third-party backend**, so participant assets are blocked from
  it, full stop.

This means Canva would be wired in as a **third-party asset backend**, gated the same way the renderer's
"self-hosted" flag gates participant content today.

---

## How it would fit our architecture

Important difference from the current builder: Canva uses **its own designs** (templates built in Canva),
not our HTML templates. So this is a **parallel generation path**, not a drop-in renderer:

```
Member profile data ──► map fields ──► Canva Autofill (Brand Template) ──► Export (PDF/PNG) ──► serve to member
        │                                   (site's single Enterprise account, server-side)
        └─ photo ──► Canva asset upload ──┘
```

- A new optional **"Canva designed assets"** feature, settings-gated (OAuth connect + template IDs +
  field mapping), sitting beside the existing **Browser ($0)** and **Gotenberg (self-hosted PDF)** paths.
- Admin builds the Brand Templates in Canva once (résumé / service flyer / social), then maps our profile
  fields → each template's autofill fields in settings.
- Members get an extra "Designed PDF / image (Canva)" button when the feature is connected.

---

## Trade-offs

**Pros**
- Genuinely professional, on-brand, varied designs without us hand-coding every template.
- Could offer multiple looks; easy for non-designers to update templates in Canva.

**Cons / costs (all real)**
- **Recurring Canva Enterprise subscription** (the main blocker — a real, ongoing business cost).
- **Third-party data egress** (member data + photos to Canva) — acceptable for own-data assets with
  consent, but **never** for participant content, and must be disclosed in the privacy policy.
- OAuth token management + refresh, asset upload, export polling (async jobs), rate-limit handling.
- Ongoing upkeep: brand-template IDs changed format in Sept 2025 — Canva-side changes need maintenance.
- We already have a **$0, fully-private** path (browser) and an optional **self-hosted pixel-perfect** path
  (Gotenberg). Canva adds polish, not a missing capability.

---

## Recommendation

Treat Canva as a **premium "designer-quality assets" add-on**, pursued only if:
1. the business is willing to pay for **Canva Enterprise**, and
2. it's comfortable sending **own-data (never participant)** assets to Canva with disclosed consent.

If yes, it's a clean, well-bounded build (1 OAuth integration + template mapping + the third-party guard,
reusing the existing asset UI). If not, the current browser + optional Gotenberg paths already meet the
need at $0 with full privacy.

**Suggested phasing if greenlit:**
- Phase 1: OAuth connect + settings (template IDs, field mapping) + the third-party/participant guard.
- Phase 2: one asset type end-to-end (résumé) — autofill → export PDF → serve.
- Phase 3: flyer + social + (advertiser) job flyer; a member-facing "designed" button.

---

## Sources (June 2026)
- Canva Autofill guide + Enterprise requirement: canva.dev/docs/connect/autofill-guide, canva.dev/docs/connect/api-reference/autofills
- Brand templates (Enterprise; Sept 2025 ID migration): canva.dev/docs/connect/api-reference/brand-templates
- Exports (PDF/PNG/…, rate limits): canva.dev/docs/connect/api-reference/exports
- Auth (OAuth 2.0 + PKCE): canva.dev/docs/connect
