# Milestone Timeline — Shuffles Social Services Jobs and Engagements

*Append-only. A curated record of major milestones — the momentum story, distinct from a line-by-line changelog. Newest at the top.*

---

## 2026-06-05 — Design v1.1 (brief expanded, design locked)
Following a request to "create a jobs plugin for disability, aged care and social services," the existing locked design was **extended** rather than rebuilt. Added to the architecture:
- **ABN vs TFN engagement split** with segregated boards (participant board = ABN-only; TFN board excludes ABN) — enforced in the query layer.
- **Multi-select funding sources** (NDIS, Aged Care, DVA, iCare, Medicare, private, none — one/many/none).
- **Accessibility / CALD layer** ported from Shuffles Provider Finder (voice, 7 languages, read-aloud, display modes — $0 to run).
- **Strong SEO** — `JobPosting` / Google-for-Jobs, indexable pages, sitemaps; participant pages kept `noindex`.
- **Resell licensing** + two monetisation streams (employer advertising subscription; provider application-fee subscription).
- **Incumbent** (current workers wanting more) and **agency** actor refinements.

Delivered as the **docs-first** package: amended `JOBS-BOARD-PLAN.md` (v1.1), easy-read `RULES.md`, the full marketing set (this doc set), and seeding-partner / endorsement materials. No plugin code yet — build begins at Phase 0 (scaffold).

## (earlier) — Design v1.0 (original lock)
Four-sided marketplace designed (Job Ads, Worker Profiles, Participant Needs, Worker Availability), NDIS-first and sector-agnostic. Entity schemas, taxonomy seeds, matching engine, compliance/credential layer, `shuffles-growth` integration via four filter hooks, and a 9-phase rollout plan captured in `docs/JOBS-BOARD-PLAN.md`. Plugin slug, namespaces, `CLAUDE.md` working rules and build/deploy pattern established. Status: pre-Phase 0, scaffold not yet built.

---

### Planned milestones (to be dated as they ship)
- **Phase 0 — Scaffold:** CPTs + taxonomies (incl. ABN/TFN + funding fields) registered, seeded categories live in wp-admin, settings tabs.
- **Phase 1 — Advertisers:** job posting, segregated ABN/TFN boards, A11y layer, SEO on job pages, expiry cron.
- **Phase 2 — Workers:** worker profiles, directory, ABN capture, basic compliance fields.
- **Phase 3 — Matching:** real-time "Matching X" panels.
- **Phase 4 — Participants:** participant-need board, pseudonym handling, gated visibility, internal messaging, provider application-fee gate.
- **Phase 5 — Compliance workflow:** credential uploads, verification queue, expiry warnings, verified badge.
- **Phase 6 — `shuffles-growth` bridge:** internal listings in member referral research.
- **Phase 7 — Monetisation:** PMPro gating, FluentCart billing, resell licensing, promoted placement.
- **Phase 8 — Matching v2:** scored matching table + cron.
- **Phase 9 — Multi-sector:** compliance-profile presets, white-label.
