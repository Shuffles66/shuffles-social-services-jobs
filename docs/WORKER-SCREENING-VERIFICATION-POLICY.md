# Worker Screening and Verification Policy

**Organisation:** {{Organisation / Platform name — e.g. Just Tasks}}
**Applies to:** workers, contractors and providers who seek a verified badge or apply for engagements.
**Owner:** {{Role — e.g. Trust & Safety Officer / Operations Manager}}
**Approved:** {{date}}  ·  **Version:** 1.0  ·  **Next review:** {{date — within 12 months}}

> **About this document.** This template describes how the platform actually enforces credential verification — it mirrors the trust model built into `includes/class-credentials.php` (evidence stored privately, status starting **pending**, the green "✓ Verified" badge granted **only** by an admin against an in-date credential). It is aligned to the **NDIS Worker Screening** requirements and good-practice safer-recruitment principles, but it is **not legal advice** — have it reviewed and formally adopted by your organisation before publishing, and tailor the bracketed details.

---

## 1. Purpose
We help participants, families and employers engage workers with confidence. This policy explains the checks we recognise, how a worker's credentials are verified on the platform, how evidence is kept private and secure, and the limits of what verification can guarantee. The goal is safer engagements without creating a false sense of certainty.

## 2. Checks we recognise
A worker, contractor or provider may record any of the following. Each is a separate credential with its own number, issue date and expiry:
- **NDIS Worker Screening Check** — clearance to work in risk-assessed NDIS roles.
- **Working With Children Check / Blue Card** — the relevant state or territory child-related work clearance.
- **National Police Check** — nationally coordinated criminal history check.
- **First Aid / CPR** — current first aid and CPR certification.
- **Relevant qualifications** — Cert III/IV, diploma or degree appropriate to the service offered.
- **Insurances** — professional indemnity and/or public liability cover (for ABN contractors and providers).
Other credentials (e.g. driver's licence) may be recorded as supporting evidence.

## 3. How verification works on the platform
1. **Worker uploads evidence.** The worker records a credential and attaches a supporting document. The credential is created with status **PENDING**.
2. **An admin reviews it.** A staff member with administrator access views the evidence and either **approves** (status → *Verified*) or **rejects** it, optionally with a note.
3. **The badge is granted by an admin only.** The green **"✓ Verified"** badge appears on a worker's profile **only** when an administrator has approved **at least one in-date credential**. Verification is recomputed automatically whenever a credential is approved, deleted or expires.
4. **Self-claims are never trusted.** There is no way for a worker to mark themselves "verified". A user-supplied "verified" claim is ignored; the badge is reachable only through admin approval of real evidence.

## 4. Evidence privacy and security
- **Stored privately.** Evidence documents are stored inside the database, not as files in a public web folder. There is **no public URL** that can be guessed or shared.
- **Served only to the owner or an admin.** A document can be viewed only by the worker who uploaded it or by an administrator, through an authenticated, capability-checked handler. Everyone else is refused.
- **Validated on upload.** Files are checked server-side for **type** (PDF, JPG or PNG only — verified by content, not just the file extension) and **size** (maximum 8 MB). Anything else is rejected before it is stored.

## 5. Expiry and renewal
Most checks expire. The platform records each credential's expiry date and, on a daily schedule:
- **Sends reminders** to the worker ahead of expiry (at the configured lead time, default {{30}} days) and again on the expiry day, asking them to renew and upload the new document.
- **Drops the badge automatically.** When a credential lapses it is marked *expired* and the worker's verified status is recomputed. If no in-date verified credential remains, the **"✓ Verified" badge is removed** without any manual step.

## 6. ABN verification
Where a worker, contractor or provider operates under an ABN, the ABN is **normalised and validated** (digit length and checksum) and, if configured, **checked against the Australian Business Register (ABR)**. The ABR record is **read-only** — it is displayed for verification and cross-matching but is never edited or republished by us. A mismatch is flagged for review, not used to silently reject anyone.

## 7. Member responsibilities
Workers, contractors and providers must:
- Keep their checks **current** and renew them before they lapse.
- Upload **genuine, accurate** evidence that belongs to them.
- Update or remove a credential promptly if it is suspended, cancelled or no longer valid.
- Not represent themselves as verified for work they are not actually cleared to do.

## 8. Misuse
Uploading **forged, altered or false** evidence, or impersonating another person's credentials, is a serious breach. It will result in **removal of the badge and/or the account**, and may be **referred** to the relevant authority (e.g. the NDIS Quality and Safeguards Commission, the issuing body, or the police where a crime may have occurred).

## 9. Retention
Credential evidence is retained in line with our **Data Retention Policy** — generally **no longer than 7 years** (per ATO guidance). When a worker profile is deleted, associated credential evidence is **purged** after a short grace period, and a worker may delete their own credentials at any time.

## 10. Limitations
Verification is a **safeguard, not a guarantee**. A badge confirms that an administrator sighted an in-date document at a point in time; it does not certify a person's ongoing conduct, suitability for a specific task, or that a clearance has not changed since review. Participants, families and employers should **still exercise their own judgement** — meet the worker, check references, and confirm any check directly with the issuing body where it matters.

---

### Plain-English summary (for participants)
*The green "✓ Verified" tick means a real staff member looked at the worker's actual documents — like their NDIS Worker Screening Check or police check — and confirmed they were valid and current. Workers can't tick themselves as verified, the documents are kept private (only the worker and our admins can see them), and the tick disappears by itself when a check runs out. It's a helpful safety step, but it isn't a promise — please still use your own judgement when choosing someone.*

*Maintenance: keep this aligned with the NDIS Worker Screening requirements and with `includes/class-credentials.php` (recognised checks, pending→admin approval, private DB storage, expiry sweep). Update the bracketed details before publishing. Last updated: 2026-06-07.*
