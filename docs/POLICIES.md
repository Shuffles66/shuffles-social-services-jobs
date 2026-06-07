# Policy register

Living checklist of the policies the platform should have. Several are NDIS-relevant. Each formal
doc is a **template / operational document** — review and formally adopt before relying on it (not
legal advice). A plain-English, member-facing summary of every policy is **published in-product** via
the `[sssj_policies]` shortcode / Settings → Policies (single source: `includes/class-policies.php`).

**Two layers, kept in sync:**
1. **Formal templates** — the full documents under `docs/` (below). Status “Drafted” = ready for your
   review, bracketed-detail fill-in, and formal adoption before you rely on them.
2. **Published summaries** — the easy-read member-facing versions, live on the site via `[sssj_policies]`.

| Policy | Formal template | Published (in-app) | Notes |
|---|---|---|---|
| **Complaints Management & Resolution** | ✅ `docs/COMPLAINTS-POLICY.md` | ✅ `[sssj_policies]` | NDIS Practice Standards + Complaints Rules 2018; built around Fluent Support. |
| **Privacy** | ✅ `docs/PRIVACY-POLICY.md` | ✅ | Privacy Act 1988 / APPs; pseudonymous participants, relay, credential/résumé storage, export/erasure. |
| **NDIS Code of Conduct** | ✅ `docs/NDIS-CODE-OF-CONDUCT.md` | ✅ | The seven elements; acknowledged at onboarding/profile. |
| **Incident Management & Reportable Incidents** | ✅ `docs/INCIDENT-MANAGEMENT-POLICY.md` | ✅ | Reportable categories + NDIS Commission notification timeframes (confirm current Rules). |
| **Safeguarding / Risk Management** | ✅ `docs/SAFEGUARDING-RISK-POLICY.md` | ✅ | Worker screening, by-design safeguards, risk cycle; zero tolerance. |
| **Terms of Use / Acceptable Use** | ✅ `docs/TERMS-OF-USE.md` | ✅ | Member terms; connector model; prohibited use; fees; liability {{needs legal review}}. |
| **Worker Screening & Verification** | ✅ `docs/WORKER-SCREENING-VERIFICATION-POLICY.md` | ✅ | Matches `class-credentials.php`: admin-only verification, evidence privacy, expiry. |
| **Data Retention & Destruction** | ✅ `docs/DATA-RETENTION-POLICY.md` | ✅ | Privacy Act/APP 11 + ATO; credential ≤7 years; retention table; purge + grace. |
| **Cookie / Consent** | ✅ `docs/COOKIE-CONSENT-POLICY.md` | ✅ | Privacy-preserving defaults; category table; no ad targeting on participant pages. |
| **Anti-Discrimination / Inclusion (CALD, disability, LGBTQIA+)** | ✅ `docs/ANTI-DISCRIMINATION-INCLUSION-POLICY.md` | ✅ | Protected attributes; lawful participant preferences; AHRC escalation. |

> **Status:** all ten drafted as formal templates **and** published as plain-English summaries in-product.
> **Next action (yours):** review each template, fill the `{{bracketed}}` details (owner, dates, contacts,
> governing law, liability), have them formally adopted, and publish the public **Policies** page
> (Settings → Pages → “Policies (safety & privacy)” → Create). Get the Terms of Use liability/disclaimer
> and governing-law clauses reviewed by a lawyer before relying on them.

*Maintenance: keep the formal `docs/` templates and the published summaries (`class-policies.php`) in sync. Last updated: 2026-06-07.*
