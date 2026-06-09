# Roadmap & To‑Do — Shuffles Social Services Jobs & Engagements

Living backlog. Keep current as items ship or priorities change. (Companion to
`docs/business_rules_and_logic.md` and the in‑app Changelog.)

- **Last updated:** 2026-06-07

## Status
- **Live:** v0.55.4 (committed `25bab52`).
- **Deployed, awaiting commit:** v0.56.0 (Provider Import preview), v0.57.0 (header menu sync).
- **Built, not yet deployed:** v0.58.0 (form section cards / completeness / sticky save / toggle / toast),
  v0.59.0 (provider **swipe deck** `[sssj_swipe]` + section left accent borders).
  → **Action:** deploy + commit v0.56.0 → v0.59.0 when authorised.

## Next up (prioritised)
1. **Deploy + commit** the pending versions (v0.56–v0.59).
2. **Swipe deck follow‑ups** (NEW):
   - A **"My shortlist"** surface — a dashboard tab / page listing providers saved via swipe (`_sssj_saved_orgs`), with remove + contact.
   - Confirm swipe **right‑swipe semantics** (currently = save to shortlist; alternatives: express interest / open profile).
   - Optional: a swipe deck for **workers** too (`type="worker"`).
3. **Application process expansion** (NEW workstream — see Workshop A). Pipeline stages, screening
   questions, basis‑aware apply (employment vs contract/quote), notifications, withdraw.
4. **Employer vs ABN‑contractor — one account, no confusion** (NEW workstream — see Workshop B).
   Role‑based onboarding + dashboard reveal; mode‑appropriate fields; legislation‑aware guidance.
5. **Carry the v0.58 section cards to the org + participant forms** (worker form done; add org/need section maps).
6. **"All jobs board" decision** (Cowork brief): retire the old WP Job Manager/Elementor `all-jobs-board`
   page and make `[sssj_job_board]` the public Jobs page; then add board gap items (sticky filter bar,
   active‑filter chips, single‑board TFN/ABN toggle + Sort + aria‑live count, grid/list toggle,
   save/bookmark, loading skeletons, empty‑state CTA, emoji→inline‑SVG). + site‑level menu/dev‑page cleanup.
7. **NDIS data:** per‑provider live lookup is the verification path (works server‑side). The full **named**
   list is only via the **Provider Finder "Download Providers List as CSV"** (manual — ndis.gov.au is
   Cloudflare‑blocked server‑side, so no auto‑fetch). The **compliance‑actions CSV** (named, banned/revoked)
   feeds the Reference Check banning plugin. Bulk import stays a **preview‑only** PoC for now.

## Agreed designs (workshopped 2026-06-07)

### Workstream B — Employer vs ABN contractor, one account (DECIDED)
- **Unified dashboard, reveal by hat.** One account; `[sssj_roles]` becomes a clear **hat picker** in
  plain language: *Employer / company* · *Available for work (employee, TFN)* · *Available for contracting
  (sole trader, ABN)* · *Participant* · *Participant representative/nominee* · *Supplier*. Hats are additive
  and map to caps. The dashboard **reveals only the matching profile sections + fields per hat** — never a
  muddled mega‑form, never separate logins.
- **Mode‑appropriate fields + required fields (legislation‑aware, not legal advice):**
  - *Employer hat* → company profile required (legal/entity name, **ABN required**, employer confirmation);
    guidance notice: PAYG withholding, Fair Work, super.
  - *Contractor hat (ABN)* → **ABN required** + insurances (PL/PI) + genuine‑contractor acknowledgement;
    guidance notice: sham‑contracting caution, GST.
  - *Employee hat (TFN)* → availability + right‑to‑work confirmation; no ABN needed.
- Reusable **guidance notice** components per mode; acknowledgements stored.
- **Phases:** B1 hat picker + dashboard reveal → B2 mode fields + required‑field enforcement → B3 legislation
  guidance + acknowledgements.

### Workstream A — Application process expansion (DECIDED)
- **Full pipeline:** Applied → Shortlisted → Interview/Quote review → Offer → Hired / Declined / Withdrawn,
  with **status history** + **email notifications** (applicant + poster) and applicant **withdraw**.
- **Basis‑aware apply:**
  - *Employee/TFN role* → employment application: availability, right‑to‑work, **per‑listing screening
    questions**, cover note.
  - *ABN contract / participant task* → expression of interest: **quoted rate + unit**, ABN + insurance
    confirmation, availability, message.
- **Phases:** A1 pipeline stages + notifications + withdraw → A2 basis‑aware apply forms → A3 per‑listing
  screening questions.

#### TFN application — workshop decisions (2026-06-07, locked) + build phasing
- **Apply fields:** standard set (availability, start date, right‑to‑work) **+ employer screening questions** per job.
- **Stored résumés:** candidates keep **multiple named résumés**, pick one on apply (private; shared with the employer applied to).
- **Pipeline depth = a per‑job choice the employer makes when creating the job** (Full pipeline w/ stages + notifications **vs** Simple), **changeable later**.
- **Build phasing:**
  - ✅ **Phase 1 (v0.77.0): stored résumés** — `Shuffles_SSJ_Resumes` (table `{prefix}sssj_resume`, DB_VERSION 6), `[sssj_resumes]` + dashboard "My résumés" tab, private DB‑stored files served via `sssj_resume_file` (owner/admin/applied‑employer). Multiple named, default, ≤5, PDF/DOC/DOCX/RTF/ODT ≤8 MB.
  - ✅ **Phase 2 (v0.78.0):** TFN apply form picks a résumé + availability/start‑date/required right‑to‑work + **employer screening questions** (set on the job form) + **per‑job application mode** (full/simple). App table gained `resume_id` + `extra` (JSON) cols (DB_VERSION 7); `Applications::apply($job,$need,$cover,$extra)`; employer sees all extras + résumé link on each applicant; résumé view gated to the applied‑to employer via `shuffles_ssj_resume_can_view`.
  - ⏳ **Phase 3:** full pipeline — Hired/Declined + status history + withdraw + email notifications (surfaced in "full" mode).

**Recommended build order:** B1 (foundation — defines the hats that drive everything) → A1 (pipeline) →
A2/A3 (basis‑aware apply + screening) → B2/B3 (mode fields + legislation). Ships incrementally, one phase
per version.

## New requests (2026-06-07) — to scope
1. **Onboarding wizard** — a guided first‑run that asks "what do you want to do?", sets the right **hats**
   (B1) and routes to the matching profile setup. Builds on B1; lives at first login / a `[sssj_onboard]`.
2. **Employer can seek BOTH ABN and TFN** — already supported (every job has an `engagement_basis`; the
   employer hat grants posting). Make the basis choice explicit/clear in onboarding + the job form.
3. **Contractor (sole trader) presence in the PROVIDERS directory** — a contractor may also peruse
   employment, but most will want to appear in the providers directory. → opt‑in: a contractor can list
   in the Organisations/Providers directory, tagged **Sole trader**.
4. **Provider size/type for filtering** — new field on orgs (and the sole‑trader case) → a directory
   filter. Values TBD (e.g. Sole trader / Small / Medium / Large, or by structure).
5. **AI search** — a natural‑language box ("type what you're looking for") that interprets + best‑matches
   /triages across workers, providers, jobs, needs. Member‑safe wording only ("our AI", never the vendor).
   Likely powered via the **shuffles‑growth AI engine** (the 4 documented filter hooks) — mirrors the SPF
   finder. Powering approach TBD.
6. **Per‑field masking** — members can hide individual fields (e.g. Phone, email) from public display while
   still storing them. A "hide from public" toggle per field; participant contact stays relay‑only always.
7. **Employment job syndication (STANDING DIRECTIVE — all employment-capable projects/plugins):**
   - **Push OUT** — for *employment* (TFN) jobs, an option (later) to **publish to other platforms / job
     boards**, **gated by a purchase** (per‑post or subscription). Architecture: a pluggable
     "destinations" layer (e.g. Indeed/Seek/Google‑for‑Jobs feed, social) behind a paywall.
   - **Pull IN** — the opportunity to **receive jobs from other sources** (external feeds/APIs) into the
     board. Architecture: an import/ingest layer (XML/JSON/CSV feed adapters) → `sssj_job` (likely as
     drafts/flagged source). Applies to ABN/TFN as appropriate; primarily employment.

### Decisions (2026-06-07, locked)
- **Onboarding:** build a guided first‑run **wizard** (sets hats → routes to setup; uses B1).
- **Employer ABN+TFN:** already supported; make the basis choice explicit in onboarding + job form.
- **Sole trader in providers directory:** **opt‑in**, tagged **"Sole trader."**
- **Provider size filter:** capture **BOTH** a **size band** (Sole trader / Small 2–10 / Medium 11–50 /
  Large 50+) **and a structure** (Sole trader / Partnership / Company / Not‑for‑profit / Government) →
  both become directory filters.
- **AI search:** **smart synonym/keyword matcher now** (free, interprets the query against the existing
  matcher), **real AI layer later** (via the shuffles‑growth bridge). Member‑safe wording always.
- **Per‑field masking:** a "hide from public" toggle on **contact + sensitive fields** (phone, email,
  exact address, social links, …); stored but not shown. Participant contact stays relay‑only regardless.

**Build order (locked):** C1 **Onboarding wizard** → C2 **Provider size band + structure** (+ filters) →
C3 **Sole‑trader opt‑in in providers directory** → C4 **Per‑field masking** → C5 **Smart search** (AI‑ready)
— interleaved with application‑process A1→A3 and legislation B2/B3 as you prioritise. (Syndication push/pull
= later, see #7.)

### Status (shipped)
- ✅ **C1** Onboarding wizard — v0.61.0 (`[sssj_onboard]`).
- ✅ **C2** Provider size band + structure (+ directory filters) — v0.61.0.
- ✅ **C3** Sole‑trader opt‑in in providers directory — v0.61.0.
- ✅ **AJAX filtering** (instant in‑place results, "I need support" first) — v0.63.0.
- ✅ **C4** Per‑field masking — v0.64.0 (`Shuffles_SSJ_Privacy`; worker rate, org phone/website; "members only").
- ✅ **C5** Smart synonym search (AI‑ready) — v0.65.0 (`Shuffles_SSJ_Search`; `shuffles_ssj_search_expand_terms` hook for AI later).
- ✅ **D** Multi‑user organisations / teams — v0.66.0 (`Shuffles_SSJ_Org_Team`; `[sssj_org_team]` + dashboard Team tab).
- **Next up:** A1→A3 application pipeline; B2/B3 mode fields + legislation; E/F/G; syndication (#7).

## Workstream D — Multi-user organisations (✅ shipped v0.66.0)
- Several **users belong to one organisation** (a team), not just a single `org_user_id` owner.
- An **organisation admin** can **invite / manage / remove** other members of that org and set their role
  within it (e.g. org‑admin vs member/recruiter).
- Design sketch: an org↔users membership map (e.g. `_sssj_org_members` on the org, or a small table) with a
  per‑member org‑role; the org‑admin gets a "Team" section in the dashboard (invite by email, change role,
  remove); posting/editing the org + its jobs is allowed for any member per their org‑role; ownership
  transfer supported. Keep the existing single‑owner path working (owner = first org‑admin).

## Workstream E — Shareable marketing assets ("Create an asset" wizard) (NEW 2026-06-07)
A wizard (layout + colour choices, reusing the design tokens / Style Studio) that turns a member's
profile/listing into **readable graphic + text assets** to share/print. Build approach: HTML/CSS asset
templates → **print‑to‑PDF via the browser** (no server PDF lib), a downloadable graphic, plus **copy‑paste
text**. Per audience:
- **Sole traders / workers:** a **résumé** (graphic + text) and a **service flyer**. **Always location +
  services at the top.**
- **Employers:** the same for a job listing — recruiting context/wording; flyer + shareable link +
  copy‑paste blurb. Public listings already emit JobPosting/Org SEO.
- **Participants:** a **shareable link + ready copy‑paste "how to respond/apply"** text. ⚠ **Privacy:**
  participant needs are pseudonymous + `noindex` — so this is a **private/relay‑safe link**, NOT public SEO.
  (Public "SEO info" applies to jobs / workers / orgs per their visibility, never to participant needs.)
- **Best-practice guidance (DONE — content ready, surface inline when building E):** `docs/BEST-PRACTICE-ASSETS.md`
  is the single source of best-practice for flyer + résumé creation in the social-services sector (national/AU
  audience): location+services first, Easy-Read plain English, person-centred/strengths-based language, show
  safety credentials, specifics, privacy/dignity (never identify a participant; consented photos only),
  accessible-by-design, one clear CTA. **Already accessible in-app** as the `asset-best-practice` Guides panel
  (`Shuffles_SSJ_Guides::sections()`, via `[sssj_guides]` + Settings → Guides). **When E is built, these rules
  MUST appear inline in the asset wizard** (tips beside each step, sensible defaults, an accessibility +
  no-participant-PII check before download/share). Keep doc ↔ Guides ↔ wizard copy aligned.

## Workstream F — Testimonials (NEW 2026-06-07)
A testimonials section on most user pages (worker / sole trader / organisation). Owner‑curated + optional
submitted testimonials, **moderated** before they show (never auto‑published, mirrors the credential/verify
ethos). Decision pending: open submission (moderated) vs owner‑entered only vs both.

## Workstream G — Site self-promotion: social graphics + posts (NEW 2026-06-07)
Generate **self-promoting graphics + social posts for the platform itself** — **one positive at a time**
(a drip: a single shareable graphic + caption per go, rotating through positives — e.g. a milestone/stat,
a safety guardrail, a success/testimonial, a "providers near you" prompt). Build approach: brand-token
HTML/CSS graphic templates + caption text → copy/download to post manually now; optional auto-post to
social later (ties to the syndication/destinations idea, #7). Admin-driven; pulls from real data
(counts/guardrails/testimonials) so claims stay true.

## Workstream H — Member reviews & ratings (NEW 2026-06-07, John request, to build)
A **review + star-rating system for Contractors (sole traders) and Providers (organisations)** so members
can rate/review who they've worked with.
- **Subject:** a `sssj_worker` (contractor/sole trader) and a `sssj_org` (provider). (Employees/participants
  are NOT publicly rated — keep the vulnerable side unrated; revisit later if needed.)
- **Storage sketch:** a custom table `{prefix}sssj_review` (or a `sssj_review` CPT) — `subject_id`,
  `subject_type`, `reviewer_user_id`, `rating` 1–5, `title`, `body`, `status` (pending/approved/rejected),
  `created`, optional `engagement_id` (link to an application/booking to prove a real interaction).
- **Trust/anti-abuse:** **moderated before showing** (mirrors the credential/verify ethos — never
  auto-published); one review per reviewer per subject (editable); ideally **gated to people who actually
  engaged** (had an application/message/booking) to stop drive-by/fake reviews; reviewer shown by name or
  pseudonym per privacy rules; subject can **post one public response** per review.
- **Display:** aggregate **average + count** as a badge on the directory cards and the profile; full list on
  the profile (most-recent / highest). Feeds the matcher's "trust" dimension later. Emit `Review`/
  `AggregateRating` schema.org **only** for public worker/org pages (never participants).
- **Relation to F (testimonials):** F = owner-curated quotes; H = third-party star reviews. Likely share a
  moderation queue + display component; decide whether to merge or keep separate at build time.

## Workstream I - SDA (Specialist Disability Accommodation) listings (NEW 2026-06-09, John request, to build)
A FIFTH listing type: SDA vacancy / property listings, so SDA providers advertise vacant dwellings and
participants / nominees find suitable housing. SDA is provider-side and about a dwelling (not participant
PII), so listings are PUBLIC + SEO-indexable (unlike participant needs, which stay noindex).
- **Entity:** new CPT `sssj_sda` (a vacancy listing) linked to an `sssj_org` (the SDA provider). Mirrors the
  existing CPT pattern: registrar, meta, board shortcode, single page, SEO, A11y, design system.
- **Key fields (meta):** `sda_design_category` (Improved Liveability | Fully Accessible | Robust | High
  Physical Support), `building_type` (apartment | villa/duplex/townhouse | house | group home | other),
  `bedrooms`, `bathrooms`, `total_residents`, `vacancies` (places available), `private_or_shared`,
  `location_suburb/state/postcode` + lat/long, `ooa` (on-site overnight assistance: yes | shared | no),
  `sil_arrangement` (named provider | participant's choice | flexible), `accessibility_features[]` (ceiling
  hoist, accessible bathroom, wider doors, etc.), `available_from`, `status` (vacant | waitlist | filled),
  `rrc_note` (reasonable rent contribution, plain text, not financial advice), `enquiry_mode` (internal
  relay), `gallery` (photos + floorplan), `video_url` (YouTube/Vimeo embed).
- **Shortcodes:** `[sssj_sda_board]` (browse/search: filter by design category, building type, bedrooms,
  location + travel radius, OOA, status); `[sssj_post_sda]` (provider posts/edits a vacancy; gated by org
  ownership + monetisation); single-listing page (gallery + map + features + "Enquire" button).
- **Privacy/safety:** enquiries run through the internal relay (participant identity protected); listings
  show the dwelling, never current residents' details.
- **SEO:** public + indexable; emit `Accommodation` / `Residence` JSON-LD (contrast: `sssj_need` stays
  noindex). Good organic-discovery surface.
- **Maps:** reuse the shared maps + radius layer.
- **Monetisation:** SDA posting gated/featured like job ads (provider advertising subscription); free tier =
  N listings.
- **Matching/alerts:** participants can save an SDA search + get email alerts (reuse the Alerts hub); later,
  match participant needs to SDA design category + location.
- **Media + video:** photos via the stock-photo pipeline (Australian-tuned), floorplan upload, and a
  `video_url` embed field. Dovetails with the "snappy marketing videos" goal (listings + the demo tour are
  built to screen-record cleanly).
- **Compliance note:** SDA has specific NDIS rules (design-category enrolment, RRC). The plugin presents
  listings + general info, NOT financial or eligibility advice; show a short disclaimer.
- **Build phases:** (1) CPT + fields + provider post form; (2) board + filters + single page + maps; (3) SEO
  schema + save-search alerts + matching; (4) monetisation gating + featured + video embed.

## Workstream J - Demo tour: next iterations + deployment vision (NEW 2026-06-09, John request)
The `[sssj_demo_tour]` showcase (v1.10.9, live) is a static persona tour. Vision: evolve it into a guided,
interactive "test drive" that funnels visitors AND doubles as marketing-video source. Ship in small,
independently deployable phases (each lint -> build -> deploy on its own) so value lands continuously.

**Phase 1 - Convert (ship first):**
- Role-specific CTAs per persona (participant -> Request support, worker -> Create profile, employer ->
  Post a job), replacing the single "Try this yourself".
- "Which are you?" filter at the top using the three hat groups (I need support / I am looking for work /
  I offer work), so visitors see only the relevant stories.

**Phase 2 - Real test drive (depends on the enriched demo seeder + Unsplash, both wired):**
- Embed each persona's real seeded content inline (Jordan's worker card, Riverview's job ad, Aria's
  pseudonymous request), refreshing as the seeder runs.
- "Explore as this persona" links: admins use View-as; the public deep-links to the relevant board
  pre-filtered to that persona's area.
- One live mini-feature per persona (working mini map + radius slider, the real language toolbar, a sample
  search) in place of a static callout. Show, don't tell.

**Phase 3 - Trust + on-brand:**
- A prominent "Safety, built in" strip (pseudonymity, verified checks, safe relay) and a real-stats band
  (jobs / verified workers / providers, shown only once big enough, like the promo studio).
- Demonstrate the accessibility layer on the tour itself (language switcher, read-aloud, easy-read); pairs
  with the DeepL translation work. Diverse, authentic Australian imagery via Unsplash.
- Sticky persona nav + scroll-spy for the long page; mobile polish (swipeable gallery).

**Phase 4 - Marketing video / kiosk:**
- An auto-play "kiosk" mode (`[sssj_demo_tour autoplay]`) that advances through personas with timed reveals,
  clean 16:9 framing for screen-recording snappy promos or looping on a reception screen. Directly serves
  the marketing-video goal.

**Phase 5 - SDA persona:**
- Add an 8th persona once Workstream I (SDA) ships: an SDA provider listing a dwelling and/or a participant
  seeking SDA housing.

**Deployment vision:** each phase is independently shippable and lint-gated; build order Phase 1 -> 2 -> 3 ->
4, with Phase 5 after SDA. Phases 2+ assume the live site has been seeded (Run now) and demo photos loaded.

## Status (shipped, cont.)
- ✅ **v0.67.0** location fixes: worker-form place **autocomplete + lat/long** (maps script now enqueued on
  the worker form); board **place-select recenters/zooms the map + fires the AJAX filter** with a default
  radius (`sssj:placechosen` event; `window.SSSJMaps.focus()`); **ABN-gate apply/respond messages now link to
  "Edit my profile"** (new static `Shuffles_SSJ_Shortcodes::page_link()`).
- ⏳ **Known follow-up:** the results **map markers are NOT refreshed after an AJAX filter** (the map sits
  outside `[data-sssj-results]`, so it keeps the page-load pins; only recenters on place-select). To finish:
  return updated points in the `sssj_filter` AJAX response and re-plot markers, or move the map into the
  refreshed region. Logged for a later pass.

## Later / optional
- shuffles‑growth AI bridge (the 4 filters); real‑time matching panels; FluentCart Pro product for live
  licensing/billing; full i18n native review; keyless Leaflet/OSM map tiles; richer AU suburb dataset.
