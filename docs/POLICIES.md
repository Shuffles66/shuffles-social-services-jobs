# Policy register (to-do)

Living checklist of the policies the platform should have. Several are NDIS-relevant. Each is a
**template / operational document** — review and formally adopt before publishing (not legal advice).
Keep status current as each is drafted, reviewed and published.

| Policy | Status | Notes / file |
|---|---|---|
| **Complaints Management & Resolution** | ✅ Drafted — needs review, adoption & publish | `docs/COMPLAINTS-POLICY.md`. NDIS Practice Standards + Complaints Rules 2018; built around Fluent Support. Fill bracketed details; publish as a page / Guide. |
| **Privacy Policy** | ⏳ To do | Australian Privacy Principles; what data we collect (participants pseudonymous, workers, orgs), credential storage, relay messaging, exporter/eraser hooks already in `class-privacy.php`. |
| **NDIS Code of Conduct (acknowledgement)** | ⏳ To do | Workers/providers acknowledge the NDIS Code of Conduct; surface at onboarding/profile. |
| **Incident Management & Reportable Incidents** | ⏳ To do | Referenced by the Complaints Policy; reportable-incident process + NDIS Commission notification. |
| **Safeguarding / Risk Management** | ⏳ To do | Worker screening, WWCC, vulnerable-person safeguards (plugin enforces verification; policy doc needed). |
| **Terms of Use / Acceptable Use** | ⏳ To do | Platform terms for members; conduct, prohibited use, listing rules. |
| **Worker Screening & Verification** | ⏳ To do (partly enforced in product) | Admin-only verification, evidence privacy, expiry — documented to match `class-credentials.php`. |
| **Data Retention & Destruction** | ⏳ To do | Credential ≤7 years (ATO), résumé/application retention, purge on deletion + grace. |
| **Cookie / Consent** | ⏳ To do | Plugin defaults to privacy-preserving; document consent handling. |
| **Anti-Discrimination / Inclusion (CALD, disability, LGBTQIA+)** | ⏳ To do | Aligns with the accessibility/CALD layer and best-practice guidance. |

> **Next action:** prioritise Privacy + NDIS Code of Conduct + Incident/Reportable next, then the rest.
> When each is drafted, add it under `docs/` and (where member-facing) publish as a page or Guide.

*Maintenance: update statuses as policies are drafted/adopted. Last updated: 2026-06-07.*
