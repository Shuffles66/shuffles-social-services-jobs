# Business Rules & Logic — Shuffles Social Services Jobs & Engagements

The authoritative plain-English record of **how this plugin decides things** — the rules behind
gating, visibility, verification, segregation, privacy and the automated checks. Keep this current
on every change (it is the "why" companion to the code; the "what/where" lives in CLAUDE.md and
`docs/JOBS-BOARD-PLAN.md`).

- **Last updated:** v0.69.0 (2026-06-07)
- **Scope:** business logic only. UI/markup, deploy mechanics and naming conventions live elsewhere.
- **In-app view:** Settings → **Business Logic** tab renders a plain-English version from `Shuffles_SSJ_Business_Rules::sections()`/`invariants()`. Keep that class and this doc in sync.

---

## At a glance — the rules in plain English

*(No technical terms. The numbered sections further down add the code/hooks/settings that enforce each rule, for developers.)*

- **One account, many hats.** A member can be a worker, a candidate, a participant, a sole-trader provider, someone representing a provider, an employee, or a supplier — any combination. Telling us your role(s) unlocks what you can post.
- **A provider isn't always a company.** A provider can be an individual sole trader, not only an organisation — and an "organisation" might instead be a supplier, housing/SDA, real estate, or professional services.
- **Contractor work and employee work stay separate.** Jobs are either contractor (you invoice) or employee (you're paid wages) — and the two never appear mixed on the same board.
- **Businesses prove who they are.** An ABN is required for contractor and organisation listings, and it's checked.
- **Funding never blocks you.** A participant can name one funding source, several, or none — it helps matching but never hides results.
- **Participants pay nothing; providers fund the platform.** Participants employing, or seeking workers or providers, are always free. Providers pay to advertise beyond a free limit, to feature, to respond to work, and to appear in the directory. (Charging is off until a site switches it on.)
- **Badges are earned, not claimed.** A "Verified" or blue-tick badge is only ever granted by a person on the team after checking evidence. Evidence files are private — never on a public link.
- **NDIS registration is checked against the real register.** When a provider (or sole trader) gives their NDIS Registration No, we read their public NDIS Commission listing — status, registration groups, expiry, ABN, head office, website, outlets and phone — show it **read-only** (the member can't edit it), and re-check it every month, quietly alerting our team (never the provider) if anything changes. A revoked/banned status shows in red.
- **Participants are protected above all.** Their requests are anonymous, suburb-level only, visible to logged-in members only, never shown to search engines, approved by an admin before they appear, and all first contact runs through a safe relay — a worker never sees their email or phone.
- **We don't reveal our tech suppliers.** Behind-the-scenes AI/search tools are never named to members or the public.
- **Lead with safety; show numbers when they impress.** The home page leads with the safety guardrails, and headline counters can stay hidden until the totals are big enough to be impressive.

---

## 1. Actors & roles

- A **member is one account that wears many "hats"**, declared via `[sssj_roles]` (`Shuffles_SSJ_Roles::hats()`): **Employer/company**, **NDIS/service provider**, **Supplier**, **Available for contracting (sole trader/ABN)**, **Looking for employee work (PAYG/TFN)**, **Participant**, **Participant representative/nominee** — any combination.
- Hats are **additive** and self-declared. Declaring a hat **grants capabilities** (`role_caps()`) and is **never auto-revoked**, and it **reveals the matching dashboard sections** (`reveals_for()`) so the dashboard only shows what's relevant. Members who haven't picked hats yet fall back to capability detection (nothing disappears). Legacy keys (`worker`) are still recognised.
- Role → capability map (`Shuffles_SSJ_Roles::role_caps()`):
  - participant → `sssj_post_need` + `sssj_post_job`
  - provider / representative → `sssj_post_job` + `sssj_post_org`
  - supplier → `sssj_post_org`
  - worker / candidate → `sssj_post_worker`
- `is_participant($uid)` = has the participant role **or** the `sssj_post_need` cap.
- `is_provider($uid)` = has provider/representative/supplier role **or** owns an `sssj_org` profile.
- **"Provider" ≠ "Organisation".** A provider is one *category* of organisation. An organisation may instead be a supplier, SDA/housing, real estate, professional services, or other (see §6). A provider may be an **individual sole trader** (a worker profile), not only a company.

## 2. Entities & who may post

| Entity | CPT | Posted by (capability) | Public? |
|---|---|---|---|
| Job ad | `sssj_job` | advertisers (`sssj_post_job`) | Public |
| Worker / candidate profile | `sssj_worker` | individuals (`sssj_post_worker`) | Opt-in (visibility flag) |
| Participant need | `sssj_need` | participants/nominees (`sssj_post_need`) | Pseudonymous, logged-in only, **moderated** |
| Organisation | `sssj_org` | providers/suppliers (`sssj_post_org`) | Public + SEO |

Posting precedence is checked in order: `sssj_post_job` → `sssj_post_worker` → `sssj_post_need`.

## 3. ABN vs TFN segregation (hard rule)

- Every job carries `engagement_basis` = **abn** | **tfn**. Participant needs are **always abn** (immutable).
- Separation is enforced in the **query layer** (`Shuffles_SSJ_Query::base_args($basis)`), never the template:
  - `[sssj_tfn_board]` → TFN only; never an ABN listing; never a participant need.
  - `[sssj_abn_board]` → ABN only; never a TFN listing.
  - `[sssj_need_board]` → participant needs; never a TFN position.
- **ABN is mandatory** for ABN advertisers, all organisations, and any worker responding to ABN/participant work. Validated by checksum; for TFN roles the ABN field is hidden and never stored.
- On record, fires `do_action('shuffles_ssj_abn_recorded', $abn, $type, $id)` (lets the Reference Check plugin cross-match — flag only, never auto-reject).

## 4. Funding

- `funding_sources[]` is multi-value (one / many / none — "self-funded" is valid). Funding is a **soft match signal — never a hard filter** that can zero out results.

## 5. Free vs paid (monetisation)

**OFF by default** (`monetisation_enabled = 0`) — a site is never accidentally locked.

- **Free side (the consumer/participant side):** participants employing directly, seeking workers, or seeking providers **post for free** — even when monetisation is on. `Monetisation::can_post_job()` exempts `is_participant() && ! is_provider()`.
- **Paid side (providers):** providers seeking staff, sponsorship, or a directory listing are the charged side.
  - **Posting jobs:** free tier allows `free_active_listings`; an advertiser subscription lifts the cap and enables featured placement.
  - **Responding** to a participant need / ABN task requires the provider subscription (`gate_respond()` on `shuffles_ssj_can_respond`); TFN responses are ungated.
  - **Directory listing:** when monetisation is on, only providers who hold a listing subscription (or admins) appear in the Organisations directory — `Monetisation::can_list_directory()` stamps `org_listed` on save and the directory query gates on `org_listed = 1`.
- Gating provider is pluggable: **PMPro** (level IDs) or **FluentCart** (product IDs), via `gating_provider`. Filters: `shuffles_ssj_has_advertiser_sub` / `_has_provider_sub` / `_can_post_job` / `_can_list_directory`.

## 6. Organisations: categories & sponsorship

- `Shuffles_SSJ_Org::categories()`: support provider, supplier/services to the sector, SDA/housing, real estate, professional services, other. Shown as a badge + a directory filter (`?sssj_orgcat=`).
- **Sponsored placement** is **admin-granted only** (`org_sponsored`, set in the Verification metabox). Sponsored orgs sort to the top of the directory (a named meta clause that never excludes orgs missing the meta) and show a ★ Sponsored badge.

## 7. Verification & trust

- **Blue tick** (`sssj_blue_tick`) and the **green ✓ Verified** credential badge are **admin-granted only** — never self-claimed.
- Credentials (`Shuffles_SSJ_Credentials`): worker uploads evidence → status **Pending** → admin Approves/Rejects. The verified badge requires ≥1 approved, non-expired credential.
- **Evidence files are stored in the database**, not as public files, and served only to the owner/admin via a nonce-signed handler (no public URL).
- Daily `expiry_sweep()` emails reminders before expiry and **auto-drops the badge** when a verified credential lapses.

## 8. NDIS provider registration (live check)

- Applies to **organisations and sole-trader individuals** (worker profiles registered in their own right).
- The member enters their **NDIS Registration No** = the number after `?id=` in their NDIS Commission listing URL (it is the Commission's Drupal node id). Stored as `ndis_register_id`; mirrored into the legacy `ndis_provider_number` for back-compat (one user-facing field).
- On save (and monthly), the plugin reads the **public** Commission listing server-side and stores the live **registration status, approved registration groups, "in force until" date, plus the legal name, ABN, head-office location and website** (`Shuffles_SSJ_NDIS_Register`). Shown as a table on the profile, and previewable on the form via **"Scan now"**.
- **ABN cross-check:** if the ABN on the NDIS register differs from the ABN on file (the org's ABN, or the sole trader's `worker_abn`), a **red warning note** is shown.
- **Read-only register data:** the details read from the register (status, groups, ABN, head office, website, outlets + phone) are **not user-editable** — they come straight from the Commission's listing.
- A **"Revoked" / "Banned" status renders on a red background** (never green); `status_tone()` checks the negative words first so "Registration revoked" doesn't false-match the positive set.
- **Monthly cron** re-reads every registered org **and** sole trader; on any change (status, groups, expiry) it **alerts staff only** (`ndis_alert_email`, default admin) — **never the provider** — and fires `shuffles_ssj_ndis_changed`.
- **Safe-by-design:** a fetch/parse failure **never overwrites** stored values; it flags `ndis_scan_state` and alerts staff (a register-page layout change can't silently read as "no change"). Feature toggle: `ndis_scan_enabled` (Settings → Compliance).
- There is **no official public JSON API** for the Commission register; this reads server-rendered HTML on a gentle cadence with a short per-id cache.
- **Bulk:** for *all* providers, the official **datasets** are the route, not scraping — the NDIS "Active providers" CSV and the data.gov.au compliance-actions CSV (the latter is for the Reference Check banning plugin). The admin **Provider Import** tab is a **proof of concept that is preview-only — it reads/maps/reports a CSV but writes nothing**. (The write path — draft `sssj_org` records, idempotent by ABN, + a `shuffles_ssj_ndis_compliance_row` hook — exists in code behind a hard `PREVIEW_ONLY` switch for when bulk import is actually wanted.)

## 9. ABR (ABN) verification

- When an ABR GUID is configured (Settings → Compliance, `abr_guid`), ABNs are checked against the Australian Business Register on save (`shuffles_ssj_abn_recorded` → `Shuffles_SSJ_ABN::on_abn_recorded`). Entity name + active status are stored and shown as an "ABR Active · <Name>" badge. Without a GUID, only the offline checksum runs.
- **Full register record (`_sssj_abr_details`, v0.68):** the complete ABR response — entity name, **trading / business names** (`BusinessName[]`), ABN status + effective date, entity type + code, ACN, GST registration, and main business location — is captured into one large read-only field (`Shuffles_SSJ_ABN::abr_lookup()` → `format_details()`), stored in `_sssj_abr_details` (+ `_sssj_abr_names`). Shown as a read-only "recorded details" field on the worker & organisation **forms** (owner-only) and as a public block on the **organisation profile** (`abr_details_html()`). It is the Register's own data — **read-only, never edited here** (same principle as the NDIS register data).

## 10. Privacy (structural, non-negotiable)

- Participant needs: **pseudonymous** (`P-XXXXXXX`), suburb-level location only, **logged-in only**, **always `noindex`**, **no public profile page**, and **pre-moderated** (`pending` until an admin approves).
- First contact always runs through the **internal relay** (`Shuffles_SSJ_Messaging`) — a worker never sees a participant's email/phone; the participant is shown only as the pseudonym.
- Worker profiles honour their `visibility` flag (`public` / `logged_in` / `hidden`) **in the query layer**, and never expose PII.
- **Per-field masking (`Shuffles_SSJ_Privacy`, meta `_sssj_mask`).** Beyond the whole-profile visibility flag, an owner can mark *individual* sensitive fields "members only": worker **pay rate**; organisation **phone** and **website** (maskable set defined in `Shuffles_SSJ_Privacy::maskable()`, extensible via the `shuffles_ssj_maskable_fields` filter). A masked field shows a "Log in to view" lock (`lock_html()`) to logged-out guests; `show($post_id,$key)` returns true for signed-in members, the owner (author / `user_id` / `org_user_id`) and admins. The profile itself stays findable — only the field's value is gated. Masking is **additive** and can never reveal register-sourced NDIS data, which is read-only regardless of these toggles.
- Vendor-naming rule: never name the third-party AI/search vendor to members/public ("our AI"). Showing the NDIS Commission register or Google Business data is fine — that is the source's own public data, attributed.

## 11. Matching

- `Shuffles_SSJ_Matcher` gathers candidates sharing a category, then scores on services, location (Haversine distance), availability, engagement basis, rate and trust. Used for the "best matches" panels and candidate alerts.
- **Smart keyword search (`Shuffles_SSJ_Search`, C5).** A board search opts in via the `sssj_smart_search` query var (set in `Shuffles_SSJ_Query`; org directory also flips `suppress_filters=false` since `get_posts()` suppresses the `posts_search` filter). The phrase is expanded into the phrase + its words + any matching synonym group (`synonyms()`, sector-tuned: support worker/carer/DSW, aged care/home care, OT/occupational therapist, …) and OR-matched against title/excerpt/content. OR makes search **broader, never narrower** — it can't zero out a sensible search. Expansion runs through `shuffles_ssj_search_expand_terms` and the table through `shuffles_ssj_search_synonyms`, so an **AI expander can be dropped in later** with no board changes (and never names the vendor to members).

## 12. Alerts (opt-in)

- `Shuffles_SSJ_Alerts` (daily cron): worker job-match alerts, advertiser new-candidate alerts, saved-search digests — all **opt-in**. Master switch `alerts_enabled`. Delivery via wp_mail or a FluentCRM automation (`shuffles_ssj_alert_sent` / `_suppress_default`).

## 13. CRM sync

- Ticking a program value (e.g. NDIS) maps to FluentCRM tags/lists (`Shuffles_SSJ_CRM_Sync`), logged per user. Pick-existing + alert-admin-on-missing.

## 14. Scheduled jobs (cron)

| Hook | Frequency | Does |
|---|---|---|
| `shuffles_ssj_daily` | Daily | Close expired ads, refresh featured placement, credential expiry sweep, licence re-validation |
| `sssj_alerts_daily` | Daily | Send alert digests |
| `sssj_ndis_monthly` | Monthly (30d) | Re-check NDIS register, alert on change |

Last-run/next-run/status are recorded by `Shuffles_SSJ_Cron_Monitor` and shown on the **Cron Job List & Status** tab. A run that starts but never finishes is flagged "Did not complete".

## 15. Front-page hero & counters

- `[sssj_hero]` shows a "🛡️ Safety, built in" strip (single source: `Shuffles_SSJ_Display::safety_guardrails()` / `shuffles_ssj_hero_guardrails` filter; full list in `docs/SAFETY-GUARDRAILS.md`). Toggle `safety="off"`.
- `[sssj_stats]` counters take `min="N"` — a counter stays **hidden until its real total reaches N**, so small early numbers don't show until the marketplace is sizeable.

## 16. UX rule — branded busy state

- Any lookup/query that may take a few seconds shows the **Shuffles spinner** (site logo pulse if set, else a brand-blue ring): profile saves (NDIS/ABR/geocode), website auto-fill, and the NDIS re-check. Driven by `form[data-sssj-busy]` / `window.SSSJSpinner`.

## 17. Organisation teams (multi-user orgs) — D

- `Shuffles_SSJ_Org_Team`. An organisation (`sssj_org`) has **one owner** (its creator, `org_user_id`) plus any number of team members in `_sssj_org_members` (`[ user_id => 'admin'|'member' ]`). The owner is an implicit admin.
- **Org admins** (owner, `admin` members, or site admins) can **add** an existing member, **change a role**, or **remove** a member — via `[sssj_org_team]` and the dashboard **Team** tab (`admin_post_sssj_org_team`, nonce `sssj_org_team`, gated by `Org_Team::is_admin($org_id, $uid)`).
- **Hard limits:** the owner can never be removed or demoted. Adding a member **never creates an account** (`find_user()` only links an existing email/username; missing → "ask them to sign up first"). Removing a member only **unlinks** them — their WP account and site-wide roles are untouched.
- **Request to join (member-initiated, v0.69).** A logged-in member can **"Request to join"** an org from its profile (`[sssj_org_join]` op `request`, optional message → `_sssj_org_join_requests` meta). It stays **pending** until an org admin **Approves** (`approve_request` → `add_member`) or **Declines** (`decline_request`) it in the Team manager — no one joins without admin approval. The member can **cancel** their pending request; org admins are emailed on a new request (`shuffles_ssj_send_join_request_email` filter to suppress). The dashboard **Team** tab shows a pending-count badge.
- Adding a member additively grants `sssj_post_job` so they can act for the org (never revoked here). `orgs_administered_by()` / `orgs_for_user()` resolve a user's orgs (membership matched via the serialized `i:<uid>;` needle).

---

## Key invariants ("never" rules)

1. Never show a verified/blue-tick badge that wasn't admin-granted.
2. Never expose participant identity or contact details; never index a participant need.
3. Never leak a credential evidence file via a public URL.
4. Never return mixed ABN/TFN results outside an explicitly labelled tab.
5. Never let funding zero out results (soft signal only).
6. Never email a provider about an NDIS-registration change (staff only).
7. Never overwrite stored NDIS data on a failed scan — alert staff instead.
8. Never name the AI/search vendor in member-facing copy.
9. Never lock a site by default — monetisation is off until switched on.

*Maintenance: update this file whenever a rule changes, a gate is added, or a default flips. Bump the "Last updated" line.*
