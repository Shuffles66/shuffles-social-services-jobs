# Job syndication (#7) — scope, grounded in REAL opportunities

**Researched June 2026.** This document lists only channels that genuinely exist and are open to a
site like ours right now, with their real cost/mechanism. Commercial terms (pricing, feed onboarding)
change and must be confirmed directly with each provider before building against them.

Two directions:
- **Push-out** — get *our* jobs in front of more people on other platforms.
- **Pull-in** — bring *external* jobs in (as drafts/leads) so our boards feel populated and our matcher
  has more signal.

---

## A. Push-out — REAL channels

| Channel | Real? | Cost | Mechanism | Verdict |
|---|---|---|---|---|
| **Google for Jobs** | ✅ Yes | **Free** (organic) | On-page `JobPosting` JSON-LD (we already emit this) | **DO — highest value, already 90% built.** Harden the schema. Not purchase-gated (it's organic SEO). |
| **Jora** (AU, owned by SEEK, ~4M visits/mo) | ✅ Yes | **Free** (up to ~100 ads/mo), paid boost optional | Submit a standard **XML/JSON job feed URL**; Jora crawls it | **DO — prime free feed target.** Reaches the SEEK ecosystem indirectly. |
| **EthicalJobs.com.au** (AU NFP / community / social-impact, ~300k visits/mo, 9k+ orgs) | ✅ Yes | **Paid per ad** | XML feed via ATS partners (Subscribe-HR, JobAdder, dstribute) or a direct feed arrangement | **DO (Phase B) — perfectly sector-aligned; the paid model fits "purchase-gated".** Confirm pricing + feed access with EthicalJobs. |
| **Adzuna** (AU aggregator) | ✅ Yes | Free to be aggregated | Ingests employer/ATS XML feeds; also crawls | **DO — the same XML feed we build for Jora is likely ingestible.** Confirm feed submission. |
| **Indeed** | ⚠️ Changed | **No longer free** | As of **31 Mar 2026** Indeed dropped free organic visibility for single-source XML/API feeds — now requires an ATS integration with **Indeed Apply** (or paid Sponsored) | **SKIP for free.** Only reachable via paid Sponsored or becoming an Indeed-Apply ATS partner — out of scope. |
| **SEEK** (direct) | ⚠️ Restricted | Paid, partner-only | SEEK Job Posting API is partner-gated (access has been litigated, e.g. SEEK v Employment Hero) | **SKIP direct.** Not realistically open to us; we reach SEEK's audience indirectly via **Jora**. |
| **Facebook Jobs** | ❌ Not for us | n/a | Shut down 2023; **relaunched Oct 2025 but US-only**, via Marketplace, no third-party feed/API | **SKIP.** Our real Facebook channel is the **Shuffles FB *groups*** — manual posting (which the self-promo studio, Workstream G, supports), not an API. |
| **LinkedIn** | ❓ Needs check | Free "Limited Listings" historically, increasingly gated | XML feed (Limited Listings) — current free availability unconfirmed | **DEFER** until current terms are verified. |

**Net push-out plan:** build **one standard job XML feed** → serves **Jora + Adzuna** (free) and is the
basis for **EthicalJobs** (paid). **Google for Jobs** is covered by on-page schema (free, no feed). That
single feed + schema is ~80% of the realistic reach.

### Privacy / safety guards (non-negotiable)
- **Never syndicate participant needs** (`sssj_need`) — they never leave the site.
- **Anonymous jobs** (`is_anonymous`) syndicate with `hiringOrganization` = "Private advertiser" and no
  org name/logo — same rule as on-site.
- Only **published, non-expired** jobs; respect each board's content rules.

### Purchase-gating model
- Google for Jobs = **free/organic** (don't gate basic schema; everyone benefits).
- **Partner feeds (Jora/Adzuna/EthicalJobs) = the purchase.** A per-job "Boost / Syndicate" purchase
  (FluentCart product, runtime-detected) sets `_sssj_syndicate = 1`; only flagged jobs enter the partner
  feed. EthicalJobs (paid per ad) maps naturally to a higher tier. Pluggable **Destinations** layer so
  new boards can be added without touching the rest.

---

## B. Pull-in — REAL sources (free APIs)

| Source | Real? | Cost | AU coverage | Use |
|---|---|---|---|---|
| **Adzuna API** (`developer.adzuna.com`) | ✅ Yes | Free key (25 req/min, 250/day) | Yes (`au`) | **Primary.** Search AU social-services jobs (support worker, disability, aged care) → import as drafts/leads. |
| **Jooble API** (`jooble.org/api/about`) | ✅ Yes | Free tier | Yes | Secondary source. |
| **Careerjet API** | ✅ Yes | Free webmaster API (rate-limited) | Yes | Secondary source. |
| **Generic RSS** | ✅ Yes | Free | Varies | Long-tail: any board exposing an RSS feed. |

### ⚠️ Legal reality for pull-in (important)
These APIs license job data for **search/display with attribution and a link back to the source** — they
do **not** grant the right to republish someone else's listings as your own native ads. So pull-in must:
- Ingest as **attributed "from around the web" references** — title, snippet, **link out to apply at the
  source** — held as **drafts for admin review**, never auto-published as native `sssj_job` ads.
- Honour each API's terms (attribution, caching limits, no scraping beyond the API).
- Optionally feed the **matcher as market signal** rather than public listings.

This keeps us compliant and avoids duplicate-content SEO penalties.

---

## C. Proposed architecture (pluggable, standalone-first)

```
Push-out
  Job (purchased "Syndicate") ──► _sssj_syndicate=1
        │
        ├─ Standard job XML feed endpoint  (/sssj/feed/jobs.xml)  ──► Jora, Adzuna
        ├─ Per-destination adapters (Destinations layer)          ──► EthicalJobs (paid), future boards
        └─ On-page JobPosting JSON-LD (already shipped)            ──► Google for Jobs (free)

Pull-in
  Settings: API keys (Adzuna app_id/app_key, Jooble key, …)
        │
        └─ Feed Adapters (Adzuna | Jooble | Careerjet | RSS)
                 │  WP-Cron, filtered to our sectors + AU
                 └─► attributed draft "external jobs" ──► admin review/curate ──► outbound link (apply at source)
```

- **Standalone-first:** FluentCart/PMPro runtime-detected for the purchase gate; works without them (manual
  enable). Feed endpoint is plain WP (rewrite rule), no dependencies.
- **Reuses** the existing `engagement_basis`, `is_anonymous`, expiry and JobPosting plumbing.

---

## D. Phased build plan

**Phase A — free reach (low risk, high value)**
1. Harden the `JobPosting` JSON-LD for full Google for Jobs eligibility (validThrough, directApply,
   employmentType, jobLocation, salary per the third-party rules).
2. Build the **standard job XML feed** endpoint (all eligible published jobs, privacy guards applied).
3. Submit the feed to **Jora** and **Adzuna** (one-time onboarding with each).

**Phase B — purchase-gated partner syndication**
4. "Boost / Syndicate this job" purchase (FluentCart product) → `_sssj_syndicate` flag + a **Destinations**
   settings tab (toggle each board, see status).
5. Gate the partner feed to syndicated jobs; add **EthicalJobs** as a destination (paid tier).

**Phase C — pull-in**
6. Settings for Adzuna (and later Jooble/Careerjet/RSS) API keys.
7. Cron importer → attributed "external jobs from around the web" as admin-reviewed drafts with outbound
   apply links (ToS-compliant). Optional matcher signal.

---

## E. Decisions / commercial confirmations needed from John (real-world, not code)
- **Jora:** complete free feed onboarding (`au.jora.com/cms/get-your-feed-included-on-jora`).
- **EthicalJobs:** confirm current per-ad pricing + how they accept a feed (direct vs via an ATS partner).
- **Adzuna:** register a free API key; confirm their terms cover our pull-in display + push feed.
- **SEEK / LinkedIn / Indeed:** confirmed **not** viable as free channels — proceed only if you want to pay
  for a partner/sponsored arrangement (separate decision).
- **Purchase model:** is syndication a per-job add-on (FluentCart) or bundled into a paid advertiser tier?

---

## Sources (researched June 2026)
- Indeed single-source feed cutoff (31 Mar 2026): hrdive.com, Indeed Employer Help Center, docs.indeed.com
- Google for Jobs `JobPosting`: developers.google.com/search/docs/appearance/structured-data/job-posting
- Jora XML feed (free): au.jora.com/cms/get-your-feed-included-on-jora
- EthicalJobs integrations: ethicaljobs.com.au, dstribute.io, subscribe-hr.com.au, jobadder.com
- Adzuna API: developer.adzuna.com (au, free key, 25/min · 250/day)
- Jooble API: jooble.org/api/about · Careerjet API (public webmaster API)
- SEEK API partner-gated / litigated: smartcompany.com.au (SEEK v Employment Hero)
- Facebook Jobs US-only relaunch Oct 2025: techcrunch.com, shrm.org
