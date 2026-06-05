# Shuffles Social Services Jobs and Engagements — Plugin Architecture Plan

> **Version:** 1.1 (design locked, pre-Phase 0) — amended 2026-06-05.
> **Parent plugin:** [`shuffles-growth`](../../shuffles-growth-plugin) — integrates via four filter hooks, zero edits required there.
>
> **v1.1 amendments (this revision):** ABN-vs-TFN engagement split + **segregated boards** (§19), multi-select **funding sources** (§20), the **A11y / CALD** layer ported from Shuffles Provider Finder (§21), **strong SEO** incl. JobPosting / Google-for-Jobs (§22), **resell licensing** + two monetisation streams (§23), and **incumbent / agency** actor refinements (§24). Entity tables in §3, the taxonomy table in §4, and the phasing in §25 have been updated to match. See the **Coverage map** below.

---

## 0. Coverage map (v1.1 — the original brief → where it lives)

| Requirement | Home |
|---|---|
| Employers — one-off **and** ongoing | `sssj_job.engagement_type` (§3a), board filters (§19) |
| Agencies | `sssj_job.organisation_type` (§3a, §24) |
| Incumbents (current workers wanting more matches) | `sssj_worker.employment_status` + `open_to` (§3b, §24) |
| Candidates | `sssj_worker` (§3b) |
| Participants seeking workers | `sssj_need` — always ABN, never TFN (§3c, §19) |
| ABN vs TFN distinction | `engagement_basis` + segregated boards (§19) |
| Recording ABNs (critical) | `advertiser_abn` / worker `abn` + ABR lookup (§19) |
| Members can apply for jobs | `sssj_application` (§7), access control (Access Control Hierarchy) |
| Participant anonymity at all times | pseudonym `participant_ref`, suburb-only, gated contact (§3c, §5, CLAUDE.md privacy block) |
| Google Maps + radius enquiries/displays | location autocomplete + radius matching (§14, §21) |
| TFN section excludes ABNs / participant section excludes TFN | query-layer board segregation (§19) |
| Seeded industry-standard categories/descriptors | `Shuffles_SSJ_Taxonomy_Seeder` + `data/seed-*.json` (§4) |
| Maintainable options grouped under tabs | settings tabs (§23 lists the tab set) |
| A11y as in the other plugins | CALD / accessibility layer (§21) |
| Strong SEO (jobs + system) | JobPosting / Google-for-Jobs, indexable pages, sitemaps (§22) |
| Multi-funding — one / many / none | `funding_sources[]` + `sssjt_funding_source` (§20) |
| Verified WWCC / blue card / police / certifications, pre-matched | compliance layer + matcher readiness dimension (§5, §6) |
| Licensing for resale | FluentCart license + GitHub updater (§23) |
| Monetisation — employer advertising subscription | PMPro / FluentCart gating (§23) |
| Monetisation — provider application-fee subscription | gates "respond to a participant need / ABN task" (§23) |
| Easy-read rules | [`RULES.md`](RULES.md) |
| Marketing + partner/endorsement docs | `EXEC-SUMMARY` / `FEATURES` / `PARTNER-PROSPECTUS` / `ENDORSEMENT-ONEPAGER` / `CONCEPT-PITCH-DECK` |

---

## 1. Design principles

1. **NDIS-first, sector-agnostic underneath.** The domain model is compliance-aware but not hard-coded for disability. A "compliance profile" (NDIS, Aged Care, Allied Health, Generic) determines which credential fields are required.
2. **Four-sided marketplace, not just two.** Traditional jobs boards have Advertisers ↔ Seekers. For NDIS we need a second axis: Participants ↔ Workers (for self-/plan-managed direct hires). Both axes share infrastructure but have different privacy models.
3. **WP-native where it pays, custom tables where it doesn't.** Main entities are CPTs (queryable, admin-editable, permalinkable). Relationships and high-volume state (applications, messages, match scores) go in custom tables.
4. **Separate plugin, hooks into `shuffles-growth`.** Zero edits to the sibling plugin. This one reuses the four filter hooks shipped in `shuffles-growth` v0.3.68.
5. **Privacy is structural, not optional.** Participants especially — their listings default to pseudonymous + contact-gated.
6. **Data portability.** Every entity exports cleanly (WXR, JSON, CSV) so sites can spin up on other domains with seed data.

---

## 2. Slugs + namespaces

- **Plugin slug:** `shuffles-social-services-jobs`
- **Text domain:** `shuffles-social-services-jobs`
- **Class prefix:** `Shuffles_SSJ_` / `SSJ_`
- **CPT prefix:** `sssj_`
- **Taxonomy prefix:** `sssjt_`
- **Settings option:** `shuffles_ssj_settings`
- **DB table prefix:** `{wp_prefix}sssj_`
- **Version constant:** `SHUFFLES_SSJ_VERSION`
- **Primary constant block:**
  ```
  SHUFFLES_SSJ_VERSION
  SHUFFLES_SSJ_FILE
  SHUFFLES_SSJ_DIR
  SHUFFLES_SSJ_URL
  SHUFFLES_SSJ_SLUG
  ```

---

## 3. Entity model — the four sides

| # | Entity | CPT | Who posts it | Public by default? |
|---|---|---|---|---|
| 1 | **Job Ad** | `sssj_job` | Organisations (advertisers) | Yes |
| 2 | **Worker Profile** | `sssj_worker` | Individuals (workers / job-seekers) | Opt-in |
| 3 | **Participant Need** | `sssj_need` | NDIS participants or their nominees | Pseudonymous + gated |
| 4 | **Worker Availability** | merged into `sssj_worker` as a status + availability window (separate CPT would over-fragment) | — | — |

### 3a. `sssj_job` (Job Ad)

**Post title** = role title. **Content** = free-form description. **Meta:**

| Key | Type | Purpose |
|---|---|---|
| `organisation_id` | int | Linked WP user or member CPT |
| `organisation_type` | enum | employer / agency / sole-trader (agency posts on behalf + later manages a roster — §24) |
| `engagement_basis` | enum | **abn** (contractor / sole-trader / fee-for-service incl. direct participant tasks — worker invoices via an ABN) **or** **tfn** (PAYG employee — wages, tax withheld, no ABN). Drives board segregation — §19 |
| `engagement_type` | enum | **one-off** (single task / engagement) or **ongoing** (continuing role) |
| `advertiser_abn` | string | REQUIRED when `engagement_basis = abn`; normalised to digits + optional ABR lookup (§19) |
| `location_suburb` | string | Suburb-level location |
| `location_state` | string | State (AU two-letter code) |
| `location_postcode` | string | 4-digit postcode |
| `work_mode` | enum | in-person / remote / hybrid |
| `employment_type` | enum | casual / PT / FT / contract / on-call / fee-for-service |
| `hours_per_week_min` / `max` | int | Range |
| `rate_min` / `max` | decimal | Rate range |
| `rate_unit` | enum | hour / day / annum |
| `start_date` | date | Earliest start |
| `expires_at` | date | Auto-transition to `closed` status |
| `application_url` | url | External apply link — OR ↓ |
| `application_email` | email | Receive by email — OR ↓ |
| `application_internal` | bool | Use plugin's internal messaging relay |
| `required_credentials` | array | Structured — see §5 |
| `preferred_qualifications` | array | IDs from `sssjt_qualification` |
| `is_promoted` | bool | Paid featured placement |
| `view_count` / `apply_count` | int | Cached for sorting |

**Taxonomies:** `sssjt_category`, `sssjt_role`, `sssjt_mode`, `sssjt_compliance_profile`.

### 3b. `sssj_worker` (Worker Profile)

**Post title** = display name. **Content** = bio. **Meta:**

| Key | Type | Purpose |
|---|---|---|
| `user_id` | int | Linked WP user — strict 1:1 |
| `is_available` | bool | Currently looking for work |
| `employment_status` | enum | seeking / **employed-open-to-more (incumbent)** / not-looking — §24 |
| `open_to` | array | extra-shifts / ongoing / casual-pool (incumbents wanting more matches — §24) |
| `abn` | string | Worker's ABN — REQUIRED to respond to ABN engagements & participant needs; normalised + optional ABR lookup (§19) |
| `available_from` / `available_until` | date | Availability window |
| `years_experience` | int | — |
| `services_offered` | array | Term IDs from `sssjt_category` |
| `preferred_locations` | array | `[{suburb,state,postcode,radius_km}, …]` |
| `preferred_work_mode` | enum | in-person / remote / hybrid |
| `rate_min` / `max` / `unit` | decimal + enum | — |
| `credentials` | JSON | See §5 |
| `languages` | array | Languages spoken |
| `gender_identity` | string | Optional; used for participant-side matching |
| `gender_match_preference` | enum | willing / same / either |
| `verified_at` / `verified_by_admin_id` | datetime + int | Admin-vetted flag — NEVER user-self-set |
| `visibility` | enum | public / logged_in / verified_only |

### 3c. `sssj_need` (Participant Need)

**Post title** = short description (e.g. "Morning personal care support, Armidale"). **Content** = longer description. **Meta:**

| Key | Type | Purpose |
|---|---|---|
| `participant_ref` | string | Pseudonymous code — NEVER a real name |
| `nominee_user_id` | int | Who posted on behalf, linked to WP user |
| `location_suburb` | string | Suburb only (no full address) |
| `location_state` | string | State |
| `support_categories` | array | Term IDs from `sssjt_support_category` |
| `hours_per_week_min` / `max` | int | — |
| `schedule_pattern` | enum | morning / evening / weekends / flexible / overnight |
| `start_date` | date | When help needed |
| `ongoing_or_temp` | enum | ongoing / temporary |
| `funding_sources` | array | **Multi-select — one, many, or none.** NDIS, Aged Care (HCP/CHSP), DVA, iCare / Workers' Comp, Medicare / CDM, Privately funded, **No funding / self-funded**. Admin-editable `sssjt_funding_source` taxonomy (§20) |
| `funding_management` | enum | self-managed / plan-managed / agency / private (how the chosen funding is administered) |
| `gender_preference` | enum | any / female / male / non-binary |
| `language_preference` | array | — |
| `interests_description` | text | Matching hooks |
| `contact_mode` | enum | internal-only / phone-after-match / email-after-match |
| `visibility` | enum | logged_in / verified_workers_only |

Participants are the most vulnerable demographic here — defaults are conservative. **Every `sssj_need` is implicitly `engagement_basis = abn`** — a participant directly engages a contractor / sole-trader who invoices via an ABN — so the participant board **NEVER surfaces TFN positions** and a responder MUST hold a recorded ABN (§19).

---

## 4. Taxonomies (pre-loaded NDIS-first, editable)

All hierarchical, all seeded on activation via `Shuffles_SSJ_Taxonomy_Seeder`. Admins can edit/add/delete.

| Taxonomy | Purpose | NDIS seed values (examples — full list in seed file) |
|---|---|---|
| `sssjt_category` | Service category | Disability Support Worker, Support Coordination, Plan Management, Psychosocial Recovery Coach, OT, Physiotherapy, Speech Pathology, Allied Health Assistant, Behaviour Support, Complex Care, SIL/SDA, Community Access, Domestic Assistance, Transport, Respite, Early Childhood Intervention |
| `sssjt_role` | More granular role title | Home and Community Care Worker, Complex Care DSW, Lifestyle Support, Overnight Support, Positive Behaviour Support Practitioner |
| `sssjt_qualification` | Formal qualifications | Cert III Individual Support, Cert IV Disability, Cert IV Mental Health, Diploma Community Services, Bachelor Nursing, Bachelor OT |
| `sssjt_compliance_profile` | Drives required-fields | NDIS Worker, NDIS Practitioner, Aged Care Worker, Allied Health, Generic |
| `sssjt_mode` | Work mode | In-person, Remote/Telehealth, Hybrid |
| `sssjt_employment_type` | Engagement type | Casual, Part-time, Full-time, Contract, Sole trader, Agency |
| `sssjt_support_category` | Participant need types (mirrors NDIS line items) | Personal Care, Community Participation, Capacity Building, Core Support |
| `sssjt_funding_source` | Funding a participant can attach to a need (one / many / none) | NDIS, Aged Care (HCP/CHSP), DVA, iCare / Workers' Comp, Medicare / CDM, Privately funded, No funding / self-funded |

### Why taxonomies (not `select` meta fields)?

- Admin UI is native + familiar
- Term hierarchy works for nested categories (e.g. Allied Health → OT → Paediatric OT)
- Queryable via `tax_query` (fast, indexed)
- Users can follow/save categories later

---

## 5. Compliance & credentials — the critical NDIS layer

Every `sssj_worker` has a `credentials` structure (JSON in a single meta key for MVP; migrate to `sssj_credential` custom table when audit history is needed):

```json
{
  "ndis_worker_screening": { "number": "NDISWC…", "expires": "2027-03-04", "status": "cleared", "evidence_url": null },
  "wwcc":                   { "number": "…",       "expires": "2028-01-10", "state": "NSW" },
  "police_check":           { "date": "2025-08-15", "jurisdiction": "AU" },
  "first_aid":              { "type": "HLTAID011", "expires": "2026-06-30" },
  "public_liability":       { "amount": 20000000, "expires": "2026-09-01" },
  "professional_indemnity": { "amount": 5000000,  "expires": "2026-09-01" },
  "vehicle_insurance":      { "comprehensive": true, "expires": "2026-04-01" },
  "driver_licence":         { "class": "C", "state": "NSW", "expires": "2030-01-01" }
}
```

### `Shuffles_SSJ_Compliance` responsibilities

- **Required-vs-optional logic** driven by `sssjt_compliance_profile` (e.g. NDIS Worker profile requires NDIS WSC + WWCC; Aged Care Worker requires different)
- **Expiry warnings** — cron job that flags profiles with credentials expiring in <30 days
- **Admin verification queue** — admins see pending verifications, review uploaded evidence (stored in a private uploads dir), mark `verified_at`
- **Public display** — shows "✓ Verified" badge only after admin sign-off, never just because the user typed something

### Privacy for credential documents

- Uploads go to `wp-content/uploads/sssj-credentials/{hashed-filename}` with `.htaccess` deny-all
- Accessible only via signed URL through a REST endpoint that checks capability
- Auto-delete after worker deletes account + 30 day grace period
- MIME validation server-side (not just extension)

---

## 6. Matching engine

Two layers:

### 6a. Real-time filter-based matching (MVP — cheap)

Standard `WP_Query` + `meta_query` + `tax_query`. When a user views a Job Ad, a "Matching workers" panel fetches workers with overlapping `services_offered`, location within radius, compatible availability. Same in reverse for workers viewing jobs.

### 6b. Score-based matching (v2 — better ordering)

A `sssj_match_score` custom table populated by a weekly cron + on-demand trigger when an entity is created or edited:

```sql
CREATE TABLE {prefix}sssj_match_score (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_type ENUM('job','worker','need') NOT NULL,
  source_id BIGINT UNSIGNED NOT NULL,
  target_type ENUM('job','worker','need') NOT NULL,
  target_id BIGINT UNSIGNED NOT NULL,
  score DECIMAL(4,3) NOT NULL,          -- 0..1
  reason_json TEXT NOT NULL,            -- serialised list of match dimensions
  computed_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY unique_pair (source_type, source_id, target_type, target_id),
  INDEX idx_source (source_type, source_id, score DESC),
  INDEX idx_target (target_type, target_id, score DESC)
) ENGINE=InnoDB;
```

### Scoring dimensions (configurable weights)

| Dimension | Default weight | Notes |
|---|---|---|
| Category overlap | 0.35 | Intersection of `services_offered` vs job category |
| Location overlap | 0.25 | Same suburb = 1.0; within radius = graded; other state = 0 |
| Availability fit | 0.15 | Hours / schedule compatibility |
| Compliance readiness | 0.15 | Worker has all required credentials for this job |
| Rate compatibility | 0.10 | Overlapping bands |

### 6c. AI-assisted matching (v3 — via `shuffles-growth`)

Use the existing `shuffles_growth_extra_search_results` filter to inject jobs-board entities into the AI's scoring run. Label-prefix each payload so the AI's `referrer_type` classification is deterministic:

```php
[
  'title'   => 'Job Ad — Senior Support Worker · Armidale',
  'url'     => 'https://your-site.example/jobs/1234',
  'content' => "[JOBS BOARD — JOB ADVERTISER] {...details...}",
  'score'   => null   // let the AI score
]
```

This gives member dashboards a unified "referral matches + internal job signals" view without building a separate matching UI.

---

## 7. Supporting DB tables

### `sssj_application` — an advertiser's applicants / a worker's applications

```sql
CREATE TABLE {prefix}sssj_application (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  job_id BIGINT UNSIGNED NOT NULL,          -- FK sssj_job.ID
  worker_id BIGINT UNSIGNED NOT NULL,       -- FK sssj_worker.ID
  applicant_user_id BIGINT UNSIGNED NOT NULL,
  cover_message TEXT,
  status ENUM('new','viewed','shortlisted','interview','offer','rejected','withdrawn') DEFAULT 'new',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY unique_app (job_id, applicant_user_id),
  INDEX idx_job (job_id),
  INDEX idx_applicant (applicant_user_id)
) ENGINE=InnoDB;
```

### `sssj_message` — internal P2P messaging relay

```sql
CREATE TABLE {prefix}sssj_message (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  thread_id BIGINT UNSIGNED NOT NULL,
  from_user_id BIGINT UNSIGNED NOT NULL,
  to_user_id BIGINT UNSIGNED NOT NULL,
  context_entity_type ENUM('job','worker','need'),
  context_entity_id BIGINT UNSIGNED,
  body TEXT NOT NULL,
  read_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_thread (thread_id, created_at),
  INDEX idx_to_user (to_user_id, read_at),
  INDEX idx_from_user (from_user_id)
) ENGINE=InnoDB;
```

### `sssj_credential` (optional, Phase 5+) — credential audit history

```sql
CREATE TABLE {prefix}sssj_credential (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  worker_id BIGINT UNSIGNED NOT NULL,
  kind VARCHAR(64) NOT NULL,            -- 'ndis_worker_screening' etc
  number VARCHAR(120),
  issued_date DATE,
  expires_date DATE,
  evidence_path VARCHAR(255),           -- path in wp-content/uploads/sssj-credentials
  verified_at DATETIME DEFAULT NULL,
  verified_by_admin_id BIGINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX idx_worker (worker_id),
  INDEX idx_expires (expires_date)
) ENGINE=InnoDB;
```

---

## 8. Integration with `shuffles-growth`

Extension hooks shipped in `shuffles-growth` v0.3.68:

```php
class Shuffles_SSJ_Growth_Bridge {
    public static function init(): void {
        add_filter( 'shuffles_growth_tavily_queries',       [ __CLASS__, 'inject_queries' ],     10, 3 );
        add_filter( 'shuffles_growth_extra_search_results', [ __CLASS__, 'inject_results' ],     10, 3 );
        add_filter( 'shuffles_growth_ai_prompt_extras',     [ __CLASS__, 'inject_prompt' ],      10, 3 );
        add_filter( 'shuffles_growth_experimental_queries', [ __CLASS__, 'inject_experiments' ], 10, 2 );
    }

    public static function inject_queries( $queries, $profile, $run_id ) {
        // Append: site:your-site.example/jobs "[location]" [category]
        return $queries;
    }

    public static function inject_results( $results, $profile, $run_id ) {
        // Query sssj_job / sssj_worker / sssj_need matching profile
        // Prefix content with [JOBS BOARD — ...] tags
        // Return merged $results array
        return $results;
    }

    public static function inject_prompt( $extras, $profile, $mode ) {
        $extras[] = 'Results tagged [JOBS BOARD — ...] come from the internal Shuffles Social Services Jobs and Engagements and should be preferred over external sources. Use referrer_type labels starting with "Jobs Board — " so the UI can group them distinctly.';
        return $extras;
    }
}
```

### Card-level UI buckets (to add on `shuffles-growth` side when ready)

- **🏥 Participant Needs** (pink/rose) — `sssj_need` matches
- **🤝 Worker Availability** (green) — `sssj_worker` matches where `is_available = true`

When you ship the plugin, add those two `foreach` branches in `class-member-dashboard.php → render_card()`. The existing buckets stay: 💼 Employment Opportunities (amber) for job ads, 🧑‍💼 Potential Candidates (blue) for external job-seekers.

---

## 9. Integration with the Shuffles WP stack

| Existing tool | How the jobs board uses it |
|---|---|
| **PMPro** | Membership-level gating. Free members can post 1 active listing; paid tiers unlock unlimited + promoted placement. Re-use the existing Elite check pattern. |
| **FluentCart** | Per-job-post fees, featured-listing upsells, recruiter credit packs. Mirror the "Research Run Pack" pattern from `shuffles-growth`. |
| **FluentCRM** | Tag-based automation: new worker signup → send onboarding email sequence; job expiring in 7 days → nudge advertiser. |
| **Fluent Boards** | Optional — each advertiser can sync received applications into a Fluent Board as a hiring pipeline (same pattern as Sponsor Prospects). |
| **Fluent Forms** | Posting forms for each entity type. Easier than building custom forms; integrates with FluentCRM tagging out of the box. |
| **ACF** | Supplement CPT meta for complex fields (credential sub-repeaters, multi-value locations). |
| **Notion** | Sync hook — new worker → row in a "Candidate Pipeline" DB; new job → row in "Open Roles" DB. Re-use the existing Notion client from `shuffles-growth`. |

---

## 10. Front-end surfaces (shortcodes first, blocks later)

MVP ships as shortcodes (fast, no block-editor learning curve):

| Shortcode | Renders |
|---|---|
| `[sssj_job_board]` | Main searchable board with filters |
| `[sssj_job_detail]` | Single-job page (auto on `sssj_job` permalink) |
| `[sssj_worker_directory]` | Worker search (gated by PMPro level + visibility) |
| `[sssj_worker_profile]` | Worker detail page (auto on `sssj_worker` permalink) |
| `[sssj_need_board]` | Participant needs listing (gated) |
| `[sssj_post_job]` | Advertiser posting form |
| `[sssj_post_worker]` | Worker profile form |
| `[sssj_post_need]` | Participant need form (often posted by nominee) |
| `[sssj_my_listings]` | Member dashboard — their posts, applications, matches |

Templates live in `templates/` and are WordPress-overridable (`wp-content/themes/{theme}/shuffles-social-services-jobs/`).

Blocks (Phase 4+): `sssj/latest-jobs`, `sssj/category-grid`, `sssj/featured-workers`.

---

## 11. REST API

```
/wp-json/shuffles-social-services-jobs/v1/

GET    /jobs                    List + filter
GET    /jobs/{id}
POST   /jobs                    (advertiser capability)
PATCH  /jobs/{id}
DELETE /jobs/{id}

GET    /workers
GET    /workers/{id}            (respects visibility)
POST   /workers
PATCH  /workers/{id}

GET    /needs                   (auth required — participant listings)
POST   /needs

GET    /matches?for=job&id=N    (run the matcher)
POST   /apply                   Submit an application
POST   /message                 Send an internal message

POST   /shuffles-growth/search  Called by shuffles-growth's extra-search-results filter
```

All protected endpoints require WP auth (cookie + nonce for logged-in, App Password for REST).

---

## 12. Phased delivery plan

| Phase | Scope | Time estimate | Ship-ready deliverable |
|---|---|---|---|
| **0 · Scaffold** | Plugin skeleton, CPTs + taxonomies registered, activation seeder, settings page | 2 days | Plugin activates on the site; CPTs visible in wp-admin with NDIS seed data |
| **1 · Advertisers** | Job Ad posting form, public board, single-job page, expiry cron, admin columns | 5 days | Organisations can post and members can browse jobs |
| **2 · Workers** | Worker Profile posting form, directory, profile page, visibility controls, basic compliance fields (WSC, WWCC, Police, First Aid) | 5 days | Workers have profiles that advertisers can browse |
| **3 · Matching (real-time)** | `Matcher` class with `WP_Query` + `tax_query` scoring; "Matching X" panels on each detail page | 3 days | Job pages show matching workers; worker pages show matching jobs |
| **4 · Participants** | Participant Need CPT + pseudonym handling, nominee posting, gated visibility, internal messaging relay | 5 days | Full four-sided marketplace active |
| **5 · Compliance workflow** | Credential uploads, admin verification queue, expiry warnings cron, public "✓ Verified" badge | 4 days | Workers can submit evidence; admins verify; compliance shows on public profiles |
| **6 · `shuffles-growth` bridge** | Implement the four filter hooks. Jobs-board entities appear in member referral research as dedicated buckets on cards | 3 days | AI research runs include internal jobs-board matches |
| **7 · Monetisation** | PMPro tier gating, FluentCart integration (per-post fees, featured listings, credit packs), promoted-placement sort | 4 days | Paid tiers live |
| **8 · Matching v2** | `sssj_match_score` table + background cron + on-demand recompute, score-ordered results | 3 days | Better-quality match ordering |
| **9 · Multi-sector** | Compliance profile presets, per-profile required-field config, admin "Duplicate sector" action for white-label deployments | 3 days | Plugin can serve Aged Care or Allied Health without code changes |

Total MVP (Phases 0–3): **~15 days**. Full v1 (0–7): **~31 days**.

---

## 13. Plugin file structure

```
shuffles-social-services-jobs/
├── shuffles-social-services-jobs.php                 # Plugin header + bootstrap
├── CLAUDE.md                          # Working rules for this plugin
├── README.md                          # Public-facing orientation
├── docs/
│   └── JOBS-BOARD-PLAN.md             # This document
├── includes/
│   ├── class-activator.php            # DB tables, flush rewrites on activate
│   ├── class-cpt-registrar.php        # Register sssj_job, sssj_worker, sssj_need
│   ├── class-taxonomy-registrar.php
│   ├── class-taxonomy-seeder.php      # Seed NDIS defaults
│   ├── class-compliance.php           # Credential validation + verification
│   ├── class-matcher.php              # Real-time + scored matching
│   ├── class-rest-api.php
│   ├── class-shortcodes.php
│   ├── class-frontend-forms.php       # Post job/worker/need form handlers
│   ├── class-messaging.php            # Internal relay (email-bridge MVP)
│   ├── class-growth-bridge.php        # shuffles-growth hooks
│   ├── class-pmpro-gating.php
│   ├── class-fluentcart-billing.php
│   ├── class-fluentcrm-automation.php
│   ├── class-notion-sync.php
│   ├── class-privacy.php              # Participant pseudonym logic, GDPR export/erase
│   ├── class-expiry-cron.php
│   └── class-settings.php
├── admin/
│   ├── class-admin.php
│   ├── class-admin-compliance-queue.php
│   ├── views/
│   │   ├── settings.php
│   │   ├── verification-queue.php
│   │   └── ...
│   └── assets/
├── public/
│   ├── class-public.php
│   └── assets/
├── templates/
│   ├── job-board.php
│   ├── job-single.php
│   ├── worker-directory.php
│   ├── worker-single.php
│   ├── need-board.php
│   ├── post-job-form.php
│   ├── post-worker-form.php
│   ├── post-need-form.php
│   └── my-listings.php
├── blocks/                            # Phase 4+
├── data/
│   ├── seed-ndis-categories.json
│   ├── seed-ndis-roles.json
│   ├── seed-ndis-qualifications.json
│   └── seed-aged-care-categories.json # future
├── languages/
└── dist/
    └── build.ps1
```

---

## 14. Key technical decisions (and why)

| Decision | Choice | Rationale |
|---|---|---|
| Storage for entities | CPTs | Admin UI comes free; permalinks work; WP-Cron + scheduled publishing built-in; export/import trivially via WXR. |
| Storage for match scores | Custom table | Thousands of pair-rows grow fast; meta queries would tank. |
| Storage for messages | Custom table | Same reason; native WP comments aren't built for P2P. |
| Storage for credentials | JSON in meta (MVP), custom table later | Simpler at MVP; migrate once history audit is needed. |
| Frontend | Shortcodes first, blocks in Phase 4+ | Shortcodes work everywhere including Elementor; blocks require editor investment. |
| Form builder | Fluent Forms for simple flows, custom React for participant-need privacy flow | FF is fast and integrates with FluentCRM; custom only where we need fine control. |
| Search UI | WP `s=` + `tax_query` + `meta_query` (MVP); FacetWP or SearchWP later | Free + fast enough for 10k listings; specialist plugins when scale demands. |
| Geocoding | Google Places API (shared key with `shuffles-growth`) | Consistent with the wizard; one API key. |
| Messaging | Email-relayed for MVP | Avoid building an inbox; relay messages through server. Real inbox in Phase 6+. |

---

## 15. Compliance + legal must-haves (not technical but critical)

- **NDIS Worker Screening integration** — currently manual; consider later integrating with the NDISQSC API (when/if they publish one)
- **Police check validation** — document retention ≤ 7 years per ATO standard
- **Privacy policy + consent prompts** on all form submissions (use `wp_consent_api` hooks)
- **GDPR / Australian Privacy Principles** — `class-privacy.php` exposes `wp_privacy_personal_data_exporter` and `wp_privacy_personal_data_eraser` filters
- **Mandatory NDIS Code of Conduct acknowledgement** on worker profile creation
- **Age gate** — participants under 18 need adult nominee flag
- **Content moderation workflow** — admin-mediated pre-publish for participant needs (prevent abuse)

---

## 16. Sector portability — how other deployments inherit

Each sector's flavour is captured in:

1. **`sssjt_compliance_profile` term** — e.g. "Aged Care Worker", "Allied Health", "Youth Services"
2. **A JSON config per profile** describing required credentials:
   ```json
   {
     "required": ["police_check", "first_aid"],
     "optional": ["wwcc", "aged_care_certificate"],
     "seed_categories": "data/seed-aged-care-categories.json",
     "terminology": {
       "participant": "client",
       "need": "care-request"
     }
   }
   ```
3. **A `shuffles_ssj_compliance_profiles` filter** that other plugins or MU-plugins can hook to register new profiles

On a fresh install, the activator asks the admin: *"What sector is this deployment for?"* → Disability / Aged Care / Allied Health / Multi-sector / Custom → seeds accordingly.

---

## 17. First deliverable (Phase 0 — plugin scaffold)

The scaffold includes:

- Plugin header, activator, deactivator, uninstaller
- CPTs + taxonomies registered (no forms, no frontend yet)
- Seed data loaded on activation from `data/seed-*.json`
- Settings page with compliance-profile picker
- `CLAUDE.md` mirroring the style of `shuffles-growth`'s (working rules, version-rule, stack, build script)
- `dist/build.ps1` matching the pattern already used for `shuffles-growth`

This alone gives a working install where wp-admin shows the CPTs and categories — tangible, shippable, and ready for Phase 1 forms.

---

## 18. Known watch points / deferred decisions

- **NDIS Worker Screening API** — not publicly exposed by NDISQSC as of writing. All WSC verification is manual. Check quarterly for availability.
- **Rate limiting** — application submission + messaging should be rate-limited from day one to prevent spam.
- **Email-based messaging relay** — MVP only; move to real inbox once volume warrants.
- **Analytics** — advertiser-side "how many views / applications" may warrant a dedicated `sssj_event` table. Defer until Phase 7.
- **Search boost for featured** — featured listings should bubble to the top of search results. Decide: order-by-meta vs promoted-section.
- **Email notifications** — use WordPress `wp_mail` with `Shuffles_SSJ_Mailer` wrapper for testability. Templates in `templates/emails/`.

---

## 19. ABN vs TFN engagement basis + segregated boards (v1.1)

This is the headline structural addition. Every paid engagement in the sector falls into one of two legally distinct buckets, and the system must keep them apart so a worker never mistakes a contracting gig for an employee job (or vice-versa), and so the participant-direct-hire market stays clean.

### 19a. The two bases

| Basis | What it means | Who pays / how | ABN required? |
|---|---|---|---|
| **ABN** | Subcontracting, sole-trader work, or **direct tasks / engagements with sector-funded participants** (NDIS, Aged Care, etc.). The worker runs a business and **invoices** for the work. | Org-to-contractor, or participant-to-contractor | **Yes — recording the ABN is mandatory** |
| **TFN** | A PAYG **employee** position (casual / part-time / full-time / on-call). The employer withholds tax, pays super, provides entitlements. | Employer payroll | **No** — an ABN must not be collected for a TFN role |

`engagement_basis` (`abn` | `tfn`) is a **required** top-level meta field on `sssj_job`. `sssj_need` is **implicitly and immutably `abn`** (a participant engages a contractor directly).

### 19b. Board segregation — enforced in the query layer, never the template

This mirrors the existing structural-privacy rule (§5, CLAUDE.md): the *query* decides what exists, not the CSS. A user must not be able to view-source their way to the wrong basis.

| Surface | Shortcode | Shows | Never shows |
|---|---|---|---|
| **TFN positions board** | `[sssj_tfn_board]` | `engagement_basis = tfn` only | any ABN listing; any participant need |
| **ABN engagements board** | `[sssj_abn_board]` | `engagement_basis = abn` org listings | any TFN listing |
| **Participant-seeking-workers board** | `[sssj_need_board]` | `sssj_need` (always ABN) | **any TFN position** |
| **General board (legacy)** | `[sssj_job_board engagement_basis="abn\|tfn"]` | filtered to one basis; if unset, a clearly-labelled tabbed split — never a silent mix |

Implementation: `Shuffles_SSJ_Query::base_args( $basis )` injects a mandatory `meta_query` clause on `engagement_basis`; all board shortcodes, the REST list endpoints, and the matcher route through it. There is no code path that returns mixed-basis results without an explicit, labelled tab.

### 19c. Recording & verifying the ABN (critical)

Reuse the proven pattern from **Shuffles Reference Check** (`includes/ndis-abr.php`):

- **Normalise** to digits on save (`Shuffles_SSJ_ABN::normalise()` ≡ `src_ndis_abr_norm` — `preg_replace('/\D+/', '', $abn)`).
- **Validate** length ≥ 11 + the **ABN checksum** (the modulus-89 weighting check — stronger than Reference Check's length-only test).
- **Optional free ABR lookup** (`Shuffles_SSJ_ABR::lookup()`): entity name, entity type, ABN status, GST + charity/DGR flags; cached per normalised ABN (mirror `wp_src_ndis_abr`). Stored against the org and against ABN-responder workers.
- **Display** monospace; show the ABR-confirmed legal name beside the trading name.
- **Forward hook** — `do_action( 'shuffles_ssj_abn_recorded', $abn, $entity_type, $entity_id )` so the **Reference Check** plugin can later cross-match a recorded ABN against the NDIS banned-ABN register and flag it (admin-only, flag-only — never auto-reject, mirroring the SPF Compliance Centre rule).

### 19d. Who needs an ABN where

- **ABN job advertiser** → `advertiser_abn` required at post time.
- **Worker responding to an ABN engagement or a participant need** → worker `abn` required before the application/response is accepted (enforced server-side in `class-frontend-forms.php` + the REST `POST /apply`).
- **TFN job advertiser / TFN applicant** → ABN field is **hidden and never stored** (collecting it would be misleading and a privacy-minimisation issue).

---

## 20. Funding sources — multi-select (v1.1)

The original single `funding_type` enum is replaced by multi-value `funding_sources[]` backed by the admin-editable `sssjt_funding_source` taxonomy (§4). A participant attaches **one, many, or none** ("No funding / self-funded" is a real, selectable option).

- **Not confined to NDIS.** Seeded: NDIS · Aged Care (HCP/CHSP) · DVA · iCare / Workers' Comp · Medicare / Chronic-Disease-Management · Privately funded · No funding / self-funded.
- **Informational, not a hard gate.** Funding overlap is a *soft* match dimension (it nudges ranking and is shown to responders) — it never zeroes out a need the way a hard filter would. (Lesson carried from SPF v0.6.320: hard buckets that match nothing return zero results; funding stays soft.)
- **`funding_management`** (self-/plan-/agency-/privately-managed) is a separate field describing *how* the chosen funding is administered — relevant to how the worker gets paid, surfaced to responders.
- **Admin-maintainable** in the settings "Taxonomies / Seeds" tab; resellers in other sectors re-seed it (Aged Care deployment might drop NDIS, add My Aged Care).

---

## 21. Accessibility & CALD layer — ported from Shuffles Provider Finder (v1.1)

The brief requires "the A11y setups as before with other plugins." Port the SPF layer (`assets/js/finder.js` + `finder.css`) wholesale, re-namespaced `sssj-`/`Shuffles_SSJ_`. It runs entirely browser-side — **$0 additional running cost**.

| Capability | Notes |
|---|---|
| **Display modes** | High-contrast, No-colour, Larger-text, Easy-Read (dyslexia-friendly) |
| **Read-aloud** | `SpeechSynthesis` — reads listings, summaries, forms |
| **Voice search** | Web Speech API (`SpeechRecognition`) with a voice-language picker; free, no key |
| **Interface translation** | 7 languages (Arabic + RTL, Mandarin, Greek, Italian, Indonesian, Punjabi) via a `shuffles_ssj_i18n` filter (auto-translated, native review pending) |
| **Persistence** | `localStorage` for everyone + user-meta for logged-in users (`shuffles_ssj_save_prefs`) |
| **Always-visible English escape** | one-tap return to English when UI ≠ English |
| **Master gate** | a **"CALD & Access" settings tab** (`cald_*` options) switches the whole layer on/off |

**Applies to** every public surface: the three boards, single-job / single-worker pages, and all posting forms (job / worker / participant-need).

**Gotchas carried forward from SPF (do not relearn the hard way):**
- CSS `filter` (No-colour) and `zoom`/`filter` (Larger-text) go on **content blocks, never the root** — a filter on an ancestor of a `position:fixed` modal traps it.
- A `position:fixed` modal inside a wp-admin table cell must be re-parented to `<body>` on open.
- De-dupe the Google Maps loader (`shuffles_ssj_external_gmaps_loader_handle()`) — a second async `maps/api/js` breaks Places Autocomplete + Geocoder. The key is **shared with `shuffles-growth`**.
- Inline-SVG buttons: paint paths directly, don't rely on `currentColor` (aggressive themes turn it white-on-white).

---

## 22. SEO — jobs and the system (v1.1)

The brief: "these jobs AND the system should have strong SEO." SPF has none (it's a JS-rendered private directory); the jobs board is the opposite — public job pages are exactly what should rank.

### 22a. Per-job structured data
- **`JobPosting` JSON-LD** on every public `sssj_job`, fully **Google-for-Jobs eligible**: `title`, `description` (HTML), `datePosted`, `validThrough` (= `expires_at`), `employmentType`, `hiringOrganization` (with ABR-confirmed name where present), `jobLocation` (suburb/state/postcode), `baseSalary` (from `rate_min/max/unit`), `directApply`, and `applicantLocationRequirements` + `jobLocationType: TELECOMMUTE` for remote/telehealth roles.
- Server-rendered, indexable single-job pages: `<title>` / meta-description templated from role + suburb + org; `rel=canonical`; OpenGraph + Twitter cards; `BreadcrumbList` JSON-LD.

### 22b. System-level SEO
- **Taxonomy landing pages** (category / role / location) with templated titles + intro copy + internal links — the long-tail surface ("disability support worker jobs in Armidale").
- **XML sitemap** for jobs (own sitemap or hand off to the site's SEO plugin / WP-core sitemap via the standard hooks).
- Clean permalink structure: `/jobs/{role}-{suburb}/`, `/categories/{category}/`.

### 22c. Privacy overrides (non-negotiable — beats SEO)
- **`sssj_need` (participant) pages → `noindex, nofollow`, access-gated, excluded from all sitemaps.** Participant anonymity outranks discoverability, always.
- **`sssj_worker` pages** follow their `visibility` flag: `public` → indexable with `Person` / `ProfilePage` schema **minus any PII** (no email/phone/exact address — contact stays behind the internal relay); `logged_in` / `verified_only` → `noindex` + gated.

---

## 23. Resell licensing + monetisation (v1.1)

Three distinct money concepts — keep them separate in code and in copy.

### 23a. Resell licensing (the plugin is a product)
- Mirror **`shuffles-growth`**'s model: a **FluentCart license key** (activation-limited) + the **GitHub updater** (`includes/github-updater.php` pattern from Reference Check) for delivering updates to licensees.
- A **"Licensing" settings tab**: enter key → shows status (active / expired / activation count). An unlicensed install runs in a limited/branded mode (configurable) but never silently breaks.
- Ties into **§16 sector portability / white-label**: a licensee re-skins + re-seeds for Aged Care / Allied Health / Youth Services via the compliance-profile presets.

### 23b. Revenue stream A — employer job-advertising subscription
- PMPro tier (or FluentCart subscription) gates **posting volume + featured/promoted placement**: free = 1 active listing; paid tiers = unlimited + `is_promoted` bubbling to the top of board + matcher results. Reuse the `shuffles-growth` Elite-check pattern (`class-access-control.php`) in `class-pmpro-gating.php::can_post_job()`.

### 23c. Revenue stream B — provider application-fee subscription
- A recurring subscription gates the **"respond to a participant need / ABN task"** action. A provider must hold an **active application subscription** before `POST /apply` (to a `sssj_need` or ABN engagement) is accepted. Enforced server-side alongside the ABN check (§19d). Free/lapsed providers can browse but not respond — with a clear upgrade prompt.

### 23d. Settings tabs (logically grouped, maintainable — the brief's "settings page" requirement)
Mirror the SPF numbered + colour-dotted tab convention (`docs/INDEX-SYSTEM.md`):
`General` · `Boards & Segregation (ABN/TFN)` · `Taxonomies & Seeds` · `Compliance & Credentials` · `Funding Sources` · `CALD & Access` · `SEO` · `Maps & Location` · `Matching` · `Monetisation (PMPro/FluentCart)` · `Licensing` · `Integrations (FluentCRM / Boards / Notion / shuffles-growth)` · `Privacy & Moderation` · `Diagnostics` · `Changelog` · `Project Management (index)`.

---

## 24. Incumbents & agencies — actor refinements (v1.1)

### 24a. Incumbents = current workers wanting more matches (confirmed with John)
No new CPT. `sssj_worker` gains:
- `employment_status` — seeking / **employed-open-to-more (incumbent)** / not-looking
- `open_to[]` — extra-shifts / ongoing / casual-pool
- the existing availability window (`available_from/until`)

An incumbent is an already-employed worker who opts in to be matched to *more* work (extra shifts, an additional ongoing role, a casual pool). The matcher surfaces them where `employment_status = employed-open-to-more` and availability overlaps; their current employer is never notified by the system.

### 24b. Agencies
`organisation_type` (employer / agency / sole-trader) on the advertiser side. An **agency**:
- posts jobs on either basis (TFN placements **and** brokered ABN engagements),
- is the only org type expected to operate across both boards,
- (Phase ≥ 7) manages a **roster** of linked workers and routes applications into a Fluent Board hiring pipeline (mirrors the Sponsor-Prospects pattern in `shuffles-growth`).

The full agency roster tooling is later-phase; v1.1 only locks the data field + the access-control role so nothing has to be migrated later.

---

## 25. Revised phasing — where the v1.1 work slots in

The original 0–9 phasing (§12) stands; the new work attaches as follows. ABN/TFN and the A11y/SEO/funding foundations are pulled **early** because they shape the data model and every public surface.

| New work | Slots into | Why there |
|---|---|---|
| `engagement_basis`, `engagement_type`, `organisation_type`, `advertiser_abn`, worker `abn`, `employment_status`/`open_to`, `funding_sources[]` + `sssjt_funding_source` | **Phase 0 (Scaffold)** | They're schema — must exist before any form or board is built |
| ABN normalise/validate/ABR lookup helpers | **Phase 1** (advertiser posting) + **Phase 2** (worker) | Where ABNs are first captured |
| Segregated boards + `[sssj_tfn_board]` / `[sssj_abn_board]` + query-layer gating | **Phase 1** | The board IS the advertiser deliverable |
| A11y / CALD layer | **Phase 1** (lands with the first public board) | Every public surface needs it from first sight |
| SEO (JobPosting + indexable pages + sitemap) | **Phase 1** | Public job pages are the thing that ranks |
| Provider application-fee gate | **Phase 4** (participants) + **Phase 7** (billing) | Gate appears when participant responses go live; billing formalises in 7 |
| Resell licensing + employer subscription | **Phase 7 (Monetisation)** | As planned |
| Agency roster tooling | **Phase 9 (Multi-sector)** or a new Phase 10 | Heaviest, least urgent |

MVP (Phases 0–3) now also delivers the **ABN/TFN split, the A11y layer, and SEO on job pages** — the three things the brief stressed most — so the first shippable board is already differentiated.

---

## 26. Standalone-first + graceful integration (v1.1)

**Design intent (John, 2026-06):** the plugin must **work standalone** — own its data in CPTs + custom tables on a vanilla WordPress install — **and** light up extra capability when the Shuffles stack (BuddyBoss, Geo my WP, FluentCart, etc.) is present. Every third-party tie-in is **optional and runtime-detected**; absence degrades gracefully and never fatals.

### 26a. The rule
- **No hard dependencies.** Requirements are WordPress core + PHP only. The plugin activates, registers its CPTs/tables/taxonomies, and runs its boards with zero other plugins installed.
- **Detection is centralised.** A `Shuffles_SSJ_Integrations` capability registry resolves `class_exists` / `function_exists` / `is_plugin_active` once; features ask the registry (`Integrations::has('geomywp')`), never the plugins directly. One place to reason about what's available.
- **Own the source of truth.** Entities and relationships live in *our* tables. Integrations *enrich* or *route*, they never *hold* core data. Uninstalling FluentCart or BuddyBoss can't orphan a listing.

### 26b. Capability matrix — present vs standalone

| Integration | When present (enriched) | Standalone fallback (always works) |
|---|---|---|
| **BuddyBoss** | Link `sssj_worker` to a BB member; read member-type + xProfile; show on profile | Worker CPT linked to a plain `WP_User`; own profile fields |
| **Geo my WP** | Reuse existing member geolocation + map tiles | Own location meta (suburb/state/postcode + lat/lng) + own Haversine radius |
| **Google Maps / Places** | Autocomplete, map display, server geocode | Manual suburb/postcode entry; geocode if a key exists; **list-only** board if no key (radius still works from stored lat/lng) |
| **FluentCart** | Subscriptions, resale licence, credit packs | Admin-set entitlements + a "billing not configured" notice; Stripe can be added later |
| **PMPro** | Tier gating (post limits, featured placement) | Own capability + per-role limits configured in the **Monetisation** settings tab — gating still enforced |
| **FluentCRM** | Tag-based onboarding + expiry automations | `Shuffles_SSJ_Mailer` (`wp_mail`) reminders + notifications |
| **Fluent Boards** | Advertiser hiring pipeline sync | In-plugin application pipeline (`sssj_application` stages) |
| **Fluent Forms** | Optional alternative posting forms | **Own native posting forms** in `templates/` (the default path) |
| **ACF** | Nicer repeater editing in admin | Own metaboxes |
| **`shuffles-growth`** | Internal listings surface in member referral research (four filter hooks) | Bridge simply inactive — the board is fully functional without it |

### 26c. What this buys
- **Resale.** A licensee on a clean WordPress site gets a working product day one; the Shuffles-specific glue is bonus, not baseline (supports §23a licensing + §16 white-label).
- **Resilience.** Deactivating any sibling plugin degrades one feature, never the marketplace.
- **Honest dependencies.** The plugin header declares **no** `Requires Plugins`; the settings **Integrations** tab shows a live "detected / not detected / fallback active" panel so an admin always knows which mode each feature is in.

---

## 27. Look & feel — one design system (v1.1)

All UI — every button, container, card, form control, badge, tab and modal, in admin and on the front-end, across all four boards — uses the shared tokens and component classes defined in **[`DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md)**. Key points:

- Everything is scoped under a single root class **`.sssj`** so it sits on top of BuddyBoss / any theme without leaking or being overridden.
- Colours, spacing, radius, shadow and typography are **CSS custom properties** — a reseller re-skins the whole plugin by overriding `--sssj-blue` (+ a few siblings), no stylesheet fork.
- No component hard-codes a hex value and no inline one-off styles — consistency is enforced mechanically, not by discipline.
- The system is the substrate the CALD accessibility modes (§21) toggle; tokens deliberately avoid `!important` so High-contrast / No-colour modes always win.

See `DESIGN-SYSTEM.md` for the token block, the component contracts, and the UI "definition of done".
