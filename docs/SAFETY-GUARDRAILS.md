# Safety Guardrails — Shuffles Social Services Jobs & Engagements

A single source of truth for the platform's **trust & safety guardrails**, written for reuse in
marketing material, partner/endorsement docs, and member-facing copy. Pairs with the live
`[sssj_hero]` shortcode (which renders the short "Safety, built in" strip on the home page).

> **Member-facing language rule:** never name the third-party AI/search vendor to members or the
> public — say "our AI" / "advanced AI". Naming the NDIS Commission register or Google Business is
> fine: those show the source's own public data, with attribution.

---

## The short spiel (home-page hero strip)

**🛡️ Safety, built in**

- Participant privacy is structural — listings are pseudonymous and contact runs through a safe internal relay.
- The ✓ Verified badge is granted only after an administrator checks the evidence — never self-claimed.
- NDIS provider registration is read live from the NDIS Commission's public register and re-checked monthly.
- Credential documents are stored privately and shown only to you and our team — never on a public page.
- Worker screening, WWCC, police checks and insurances are tracked with expiry reminders.
- Listings live inside the platform, not on public social media, which protects members' privacy and dignity and avoids the unhealthy distraction of well-meaning resharing.

*(This list is the `shuffles_ssj_hero_guardrails` filter's default — editable in one place.)*

---

## The full guardrail set (with sales angles)

### 1. Participant privacy is structural, not optional
Participant ("Need") listings are **pseudonymous by design** (a generated `P-XXXXXXX` reference, suburb-level location only), are **logged-in-only**, are **never indexed by search engines**, and have **no public profile page**. First contact always goes through an **internal relay** — a worker never sees a participant's email or phone.
> **Sales angle:** "The most vulnerable people on the platform are the most protected — anonymity is baked in, not bolted on."

### 2. Verification is admin-granted, never self-claimed
The green **✓ Verified** badge and the account-level **blue tick** are set **only by an administrator** after reviewing evidence. Users cannot mark themselves verified.
> **Sales angle:** "A verified badge here means a human on our team checked it."

### 3. Credential evidence is private and access-controlled
Police checks, WWCC, NDIS Worker Screening, certifications and insurance documents are **stored in the database, not as public files**, and are served only to the owner or an administrator through a signed, capability-checked link. There is **no public URL** to a credential document.
> **Sales angle:** "Sensitive documents never sit in a public folder — they can't be guessed, scraped, or shared by accident."

### 4. Live NDIS registration checks
When a provider gives their **NDIS Registration No**, the platform reads their **public NDIS Commission listing** and shows the live **registration status, approved registration groups, expiry date, legal name, ABN, head office, website, and outlets + phone** — then **re-checks monthly** and alerts our team to any change (status, groups, or expiry). These register-sourced details are **read-only — the member can't edit them**, and a **"Revoked"/"Banned" status is shown on a red background** (never green). If the register's ABN differs from the ABN on file, a **red mismatch warning** is shown.
> **Sales angle:** "Registration isn't taken on trust — it's read from the NDIS Commission's own register, shown read-only, and watched for changes."

### 5. Expiry tracking that drops a stale badge automatically
Credentials carry expiry dates. The platform emails **reminders before expiry**, and an expired credential **automatically removes** the verified badge.
> **Sales angle:** "A check that's lapsed stops counting the day it lapses — no stale 'verified' badges."

### 6. ABN verification against the Australian Business Register
ABNs are validated by checksum and (where configured) **checked against the ABR** for entity name + active status. ABNs are required for all non-employee (non-TFN) listings.
> **Sales angle:** "Businesses are who they say they are — checked against the national register."

### 7. ABN/TFN segregation enforced at the data layer
Employee (TFN) and contractor (ABN) roles are separated in the **query layer, not just the page template** — a TFN board can never accidentally surface an ABN role, and participant requests never appear on a jobs board.
> **Sales angle:** "The right roles in the right place, guaranteed by design — not by a setting someone could get wrong."

### 8. Participant listings are pre-moderated
New participant requests default to **pending** and require **admin approval before they publish**.
> **Sales angle:** "Nothing about a participant goes live until a human approves it."

### 9. Safe-by-design automation
The NDIS auto-check **never overwrites stored data on a failure** and instead **alerts staff** — so a register-page change can't silently read as "still approved". Reads only public data, on a gentle monthly cadence.
> **Sales angle:** "Our automation fails loudly to us, never quietly to you."

### 10. Privacy-respecting accessibility & language layer
The whole CALD/accessibility layer (high-contrast, larger text, read-aloud, voice input, 7-language interface) runs **in the browser at $0**, with preferences stored locally and per-account — **no extra data leaves the page**.
> **Sales angle:** "Accessible to everyone, with nothing sent anywhere to make it work."

### 11. Your data, your control
GDPR / Australian Privacy Principles export & erase hooks cover every entity type; an admin can suspend or remove a profile; the platform owns its data in its own tables and works standalone.
> **Sales angle:** "Built for Australian privacy obligations, with export and erase on tap."

### 12. On-platform by design: privacy, dignity and no "post piracy"
Listings, requests and profiles are designed to **live and be acted on inside the platform**, not to be copied out and splashed across public social media. This is a deliberate **core objective**: it **maintains privacy**, **increases dignity** for the people involved (especially participants and job-seekers), and **reduces the well-meaning copying or resharing of posts by members of the public** ("trolls" who mean well but cause harm). That kind of off-platform resharing can expose people, strip away the safeguards above, and become an unhealthy distraction for everyone. Participant requests are logged-in-only, pseudonymous and never indexed; anonymous advertising and per-field masking keep sensitive details off public view; and the safe internal relay means a connection can happen without anyone's contact details ever being broadcast.
> **Sales angle:** "Your post does its job here, quietly and safely — it isn't turned into a public social-media spectacle. Privacy and dignity by design, not damage control after the fact."

---

## Future: credibility counters (when the population is sizeable)

The **`[sssj_stats]`** shortcode shows **live animated counters** (open jobs, available workers,
organisations, people placed). It now takes a **`min`** attribute that hides any counter below that
number — e.g. `[sssj_stats min="25"]` — so small/unimpressive totals stay hidden until the marketplace
grows. Drop it next to the hero now with a `min`, and the counters simply *appear* once each number is
big enough. No code change needed; just the population growing. (Set `min="0"` to always show.)

> **Sales angle (later):** "Join 1,200+ verified workers and 300+ registered providers."

---

## Where these live in the product

| Guardrail | In the product |
|---|---|
| Participant privacy / relay | `sssj_need` CPT (noindex, logged-in), `class-messaging.php` relay |
| Admin-only verification | `class-verification.php`, `class-credentials.php` verification queue |
| Private credential evidence | DB-stored blobs, nonce-signed serve handler |
| Live NDIS register check | `class-ndis-register.php` + Settings → Compliance |
| Expiry tracking | `class-credentials.php::expiry_sweep()` (daily cron) |
| ABN / ABR | `class-abn.php` |
| ABN/TFN segregation | `class-query.php::base_args()` |
| Hero safety strip + counters | `[sssj_hero]` (`shuffles_ssj_hero_guardrails` filter) |
| On-platform / no post-piracy | Pseudonymous `sssj_need`, anonymous advertising, per-field masking, internal relay, noindex |

*Maintain this file whenever a guardrail is added or changed — it feeds both marketing and the hero strip.*
