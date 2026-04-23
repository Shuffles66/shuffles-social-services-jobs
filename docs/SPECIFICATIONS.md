# Technical Specifications — Shuffles Social Services Jobs and Engagements

**Version:** 1.1 (design) · **Date:** 2026-06-05

This is the technical reference. The full architecture lives in [`JOBS-BOARD-PLAN.md`](JOBS-BOARD-PLAN.md); this document is the at-a-glance footing for engineers and technical evaluators. Field-level detail and rationale are in the plan's §3–§25.

---

## 1. Platform & namespaces

| | |
|---|---|
| WordPress | 6.4+ |
| PHP | 8.1+ |
| Plugin slug / text domain | `shuffles-social-services-jobs` |
| Class prefix | `Shuffles_SSJ_` / `SSJ_` |
| CPT prefix | `sssj_` |
| Taxonomy prefix | `sssjt_` |
| Settings option | `shuffles_ssj_settings` |
| DB table prefix | `{wp_prefix}sssj_` |
| Version constant | `SHUFFLES_SSJ_VERSION` |

## 2. Entities (CPTs)

| CPT | Role | Default visibility | Indexable? |
|---|---|---|---|
| `sssj_job` | Job Ad (employer/agency) | Public | Yes (JobPosting) |
| `sssj_worker` | Worker Profile (+ availability, + incumbent status) | Opt-in | Per `visibility`, no PII |
| `sssj_need` | Participant Need | Pseudonymous + logged-in-only | **Never** (`noindex`, gated) |

**Key new v1.1 meta:** `sssj_job.engagement_basis` (abn/tfn), `engagement_type` (one-off/ongoing), `organisation_type` (employer/agency/sole-trader), `advertiser_abn`; `sssj_worker.employment_status` (seeking/employed-open-to-more/not-looking), `open_to[]`, `abn`; `sssj_need.funding_sources[]`, `funding_management`. Full meta tables: plan §3.

## 3. Taxonomies (seeded on activation, admin-editable)

`sssjt_category` · `sssjt_role` · `sssjt_qualification` · `sssjt_compliance_profile` · `sssjt_mode` · `sssjt_employment_type` · `sssjt_support_category` · **`sssjt_funding_source` (new v1.1)**.
Seeds in `data/seed-*.json`, loaded by `Shuffles_SSJ_Taxonomy_Seeder`.

## 4. Custom tables

```
{prefix}sssj_match_score   — computed pair scores (source/target × type)
{prefix}sssj_application   — applicants / applications, with status pipeline
{prefix}sssj_message       — internal P2P messaging relay
{prefix}sssj_credential    — (Phase 5+) credential audit history
```
Schemas: plan §6–§7.

## 5. ABN / TFN segregation (core invariant)

- `engagement_basis` required on `sssj_job`; `sssj_need` is immutably `abn`.
- **All** board shortcodes, REST list endpoints and the matcher route through `Shuffles_SSJ_Query::base_args($basis)`, which injects a mandatory `meta_query` on `engagement_basis`. No mixed-basis path without an explicit labelled tab.
- ABN handling reuses the Reference Check pattern: normalise (`preg_replace('/\D+/','',…)`), length ≥ 11 + **modulus-89 checksum**, optional ABR lookup cached per ABN, monospace display, forward `do_action('shuffles_ssj_abn_recorded', …)`.

## 6. Matching engine

- **v1 (real-time):** `WP_Query` + `tax_query` + `meta_query`; dimensions = category overlap (0.35), location/radius (0.25), availability (0.15), compliance-readiness (0.15), rate (0.10). Funding overlap is a soft re-rank, never a hard filter.
- **v2 (scored):** `sssj_match_score` table + weekly cron + on-create/edit recompute.
- **v3 (AI):** via `shuffles_growth_extra_search_results` — label-prefixed payloads (`[JOBS BOARD — …]`).

## 7. Accessibility / CALD

Ported from Shuffles Provider Finder (`assets/js/finder.js` + `finder.css`), re-namespaced `sssj-`. Voice search (Web Speech API), 7-language i18n via `shuffles_ssj_i18n` filter (Arabic RTL), read-aloud (SpeechSynthesis), high-contrast/no-colour/larger-text/Easy-Read. Prefs in `localStorage` + user-meta (`shuffles_ssj_save_prefs`). Master-gated by `cald_*` options. Browser-side — no server cost.

## 8. SEO

`JobPosting` JSON-LD (Google-for-Jobs fields incl. `validThrough`, `baseSalary`, `directApply`, telecommute for remote), canonical/OG/Twitter/`BreadcrumbList`, server-rendered single + taxonomy-archive pages, XML sitemap. **`sssj_need` → `noindex` + sitemap-excluded; `sssj_worker` → per visibility, PII-stripped.**

## 9. Maps & location

Google Maps + Places autocomplete + Geocoder; radius (Haversine) matching as in `shuffles-provider-finder`. **Loader de-duplicated** (`shuffles_ssj_external_gmaps_loader_handle()`) — shared key with `shuffles-growth`.

## 10. Access control & monetisation

Posting checks run in order: `sssj_post_job` (advertiser, PMPro tier limits) → `sssj_post_worker` → `sssj_post_need` (→ `pending` moderation). Three money concepts: resell licence (FluentCart key + GitHub updater), employer advertising subscription (volume + `is_promoted`), provider application-fee subscription (gates `POST /apply` to needs / ABN tasks). Reuses the `shuffles-growth` `class-access-control.php` Elite-check pattern.

## 11. REST API

`/wp-json/shuffles-social-services-jobs/v1/` — `jobs`, `workers`, `needs`, `matches`, `apply`, `message`, plus `shuffles-growth/search`. List endpoints carry `engagement_basis`; protected endpoints require WP auth (cookie+nonce / App Password). Detail: plan §11.

## 12. Front-end surfaces

Shortcodes first: `[sssj_job_board]`, `[sssj_tfn_board]`, `[sssj_abn_board]`, `[sssj_need_board]`, `[sssj_job_detail]`, `[sssj_worker_directory]`, `[sssj_worker_profile]`, `[sssj_post_job]`, `[sssj_post_worker]`, `[sssj_post_need]`, `[sssj_my_listings]`. Theme-overridable templates in `templates/`. Blocks in Phase 4+.

## 13. Integrations

PMPro · FluentCart · FluentCRM · Fluent Boards · Fluent Forms · ACF · Notion · Google Maps · **`shuffles-growth`** (four filter hooks, zero edits — plan §8).

## 14. Privacy & compliance

`class-privacy.php` exposes `wp_privacy_personal_data_exporter` + `_eraser` per entity; `wp_consent_api` on credential uploads; credential docs in `wp-content/uploads/sssj-credentials/` (`.htaccess` deny-all + signed REST URL + server-side MIME check); doc retention ≤ 7 years; auto-purge after deletion + 30-day grace. Participant needs are pre-publish moderated.

## 15. Build & deploy

`dist/build.ps1` (mirrors `shuffles-growth`): reads `Version:` header, packages `admin/ public/ includes/ templates/ data/ blocks/ shuffles-social-services-jobs.php CLAUDE.md`, outputs `dist/shuffles-social-services-jobs-{version}.zip` (forward-slash entries, `robocopy /Z /R:5 /W:2`). Deploy via `PYTHONUTF8=1 python3 /tmp/sftp_sync.py`. Every ship: bump `Version:` + `SHUFFLES_SSJ_VERSION`, add a changelog entry in `admin/views/settings.php` `#sssj-tab-changelog`.

## 16. Sector portability

`sssjt_compliance_profile` term + a per-profile JSON config (required/optional credentials, seed file, terminology) + a `shuffles_ssj_compliance_profiles` filter. Activator asks the deploying admin which sector → seeds accordingly. Underpins the resell/white-label model.

## 17. Known technical watch points

NDIS Worker Screening has no public verification API (manual for now); rate-limit applications + messaging from day one; email-relay messaging is MVP-only (real inbox later); featured-listing search boost decision (order-by-meta vs promoted section); validate credential MIME server-side. Full list: plan §18.
