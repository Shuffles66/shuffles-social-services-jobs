# Roadmap & To‑Do — Shuffles Social Services Jobs & Engagements

Living backlog. Keep current as items ship or priorities change. (Companion to
`docs/business_rules_and_logic.md` and the in‑app Changelog.)

- **Last updated:** 2026-06-07

## Status
- **Live:** v0.55.4 (committed `25bab52`).
- **Deployed, awaiting commit:** v0.56.0 (Provider Import preview), v0.57.0 (header menu sync).
- **Built, not yet deployed:** v0.58.0 (form section cards / completeness / sticky save / toggle / toast),
  v0.59.0 (provider **swipe deck** `[sssj_swipe]` + section left accent borders).
  → **Action:** deploy + commit v0.56.0 → v0.59.0 when authorised.

## Next up (prioritised)
1. **Deploy + commit** the pending versions (v0.56–v0.59).
2. **Swipe deck follow‑ups** (NEW):
   - A **"My shortlist"** surface — a dashboard tab / page listing providers saved via swipe (`_sssj_saved_orgs`), with remove + contact.
   - Confirm swipe **right‑swipe semantics** (currently = save to shortlist; alternatives: express interest / open profile).
   - Optional: a swipe deck for **workers** too (`type="worker"`).
3. **Application process expansion** (NEW workstream — see Workshop A). Pipeline stages, screening
   questions, basis‑aware apply (employment vs contract/quote), notifications, withdraw.
4. **Employer vs ABN‑contractor — one account, no confusion** (NEW workstream — see Workshop B).
   Role‑based onboarding + dashboard reveal; mode‑appropriate fields; legislation‑aware guidance.
5. **Carry the v0.58 section cards to the org + participant forms** (worker form done; add org/need section maps).
6. **"All jobs board" decision** (Cowork brief): retire the old WP Job Manager/Elementor `all-jobs-board`
   page and make `[sssj_job_board]` the public Jobs page; then add board gap items (sticky filter bar,
   active‑filter chips, single‑board TFN/ABN toggle + Sort + aria‑live count, grid/list toggle,
   save/bookmark, loading skeletons, empty‑state CTA, emoji→inline‑SVG). + site‑level menu/dev‑page cleanup.
7. **NDIS data:** per‑provider live lookup is the verification path (works server‑side). The full **named**
   list is only via the **Provider Finder "Download Providers List as CSV"** (manual — ndis.gov.au is
   Cloudflare‑blocked server‑side, so no auto‑fetch). The **compliance‑actions CSV** (named, banned/revoked)
   feeds the Reference Check banning plugin. Bulk import stays a **preview‑only** PoC for now.

## Agreed designs (workshopped 2026-06-07)

### Workstream B — Employer vs ABN contractor, one account (DECIDED)
- **Unified dashboard, reveal by hat.** One account; `[sssj_roles]` becomes a clear **hat picker** in
  plain language: *Employer / company* · *Available for work (employee, TFN)* · *Available for contracting
  (sole trader, ABN)* · *Participant* · *Participant representative/nominee* · *Supplier*. Hats are additive
  and map to caps. The dashboard **reveals only the matching profile sections + fields per hat** — never a
  muddled mega‑form, never separate logins.
- **Mode‑appropriate fields + required fields (legislation‑aware, not legal advice):**
  - *Employer hat* → company profile required (legal/entity name, **ABN required**, employer confirmation);
    guidance notice: PAYG withholding, Fair Work, super.
  - *Contractor hat (ABN)* → **ABN required** + insurances (PL/PI) + genuine‑contractor acknowledgement;
    guidance notice: sham‑contracting caution, GST.
  - *Employee hat (TFN)* → availability + right‑to‑work confirmation; no ABN needed.
- Reusable **guidance notice** components per mode; acknowledgements stored.
- **Phases:** B1 hat picker + dashboard reveal → B2 mode fields + required‑field enforcement → B3 legislation
  guidance + acknowledgements.

### Workstream A — Application process expansion (DECIDED)
- **Full pipeline:** Applied → Shortlisted → Interview/Quote review → Offer → Hired / Declined / Withdrawn,
  with **status history** + **email notifications** (applicant + poster) and applicant **withdraw**.
- **Basis‑aware apply:**
  - *Employee/TFN role* → employment application: availability, right‑to‑work, **per‑listing screening
    questions**, cover note.
  - *ABN contract / participant task* → expression of interest: **quoted rate + unit**, ABN + insurance
    confirmation, availability, message.
- **Phases:** A1 pipeline stages + notifications + withdraw → A2 basis‑aware apply forms → A3 per‑listing
  screening questions.

**Recommended build order:** B1 (foundation — defines the hats that drive everything) → A1 (pipeline) →
A2/A3 (basis‑aware apply + screening) → B2/B3 (mode fields + legislation). Ships incrementally, one phase
per version.

## Later / optional
- shuffles‑growth AI bridge (the 4 filters); real‑time matching panels; FluentCart Pro product for live
  licensing/billing; full i18n native review; keyless Leaflet/OSM map tiles; richer AU suburb dataset.
