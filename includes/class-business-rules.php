<?php
/**
 * Business rules & logic, the in-app, plain-English record of how the plugin decides things.
 *
 * This is the single source for the Settings → "Business Logic" tab. It mirrors the prose doc
 * docs/business_rules_and_logic.md (which is NOT packaged in the build), keep the two in sync.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Business_Rules {

	/** Shown at the top of the tab. */
	public static function last_updated() {
		return 'v1.10.15 (2026-06-09)';
	}

	/**
	 * Sections of business logic. Each: title + intro + an array of plain-English rule lines.
	 *
	 * @return array
	 */
	public static function sections() {
		return apply_filters(
			'shuffles_ssj_business_rules',
			array(
				array(
					'title' => __( 'Members & roles', 'shuffles-social-services-jobs' ),
					'intro' => __( 'One account can wear many hats. Roles are self-declared and add abilities; they are never taken away automatically.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'One account, many “hats”. In My roles a member ticks the hats that apply, Employer/company, NDIS/service provider, Supplier, Available for contracting (sole trader/ABN), Looking for employee work (PAYG/TFN), Participant, or Participant representative/nominee, any combination.', 'shuffles-social-services-jobs' ),
						__( 'The dashboard reveals only the sections matching the ticked hats, so an employer who also contracts manages both from one place without confusion (members with no hats yet fall back to capability detection, so nothing disappears).', 'shuffles-social-services-jobs' ),
						__( 'A member can pick a “primary role” to focus the experience: the dashboard opens to that role’s tab and the menu leads with its items. This is focus, not lock-out, a “See all” option always reveals everything the member can use. No primary role = show everything.', 'shuffles-social-services-jobs' ),
						__( 'Each hat grants the matching abilities: employer/provider → post jobs + an organisation; supplier → an organisation; contractor/candidate → a worker profile; participant/representative → support requests (participants may also post their own roles, free).', 'shuffles-social-services-jobs' ),
						__( '“Provider” is not the same as “Organisation”, and an employer is not necessarily an NDIS provider. A provider can also be an individual sole trader (a worker profile with an ABN), not only a company.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'ABN vs TFN, kept strictly apart', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Contractor (ABN) and employee (TFN) work never mix on the same board.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'Every job is either ABN (contractor/sole-trader, invoices) or TFN (employee, wages with tax withheld). Participant support requests are always ABN.', 'shuffles-social-services-jobs' ),
						__( 'The employee board never shows contractor roles and vice-versa, this is enforced in the data layer, not just hidden on the page.', 'shuffles-social-services-jobs' ),
						__( 'Volunteer (unpaid) is a third type with its own board ([sssj_volunteer_board]); volunteer roles never appear on the paid TFN/ABN boards. Any logged-in member can apply (no ABN, no pay).', 'shuffles-social-services-jobs' ),
						__( 'An ABN is required for contractor advertisers, all organisations, and any worker responding to ABN or participant work. It is validated (checksum), and never asked for on TFN roles.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Funding', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Funding is a helpful signal, not a barrier.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'A participant can attach one, many, or no funding sources (self-funded is valid). Funding helps matching but never filters results down to zero.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Who pays, who is free', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Monetisation is OFF by default, a site is never accidentally locked. When it is on:', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'FREE: participants employing directly, seeking workers, or seeking providers, always free, even with monetisation on.', 'shuffles-social-services-jobs' ),
						__( 'PAID (providers): posting jobs beyond the free limit, featured placement, responding to participant/ABN work, and appearing in the Organisations directory.', 'shuffles-social-services-jobs' ),
						__( 'Billing can run through PMPro (membership levels) or FluentCart (products), whichever the site uses.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Organisations: categories & sponsorship', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Organisations are typed, and sponsorship is something only an admin grants.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'Categories: support provider, supplier/services to the sector, SDA/housing, real estate, professional services, or other, shown as a badge and a directory filter.', 'shuffles-social-services-jobs' ),
						__( 'A “Sponsored” organisation sorts to the top of the directory and shows a ★ badge. Only an administrator can grant it, members cannot self-promote.', 'shuffles-social-services-jobs' ),
						__( '“Open to…” options: a job or organisation can flag that it’s open to overseas applicants / offers visa sponsorship, accepts work-placement (student-placement) enquiries, and (organisations) welcomes volunteers, shown as badges on cards and profiles.', 'shuffles-social-services-jobs' ),
						__( 'Teams: an organisation has one owner (its creator) plus any number of team members, each a “member” or an “admin”. An org admin can add an existing person (by email/username), change a member’s role, or remove someone, the owner can never be removed or demoted. Adding a member never creates an account, and removing one only unlinks them (their account is untouched).', 'shuffles-social-services-jobs' ),
						__( 'Joining: a logged-in member can “Request to join” an organisation from its profile (optional message). The request stays pending until an org admin Approves it (which adds them to the team) or Declines it, no one joins without admin approval. The member can cancel a pending request; admins are emailed about new requests.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Verification & trust', 'shuffles-social-services-jobs' ),
					'intro' => __( 'A badge always means a human on the team checked something, never a self-claim.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'The blue tick and the green “✓ Verified” credential badge are granted only by an administrator.', 'shuffles-social-services-jobs' ),
						__( 'Workers upload evidence (WWCC, police check, NDIS screening, etc.) → it sits as Pending → an admin approves or rejects. The badge needs at least one approved, in-date credential.', 'shuffles-social-services-jobs' ),
						__( 'Evidence files are stored privately (in the database, no public link) and served only to the owner or an admin.', 'shuffles-social-services-jobs' ),
						__( 'When a credential expires, reminders go out beforehand and the badge drops automatically.', 'shuffles-social-services-jobs' ),
						__( 'ABN verification: if an ABR Web Services GUID is set (Settings → Compliance), any ABN saved on a job, worker or organisation is checked against the Australian Business Register on save. The FULL register response, entity name, trading/business names, status, type, ACN, GST, location, is recorded read-only and shown (organisation profile + owner forms). It is the Register’s own data, never edited here.', 'shuffles-social-services-jobs' ),
						__( 'Reviews & ratings: members rate contractors (worker profiles) and providers (organisations) 1–5 stars. You may only review someone you have genuinely engaged with (a relay message exists between you, applying starts one), and never yourself; one editable review per member per subject.', 'shuffles-social-services-jobs' ),
						__( 'Every review is held as Pending and only appears once an admin approves it; the reviewed party may post one public response. The approved average is cached on the profile and feeds the matching “trust” signal. Reviews can be switched off globally (Settings → Reviews & Ratings) without losing existing ones.', 'shuffles-social-services-jobs' ),
						__( 'Testimonials (separate from star reviews): qualitative endorsements shown on a contractor’s or provider’s profile under “What people say”. A member can submit one, and the owner can add a quote they received elsewhere. Every testimonial is pre-moderated (held Pending until an admin approves), and the owner then curates which approved ones to Feature, only Approved + Featured show publicly. The person submitting chooses how they are credited (so a participant need never reveal their identity), and testimonials only attach to worker/provider profiles, never participant requests. Switchable off globally (Settings → Testimonials).', 'shuffles-social-services-jobs' ),
						__( 'Advertising and media: we prefer not to use children in advertising or commercial media (strict safeguards and guardian consent if one must appear); anyone in commercially-intended media is paid fairly; written informed consent (including any agreed remuneration) is obtained from everyone shown; advertising is truthful and inclusive and never identifies a participant. Promotional video links are accepted from trusted video hosts only.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'NDIS provider registration (live check)', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Registration is read from the NDIS Commission’s own public register, not taken on trust.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'An organisation or a sole-trader individual enters their NDIS Registration No (the number after ?id= in their Commission listing URL).', 'shuffles-social-services-jobs' ),
						__( 'On save (or via “Scan now”), the plugin reads their public listing and shows the live registration status, approved registration groups, expiry date, plus the legal name, ABN, head-office location and website.', 'shuffles-social-services-jobs' ),
						__( 'ABN cross-check: if the ABN on the register differs from the ABN on file, a red warning note is shown so it can be checked.', 'shuffles-social-services-jobs' ),
					__( 'These register-sourced details (status, registration groups, ABN, head office, website, outlets and phone) are read-only, a member can’t edit them; they come straight from the Commission’s listing.', 'shuffles-social-services-jobs' ),
					__( 'A “Revoked” or “Banned” status is shown on a red background (never green).', 'shuffles-social-services-jobs' ),
						__( 'A monthly check re-reads every registered org and sole trader and alerts staff (never the provider) if the status, groups, or expiry change.', 'shuffles-social-services-jobs' ),
						__( 'Safe-by-design: if a check fails, stored details are kept (never wiped) and staff are alerted, a register-page change can’t silently look like “still approved”.', 'shuffles-social-services-jobs' ),
							__( 'Banned-register cross-match: a private register of banned/sanctioned ABNs (imported from the NDIS Commission’s published enforcement actions, or added by hand) is cross-checked whenever an ABN is recorded on a job, worker or organisation. A match is flag-only and staff-only, it never blocks posting, never auto-rejects, and is never shown to the public or the provider; staff get an email and a human reviews it (Settings → Safety Register).', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Participant privacy (non-negotiable)', 'shuffles-social-services-jobs' ),
					'intro' => __( 'The most vulnerable people on the platform are the most protected.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'Participant requests are pseudonymous (a code, never a name), suburb-level only, visible to logged-in members only, never indexed by search engines, and have no public profile page.', 'shuffles-social-services-jobs' ),
						__( 'New participant requests are held for admin approval before they appear.', 'shuffles-social-services-jobs' ),
						__( 'First contact runs through a safe internal relay, a worker never sees a participant’s email or phone.', 'shuffles-social-services-jobs' ),
						__( 'Worker profiles honour their chosen visibility (public / logged-in / hidden) everywhere, in the directory, in search-engine indexing AND at the profile’s own web link. A “members only” or “hidden” profile cannot be opened by its direct URL by anyone who could not see it in the directory (the owner and admins can always preview their own page), and is kept out of the sitemap. The same applies to a hidden organisation: only its own team and admins can open it. Profiles never expose personal contact details publicly.', 'shuffles-social-services-jobs' ),
						__( 'Owners can mask individual sensitive fields (worker pay rate; organisation phone & website) as “members only”. Logged-out visitors then see a “Log in to view” lock instead of the value; signed-in members, the owner and admins still see it, and the profile stays findable. Masking can never reveal register-sourced NDIS data, which stays read-only regardless.', 'shuffles-social-services-jobs' ),
						__( 'Anonymous advertising: an employer can post a job anonymously, the listing shows “Anonymous” instead of the organisation name/logo, the organisation isn’t revealed (and the role is hidden from that organisation’s public open-positions), and the advertiser’s name is kept out of search-engine data. Participant requests are anonymous by default.', 'shuffles-social-services-jobs' ),
						__( 'Stored résumés are private: a candidate may keep several named résumé files on their profile, stored securely (bytes in the database) and streamed only through an authenticated link to the owner, an admin, or an employer they have applied to, never via a public URL.', 'shuffles-social-services-jobs' ),
						__( 'Listing lifecycle: a participant request (and a job ad) carries a close date. A request with no date set defaults to about two months out. The owner gets a “closing soon” reminder, the listing auto-closes on its date (so nobody is contacted about something already sorted), the owner gets a “now closed” reminder, and the owner can reopen it any time with one click, which gives it a fresh close date.', 'shuffles-social-services-jobs' ),
						__( 'Posts belong on the platform, not on public social media. Keeping requests and profiles inside the marketplace protects members’ privacy and dignity, and reduces the well-meaning copying or resharing of listings by the public, which can expose people and become an unhealthy distraction.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Matching, alerts & CRM', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Connecting the right people, and keeping them informed, only if they opt in.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'Matching scores candidates on shared categories, distance, availability, engagement basis, rate and trust.', 'shuffles-social-services-jobs' ),
							__( 'Applying to an employee (TFN) job captures a chosen résumé, availability, earliest start date, a required right-to-work confirmation, and answers to the employer’s screening questions. The employer sets those questions on the job and picks how applications are handled, full pipeline (track stages) or simple, changeable later. ABN / participant work keeps the lighter expression-of-interest path.', 'shuffles-social-services-jobs' ),
						__( 'Application pipeline: New → Shortlisted → Interview → Offer → Hired / Declined (with Viewed and the applicant’s Withdrawn). Full-pipeline jobs expose every stage and keep a status history; simple jobs use a minimal set. The applicant is emailed when their status changes, and can withdraw their own application at any time.', 'shuffles-social-services-jobs' ),
						__( 'Keyword search is synonym-aware: it understands sector language (e.g. “support work” also finds “carer” / “DSW”, “OT” finds “occupational therapist”) and broadens with OR so a sensible search never returns nothing. It is built deterministically now and ready for a smarter (AI) expander later.', 'shuffles-social-services-jobs' ),
						__( 'Email alerts (job matches, new candidates, saved searches) are opt-in and sent on a daily schedule.', 'shuffles-social-services-jobs' ),
						__( 'Ticking a program (e.g. NDIS) can keep the matching CRM tags/lists in sync, logged per member.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Front page & growth', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Lead with safety; show numbers only once they’re impressive.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'The hero banner carries a “Safety, built in” strip summarising the privacy & verification guardrails.', 'shuffles-social-services-jobs' ),
						__( 'Counters can be set to stay hidden until each total reaches a chosen minimum, so small early numbers don’t show until the marketplace has grown.', 'shuffles-social-services-jobs' ),
						__( 'Any lookup that takes a few seconds shows the branded Shuffles spinner.', 'shuffles-social-services-jobs' ),
						__( 'Self-promotion graphics (the admin-only “Promote the platform” studio) are built only from public, aggregate numbers and brand messaging, never from participant data or any private detail, and a counter is only ever shown once it is big enough to impress.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'AI Profile Card', 'shuffles-social-services-jobs' ),
					'intro' => __( 'A member-only tool to turn a member’s own profile into a shareable image. Off by default; it bills per image, so it is gated.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'The generator stays off until an administrator switches it on and saves an image-engine key, so it never bills by accident.', 'shuffles-social-services-jobs' ),
						__( 'Each member can make a set number of cards per calendar month (resets on the 1st). Where a membership system is present, paid members can be given a higher limit.', 'shuffles-social-services-jobs' ),
						__( 'The artwork is always a decorative, text-free background; the member’s location, services and name are drawn on top in their own browser, so the words stay sharp and correct.', 'shuffles-social-services-jobs' ),
						__( 'Only the member’s own profile is ever used to build a card; a participant’s details, or anyone else’s, are never used.', 'shuffles-social-services-jobs' ),
					),
				),
			)
		);
	}

	/** The hard "never" rules. */
	public static function invariants() {
		return apply_filters(
			'shuffles_ssj_business_invariants',
			array(
				__( 'Never show a verified or blue-tick badge that wasn’t granted by an administrator.', 'shuffles-social-services-jobs' ),
				__( 'Never expose a participant’s identity or contact details, and never let a participant request be indexed.', 'shuffles-social-services-jobs' ),
				__( 'Never expose a credential evidence file through a public link.', 'shuffles-social-services-jobs' ),
				__( 'Never mix contractor (ABN) and employee (TFN) results outside a clearly labelled tab.', 'shuffles-social-services-jobs' ),
				__( 'Never let funding filter results down to zero, it’s a signal, not a gate.', 'shuffles-social-services-jobs' ),
				__( 'Never email a provider about an NDIS-registration change, that alert goes to staff only.', 'shuffles-social-services-jobs' ),
				__( 'Never block, auto-reject, or publicly reveal a banned-register ABN match, it is a staff-only flag for human review (the data can be stale or about a different entity sharing an ABN). Never contact the provider on the basis of the flag alone.', 'shuffles-social-services-jobs' ),
				__( 'Never overwrite stored NDIS details when a check fails, keep them and alert staff.', 'shuffles-social-services-jobs' ),
				__( 'Never let a member edit the details read from the NDIS register (status, groups, ABN, outlets, phone, …), they are the Commission’s data, shown read-only.', 'shuffles-social-services-jobs' ),
				__( 'Never rely on hiding a private profile from the directory alone, enforce its visibility at the page link and in the sitemap too, so it can’t be reached by guessing the URL.', 'shuffles-social-services-jobs' ),
				__( 'Never name the third-party AI/search vendor in anything members or the public can see.', 'shuffles-social-services-jobs' ),
				__( 'Never lock a site by default, monetisation stays off until it’s switched on.', 'shuffles-social-services-jobs' ),
			)
		);
	}
}
