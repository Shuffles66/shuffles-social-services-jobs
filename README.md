# Shuffles Social Services Jobs and Engagements

A WordPress plugin for a **four-sided work marketplace** in the social services sector. Designed NDIS-first, but the compliance model and taxonomies are swappable so the same plugin can serve Aged Care, Allied Health, Youth Services, and other sectors.

> **Status:** pre-Phase 0 — design locked, scaffold not yet built. See [`docs/JOBS-BOARD-PLAN.md`](docs/JOBS-BOARD-PLAN.md) for the full architecture plan.

## What it is

A jobs board that matches across four entity types, not two:

| # | Entity | Who posts | CPT |
|---|---|---|---|
| 1 | **Job Ad** | Organisations (advertisers) | `sssj_job` |
| 2 | **Worker Profile** | Individuals seeking work | `sssj_worker` |
| 3 | **Participant Need** | NDIS participants or their nominees | `sssj_need` |
| 4 | **Worker Availability** | merged into Worker Profile as a status | — |

Traditional boards cover axes 1↔2. For NDIS self- and plan-managed participants, axes 3↔4 matter equally — that's where the actual direct-hire market lives. The plugin treats both axes as first-class.

## Principles

1. **NDIS-first, sector-agnostic underneath.** A configurable "compliance profile" (NDIS / Aged Care / Allied Health / Generic) drives which credentials are required.
2. **Privacy is structural.** Participant listings default to pseudonymous with gated contact.
3. **WP-native where it pays.** CPTs + taxonomies for entities, custom tables only for match scores and messaging.
4. **Integrates with `shuffles-growth`.** The existing AI research engine pulls jobs-board entities into member referral matches via four already-shipped filter hooks.
5. **Portable.** Every entity exports cleanly (WXR, JSON, CSV) so new deployments can seed themselves.

## Relationship to `shuffles-growth`

This plugin hooks into the shipped extension points in [`shuffles-growth-plugin`](../shuffles-growth-plugin) without requiring any edits there:

| Filter | Used for |
|---|---|
| `shuffles_growth_tavily_queries` | Append `site:provider.directory/jobs …` style queries |
| `shuffles_growth_extra_search_results` | Inject internal listings directly (bypass external web search) |
| `shuffles_growth_ai_prompt_extras` | Tell the AI how to label internal results |
| `shuffles_growth_experimental_queries` | Opt-in jobs-board-specific queries |

Internal results prefixed with `[JOBS BOARD — JOB ADVERTISER]`, `[JOBS BOARD — WORKER PROFILE]`, `[JOBS BOARD — PARTICIPANT NEED]`, `[JOBS BOARD — WORKER AVAILABILITY]` so the AI's classification is deterministic.

The member-dashboard card renderer already has stubs for two new buckets (🏥 Participant Needs and 🤝 Worker Availability) to be wired when the plugin ships.

## Planned phases

| Phase | Scope | Time |
|---|---|---|
| 0 · Scaffold | Plugin skeleton, CPTs + taxonomies, settings page, seed data | 2 days |
| 1 · Advertisers | Job Ad posting, public board, expiry cron | 5 days |
| 2 · Workers | Worker profiles, directory, basic compliance | 5 days |
| 3 · Matching (real-time) | `WP_Query`-based "Matching X" panels | 3 days |
| 4 · Participants | Participant Need CPT + pseudonym handling + gated visibility + messaging | 5 days |
| 5 · Compliance workflow | Credential uploads + admin verification queue + expiry warnings | 4 days |
| 6 · `shuffles-growth` bridge | Implement the four filter hooks | 3 days |
| 7 · Monetisation | PMPro gating, FluentCart billing | 4 days |
| 8 · Matching v2 | Score-based matching with custom table + cron | 3 days |
| 9 · Multi-sector | Compliance profile presets, white-label support | 3 days |

MVP = Phases 0–3 (≈ 15 days). Full v1 = Phases 0–7 (≈ 31 days).

## Tech stack

- **WordPress 6.4+**, PHP 8.1+
- **Custom Post Types + Taxonomies** — main entities
- **Custom DB tables** — match scores, messages, applications
- **Fluent Forms** — posting forms (where simple); custom React form for participant-need privacy flow
- **PMPro** — membership-level gating
- **FluentCart** — per-listing fees, featured placements, credit packs
- **FluentCRM** — tag-based automation
- **Fluent Boards** — optional advertiser-side hiring pipeline
- **Google Places API** — location autocomplete (shared key with `shuffles-growth`)
- **ACF** — supplementary complex meta

## Project layout (planned)

```
shuffles-social-services-jobs/
├── shuffles-social-services-jobs.php
├── CLAUDE.md
├── README.md
├── docs/
│   └── JOBS-BOARD-PLAN.md
├── includes/
│   ├── class-activator.php
│   ├── class-cpt-registrar.php
│   ├── class-taxonomy-seeder.php
│   ├── class-compliance.php
│   ├── class-matcher.php
│   ├── class-growth-bridge.php
│   ├── class-pmpro-gating.php
│   ├── class-fluentcart-billing.php
│   ├── class-privacy.php
│   └── ...
├── admin/
├── public/
├── templates/
├── data/
│   ├── seed-ndis-categories.json
│   ├── seed-ndis-roles.json
│   └── seed-ndis-qualifications.json
└── dist/
    └── build.ps1
```

## Getting started (once scaffold ships)

1. Upload the plugin zip via wp-admin → Plugins → Add New
2. Activate → the activator registers CPTs + seeds NDIS taxonomies
3. On first admin visit: pick a compliance profile (Disability / Aged Care / Allied Health / Multi-sector)
4. Add permalinks for the CPTs (`Settings → Permalinks → Save`)
5. Place the `[sssj_job_board]` shortcode on a page and you're live

## License

TBD — likely GPLv2+ to match WordPress convention.

## Related

- **[shuffles-growth-plugin](../shuffles-growth-plugin)** — the sibling plugin this one integrates with
