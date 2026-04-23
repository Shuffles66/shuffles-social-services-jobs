# Shuffles Social Services Jobs and Engagements — Documentation Set

This folder holds the design, sales, compliance and partner documentation for the **Shuffles Social Services Jobs and Engagements** plugin. It mirrors the proven `docs/` convention used by Shuffles Provider Finder.

> **Not deployed.** These are internal design + go-to-market artefacts (except where shared with partners). They are maintained alongside each build, not shipped to the live server.

## The documents

| File | Audience | Purpose |
|---|---|---|
| [`JOBS-BOARD-PLAN.md`](JOBS-BOARD-PLAN.md) | Engineers | **Source of truth.** Full architecture — entities, taxonomies, ABN/TFN segregation, funding, A11y, SEO, licensing, matching, phasing. Now at v1.1. |
| [`SPECIFICATIONS.md`](SPECIFICATIONS.md) | Technical evaluators | At-a-glance technical footing distilled from the plan. |
| [`DESIGN-SYSTEM.md`](DESIGN-SYSTEM.md) | Engineers / design | Design tokens + button/card/form component contracts — the one consistent look & feel. |
| [`SECURITY-ACCESS.md`](SECURITY-ACCESS.md) | Compliance / security | Who-can-see-what matrix, participant-anonymity model, credential handling, privacy law. |
| [`RULES.md`](RULES.md) | Everyone (plain language) | Easy-read rules of the system. An image-supported Easy Read version can be built from it. |
| [`EXEC-SUMMARY.md`](EXEC-SUMMARY.md) | Execs / sales | One-to-two-page pitch: problem, solution, who it's for, commercial value. |
| [`FEATURES.md`](FEATURES.md) | Sales / marketing | Feature-by-feature list with copy-ready "Sales angle" lines + build status. |
| [`TIMELINE.md`](TIMELINE.md) | Sales / stakeholders | Append-only milestone history (momentum proof). |
| [`INDEX-SYSTEM.md`](INDEX-SYSTEM.md) | Maintainers | Tab / CPT / feature codes + colour domains; source for the in-plugin PM tab. |
| [`PARTNER-PROSPECTUS.md`](PARTNER-PROSPECTUS.md) | Seeding partners | Pitch to founding partners (providers, plan managers, peak bodies). |
| [`ENDORSEMENT-ONEPAGER.md`](ENDORSEMENT-ONEPAGER.md) | Advocacy / peak bodies | Concept-endorsement one-pager (logo + quote program). |
| [`CONCEPT-PITCH-DECK.md`](CONCEPT-PITCH-DECK.md) | Partners / investors | Slide-by-slide concept deck outline. |

## Reading order

- **New engineer:** `JOBS-BOARD-PLAN.md` → `SPECIFICATIONS.md` → `SECURITY-ACCESS.md` → the plugin `CLAUDE.md`.
- **Sales / partnerships:** `EXEC-SUMMARY.md` → `FEATURES.md` → `PARTNER-PROSPECTUS.md` / `ENDORSEMENT-ONEPAGER.md` → `CONCEPT-PITCH-DECK.md`.
- **Anyone (how it works):** `RULES.md`.

## Maintenance cadence

On each feature ship: bump versions in the plugin, add a changelog entry, and update `FEATURES.md` (status), `SPECIFICATIONS.md`, `SECURITY-ACCESS.md`, `INDEX-SYSTEM.md` (+ the PM tab) and append a `TIMELINE.md` milestone. `EXEC-SUMMARY.md` and the partner docs update at major milestones.

## Status

**v1.1 — design locked, pre-build (Phase 0 next).** This documentation package is complete; plugin code has not yet been written.
