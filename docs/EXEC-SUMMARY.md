# Executive Summary — Shuffles Social Services Jobs and Engagements

**Version:** 1.1 (design) · **Date:** 2026-06-05 · **Platform:** WordPress (PMPro + FluentCart + FluentCRM + Google Maps) · **Sibling:** `shuffles-growth`

## In one line
A **four-sided work marketplace** for the disability, aged-care and social-services sectors — where employers, agencies, workers **and participants themselves** can post and find work — built around the legal split between **ABN (contractor)** and **TFN (employee)** engagements, with participant privacy guaranteed by design.

## The problem it solves
The care-and-support workforce market is broken at both ends. Employers and agencies struggle to find checked, available workers. **Participants who self- or plan-manage their funding have almost nowhere safe to advertise that they need a worker** — and when they do, their identity and contact details are exposed. Generic job boards don't understand the sector's hard rules: an ABN sub-contract task is legally and practically different from a TFN employee shift, and the two must never be confused. Credentials (NDIS Worker Screening, Working With Children, police checks) are assumed, not verified. And accessibility — for a workforce and client base that is disproportionately CALD or living with disability — is an afterthought.

## The solution
Shuffles Social Services Jobs and Engagements is purpose-built for the sector:

- **Four sides, not two.** Job Ads (employers/agencies) ↔ Worker Profiles (candidates **and** already-employed "incumbents" wanting more), **plus** Participant Needs ↔ available Workers. The participant-direct-hire market is treated as first-class.
- **ABN vs TFN, kept strictly apart.** Separate, segregated boards enforced at the database query layer — the TFN section never shows ABN work; the participant section never shows TFN jobs. Recording the ABN is mandatory and verifiable against the Australian Business Register.
- **Participants stay anonymous.** Pseudonymous listings, suburb-only location, no public contact details, contact only through a safe internal relay, and **every participant page hidden from search engines** — privacy beats discoverability, always.
- **Workers are verified, and matches are pre-checked.** Admin-verified "✓ Verified" badges for WWCC/Blue Card, NDIS Worker Screening, police checks, First Aid and insurance. The matcher only surfaces workers who already hold the credentials a job requires.
- **Funding is flexible.** Participants attach one, many, or no funding source (NDIS, Aged Care, DVA, iCare, Medicare, private, or none). Not locked to NDIS.
- **Built for everyone.** The accessibility / CALD layer from Shuffles Provider Finder — voice search, 7-language interface, read-aloud, high-contrast / no-colour / larger-text / Easy-Read — at **zero running cost**.
- **Found by Google.** Strong SEO: `JobPosting` structured data (Google-for-Jobs eligible), indexable job and category pages, sitemaps — while participant listings remain `noindex`.

## Who it's for
- **Disability, aged-care and allied-health providers and agencies** hiring on either basis.
- **Self- and plan-managed NDIS participants** (and their nominees) who want to find their own workers safely.
- **Support workers** — job-seekers and already-employed people open to more work.
- **Operators and peak bodies** who want to run a sector job board — including **resellers** who white-label it for their own sector.

## Commercial value
- **Three revenue streams:** an employer **job-advertising subscription** (volume + featured placement), a provider **application-fee subscription** (to respond to participant needs / ABN tasks), and a **resell licence** for operators who run their own deployment.
- **Sector lock-in by design:** ABN/TFN correctness, credential verification and participant safety are exactly what generic boards can't do — hard to copy, easy to trust.
- **Low cost to operate:** the entire accessibility layer is browser-side ($0); maps share the existing key; AI matching rides the already-built `shuffles-growth` engine.
- **Network effects:** more participants attract more workers, which attracts more agencies — the four-sided model compounds.

## Per-feature pitch (one line each)
> Detailed in `FEATURES.md`.

- **ABN/TFN segregated boards** — the right kind of work, never the wrong one.
- **Participant-direct-hire board** — a safe place for participants to find their own workers.
- **Anonymous by design** — participant identity and contact protected at every step.
- **Verified credentials** — WWCC, NDIS screening, police checks and insurance, admin-verified.
- **Pre-matched** — only workers who already hold the required checks are surfaced.
- **Multi-funding** — NDIS, aged care, DVA, iCare, Medicare, private, or none — pick one, many, or none.
- **Maps & radius search** — find work or workers near you.
- **Accessibility & CALD suite** — voice, 7 languages, read-aloud, Easy-Read — at no running cost.
- **Strong SEO** — Google-for-Jobs-ready job pages; private listings stay private.
- **Three ways to earn** — employer subscriptions, provider application fees, and a resell licence.
- **AI matching via `shuffles-growth`** — internal listings appear in members' referral research.

## Maturity
**Design locked (v1.1), pre-build.** This document set defines the product, its rules, and its go-to-market. The first build phase (scaffold) makes the entities and seeded categories live in wp-admin; the MVP (Phases 0–3) ships the ABN/TFN boards, the accessibility layer and SEO together. See `TIMELINE.md` for milestones and `SPECIFICATIONS.md` for the technical footing.
