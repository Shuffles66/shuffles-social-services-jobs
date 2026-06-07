# Data Retention and Destruction Policy

**Organisation:** {{Just Tasks}} / **Applies to:** all personal information and records we hold / **Owner:** {{Role — e.g. Privacy Officer}} / **Approved:** {{date}} · **Version:** 1.0 · **Next review:** {{date}}

> **About this document.** This policy sets out how long *Just Tasks* (the Shuffles Social Services Jobs marketplace) keeps personal information and records, and how we securely destroy or de-identify them once they are no longer needed. It is aligned to the **Privacy Act 1988 (Cth)** and **Australian Privacy Principle 11 (APP 11)** — we retain personal information only as long as it is needed for the purpose it was collected (or as the law requires), and then take reasonable steps to destroy it or de-identify it — and to **ATO record-keeping guidance** for financial and tax records. This is a template, **not legal advice**; review and adopt it before publishing, and tailor every {{bracketed}} value to your organisation.

## 1. Purpose and principle

We collect personal information to run a jobs and engagements marketplace for the NDIS, aged-care and social-services sector. We keep that information **only as long as it is needed** for the purpose it was collected, or as long as the law requires us to keep it. Once neither test is met, we **securely destroy or de-identify** the information. We do not keep records "just in case".

## 2. Retention schedule

| Record type | Retention period | Notes |
| --- | --- | --- |
| Member account and profile | While active + {{X months}} after closure | Covers login, contact details, roles/"hats", org membership. |
| Participant requests (pseudonymous) | While active + a short grace period | Held under a pseudonym; no public SEO; purged on closure/expiry. |
| Worker credential evidence | ≤ {{7 years}} (per ATO), then purge | Tickets, checks, NDIS/registration evidence; supports work history. |
| Résumés | Until deleted by the member or account closure + grace | Private files; member controls deletion at any time. |
| Applications and messages | While relevant + {{X months}} | Includes EOIs, application pipeline status and in-platform messages. |
| Reviews | While the subject profile exists | Removed if the reviewed profile is deleted. |
| Complaints and incident records | Per NDIS/legal requirements, typically {{7 years}} | Safeguarding and compliance obligations may extend this. |
| Financial / subscription records | {{5–7 years}} (ATO) | Invoices, payments, listing/subscription fees, tax records. |
| Server logs / backups | Rolling {{X days}} | Logs and backups rotate out automatically. |

## 3. Secure destruction

When a retention period ends, we destroy records securely:

- **Database records** are permanently deleted (not just hidden), including related metadata.
- **Private credential and résumé files** are deleted from storage; these are served only through authenticated handlers, never public URLs.
- Where aggregate **statistics** are still useful (e.g. listing counts, marketplace metrics), we **de-identify** the data so no individual can be re-identified, and destroy the underlying personal information.

## 4. Deletion on account closure

When a member closes their account, we retain the data for a short **grace period** (to allow recovery and to meet any active obligation), then **purge** it per the schedule above. Participant data is held under a **pseudonym** and carries no public profile, so closure removes the pseudonymous record without exposing identity. Records subject to a legal retention period (e.g. financial or complaint records) are retained for that period even after closure.

## 5. Member rights

Members may request **access** to, or **erasure** of, their personal information at any time. We support the WordPress **personal-data exporter and eraser** hooks so requests can be actioned through standard tooling. A request will be honoured unless a **legal hold** or statutory retention requirement (e.g. ATO, NDIS) requires us to keep specific records; in that case we keep only what the law requires and destroy the rest.

## 6. Backups

Deleted data may **persist in backups** until those backups are rotated out of our retention cycle. Backups are encrypted, access-controlled and not used for day-to-day access. When we restore from a backup, we re-apply outstanding deletion and erasure requests so removed data does not reappear.

## 7. Responsibilities and review

The **{{Owner — e.g. Privacy Officer}}** is responsible for this policy, for ensuring retention periods are applied, and for overseeing secure destruction. This policy is reviewed at least **annually** (or sooner if our practices, the law, or NDIS requirements change). Staff and contractors who handle personal information must follow it.

### Plain-English summary (for participants)

*We only keep your information for as long as we need it to run Just Tasks, or for as long as the law makes us. After that, we securely delete it or strip out anything that could identify you. If you're a participant, your details are kept under a pseudonym and aren't shown publicly. You can ask us to see or delete your information at any time — we'll do it unless the law says we have to keep certain records (like tax or complaint files) for a set time. Deleted information may linger in our backups for a little while until those backups are replaced.*

*Maintenance: keep the retention schedule and {{bracketed}} periods current; review whenever Privacy Act/APP, ATO or NDIS requirements change, and at least annually. Last updated: 2026-06-07.*
