# Reference & Indexing System — Shuffles Social Services Jobs and Engagements

**Version:** 1.1 (design) · **Date:** 2026-06-05 · *Source of truth for the in-plugin "Project Management" tab. Maintain this and the settings `tab_meta()` together on any tab/CPT/area change.*

This mirrors the convention proven in Shuffles Provider Finder: settings tabs are number-prefixed and colour-dotted **by domain**, each entity/table/feature has a stable code, and one read-only admin tab indexes everything.

---

## 1. Colour domains

| Dot | Domain | Covers |
|---|---|---|
| 🟦 indigo | Data / CPT | entities, taxonomies, seeds |
| 🔵 blue | Search / AI / Matching | matcher, `shuffles-growth` bridge |
| 🟢 teal | CALD / Access | accessibility + i18n |
| 🟠 amber | Trust / Compliance | credentials, verification, ABN, privacy/moderation |
| 🟧 orange | Members / Monetisation | PMPro, FluentCart, licensing |
| ⬜ slate | Core / Ops | general, maps, SEO, diagnostics, changelog, PM |

## 2. Settings tabs (T-codes)

| Code | Tab | Domain |
|---|---|---|
| T1 | General | slate |
| T16 | Appearance | orange |
| T2 | Boards & Segregation (ABN/TFN) | indigo |
| T3 | Taxonomies & Seeds | indigo |
| T4 | Compliance & Credentials | amber |
| T5 | Funding Sources | amber |
| T6 | CALD & Access | teal |
| T7 | SEO | slate |
| T8 | Maps & Location | slate |
| T9 | Matching | blue |
| T10 | Monetisation (PMPro / FluentCart) | orange |
| T11 | Licensing | orange |
| T12 | Integrations (FluentCRM / Boards / Notion / shuffles-growth) | blue |
| T13 | Privacy & Moderation | amber |
| T14 | Diagnostics | slate |
| T15 | Changelog | slate |
| PM | Project Management (this index) | slate |

## 3. Entities & tables

| Code | Item | Domain |
|---|---|---|
| CPT-01 | `sssj_job` — Job Ad | indigo |
| CPT-02 | `sssj_worker` — Worker Profile (+ availability, incumbent) | indigo |
| CPT-03 | `sssj_need` — Participant Need | indigo (amber-gated) |
| TAX-01…08 | `sssjt_category` / `_role` / `_qualification` / `_compliance_profile` / `_mode` / `_employment_type` / `_support_category` / **`_funding_source`** | indigo |
| TBL-01 | `sssj_match_score` | blue |
| TBL-02 | `sssj_application` | orange |
| TBL-03 | `sssj_message` | amber |
| TBL-04 | `sssj_credential` | amber |

## 4. Screens

`[S]` board/search · `[J]` single job · `[W]` worker profile/directory · `[N]` participant-need board · `[P]` posting forms · `[M]` my-listings dashboard · `[A]` admin/settings · `[V]` verification queue · `[ALL]` site-wide.

## 5. Feature areas (F-codes — match `FEATURES.md`)

| Code | Area | Domain |
|---|---|---|
| F-A | Four-sided marketplace | indigo |
| F-B | ABN/TFN segregated boards | indigo |
| F-C | ABN recording & verification | amber |
| F-D | Participant privacy & anonymity | amber |
| F-E | Credential verification & pre-matching | amber |
| F-F | Matching & maps | blue |
| F-G | Multi-funding | amber |
| F-H | Accessibility & CALD | teal |
| F-I | Strong SEO | slate |
| F-J | Monetisation & resell licensing | orange |
| F-K | Posting, applications & messaging | orange |
| F-L | Admin, settings & integrations | slate |

## 6. Shortcodes

`[sssj_job_board]` · `[sssj_tfn_board]` · `[sssj_abn_board]` · `[sssj_need_board]` · `[sssj_job_detail]` · `[sssj_worker_directory]` · `[sssj_worker_profile]` · `[sssj_post_job]` · `[sssj_post_worker]` · `[sssj_post_need]` · `[sssj_my_listings]`.

## 7. Cross-references

- Architecture detail → `JOBS-BOARD-PLAN.md` (§ numbers).
- Feature copy / sales angles → `FEATURES.md` (F-codes).
- Access & privacy → `SECURITY-ACCESS.md`.
- Plain-language rules → `RULES.md`.
- Look & feel / UI tokens → `DESIGN-SYSTEM.md`.

> **Maintenance rule:** any new tab, CPT, table or feature area must be added here **and** to the in-plugin PM tab's source (`tab_meta()` / `tab_labels()` / `tab_colours()` in `admin/class-admin.php`) in the same change.
