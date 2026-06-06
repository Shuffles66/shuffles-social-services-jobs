<?php
/**
 * Business rules & logic — the in-app, plain-English record of how the plugin decides things.
 *
 * This is the single source for the Settings → "Business Logic" tab. It mirrors the prose doc
 * docs/business_rules_and_logic.md (which is NOT packaged in the build) — keep the two in sync.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Business_Rules {

	/** Shown at the top of the tab. */
	public static function last_updated() {
		return 'v0.55.0 (2026-06-07)';
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
						__( 'A member can be a worker, candidate, participant, sole-trader provider, provider representative, an employee of an organisation, or a supplier — any combination.', 'shuffles-social-services-jobs' ),
						__( 'Declaring a role (My roles) grants the matching abilities: participants can post jobs and support requests; providers/representatives can post jobs and an organisation; suppliers can post an organisation; workers/candidates can post a worker profile.', 'shuffles-social-services-jobs' ),
						__( '“Provider” is not the same as “Organisation”. A provider is one kind of organisation, and a provider can be an individual sole trader (a worker profile), not only a company.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'ABN vs TFN — kept strictly apart', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Contractor (ABN) and employee (TFN) work never mix on the same board.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'Every job is either ABN (contractor/sole-trader, invoices) or TFN (employee, wages with tax withheld). Participant support requests are always ABN.', 'shuffles-social-services-jobs' ),
						__( 'The employee board never shows contractor roles and vice-versa — this is enforced in the data layer, not just hidden on the page.', 'shuffles-social-services-jobs' ),
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
					'intro' => __( 'Monetisation is OFF by default — a site is never accidentally locked. When it is on:', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'FREE: participants employing directly, seeking workers, or seeking providers — always free, even with monetisation on.', 'shuffles-social-services-jobs' ),
						__( 'PAID (providers): posting jobs beyond the free limit, featured placement, responding to participant/ABN work, and appearing in the Organisations directory.', 'shuffles-social-services-jobs' ),
						__( 'Billing can run through PMPro (membership levels) or FluentCart (products) — whichever the site uses.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Organisations: categories & sponsorship', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Organisations are typed, and sponsorship is something only an admin grants.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'Categories: support provider, supplier/services to the sector, SDA/housing, real estate, professional services, or other — shown as a badge and a directory filter.', 'shuffles-social-services-jobs' ),
						__( 'A “Sponsored” organisation sorts to the top of the directory and shows a ★ badge. Only an administrator can grant it — members cannot self-promote.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Verification & trust', 'shuffles-social-services-jobs' ),
					'intro' => __( 'A badge always means a human on the team checked something — never a self-claim.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'The blue tick and the green “✓ Verified” credential badge are granted only by an administrator.', 'shuffles-social-services-jobs' ),
						__( 'Workers upload evidence (WWCC, police check, NDIS screening, etc.) → it sits as Pending → an admin approves or rejects. The badge needs at least one approved, in-date credential.', 'shuffles-social-services-jobs' ),
						__( 'Evidence files are stored privately (in the database, no public link) and served only to the owner or an admin.', 'shuffles-social-services-jobs' ),
						__( 'When a credential expires, reminders go out beforehand and the badge drops automatically.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'NDIS provider registration (live check)', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Registration is read from the NDIS Commission’s own public register — not taken on trust.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'An organisation or a sole-trader individual enters their NDIS Registration No (the number after ?id= in their Commission listing URL).', 'shuffles-social-services-jobs' ),
						__( 'On save, the plugin reads their public listing and shows the live registration status, the approved registration groups, and the expiry date.', 'shuffles-social-services-jobs' ),
						__( 'A monthly check re-reads every registered org and sole trader and alerts staff (never the provider) if the status, groups, or expiry change.', 'shuffles-social-services-jobs' ),
						__( 'Safe-by-design: if a check fails, stored details are kept (never wiped) and staff are alerted — a register-page change can’t silently look like “still approved”.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Participant privacy (non-negotiable)', 'shuffles-social-services-jobs' ),
					'intro' => __( 'The most vulnerable people on the platform are the most protected.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'Participant requests are pseudonymous (a code, never a name), suburb-level only, visible to logged-in members only, never indexed by search engines, and have no public profile page.', 'shuffles-social-services-jobs' ),
						__( 'New participant requests are held for admin approval before they appear.', 'shuffles-social-services-jobs' ),
						__( 'First contact runs through a safe internal relay — a worker never sees a participant’s email or phone.', 'shuffles-social-services-jobs' ),
						__( 'Worker profiles honour their chosen visibility (public / logged-in / hidden) and never expose personal contact details publicly.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Matching, alerts & CRM', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Connecting the right people, and keeping them informed — only if they opt in.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'Matching scores candidates on shared categories, distance, availability, engagement basis, rate and trust.', 'shuffles-social-services-jobs' ),
						__( 'Email alerts (job matches, new candidates, saved searches) are opt-in and sent on a daily schedule.', 'shuffles-social-services-jobs' ),
						__( 'Ticking a program (e.g. NDIS) can keep the matching CRM tags/lists in sync, logged per member.', 'shuffles-social-services-jobs' ),
					),
				),
				array(
					'title' => __( 'Front page & growth', 'shuffles-social-services-jobs' ),
					'intro' => __( 'Lead with safety; show numbers only once they’re impressive.', 'shuffles-social-services-jobs' ),
					'rules' => array(
						__( 'The hero banner carries a “Safety, built in” strip summarising the privacy & verification guardrails.', 'shuffles-social-services-jobs' ),
						__( 'Counters can be set to stay hidden until each total reaches a chosen minimum — so small early numbers don’t show until the marketplace has grown.', 'shuffles-social-services-jobs' ),
						__( 'Any lookup that takes a few seconds shows the branded Shuffles spinner.', 'shuffles-social-services-jobs' ),
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
				__( 'Never let funding filter results down to zero — it’s a signal, not a gate.', 'shuffles-social-services-jobs' ),
				__( 'Never email a provider about an NDIS-registration change — that alert goes to staff only.', 'shuffles-social-services-jobs' ),
				__( 'Never overwrite stored NDIS details when a check fails — keep them and alert staff.', 'shuffles-social-services-jobs' ),
				__( 'Never name the third-party AI/search vendor in anything members or the public can see.', 'shuffles-social-services-jobs' ),
				__( 'Never lock a site by default — monetisation stays off until it’s switched on.', 'shuffles-social-services-jobs' ),
			)
		);
	}
}
