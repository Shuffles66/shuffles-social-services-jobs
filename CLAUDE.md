# CLAUDE.md — Shuffles Social Services Jobs and Engagements Plugin

## Working Rules (read first, every session)

- **Grep before Read.** Use `Grep` to locate the exact line/function, then `Read` with `offset`+`limit`. Never read a whole file to find a target.
- **No Explore agents for targeted searches.** Use `Grep`/`Glob` directly.
- **No post-change summaries** unless explicitly asked. Make the edit, move on.
- **No defensive parallel reads.** Only read files directly needed for the current change.
- **Trust CLAUDE.md and JOBS-BOARD-PLAN.md.** Don't re-read files either document already describes accurately.

---

## Current Status

**Phase 0 built — v0.1.0 (scaffold).** Activates in wp-admin: the three CPTs (`sssj_job`, `sssj_worker`, `sssj_need`), eight seeded taxonomies, the four custom tables, ABN/TFN + funding meta, the `Shuffles_SSJ_Integrations` registry, ABN checksum helper, custom caps and the 16-tab settings page are all live. Standalone-first (no required plugins). **Next: Phase 1** — advertiser job-posting form, segregated ABN/TFN boards, the A11y/CALD layer, and JobPosting SEO on job pages.

Every change session MUST complete ALL of the following steps in order:

1. Bump the version in both the `Version:` plugin header and the `SHUFFLES_SSJ_VERSION` constant in `shuffles-social-services-jobs.php`
2. Add a changelog entry for the new version at the **top** of the version list in `admin/views/settings.php` (inside `#sssj-tab-changelog`, before the previous top entry). Each entry must include the version number, today's date, and bullet points describing every change made in that version.
3. Run `dist/build.ps1` to produce a new versioned zip
4. Run `PYTHONUTF8=1 python3 /tmp/sftp_sync.py` to deploy to the live server (see sibling plugin's deployment pattern)

**Never ship a version without a matching changelog entry. The changelog step is not optional.**

**Also keep `docs/business_rules_and_logic.md` current** — whenever a business rule changes, a gate is added, or a default flips, update that doc and bump its "Last updated" line. It is the authoritative plain-English record of the plugin's decision logic.

**Also keep the header menu in sync** — the WP Appearance menu is generated from `Shuffles_SSJ_Nav_Sync::definitions()`, which mirrors the `[sssj_menu]` items in `Shuffles_SSJ_Shortcodes::menu_items()`. When you add/rename/remove a public nav item or its page mapping, update BOTH so the shortcode nav and the Appearance menu stay aligned (the menu re-syncs automatically on version change).

---

## What You Are Building

A WordPress plugin called **`shuffles-social-services-jobs`** — a four-sided work marketplace for the NDIS / aged care / allied health sectors. **The live-site domain is whatever the WordPress install uses; the plugin hardcodes no domain (it follows `home_url()`).** `shuffles.com.au` is only the brand / licence-vendor site, and both are editable in Settings.

### Four entity types

| # | Entity | CPT | Who posts | Default visibility |
|---|---|---|---|---|
| 1 | **Job Ad** | `sssj_job` | Organisations (advertisers) | Public |
| 2 | **Worker Profile** | `sssj_worker` | Individuals (workers / job-seekers) | Opt-in |
| 3 | **Participant Need** | `sssj_need` | NDIS participants or their nominees | Pseudonymous + logged-in-only |
| 4 | **Worker Availability** | merged into `sssj_worker` as `is_available` + availability window | — | — |

### Core principles

1. **NDIS-first, sector-agnostic underneath.** Compliance profile term drives required-credentials logic; swap profile → swap sector.
2. **Privacy is structural, not optional.** Participants are the most vulnerable demographic — their listings are always pseudonymous, contact flows go through internal relay.
3. **WP-native where it pays, custom tables where it doesn't.** CPTs for entities, custom tables for match scores and messages.
4. **Integrate, don't replicate.** PMPro for gating, Fluent Forms for posting, FluentCart for billing, FluentCRM for automation, Fluent Boards for advertiser pipelines, Notion for external sync.
5. **Zero edits to `shuffles-growth`.** Integration rides the four filter hooks shipped in that plugin's v0.3.68.

See [`docs/JOBS-BOARD-PLAN.md`](docs/JOBS-BOARD-PLAN.md) for the complete architecture plan — entity schemas, taxonomy seeds, matching engine design, compliance layer, phased rollout, and integration points.

---

## Environment

- **Site URL:** domain-agnostic — set by the WordPress install (no domain hardcoded). `shuffles.com.au` = brand + licence-vendor store, both editable (Settings → General / Licensing)
- **WordPress version:** assume 6.4+
- **PHP version:** assume 8.1+
- **Plugin slug:** `shuffles-social-services-jobs`
- **Text domain:** `shuffles-social-services-jobs`
- **Class prefix:** `Shuffles_SSJ_` / `SSJ_`
- **CPT prefix:** `sssj_`
- **Taxonomy prefix:** `sssjt_`
- **Settings option:** `shuffles_ssj_settings`
- **DB table prefix:** `{wp_prefix}sssj_`
- **Minimum capability for members:** `read` (subscribers)
- **Advertiser capability:** `sssj_post_job` (custom, granted to paid tiers)
- **Admin capability:** `manage_options`

---

## WordPress Stack — integration surface

- **WordPress 6.4+** with Advanced Custom Fields (ACF) for complex sub-fields
- **Fluent Forms** — posting forms where simple; custom React for privacy-sensitive participant flow
- **Fluent Boards** — optional per-advertiser hiring pipeline (mirrors the Sponsor Prospects pattern in `shuffles-growth`)
- **Paid Memberships Pro (PMPro)** — tier gating (free posts 1 listing, paid tiers unlock unlimited + featured)
- **FluentCart Pro** — per-listing fees, recruiter credit packs
- **FluentCRM** — tag-based onboarding + expiry-reminder automation
- **Google Places API** — location autocomplete (shared key with `shuffles-growth`)
- **`shuffles-growth` (sibling plugin)** — reuses its PMPro Elite check, its Google Maps key, its Notion sync client, and hooks into its AI research engine via four filters

---

## CRITICAL: Privacy and Data Isolation

**Most important architectural requirement. Every decision must enforce it.**

- Every `sssj_worker` row MUST carry the linked `user_id`. Worker visibility flags (`public` / `logged_in` / `verified_only`) MUST be enforced in the query layer, not the template layer.
- Every `sssj_need` row MUST store a pseudonym code (never a real name) and the posting `nominee_user_id`. Location granularity MUST NOT exceed suburb level.
- Participant contact details (email, phone) MUST NEVER appear on a public listing. All first-contact flows go through internal relay (`class-messaging.php`).
- Credential document uploads (police check PDFs, WWCC scans) MUST live in a `wp-content/uploads/sssj-credentials/` dir with `.htaccess` deny-all, and only be fetchable via signed REST URL with capability check.
- Admin verification queue (`verified_at`, `verified_by_admin_id`) MUST be the only path to the public `✓ Verified` badge — never trust user-supplied "verified" claims.
- GDPR / Australian Privacy Principles: `class-privacy.php` exposes `wp_privacy_personal_data_exporter` and `wp_privacy_personal_data_eraser` filters for every entity type.
- Content moderation: `sssj_need` posts default to `pending` status and require admin approval before publish.

---

## Database Schema — summary

### CPT meta (stored in `wp_postmeta`)

See `includes/class-cpt-registrar.php` for the full list. Key structured fields:

- **`sssj_job`** — `organisation_id`, `location_suburb/state/postcode`, `work_mode`, `employment_type`, `hours_per_week_min/max`, `rate_min/max/unit`, `start_date`, `expires_at`, `application_url|email`, `required_credentials[]`, `preferred_qualifications[]`, `is_promoted`, `view_count`, `apply_count`
- **`sssj_worker`** — `user_id`, `is_available`, `available_from/until`, `years_experience`, `services_offered[]`, `preferred_locations[]`, `preferred_work_mode`, `rate_min/max/unit`, `credentials` (JSON), `languages[]`, `gender_identity`, `gender_match_preference`, `verified_at`, `verified_by_admin_id`, `visibility`
- **`sssj_need`** — `participant_ref` (pseudonym), `nominee_user_id`, `location_suburb/state`, `support_categories[]`, `hours_per_week_min/max`, `schedule_pattern`, `start_date`, `ongoing_or_temp`, `funding_type`, `gender_preference`, `language_preference`, `interests_description`, `contact_mode`, `visibility`

### Custom tables

```sql
{prefix}sssj_match_score   -- computed match scores between pairs of entities
{prefix}sssj_application   -- an advertiser's applicants / a worker's applications
{prefix}sssj_message       -- internal P2P messaging relay
{prefix}sssj_credential    -- (optional) credential audit history
```

Full schemas in `docs/JOBS-BOARD-PLAN.md` §6.

### Taxonomies

Seeded on activation from `data/seed-*.json` by `Shuffles_SSJ_Taxonomy_Seeder`. Admins edit freely post-activation.

- `sssjt_category` — NDIS service categories
- `sssjt_role` — granular role titles
- `sssjt_qualification` — Cert III, Cert IV, diploma, degree, etc.
- `sssjt_compliance_profile` — NDIS Worker / NDIS Practitioner / Aged Care Worker / Allied Health / Generic
- `sssjt_mode` — In-person / Remote / Hybrid
- `sssjt_employment_type` — Casual / PT / FT / Contract / Sole trader / Agency
- `sssjt_support_category` — Participant-need types (mirrors NDIS line items)

---

## Access Control Hierarchy

Check in this order before any posting action:

```
1. Does the user have the `sssj_post_job` capability (advertiser)?
   YES → proceed, respecting PMPro tier limits (free = 1 active listing, paid = unlimited).
   NO  ↓

2. Does the user have the `sssj_post_worker` capability (worker)?
   YES → proceed.
   NO  ↓

3. Does the user have the `sssj_post_need` capability (nominee / participant)?
   YES → proceed; new posts go to `pending` for admin moderation.
   NO  → show upgrade / signup prompt.
```

PMPro tier integration: `class-pmpro-gating.php::can_post_job($user_id)` returns boolean based on:
- PMPro level + membership status
- Count of active `sssj_job` posts by this user
- Tier's `max_active_jobs` setting (0 = unlimited)

---

## Shuffles-Growth Integration

Hooks to implement in `includes/class-growth-bridge.php`:

```php
add_filter( 'shuffles_growth_tavily_queries',       [ self::class, 'inject_queries' ],     10, 3 );
add_filter( 'shuffles_growth_extra_search_results', [ self::class, 'inject_results' ],     10, 3 );
add_filter( 'shuffles_growth_ai_prompt_extras',     [ self::class, 'inject_prompt' ],      10, 3 );
add_filter( 'shuffles_growth_experimental_queries', [ self::class, 'inject_experiments' ], 10, 2 );
```

### Label convention

All internal results surfaced to the `shuffles-growth` AI engine MUST prefix `content` with one of:

- `[JOBS BOARD — JOB ADVERTISER]`
- `[JOBS BOARD — WORKER PROFILE]`
- `[JOBS BOARD — PARTICIPANT NEED]`
- `[JOBS BOARD — WORKER AVAILABILITY]`

the AI's prompt (extended via `shuffles_growth_ai_prompt_extras`) instructs it to prefix matching `referrer_type` labels with `Jobs Board — ` so the card renderer can group them distinctly.

### Card-level UI buckets (in sibling plugin)

Planned additions to `shuffles-growth/admin/class-member-dashboard.php → render_card()`:

- **🏥 Participant Needs** (pink/rose) — for `[JOBS BOARD — PARTICIPANT NEED]` results
- **🤝 Worker Availability** (green) — for `[JOBS BOARD — WORKER AVAILABILITY]` results

Existing buckets stay as-is: 💼 Employment Opportunities (amber) for job ads, 🧑‍💼 Potential Candidates (blue) for external job-seekers.

---

## Build System (planned)

`dist/build.ps1` — PowerShell script mirroring the sibling plugin's pattern:
- Reads version from `Version:` header in `shuffles-social-services-jobs.php` via `Select-String`
- Packages: `admin/`, `public/`, `includes/`, `templates/`, `data/`, `blocks/`, `shuffles-social-services-jobs.php`, `CLAUDE.md`
- Outputs to `dist/shuffles-social-services-jobs-{version}.zip` with forward-slash entry paths
- Uses `robocopy /Z /R:5 /W:2` to copy (handles OneDrive file locks)
- Run: `powershell.exe -NonInteractive -File ".\dist\build.ps1"`

---

## Phased Rollout

| Phase | Scope | Time | Status |
|---|---|---|---|
| **0 · Scaffold** | Plugin skeleton, CPTs + taxonomies registered, activation seeder, settings page | 2 days | ✅ Built (v0.1.0) |
| **1 · Advertisers** | Job Ad posting form, public board, single-job page, expiry cron | 5 days | — |
| **2 · Workers** | Worker Profile posting form, directory, profile page, visibility controls, basic compliance fields | 5 days | — |
| **3 · Matching (real-time)** | `Matcher` class with `WP_Query` + scoring; "Matching X" panels | 3 days | — |
| **4 · Participants** | Participant Need CPT + pseudonym + gated visibility + internal messaging | 5 days | — |
| **5 · Compliance workflow** | Credential uploads, admin verification queue, expiry cron, verified badge | 4 days | — |
| **6 · shuffles-growth bridge** | Implement four filter hooks; internal entities appear in member referrals | 3 days | — |
| **7 · Monetisation** | PMPro gating, FluentCart billing, promoted placement | 4 days | — |
| **8 · Matching v2** | `sssj_match_score` table + cron + score-ordered results | 3 days | — |
| **9 · Multi-sector** | Compliance profile presets, per-sector required-fields config | 3 days | — |

MVP = Phases 0–3 (≈ 15 days). Full v1 = Phases 0–7 (≈ 31 days).

---

## Known watch points

- **NDIS Worker Screening API** — not publicly exposed by NDISQSC as of writing. All WSC verification is manual for now.
- **Participant listing moderation** — MUST be pre-publish (admin-approved), not post-publish, to prevent abuse.
- **Credential document retention** — ≤ 7 years per ATO guidance; auto-purge after worker profile deletion + 30 day grace.
- **Content-Type for credential uploads** — validate MIME server-side, not just extension.
- **Rate limiting** on application submission + messaging — prevent spam.
- **`wp_consent_api`** integration required for GDPR compliance in credential-upload flows.

---

## v1.1 structural rules (amended 2026-06-05 — read with `docs/JOBS-BOARD-PLAN.md` §19–§25)

These are non-negotiable, like the privacy block above. Every code session honours them.

### ABN vs TFN segregation (§19)
- `engagement_basis` (`abn` | `tfn`) is **required** on every `sssj_job`. `sssj_need` is **always `abn`** (immutable).
- **Board separation is enforced in the QUERY LAYER, never the template.** Route every board shortcode, REST list endpoint, and the matcher through `Shuffles_SSJ_Query::base_args($basis)`, which injects a mandatory `engagement_basis` `meta_query` clause.
  - `[sssj_tfn_board]` → TFN only, NEVER an ABN listing, NEVER a participant need.
  - `[sssj_abn_board]` → ABN only, NEVER a TFN listing.
  - `[sssj_need_board]` (participant-seeking-workers) → NEVER a TFN position.
  - There is no code path that returns mixed-basis results without an explicit, labelled tab.
- **Recording the ABN is mandatory** for ABN advertisers and for any worker responding to an ABN engagement or a participant need. Normalise to digits, validate length ≥ 11 **+ checksum**, optional ABR lookup. For a **TFN** role the ABN field is hidden and never stored.
- Fire `do_action('shuffles_ssj_abn_recorded', $abn, $entity_type, $entity_id)` so the Reference Check plugin can cross-match later (flag-only, never auto-reject).

### Funding (§20)
- `funding_sources[]` is **multi-value** (one / many / none — "No funding / self-funded" is a real option). Funding is a **soft** match dimension — NEVER a hard filter that can zero out a need (SPF v0.6.320 lesson).

### A11y / CALD (§21)
- Port the SPF layer, re-namespaced `sssj-`. Master-gated by the **"CALD & Access"** settings tab. Browser-side, $0 to run. CSS `filter`/`zoom` go on content blocks, never the root. De-dupe the Maps loader (shared key with `shuffles-growth`).

### SEO (§22)
- `JobPosting` JSON-LD + indexable pages for `sssj_job`. **`sssj_need` pages are ALWAYS `noindex` + gated + excluded from sitemaps** — participant anonymity beats SEO, always. `sssj_worker` pages follow their `visibility` flag and never expose PII.

### Monetisation & licensing (§23)
- Keep three concepts separate: **resell license** (FluentCart key + GitHub updater), **employer advertising subscription** (post volume + featured), **provider application-fee subscription** (gates responding to a participant need / ABN task — checked server-side at `POST /apply`).

### Actors (§24)
- **Incumbent** = `sssj_worker.employment_status = employed-open-to-more`; no new CPT; current employer never notified. **Agency** = advertiser `organisation_type`; operates across both boards.

### Standalone-first + integration (§26)
- **No hard plugin dependencies.** Own data in CPTs + custom tables; works on vanilla WP. Every integration (BuddyBoss, Geo my WP, Google Maps, FluentCart, PMPro, FluentCRM, Fluent Boards, Fluent Forms, ACF, shuffles-growth) is runtime-detected via a central `Shuffles_SSJ_Integrations` registry and has a standalone fallback. Integrations enrich/route; they NEVER hold core data. Plugin header declares NO `Requires Plugins`.

### Design system (§27)
- ALL UI uses the shared tokens + `.sssj-*` component classes in `docs/DESIGN-SYSTEM.md`, scoped under `.sssj`. No inline colour/spacing literals; no hard-coded hex in components. Reskin via CSS variables. Tokens avoid `!important` so the CALD High-contrast / No-colour modes win.
