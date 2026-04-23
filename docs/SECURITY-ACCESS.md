# Security, Access & Privacy — Shuffles Social Services Jobs and Engagements

**Version:** 1.1 (design) · **Date:** 2026-06-05 · *Internal reference — not deployed publicly.*

This document defines who can see and do what, and how the system protects its most sensitive data — participant identities and worker credential documents.

---

## 1. The cardinal rules

1. **Participant anonymity is structural, not optional.** No real name, suburb-only location, no public contact details, internal-relay-only first contact, `noindex` + sitemap-excluded, pre-publish moderation. Enforced in the query/data layer, never the template.
2. **Verification is admin-only.** The "✓ Verified" badge appears only after a human admin reviews evidence. User-supplied "verified" claims are never trusted.
3. **ABN/TFN segregation is a security boundary, not a filter.** A user must not be able to reach the wrong basis by manipulating a request — the `meta_query` clause is mandatory on every list path.
4. **Credential documents are private by default.** Deny-all directory, signed-URL access with capability check, server-side MIME validation, retention limits.
5. **Least data.** Collect only what a role needs. TFN flows never collect an ABN; public worker pages never expose PII.

---

## 2. Roles & capabilities

| Role | Capability | Can do |
|---|---|---|
| Admin | `manage_options` | Everything; moderation queue; credential verification; settings; sees ABN/ABR detail |
| Advertiser (employer) | `sssj_post_job` | Post/manage own job ads (within PMPro tier limits); view own applicants |
| Agency | `sssj_post_job` + `organisation_type=agency` | As advertiser, across both boards; (Phase 9+) manage a worker roster |
| Worker | `sssj_post_worker` | Create/manage own profile; apply to jobs; (with application subscription + ABN) respond to needs/ABN tasks |
| Nominee / participant | `sssj_post_need` | Post a participant need (→ `pending` moderation); manage own need; message matched workers |
| Member (logged-in) | `read` | Browse permitted boards; apply for jobs; bookmark |
| Guest | — | Browse public TFN/ABN job boards only; cannot see participant needs or worker PII |

## 3. Access matrix — what each surface exposes

| Surface | Guest | Member | Worker (verified) | Advertiser | Admin |
|---|---|---|---|---|---|
| TFN board | ✅ public | ✅ | ✅ | ✅ | ✅ |
| ABN board | ✅ public | ✅ | ✅ | ✅ | ✅ |
| Participant-needs board | ❌ | gated* | ✅ (verified-only option) | gated* | ✅ |
| Worker directory | per visibility | per visibility | ✅ | ✅ | ✅ |
| Participant real identity / contact | ❌ | ❌ | ❌ | ❌ | ❌ (relay only)** |
| Credential evidence files | ❌ | ❌ | own only | ❌ | ✅ (review) |
| ABN / ABR detail panel | ❌ | ❌ | own only | own org | ✅ |

\* Participant-need visibility is `logged_in` or `verified_workers_only` per the listing. \*\* Even admins act through the relay for first contact; identity is revealed only when the participant chooses.

## 4. Participant privacy — the full model

- **Pseudonym** (`participant_ref`) generated server-side; the real WP user is the `nominee_user_id`, never displayed.
- **Location** stored at suburb/state only; never a street address.
- **Contact** (email/phone) never written to a public listing; `contact_mode` controls when/if it is shared, post-match, through the relay.
- **Indexing** — `sssj_need` pages emit `noindex,nofollow`, are excluded from sitemaps, and are gated to logged-in users.
- **Moderation** — every need defaults to `pending`; an admin approves before publish (pre-publish, never post-publish).
- **Age gate** — participants under 18 require an adult nominee flag.

## 5. Worker credentials — handling

- **Storage:** `wp-content/uploads/sssj-credentials/{hashed-filename}` with `.htaccess` deny-all.
- **Access:** signed REST URL only, capability-checked (owner or admin); server-side MIME validation (not extension).
- **Verification:** admin marks `verified_at` + `verified_by_admin_id`; only then does the public badge appear.
- **Lifecycle:** expiry-warning cron (<30 days); auto-purge after profile deletion + 30-day grace; retention ≤ 7 years.

## 6. ABN handling & security

- Normalised to digits, checksum-validated; ABR lookups cached. Displayed to the public only as the ABR-confirmed legal/trading name on ABN listings — never used to expose a private individual.
- Forward hook to Reference Check for banned-ABN cross-match is **flag-only, admin-reviewed, never auto-acting, never public** (mirrors the SPF Compliance Centre rule).

## 7. Transport, auth & abuse controls

- REST: WP auth (cookie + nonce for logged-in; App Password for external). AJAX: `check_ajax_referer()` per action.
- **Rate-limit** application submissions and messaging from day one.
- All `$_POST`/`$_GET` sanitised on access, escaped on output. Capability checks server-side, never relying on hidden UI.

## 8. Privacy law & consent

- `wp_privacy_personal_data_exporter` + `wp_privacy_personal_data_eraser` for every entity (`class-privacy.php`).
- `wp_consent_api` prompts on all form submissions, especially credential uploads.
- Aligned to the **Australian Privacy Principles**; participant data minimisation throughout.
- NDIS **Code of Conduct** acknowledgement required at worker-profile creation.

## 9. Monetisation gates (security-relevant)

- Posting volume + featured placement gated server-side by PMPro tier.
- Responding to a participant need / ABN task gated server-side by (a) a recorded valid ABN **and** (b) an active provider application subscription. Never enforced in the UI alone.
