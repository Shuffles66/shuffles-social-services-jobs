# Functional Features Listing — Shuffles Social Services Jobs and Engagements

**Version:** 1.1 (design) · **Date:** 2026-06-05

Each feature lists **what it does**, the **user/owner benefit**, a **Sales angle** (copy-ready), and **status**.
Status key: ✅ live · 🟡 partial/phase · ⬜ roadmap/design.
(At v1.1 everything is ⬜ design — the status column is the build tracker for the phases ahead.)

---

## A. Four-sided marketplace ⬜
- **Job Ads** posted by employers and agencies (`sssj_job`).
- **Worker Profiles** for candidates and already-employed "incumbents" wanting more work (`sssj_worker`).
- **Participant Needs** — participants (or nominees) advertise that they need a worker (`sssj_need`).
- **Worker Availability** built into the worker profile, so the participant↔worker market works both ways.
- **Benefit:** the real sector market — including participant direct-hire — is covered, not just employer↔seeker.
- **Sales angle:** *The only board that connects participants directly with the workers they choose.*

## B. ABN vs TFN segregated boards ⬜
- **`engagement_basis`** (ABN or TFN) on every job; **participant needs are always ABN.**
- **Separate boards** (`[sssj_tfn_board]`, `[sssj_abn_board]`, `[sssj_need_board]`) enforced in the **query layer** — the TFN board never shows ABN work; the participant board never shows TFN jobs.
- **One-off vs ongoing** flag on every job.
- **Benefit:** workers never apply for the wrong kind of engagement; the legal distinction is structural, not a label.
- **Sales angle:** *Contractor work and employee jobs, never mixed up — exactly as the sector requires.*

## C. ABN recording & verification ⬜
- **Mandatory ABN capture** for ABN advertisers and for workers responding to ABN/participant work.
- **Normalise + checksum validate**, with an optional free **ABR lookup** (legal name, status, GST, charity/DGR).
- **Forward hook** to the Shuffles Reference Check plugin for banned-ABN cross-match (flag-only, admin-reviewed).
- **Benefit:** every contractor engagement is tied to a real, checkable business identity.
- **Sales angle:** *Government-sourced business verification on every contractor listing.*

## D. Participant privacy & anonymity ⬜
- **Pseudonymous** participant listings (private code, never a real name); **suburb-only** location.
- **No public contact details**; first contact via the **internal messaging relay**.
- **Pre-publish moderation** of every participant need; participant pages are **`noindex` + gated**.
- **Benefit:** the most vulnerable users can seek help without exposing themselves.
- **Sales angle:** *Participant safety guaranteed by design — not left to settings.*

## E. Credential verification & pre-matching ⬜
- **NDIS Worker Screening, WWCC / Blue Card, police check, First Aid, certifications, insurance.**
- **Admin-verified "✓ Verified" badge** only — never self-asserted; **private signed-URL** evidence storage; **expiry-warning cron**.
- **Required-vs-optional** driven by the compliance profile (NDIS / Aged Care / Allied Health / Generic).
- **Pre-matching:** the matcher only surfaces workers who already hold a job's required credentials.
- **Benefit:** trust and compliance baked in; no manual chasing of expired checks.
- **Sales angle:** *Every match is already check-ready — verified by a human, not a tick-box.*

## F. Matching & maps ⬜
- **Real-time matching** (category + location radius + availability + compliance-readiness + rate) with "Matching workers / jobs" panels.
- **Google Maps + radius search**; telehealth/remote work surfaces regardless of distance.
- **Score-based matching v2** (custom table + cron) for better ordering.
- **AI matching** via `shuffles-growth` — internal listings appear in members' referral research.
- **Benefit:** fast, relevant, location-aware matching that improves over time.
- **Sales angle:** *Find the right worker near you — and let the AI surface the ones you'd miss.*

## G. Multi-funding ⬜
- **`funding_sources[]`** — NDIS, Aged Care (HCP/CHSP), DVA, iCare/Workers' Comp, Medicare/CDM, Privately funded, **No funding**.
- **One, many, or none** — admin-editable taxonomy; funding is a **soft** match signal, never a hard exclude.
- **Benefit:** serves the whole sector, not just NDIS; future-proof for new funding programs.
- **Sales angle:** *Not locked to NDIS — every funding pathway, or none at all.*

## H. Accessibility & CALD suite ⬜ ($0 to run)
- **Voice search** (Web Speech API) with a voice-language picker.
- **Interface translation** into Arabic (+RTL), Mandarin, Greek, Italian, Indonesian, Punjabi.
- **Display modes** — high-contrast, no-colour, larger-text, Easy-Read; **read-aloud**; always-visible English escape.
- Prefs remembered in `localStorage` + user-meta; master-gated by the **CALD & Access** settings tab.
- **Benefit:** reaches the CALD and disability communities this sector serves, at no extra cost.
- **Sales angle:** *Accessible and multilingual out of the box — at zero running cost.*

## I. Strong SEO ⬜
- **`JobPosting` JSON-LD**, Google-for-Jobs eligible, on every public job; indexable single-job + taxonomy landing pages; canonical + OpenGraph + breadcrumbs; **XML sitemap**.
- **Privacy override:** participant pages `noindex` + excluded from sitemaps; worker pages follow their visibility flag, never expose PII.
- **Benefit:** free organic traffic to jobs while private listings stay private.
- **Sales angle:** *Your jobs show up in Google for Jobs; your participants never do.*

## J. Monetisation & resell licensing ⬜
- **Employer advertising subscription** — post volume + featured/promoted placement (PMPro / FluentCart).
- **Provider application-fee subscription** — gates responding to participant needs / ABN tasks.
- **Resell licence** — FluentCart key + GitHub updater; white-label per sector.
- **Benefit:** three independent revenue lines from one platform.
- **Sales angle:** *Three ways to earn — advertisers, providers, and resellers.*

## K. Posting, applications & messaging ⬜
- **Posting forms** for jobs / workers / participant needs (Fluent Forms where simple; custom flow for the privacy-sensitive participant form).
- **Applications** tracked through stages (new → shortlisted → interview → offer); **members can apply** for jobs.
- **Internal P2P messaging relay** keeps contact details private until both sides choose to share.
- **Benefit:** a complete hiring workflow without exposing anyone prematurely.
- **Sales angle:** *Apply, shortlist and message — all inside a safe, private workflow.*

## L. Admin, settings & integrations ⬜
- **Tabbed, maintainable settings** (General · Boards/ABN-TFN · Taxonomies & Seeds · Compliance · Funding · CALD & Access · SEO · Maps · Matching · Monetisation · Licensing · Integrations · Privacy & Moderation · Diagnostics · Changelog · Project Management).
- **Seeded industry-standard** categories, roles, qualifications and support categories on activation.
- **Integrations:** PMPro, FluentCart, FluentCRM, Fluent Boards, Notion, Google Maps, and the `shuffles-growth` AI engine.
- **Benefit:** everything configurable in one familiar place; plugs into the existing Shuffles stack.
- **Sales angle:** *Configure once, integrate everywhere — no code required.*

## M. Shareable marketing assets — résumé & flyer creator ⬜ (planned, Workstream E)
- **One-click résumé + service flyer for sole traders / workers**, and a **job flyer for employers**, built from the profile/listing the member already has — location + services lead the layout.
- **Built-in "Best Practice" guidance** for the social-services sector (national/AU audience) shown right where members create assets — so every flyer/résumé is clear, safe and sector-appropriate.
- **No design skills needed:** brand-styled HTML/CSS templates → print-to-PDF in the browser, a downloadable graphic, plus copy-paste text. Participants get a **private, relay-safe share link** (never public, never indexed).
- **Benefit:** members promote themselves professionally in minutes; the platform's brand travels with every share.
- **Sales angle:** *Turn any profile into a polished résumé, flyer and shareable post — in one click, on-brand, with sector best-practice built in.*

## N. Member reviews & ratings ⬜ (planned, Workstream H)
- **Star ratings + reviews for contractors and providers**, moderated before they show and gated to people who actually engaged — feeds the matching "trust" signal.
- **Benefit:** trust you can see at a glance; quality rises to the top.
- **Sales angle:** *Real, verified reviews — so participants and employers choose with confidence.*
