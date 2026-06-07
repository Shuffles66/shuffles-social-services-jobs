# Just Tasks: Marketing and Product Master Document

**Positioning statement:** The safe, purpose-built Australian marketplace where NDIS, aged-care, disability and social-services work gets advertised, found and filled, with the most vulnerable people the most protected.

**Last updated: 2026-06-07**

---

## 1. Executive summary

Just Tasks is an Australian online marketplace for the NDIS, aged-care, disability and broader social-services sector. It connects four groups who all need each other but who have never had a single safe place to meet: people who advertise work and roles, the workers and contractors who do that work, the organisations and providers who run services, and the participants (and their representatives) who need support.

The problem it solves is real and specific. Sector work is split by hard legal and funding lines (employee versus contractor, NDIS versus aged care versus self-funded), and the most important people in it, the participants, are also the most exposed to harm if their details leak. General job boards ignore all of this. They mix contractor and employee work together, publish personal details, take any "verified" claim at face value, and assume everyone reads English and uses a standard interface. Just Tasks is built the other way around: the rules of the sector are wired into the data layer, not bolted on as settings.

What makes it different is that it is one cohesive, purpose-built platform rather than an assembly of separate tools. Everything described in this document, from segregated boards to live registration checking to multilingual access to the help workflows, runs on our internally curated and constructed tech stack and customised workflows. Safety, trust and accessibility are the defaults, not the upsell.

---

## 2. The platform in plain English

Just Tasks is a four-sided marketplace. Each side has a clear job to do, and the platform keeps them apart where it matters and brings them together where it helps.

- **People who advertise work and roles.** Employers, agencies and providers post the jobs and engagements they need filled. They choose how applications are handled and can advertise anonymously where appropriate.
- **Workers and contractors.** Individuals build a profile, store private resumes, set their availability, and respond to work. This includes people already employed who want more work, and sole traders who run their own small business.
- **Organisations and providers.** Companies, suppliers, housing and professional services list themselves in a directory, manage a team of users under one organisation, and (where they are NDIS-registered) get checked against the official register.
- **Participants and their representatives.** Participants, or a nominee acting for them, can advertise that they need a worker and choose that worker directly. Their listings are pseudonymous, suburb-level and never public.

**The ABN versus employee distinction, simply.** Some work is contractor work: the worker has an Australian Business Number (ABN), sends invoices, and manages their own tax. Other work is employee work: the worker is paid wages with tax withheld, using their Tax File Number arrangement (TFN). These two kinds of work are legally different, so Just Tasks never mixes them on the same board. There is also a third kind, volunteer (unpaid) work, which has its own board and never appears among paid roles.

**Participants are protected and pseudonymous.** A participant request is shown by a code, never a name, at suburb level only, to logged-in members only, and is never indexed by search engines. The first contact always runs through a safe internal relay, so a worker never sees a participant's email or phone number. This is structural, not a setting someone can switch off.

---

## 3. Business logic in plain English

This section is synthesised from the platform's authoritative business-rules record. It explains how Just Tasks decides things.

### Roles, hats and the primary role

One account can wear many "hats". In My Roles a member ticks the hats that apply: employer or company, NDIS or service provider, supplier, available for contracting (sole trader with an ABN), looking for employee work, participant, or participant representative or nominee. Any combination is allowed. The dashboard then reveals only the sections that match the ticked hats, so an employer who also contracts manages both from one place without clutter. A member can pick a "primary role" to focus the experience (the dashboard opens to that tab, the menu leads with its items), but this is focus, not lock-out: a "See all" option always reveals everything. Roles are self-declared and only ever add abilities; they are never taken away automatically.

A "provider" is not the same as an "organisation", and an employer is not necessarily an NDIS provider. A provider can be an individual sole trader (a worker profile with an ABN), not only a company.

### Employee, contractor and volunteer work kept strictly apart

Every job is either ABN (contractor or sole trader, invoices) or TFN (employee, wages with tax withheld). Participant support requests are always ABN. The employee board never shows contractor roles and vice versa, and this is enforced in the data layer, not just hidden on the page. Volunteer (unpaid) work is a third type with its own board and never appears on the paid boards. An ABN is required for contractor advertisers, all organisations, and any worker responding to ABN or participant work; it is checksum-validated and never asked for on employee roles.

### Funding as a soft guide

A participant can attach one funding source, many, or none (self-funded is valid). Funding helps matching but never filters results down to zero. It is a signal, not a gate.

### Who pays, who is free

Monetisation is off by default, so a site is never accidentally locked. When it is switched on: participants employing directly, seeking workers, or seeking providers are always free, even with monetisation on. Providers pay for posting jobs beyond the free limit, featured placement, responding to participant or ABN work, and appearing in the Organisations directory.

### Verification and trust (admin-granted only)

A badge always means a human on the team checked something; it is never a self-claim. The blue tick and the green "Verified" credential badge are granted only by an administrator. Workers upload evidence (Working With Children Check, police check, NDIS screening and similar), it sits as Pending, and an admin approves or rejects it. The badge needs at least one approved, in-date credential. Evidence files are stored privately (in the database, with no public link) and served only to the owner or an admin. When a credential expires, reminders go out beforehand and the badge drops automatically.

### Live registration checking

NDIS registration is read from the NDIS Commission's own public register, not taken on trust. An organisation or sole trader enters their NDIS registration number; on save (or via "Scan now") the platform reads their public listing and shows the live status, approved registration groups, expiry, plus legal name, ABN, head-office location and website. If the register's ABN differs from the ABN on file, a red warning is shown. A "Revoked" or "Banned" status appears on a red background, never green. A monthly check re-reads every registered org and sole trader and alerts staff (never the provider) if anything changes. It is safe by design: if a check fails, stored details are kept, never wiped, and staff are alerted, so a register change can never silently look like "still approved".

### Participant privacy as non-negotiable

Participant requests are pseudonymous, suburb-level, logged-in-only, never indexed, and have no public profile page. New requests are held for admin approval before they appear. First contact runs through the safe internal relay. Owners can mask individual sensitive fields (worker pay rate, organisation phone and website) as "members only". Stored resumes are private, streamed only through an authenticated link to the owner, an admin, or an employer the candidate has applied to.

### The application pipeline

Applying to an employee job captures a chosen resume, availability, earliest start date, a required right-to-work confirmation, and answers to the employer's screening questions. The employer sets those questions and picks how applications are handled: full pipeline (track stages) or simple, changeable later. The pipeline runs New, Shortlisted, Interview, Offer, then Hired or Declined, with Viewed and the applicant's Withdrawn. The applicant is emailed when their status changes and can withdraw at any time. ABN and participant work use a lighter expression-of-interest path.

### Reviews and ratings

Members rate contractors and providers from one to five stars. You may only review someone you have genuinely engaged with (a relay message exists, which applying starts), never yourself, and one editable review per member per subject. Every review is held as Pending and only appears once an admin approves it; the reviewed party may post one public response. The approved average is cached on the profile and feeds the matching "trust" signal. Reviews can be switched off globally without losing existing ones.

### Matching, alerts and earning by referral

Matching scores candidates on shared categories, distance, availability, engagement basis, rate and trust. Keyword search is synonym-aware: it understands sector language (so "support work" also finds "carer" or "DSW", and "OT" finds "occupational therapist") and broadens with OR so a sensible search never returns nothing. Email alerts (job matches, new candidates, saved searches) are opt-in and sent on a daily schedule. Members, especially participants, can earn referral income by inviting others to the platform.

### The rules that never bend

- Never show a verified or blue-tick badge that was not granted by an administrator.
- Never expose a participant's identity or contact details, and never let a participant request be indexed.
- Never expose a credential evidence file through a public link.
- Never mix contractor (ABN) and employee (TFN) results outside a clearly labelled tab.
- Never let funding filter results down to zero; it is a signal, not a gate.
- Never email a provider about a registration change; that alert goes to staff only.
- Never overwrite stored registration details when a check fails; keep them and alert staff.
- Never let a member edit the details read from the official register; they are shown read-only.
- Never lock a site by default; monetisation stays off until it is switched on.

---

## 4. Functional specification

Every capability below is built on our internally curated and constructed tech stack and customised workflows, presented to members as one cohesive platform.

### Discovery and segregated boards
- Separate employee, contractor, volunteer and participant boards, with the split enforced at the data layer.
- One-off versus ongoing flag on every role, and a clear basis label on every card.
- Synonym-aware keyword search that broadens sensibly so a reasonable search never returns nothing.
- Organisations directory with category badges and filters; sponsored organisations sort to the top (admin-granted only).
- A provider discovery deck for browsing organisations quickly and shortlisting favourites.

### Posting and applications
- Posting forms for jobs, worker profiles and participant requests, with a privacy-sensitive flow for participants.
- Employer-set screening questions per job and a choice of full-pipeline or simple application handling.
- Full application pipeline (New, Shortlisted, Interview, Offer, Hired or Declined) with status history, email notifications and applicant withdrawal.
- Anonymous advertising: a job can be posted without revealing the organisation name, logo or advertiser.

### Profiles and resumes
- Rich worker profiles with availability, services, rates, languages and visibility controls (public, logged-in, hidden).
- Multiple named, private resume files per candidate, served only through authenticated links.
- Per-field masking on sensitive values (worker pay rate, organisation phone and website) while keeping the profile findable.

### Participant requests
- Pseudonymous, suburb-level, logged-in-only requests, held for admin approval before they appear.
- Multi-funding attachment (one, many or none) used as a soft matching signal.

### Messaging relay
- Internal first-contact relay so neither side sees the other's email or phone until both choose to share.

### Verification and credentials
- Worker credential uploads (screening, checks, first aid, certifications, insurance) held as Pending for admin review.
- Admin-only "Verified" and blue-tick badges; the badge needs at least one approved, in-date credential.
- Private, database-stored evidence with no public link; expiry reminders and automatic badge drop.
- ABN capture, normalisation and checksum validation, with optional verification against the Australian Business Register and read-only display of the register's own record.
- Live NDIS registration checking from the Commission's public register, with ABN cross-check, red status for revoked or banned, and a monthly staff-only re-check that is safe by design.

### Reviews and ratings
- One-to-five-star moderated reviews of contractors and providers, gated to people who genuinely engaged.
- One editable review per member per subject, an owner right-of-reply, and a cached average that feeds matching.

### Matching and maps
- Score-based matching on categories, distance, availability, basis, rate and trust.
- Location autocomplete and radius search, with remote and telehealth work surfacing regardless of distance.
- Opt-in daily email alerts for matches, new candidates and saved searches.

### Accessibility and multilingual access
- Voice search, interface translation into multiple community languages (including right-to-left support), high-contrast, no-colour, larger-text and easy-read modes, and read-aloud, with an always-visible English escape.
- Preferences remembered per member, designed to run at no extra cost.

### Strong job-listing SEO
- Structured job-listing data on every public job, eligible for job search engines, with indexable single-job and category landing pages and a sitemap.
- A hard privacy override: participant pages are never indexed and are excluded from sitemaps; worker pages follow their visibility flag and never expose personal details.

### Monetisation and earning
- Optional advertiser and provider subscriptions, featured placement, optional banner inventory, and a referral-earning path for members.
- Resell and white-label licensing so the same platform can run in adjacent sectors.

### Self-serve help, workflows and policies
- Advice guides ("how to do it well") and step-by-step explainer workflows ("the exact steps"), personalised so a member's primary-role workflows float to the top without hiding anything.
- A published, plain-English summary of every platform policy, kept in sync with the formal templates.

### Admin, settings and a living testing worksheet
- A tabbed settings area covering boards, taxonomies, compliance, funding, access, SEO, maps, matching, monetisation, licensing, privacy and moderation, with seeded industry-standard taxonomies on activation.
- A Business Logic tab that surfaces the same rules described above, kept in sync with the authoritative record.
- A living testing worksheet (a pass/fail single source of truth) kept current as the product evolves.

---

## 5. Who this is attractive to, and why

The obvious stakeholders come first, briefly. **Participants** get to choose their own workers safely and privately, and can earn referral income. **Support workers** find both employee and contractor work in one safe place and carry their verification with them. **Sole-trader contractors** advertise a real, checkable business identity and win work directly. **Providers** list in a directory, manage a team, and prove their registration live. **Employers and agencies** post across both boards, screen applicants, and advertise anonymously when needed.

Now the less obvious audiences, and the concrete hook for each.

- **Support coordinators and plan managers.** They spend their days finding the right safe worker or provider for a participant. A board with live registration checks, verified credentials and a private relay turns hours of phone-and-email matching into a few searches they can trust.
- **Local area coordinators.** They connect people to community and mainstream supports; a single, privacy-safe place to surface local workers and providers, including in thin markets, makes that connection faster and safer.
- **Allied health clinics and practitioners.** Occupational therapists, physios, psychologists and speech pathologists can list as providers, take both contractor and employee work, and be found through synonym-aware search that understands their abbreviations.
- **Aged-care and home-care providers.** The platform is funding-agnostic, so home-care and aged-care work sits alongside NDIS rather than being squeezed into an NDIS-only mould; multi-funding and the same verification rigour apply.
- **Family members, carers and nominees.** A nominee can advertise on a participant's behalf, keep them pseudonymous, and choose the worker directly, with first contact going through a safe relay rather than handing out a phone number.
- **Disability advocates, peak bodies and advocacy organisations.** They want a marketplace whose defaults match their values: privacy by design, admin-granted trust, anti-discrimination policy, and accessible multilingual access. It is something they can recommend without reservation.
- **Registered training organisations, TAFEs and students seeking placements.** Jobs and organisations can flag that they accept work-placement enquiries, so students find placement-friendly hosts and training providers can point cohorts at a real, current source of opportunities.
- **Volunteers and volunteer-involving organisations.** A dedicated volunteer board keeps unpaid roles separate from paid work, and organisations can flag that they welcome volunteers, so goodwill is matched without muddying employment lines.
- **People re-entering the workforce (parents, retirees, career changers).** Flexible, one-off and ongoing work is all here, with guided step-by-step workflows that mean no training session is needed to get started.
- **People with lived experience of disability.** The platform is built to be used by them, not just about them: accessible modes, voice search, read-aloud, and the chance to work, contract or earn by referral.
- **Culturally and linguistically diverse (CALD) communities.** Interface translation into several community languages (with right-to-left support) and read-aloud open the marketplace to families and workers who are routinely shut out of English-only boards.
- **First Nations communities and organisations.** Suburb-level privacy, community-language access and the ability to surface local and remote work suit community-controlled organisations and the people they serve, including in regional and remote areas.
- **LGBTQIA+ communities.** Worker gender-match preferences, an inclusion policy, and privacy controls let people seek and offer support that feels safe and affirming.
- **Regional, rural and remote communities.** Remote and telehealth work surfaces regardless of distance, and radius search plus thin-market-friendly matching mean a result, not an empty page, even where providers are scarce.
- **People seeking flexible or side income.** Contractors, incumbents wanting more work, and members, especially participants, earning through referrals all have a clear path to extra income from one account.
- **Employment services and disability employment providers.** They place candidates into work; a sector-fit board with verification, screening and a real application pipeline is exactly the destination they need for the people they support.
- **Insurers and scheme bodies.** Workers' compensation, motor-accident and other scheme funders can be attached as funding sources, so injured and insured people find suitable workers without the platform being locked to a single scheme.
- **Local councils and social enterprises.** Councils running community programs and social enterprises hiring locally get a low-cost, accessible, privacy-safe channel to advertise roles and find workers in their own area.
- **Faith-based and community groups.** Organisations that mix paid roles, volunteers and pastoral support can advertise all three cleanly, with the volunteer board keeping unpaid work properly separate.
- **Investors and grant funders.** They see a defensible, compliance-led platform with multiple revenue lines, strong privacy posture, and clear social impact, which is a credible story for both commercial return and grant outcomes.
- **Resellers and white-label operators.** The platform can be licensed and re-skinned to run the same trusted model in adjacent sectors (for example childcare, community services or trades), turning the build into a repeatable business.

---

## 6. Differentiators and competitive edge

- **Privacy by design.** Participant pseudonymity, suburb-level location, the internal relay, per-field masking and never-indexed participant pages are structural, not optional settings.
- **Trust and verification.** Every trust badge is human-granted, every credential is checked and dated, and NDIS registration is read live from the official register and re-checked monthly, safely.
- **Accessibility.** Multilingual, voice-enabled, read-aloud and high-contrast access is in the box, built to run at no extra cost, not sold as a premium add-on.
- **Sector fit.** The employee, contractor and volunteer split, multi-funding, screening profiles and synonym-aware search reflect how the sector actually works.
- **One cohesive platform.** Discovery, applications, messaging, verification, accessibility, SEO, help and monetisation are one purpose-built whole, all on our internally curated and constructed tech stack and customised workflows.
- **Multiple ways to earn.** Advertiser and provider subscriptions, banner inventory and member referral income give the platform and its members several income paths.
- **Resell and white-label potential.** A proven, compliance-led model that can be licensed into adjacent sectors.

---

## 7. Trust, safety and compliance posture

Trust on Just Tasks is earned and checked, never assumed. Verification badges are granted only by an administrator after a human reviews real evidence, and that evidence is stored privately and removed from view when it expires. NDIS registration is read live from the Commission's own register, cross-checked against the ABN on file, shown read-only, and re-checked monthly with staff-only alerts so a lapse can never masquerade as approval.

Moderation is pre-publish where it matters most: participant requests are held for admin approval before they appear, and every review is held until an admin approves it. Privacy follows the Australian Privacy Principles, with pseudonymous participants, the contact relay, private credential and resume storage, and data export and erasure paths.

The platform ships with a full published policy suite, each available both as a formal template for the operator to adopt and as a plain-English summary members can read in-product: complaints management and resolution, privacy, NDIS Code of Conduct, incident management and reportable incidents, safeguarding and risk, terms and acceptable use, worker screening and verification, data retention and destruction, cookie and consent, and anti-discrimination and inclusion. Complaints and incidents have defined handling paths aligned to sector practice standards. These are operational documents to be formally adopted and, where noted, legally reviewed before they are relied upon.

---

## 8. Ways the platform creates and shares value

All revenue and value paths are optional and described generically; none is required for the platform to run.

- **Advertiser subscriptions.** Employers and agencies can pay for posting volume beyond the free limit and for featured or promoted placement.
- **Provider subscriptions.** Providers can pay to respond to participant and contractor work and to appear in the Organisations directory.
- **Banner inventory.** Optional banner slots across boards and listings provide additional inventory without ever being bundled or required.
- **Referral earning for members.** Members, especially participants, can earn referral income by inviting others, giving the community a direct stake in growth.
- **Reseller and white-label licensing.** The platform can be licensed and re-skinned to operate the same trusted model in adjacent sectors.

Crucially, participants employing directly, seeking workers or seeking providers are always free, even when monetisation is switched on, and monetisation is off by default so a site is never accidentally locked.

---

## 9. Accessibility and inclusion commitment

Just Tasks is built for the communities this sector serves, not adapted for them afterwards. Members can use voice search, switch the interface into several community languages (including right-to-left scripts), turn on high-contrast, no-colour, larger-text or easy-read modes, and have content read aloud, with an always-visible way back to English. Preferences are remembered, and the access layer is designed to run at no extra cost so it is never traded away for budget reasons.

Inclusion is also a matter of rules and policy. Worker gender-match preferences support safe and affirming choices, the anti-discrimination and inclusion policy names protected attributes and lawful participant preferences, and privacy controls let people take part on their own terms. The aim is a marketplace that a person with disability, a CALD family, a First Nations organisation or an LGBTQIA+ worker can use with confidence on the first visit.

---

## 10. Maintenance note

This is a living document. Keep it in sync with the authoritative business-rules record (`includes/class-business-rules.php`, specifically its `sections()` and `invariants()`) and with `docs/FEATURES.md` as the product evolves. When a rule changes, a gate is added, or a default flips, update this document and the dated lines above.

Two writing rules apply to every future edit of this document, and they are non-negotiable:

1. **No em dashes.** Never use the long dash character anywhere. Use commas, colons, parentheses, or the word "to" instead. Normal hyphens inside hyphenated words are fine.
2. **No third-party brand or vendor names.** Never name any external plugin, product, company, library or vendor. Always describe such capabilities generically as part of our internally curated and constructed tech stack and customised workflows, so the platform reads as one cohesive, purpose-built whole.
