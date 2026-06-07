<?php
/**
 * Testing worksheet — a structured tester checklist (single source of truth) covering every feature.
 * Rendered by the [sssj_tests] shortcode and the Settings → Testing tab. Keep suites() CURRENT on
 * every ship: when a feature changes, add/adjust its case here.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Tests {

	/**
	 * Test suites: each [ 'title', 'cases' => [ [ id, do, expect ], ... ] ]. Filterable.
	 *
	 * @return array
	 */
	public static function suites() {
		$s = array(
			array(
				'title' => __( 'Job ads & segregated boards', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'job-tfn', 'do' => __( 'As an advertiser, post a job with basis = TFN (employee).', 'shuffles-social-services-jobs' ), 'expect' => __( 'It shows on the Jobs board and the TFN board, and NEVER on the ABN board.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'job-abn', 'do' => __( 'Post a job with basis = ABN and a valid 11-digit ABN.', 'shuffles-social-services-jobs' ), 'expect' => __( 'ABN passes validation; it shows on the ABN board, never the TFN board. A bad ABN is rejected.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'abr-record', 'do' => __( 'Set an ABR Web Services GUID in Settings → Compliance, then save an organisation (or worker) with a real ABN; reopen the form.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A read-only “Australian Business Register — recorded details” field shows the full register response: entity name, trading/business names, status, type, ACN, GST and location. The organisation’s public profile shows the same record. Without a GUID, nothing is recorded (offline checksum only).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'menu-repoint', 'do' => __( 'Open the header menu and click “My dashboard” and “Jobs”.', 'shuffles-social-services-jobs' ), 'expect' => __( '“My dashboard” lands on the all-in-one dashboard hub and “Jobs” lands on our jobs board (the page actually running the shortcode) — not a legacy/old page, even if one was previously mapped.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'job-search', 'do' => __( 'On the Jobs board, search a keyword and pick a category.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Results filter to matching jobs; the count updates.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'filter-ajax', 'do' => __( 'On any board (Jobs, Employee, Contractor, Worker, Organisations, Participant requests) change the search box, a filter, the radius, or click a pagination link.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Only the result tiles refresh in place (the Shuffles spinner shows briefly) — the whole page does NOT reload, the view does not jump to the top, and the cursor stays in the search box. The address bar still updates so the filtered view can be shared.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'smart-search', 'do' => __( 'On the Jobs (and Worker / Organisations) board, search a term using different words than the listing — e.g. “support work” for a “Disability Support Worker” role, “OT” for an occupational therapist, or “aged care” for a “home care” listing.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Related listings still appear — the search matches sector synonyms (broader, never narrower), so a sensible search does not come back empty.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'job-funding', 'do' => __( 'Set funding source(s) on a job (posting form), then on the Jobs board tick the funding chips (NDIS / Aged Care / DVA / …).', 'shuffles-social-services-jobs' ), 'expect' => __( 'Results narrow to jobs matching the ticked funding (any of them); un-ticking widens again.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'job-radius', 'do' => __( 'Type a suburb in the location field and set a radius (with no Google key set).', 'shuffles-social-services-jobs' ), 'expect' => __( 'Results narrow to jobs within the radius, nearest first (keyless geocoding).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'job-expire', 'do' => __( 'Set a job’s “Closes on” date in the past and wait for the daily cron (or run it).', 'shuffles-social-services-jobs' ), 'expect' => __( 'The job moves to draft and drops off the board.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'job-logo', 'do' => __( 'Attach a job to an organisation and leave its logo blank; then post another job and upload a logo.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The first job shows its organisation’s logo on the board card; the second shows its own uploaded logo (overrides the org’s).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'job-map', 'do' => __( 'Open a single job that has a location.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A Google map of the job’s suburb/town shows on the page (suburb level, no exact address). Works without a Google Maps API key.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Applying & messaging', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'apply', 'do' => __( 'As a logged-in member, apply to a job with a cover note.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Application is recorded once (no duplicates); a message thread to the advertiser is started.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'apply-abn-gate', 'do' => __( 'Try to respond to a participant request / ABN task without a recorded ABN.', 'shuffles-social-services-jobs' ), 'expect' => __( 'You are blocked until a valid ABN is on file.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'msg-relay', 'do' => __( 'Open Messages, reply within a thread.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Reply sends; no email address is exposed; participants appear by pseudonym; non-parties can’t see the thread.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'app-status', 'do' => __( 'As the advertiser, change an applicant’s status to Offer in My dashboard.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Status saves; the applicant sees it; it counts toward the org “placed” total.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Workers', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'worker-profile', 'do' => __( 'Create a worker profile; add Services via the search-and-add picker; set a location; choose visibility.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Profile saves; services show as pills; location geocodes; one profile per user.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'worker-place-ac', 'do' => __( 'On the worker profile, start typing a suburb in “Your location” and pick a suggestion (needs a Google Maps API key set in Settings → Integrations).', 'shuffles-social-services-jobs' ), 'expect' => __( 'An autocomplete dropdown appears; picking a place fills Suburb / State / Postcode and records the lat/long, so the profile is found by location and radius.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'board-place-map', 'do' => __( 'On the Jobs board, pick a location from the “Near a suburb…” autocomplete.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The results map recenters/zooms to that place and the result tiles refresh via AJAX to that area (a default radius is applied). No full-page reload.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'worker-find', 'do' => __( 'On the Worker directory, search + tick “Available now” + set a location and radius.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Only available, in-radius workers show, nearest first.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'worker-vis', 'do' => __( 'Set a worker profile to “Logged-in members” and view the directory as a guest.', 'shuffles-social-services-jobs' ), 'expect' => __( 'That profile is hidden from guests but visible to logged-in members.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'mask-rate', 'do' => __( 'On a worker profile (Privacy section) tick “Pay rate” as members-only and set a rate; then open the worker’s page and the directory while logged out, and again while logged in.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Logged out, the rate shows a “🔒 Log in to view” lock on both the profile and the card; logged in (or as the owner / an admin) the real rate shows. The profile still appears in the directory either way.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'worker-photo', 'do' => __( 'Add a profile photo and a couple of gallery photos to a worker profile; save and open the worker’s page.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The headshot shows on the card and profile; gallery photos appear as a swipeable strip.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'worker-single', 'do' => __( 'Open a worker’s public profile page.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Full details render below the bio: availability, services, rate, location, languages/culture, verified checks and any custom fields (not a blank page).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'worker-travel', 'do' => __( 'Set “How far are you willing to travel? (km)” on a worker profile and save.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The profile shows “Willing to travel: up to X km”, and the Jobs board defaults the radius to that distance for that worker.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'worker-hidden', 'do' => __( 'Set a worker profile to “Do not display”, then check the directory and the page source.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The profile is removed from the directory entirely and its page is noindex.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Participants (privacy-critical)', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'need-post', 'do' => __( 'Post a participant support request.', 'shuffles-social-services-jobs' ), 'expect' => __( 'It is held for admin moderation; once approved it shows only a pseudonym + suburb — never a name or contact detail.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'need-gate', 'do' => __( 'Open the Participant requests board as a logged-out guest.', 'shuffles-social-services-jobs' ), 'expect' => __( 'You are prompted to log in; no requests are shown.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'need-noindex', 'do' => __( 'View the page source / robots of a participant request.', 'shuffles-social-services-jobs' ), 'expect' => __( 'It is noindex and excluded from sitemaps.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'need-details', 'do' => __( 'On a participant request card, click “View full request”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The full request details expand in place (when, gender preference, full description) — no separate page, so privacy is preserved.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'need-seeking', 'do' => __( 'Post a participant request and choose “What are you seeking?” (ongoing support / one-off task / a provider).', 'shuffles-social-services-jobs' ), 'expect' => __( 'The choice is saved and shown as a badge on the request card (e.g. “Seeking a provider”).', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Organisations', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'org-profile', 'do' => __( 'Create an org profile: logo, social links, sectors + funding (search-and-add), multiple locations.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Saves; logo + social icons + sector/funding pills appear on the profile and card.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-find', 'do' => __( 'On the Organisations directory, filter by sector, funding, location/radius, and “Only with open placements”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Results match all filters; multi-location orgs match if ANY site is in range.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'mask-org', 'do' => __( 'On an organisation profile (Privacy section) tick “Phone number” and/or “Website” as members-only; then open the org page while logged out and while logged in.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Logged out, the chosen field shows a “🔒 Log in” lock; logged in (or as the owner / an admin) the real phone / website button shows. NDIS-register details are never affected.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'team-add', 'do' => __( 'As an organisation owner, open the dashboard “Team” tab (or a page with [sssj_org_team]) and add a team member by the email or username of another existing account; try both “Member” and “Admin” roles. Then try an email with no account.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The existing person is added with the chosen role and appears in the roster. An unknown email is refused with a note to have them sign up first — no account is ever created.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'team-manage', 'do' => __( 'As an org admin, change a member’s role and then remove a member; also log in AS a promoted “Admin” member and confirm they can manage the team; finally try to remove or demote the owner.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Role changes and removals apply (removal only unlinks — the account still exists). An admin member can manage the team. The owner can never be removed or demoted.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'team-join', 'do' => __( 'As a different logged-in member, open an organisation’s profile and click “Request to join” (add a message). Then, as that org’s admin, open My dashboard → Team and Approve (or Decline) the request.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The requester sees “Request to join pending” (and can cancel). The admin sees it under “Requests to join” with a count on the Team tab; Approve adds them to the team (as Member or Admin), Decline removes it. Nobody joins without admin approval.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-counters', 'do' => __( 'Check an org card’s counters.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Shows current open jobs and total placed (all-time Offers).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-jobs', 'do' => __( 'Attach a job to an org, open the org page.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The org page lists its open positions (browse jobs by company) + Organization structured data.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-hidden', 'do' => __( 'Tick “Do not display” on an org profile, then check the Organisations directory and the page source.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The org drops off the directory and its page is noindex with no Organization structured data.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-ndis', 'do' => __( 'On an org profile tick “We are a registered NDIS provider” and enter a registration number.', 'shuffles-social-services-jobs' ), 'expect' => __( 'An “🛡️ NDIS Registered · #number” badge links to the NDIS Commission register on the card + profile; status/groups show when set (admin or auto-scan hook).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-seed-fields', 'do' => __( 'Settings → Profile Fields → “Add recommended provider fields”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A Shuffles-style organisation field set is added (specialisations, Services Provided, service delivery, ages supported, accepting clients, accessibility, languages, years operating, accreditations, Peak Bodies); the banner-flagged ones (incl. Services Provided + Peak Bodies) become filters on the Organisations directory and appear on org profiles. Re-running only adds fields that don’t already exist.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-autofill', 'do' => __( 'On the organisation form enter your website URL and click “Fetch details from my website”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Empty name / description / phone fields are pre-filled from your site for review (only empty fields are touched).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-category', 'do' => __( 'On the organisation form choose an “Organisation type” (Support provider / Supplier / SDA-housing / Real estate / Professional services / Other) and save; then filter the Organisations directory by that type.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The category saves, shows as a badge on the card + profile, and the “All organisation types” directory filter narrows to it.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-sponsored', 'do' => __( 'As an admin, edit an organisation and tick “Sponsored placement” in the Verification box; view the directory.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The org sorts to the top of the directory and shows a “★ Sponsored” badge on its card + profile; un-ticking removes both. (Admin-only — members can’t self-set it.)', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Compliance & verification', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'cred-upload', 'do' => __( 'As a worker, add a credential (e.g. WWCC) with an evidence PDF.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Saved as Pending; the evidence file has no public URL.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cred-verify', 'do' => __( 'As an admin, open Jobs & Engagements → Verification, view the evidence, Approve it.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The worker gains the ✓ Verified badge (and the verified checks show on their card).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cred-serve', 'do' => __( 'Copy an evidence link and open it while logged out (or as a different non-admin).', 'shuffles-social-services-jobs' ), 'expect' => __( 'Access is denied — only the owner or an admin can open it.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cred-expire', 'do' => __( 'Set a verified credential’s expiry in the past; run the daily cron.', 'shuffles-social-services-jobs' ), 'expect' => __( 'It expires, the badge drops, and a reminder email is sent.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'blue-tick', 'do' => __( 'As an admin, edit a worker or organisation, tick “Account verified (blue tick)” in the Verification box and update; then view the directory and profile.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A blue tick shows next to the name on cards and the profile (and on jobs from a verified org). Un-ticking removes it. It is separate from the green ✓ Verified credential badge.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'abr-check', 'do' => __( 'Enter an ABR GUID (Settings → Compliance), then post a job (ABN basis) or organisation with a valid ABN.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The ABN is checked against the Australian Business Register on save; the card/profile shows an “🏢 ABR Active · <Entity Name>” badge. Without a GUID, only the offline checksum runs.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-abn-required', 'do' => __( 'Try to save an organisation profile without an ABN (or with an invalid one).', 'shuffles-social-services-jobs' ), 'expect' => __( 'It is rejected — organisations (non-TFN businesses) require a valid 11-digit ABN. TFN job ads never ask for an ABN.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'ndis-lookup', 'do' => __( 'Edit a registered organisation, enter its NDIS Registration No (the number after ?id= in its provider-register URL, e.g. 902439) and save; then view the org profile.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A “NDIS provider registration” table shows the live Registration status, the approved registration groups and the “in force until” date, with a link to the Commission register and a “Last checked” date. (Reads the NDIS Commission’s own public listing.)', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'ndis-rescan', 'do' => __( 'As an admin, open that org profile and click “Re-check NDIS register now”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The branded Shuffles spinner shows briefly, then the table refreshes from the live listing. The monthly background check (Settings → Compliance toggle) re-reads every registered org and emails the change-alert address only when status, groups, or the expiry date change — never the provider.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'ndis-soletrader', 'do' => __( 'On a worker profile, tick “I’m an NDIS-registered sole trader” and enter the NDIS Registration No (e.g. 902439); save, then view the worker’s public profile.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The same NDIS registration table (status, groups, expiry, register link, last checked) shows on the individual’s profile, and the listing is included in the monthly auto-check.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'ndis-scannow', 'do' => __( 'On the organisation or worker form, type an NDIS Registration No and click “Scan now”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The Shuffles spinner shows, then the live legal name, status, ABN, head office, website, registration groups and expiry appear inline — without saving the form. A bad number shows a friendly error.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'ndis-abn-mismatch', 'do' => __( 'Enter an ABN on the form that differs from the ABN on the NDIS listing, then Scan now (or save and view the profile).', 'shuffles-social-services-jobs' ), 'expect' => __( 'A red warning note appears saying the register ABN differs from the ABN on file.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'ndis-outlets', 'do' => __( 'Scan a registered provider that lists outlets (e.g. 902439).', 'shuffles-social-services-jobs' ), 'expect' => __( 'The outlets and their phone number(s) appear (read from the register footer); these are read-only and have no editable field.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'ndis-revoked-red', 'do' => __( 'Scan a provider whose status is Revoked/Banned (or set the stored status to “Revoked”).', 'shuffles-social-services-jobs' ), 'expect' => __( 'The status badge shows on a RED background, not green — on both the profile table and the Scan-now preview.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'spinner', 'do' => __( 'Save an organisation or worker profile, and use the website “Fetch details” button.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A branded Shuffles spinner (site logo if set, else a blue ring) overlays the form while the few-second lookups run.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Monetisation', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'mon-off', 'do' => __( 'With monetisation OFF, post jobs and respond.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Everything is free / ungated.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'mon-cap', 'do' => __( 'Turn monetisation ON; set a free-listing cap; post beyond it without a subscription.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Posting is blocked with an upgrade prompt; a subscriber posts unlimited + gets featured placement.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'mon-participant-free', 'do' => __( 'Turn monetisation ON, then as a member whose role is Participant (only) post a direct job for a worker.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Posting is allowed free — participants employing directly or seeking workers/providers are never charged, even when monetisation is on. (A participant who is also a provider follows the provider rules.)', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'mon-directory-fee', 'do' => __( 'Turn monetisation ON, then save an organisation as a member with no advertiser/provider subscription, and view the Organisations directory.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The org is not listed in the directory until the provider holds a listing subscription (or is an admin); with monetisation OFF every org lists for free.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Hats / roles & onboarding', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'roles-pick', 'do' => __( 'Put [sssj_roles] on a page (or open the “My roles” tab in [sssj_dashboard]); as a logged-in member tick hats from the grouped picker (I offer work / I’m looking for work / I need support) — e.g. Employer/company AND Available for contracting — and save.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Hats save (confirmation shows), persist on reload, and the matching capabilities are granted (employer → post jobs + org; contractor → worker profile).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'roles-dashboard', 'do' => __( 'After setting hats, reopen the dashboard.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The dashboard reveals only the sections matching your hats — an employer sees My listings & applicants + Post a job / organisation; a contractor sees My profile + Matched jobs + Credentials; a member with both sees both, in one place. A member with no hats yet still sees everything (capability fallback).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'roles-guest', 'do' => __( 'Open the [sssj_roles] page while logged out.', 'shuffles-social-services-jobs' ), 'expect' => __( 'You are prompted to log in; no role form is shown.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'onboard', 'do' => __( 'Put [sssj_onboard] on a “Get started” page; as a new member tick some hats and Continue.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Hats save, then tailored next-step buttons appear (set up profile / create organisation / post a job / request support / go to dashboard) matching the chosen hats.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'provider-size', 'do' => __( 'Edit an organisation, set a Size + Legal structure, save; then open the Organisations directory and use the “Any size” / “Any structure” filters.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The size + structure show as badges on the card/profile and filter the directory (e.g. Size = Sole trader narrows to sole traders).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'sole-trader-dir', 'do' => __( 'As a member with the contractor hat, create an organisation/provider listing.', 'shuffles-social-services-jobs' ), 'expect' => __( 'It defaults to size = Sole trader and appears in the Organisations/Providers directory (filterable as Sole trader) alongside your worker profile.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Accessibility / CALD + appearance', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'cald-lang', 'do' => __( 'Switch the language in the toolbar.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Tagged UI labels translate; right-to-left languages flip layout; the choice persists site-wide.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cald-english', 'do' => __( 'While in another language, hit the “English Hot Key”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The interface returns to English (button shows only when not in English).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cald-modes', 'do' => __( 'Toggle High contrast / No colour / Larger text / Read aloud / Voice input.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Each mode applies and persists; modals are not trapped.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cald-langpill', 'do' => __( 'Look at the language picker in the accessibility bar.', 'shuffles-social-services-jobs' ), 'expect' => __( 'It is a compact rounded pill sized to its text (not a full-width box), matching the other accessibility controls.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'studio', 'do' => __( 'In Settings → Appearance, change colours and a preset; watch the live preview; Save.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Preview updates live; saved styles apply on the front end; the theme is untouched.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Navigation, selects & responsive', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'menu', 'do' => __( 'View [sssj_menu] logged out, then logged in.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Logged-out shows browse + Log in/Register; logged-in shows dashboard/messages/log out + capability links.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'auto-menu', 'do' => __( 'In Settings → General, tick “Show navigation menu at the top of every page”, save, and open any front-end page.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The navigation bar appears at the very top of every page; un-ticking removes it.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'dashboard', 'do' => __( 'Put [sssj_dashboard] on a page; view it logged in as a worker, then as an advertiser.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A tabbed hub shows only relevant tabs (Overview always; My listings for advertisers; Matched jobs + Credentials for workers; Saved searches; Messages). Tabs switch panels; with JS off all sections still show.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'pills', 'do' => __( 'Open any form/filter with a dropdown or multi-pick.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Single selects are searchable; multi-picks show removable pills (no bare checkbox grids).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'dynamic-filter', 'do' => __( 'On any directory, change a category, tick a chip or drag the radius — without clicking any button.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Results update automatically (no “Filter” button needed). Typing in the search box updates shortly after you stop.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'clear-all', 'do' => __( 'With several filters applied, click “Clear all”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Every filter resets and the full list returns.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'use-my-location', 'do' => __( 'Click “Use my location” next to the location field and allow the browser prompt.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A default radius is applied and results re-sort to nearest first; denying access shows a friendly message.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'distance-pill', 'do' => __( 'Search any directory with a location (type a suburb or use my location).', 'shuffles-social-services-jobs' ), 'expect' => __( 'Each result card shows a highlighted “X km away” pill (nearest location for organisations).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'map-markers', 'do' => __( 'With a Google Maps API key set, single-click a map marker, then double-click it.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Single click opens an info box (summary + View link); double click scrolls to that result’s card and surrounds it with an animated rainbow “tracer” highlight.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'mobile', 'do' => __( 'Open the boards, directories and forms on a phone (or a narrow window).', 'shuffles-social-services-jobs' ), 'expect' => __( 'Filter controls stack full-width, cards go single-column, nothing overflows; it looks professional.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'readme', 'do' => __( 'On each directory, click “Read me — things to know”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A collapsible note opens with tips specific to that directory (jobs / workers / organisations / participant requests); click again to close.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Custom profile fields & CRM sync', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'field-add', 'do' => __( 'In Settings → Profile Fields, add a multi-select field (e.g. “Programs” with options NDIS, Aged Care, DVA), shown on Workers.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The field is listed; on the worker profile form it appears as a searchable multi-select pill picker and saves.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'field-banner', 'do' => __( 'Tick “show on banner filters” for a select field; edit and delete a field.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Edit pre-fills the form; delete removes the field (saved values are untouched).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'field-filter', 'do' => __( 'With a banner-filter select field set, give two workers different values, then use that filter on the worker directory.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The directory shows a searchable “All <field>” filter; choosing a value narrows results to matching profiles; “Clear all” resets it. Works on the worker, organisation and participant-request directories.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'crm-map', 'do' => __( 'In Settings → CRM Sync, enable sync, then map a value (e.g. Funding: NDIS) to a FluentCRM tag and/or list.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The tag/list pickers list your FluentCRM tags & lists (searchable); the mapping saves.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'crm-apply', 'do' => __( 'As a member, choose that mapped value on your profile and save; then un-choose it and save.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Choosing attaches the mapped tag/list to your FluentCRM contact; un-choosing removes it. Each change shows in the per-user log (Users → edit, and Settings → CRM Sync).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'crm-missing', 'do' => __( 'Delete a mapped tag in FluentCRM, then save a profile that uses it.', 'shuffles-social-services-jobs' ), 'expect' => __( 'An admin alert appears (and a “missing” log entry) so you can re-point the mapping; nothing breaks the save.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Front-page display & animations', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'disp-hero', 'do' => __( 'Put [sssj_hero title="…" button_text="Browse jobs" button_url="/jobs" button2_text="Find a worker" button2_url="/find-a-worker" button3_text="Request support" button3_url="/request-support"] on a page.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A gradient hero banner fades in with the headline, up to FOUR call-to-action buttons (first = primary, rest = outline), and a “🛡️ Safety, built in” strip. safety="off" hides the strip.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-stats', 'do' => __( 'Add [sssj_stats] and scroll it into view; then try [sssj_stats min="25"].', 'shuffles-social-services-jobs' ), 'expect' => __( 'The counters animate up to the live totals. With min="25", any counter below 25 is hidden until the real total reaches it (so small early numbers stay hidden).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-recent', 'do' => __( 'Add [sssj_recent type="jobs" count="6"] and [sssj_recent type="orgs" layout="list"].', 'shuffles-social-services-jobs' ), 'expect' => __( 'Recent cards reveal with a staggered fade; list layout is a compact sidebar list. Participant requests only show to logged-in members.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-featured', 'do' => __( 'Add [sssj_featured]. Promote a job (mark it featured), give it a description, and reload.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Featured roles show with a subtle shine and a short ~40-character teaser from the role’s description under the rate; with none promoted it falls back to the newest jobs.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-whyus', 'do' => __( 'Create a page via Settings → Pages → “Why us (benefits)” (or put [sssj_why_us] on a page) and open it. Then try [sssj_why_us layout="carousel" per_row="3"], [sssj_why_us per_row="2" rows="2"], and font="brand".', 'shuffles-social-services-jobs' ), 'expect' => __( 'A point-form benefits list renders, each with an icon, heading and blurb. layout="carousel" gives a horizontal scrolling row (3 in view); per_row/rows control columns and how many show; font="theme" (default) matches the page font, font="brand" uses the plugin font. Grids collapse to one column on mobile.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'whyus-edit', 'do' => __( 'In Settings → Pages → “Why us — benefit points”, edit a line (icon | Heading | Blurb), add a new line, save, then reload the Why us page. Then clear the box entirely and save.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The Why us page reflects your edited/added points in order. Clearing the box restores the built-in default points.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-join', 'do' => __( 'Create a page via Settings → Pages → “Join (welcome / get started)” (or put [sssj_join] on a page) and open it logged out.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A welcome page shows a “Let’s get you onboarded” button (to the onboarding page), Create-account + Log-in buttons, and quick links to browse jobs / find a worker / organisations.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-hero-quotes', 'do' => __( 'Put [sssj_hero] with four button pairs (button_text/url … button4_text/url) into a page in the block editor and view it.', 'shuffles-social-services-jobs' ), 'expect' => __( 'All four buttons appear (not just the first) — the editor’s smart-quote curling no longer breaks the later attributes.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-motion', 'do' => __( 'Turn on the OS “reduce motion” setting and reload.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Animations are disabled — content appears immediately with final numbers.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'elementor', 'do' => __( 'With Elementor active, edit a page and open the “Shuffles Jobs” widget category; drag in Hero / Stats / Featured / Recent / Menu and set their controls.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Each widget renders the matching block with your settings, both in the editor preview and on the live page.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Email alerts', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'alert-worker', 'do' => __( 'On a worker profile tick “Email me when new jobs match my profile”, then (Settings → Email Alerts) click “Run alerts now” after a matching job is posted.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The worker receives an email digest of new matching jobs. Nothing is sent if they did not opt in.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'alert-advertiser', 'do' => __( 'When posting a job tick “Email me when new candidates match this job”; add a matching worker, then run alerts.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The advertiser receives an email digest of new matching candidates.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'alert-saved', 'do' => __( 'On a directory, set a filter and click “Save & alert me”; add a new matching listing, then run alerts. Manage via [sssj_saved_searches].', 'shuffles-social-services-jobs' ), 'expect' => __( 'The search is saved; an email arrives with new matching listings; Remove deletes the saved search.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'alert-master', 'do' => __( 'Turn the master “Enable email alerts” off and run alerts.', 'shuffles-social-services-jobs' ), 'expect' => __( 'No emails are sent; opt-ins are remembered for when it is turned back on.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Smart matching', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'match-job', 'do' => __( 'Open a job that has a service category and a location.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A “Workers who may suit this role” panel lists ranked workers, each with a short reason (shared services, distance, available, has ABN).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'match-worker', 'do' => __( 'Open a worker profile that has services + a location.', 'shuffles-social-services-jobs' ), 'expect' => __( 'An “Open roles this worker may suit” panel lists ranked jobs with reasons.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'match-me', 'do' => __( 'As a logged-in worker, put [sssj_matches] on a page.', 'shuffles-social-services-jobs' ), 'expect' => __( '“Jobs matched to you” lists ranked jobs for your profile; logged-out or no-profile shows a prompt.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'match-abn', 'do' => __( 'Compare matches for an ABN job vs a TFN job.', 'shuffles-social-services-jobs' ), 'expect' => __( 'ABN-holding workers rank above those without an ABN for the ABN role; the basis is noted in the reasons.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Guides & help', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'guides-show', 'do' => __( 'Put [sssj_guides] on a page (or open Settings → Guides) and click each guide header.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Four guides show (write a job post, respond to a job, ABN contractor, standing profile); the first is open; clicking expands/collapses each.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'guides-only', 'do' => __( 'Use [sssj_guides only="respond-to-job"].', 'shuffles-social-services-jobs' ), 'expect' => __( 'Only the chosen guide renders.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Dashboard & operations', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'dash-profile', 'do' => __( 'Open [sssj_dashboard] as a logged-in member and click the “My profile” tab.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Your personal worker/candidate profile editor is embedded right there, with buttons to manage an organisation or a participant support request, and a tip pointing to the “My roles” tab.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'form-enhance', 'do' => __( 'Open the Create worker profile page and scroll/fill it in.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Fields are grouped into titled section cards (each with a tasteful left accent border); a completeness meter updates as you fill fields; the “available for work” checkbox shows as a pill toggle (and still submits); the About box has a character counter; file buttons are styled and the profile photo previews; a sticky Save bar appears on scroll; Ctrl+S saves; the tab title shows a ● when there are unsaved changes. Tom Select pickers, the suburb autocomplete, NDIS “Scan now” and form submission all still work.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'swipe', 'do' => __( 'Put [sssj_swipe] on a page; swipe/drag the top card right and left, use the ✕/♥/↺ buttons and the ← → arrow keys.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Providers show one card at a time; right saves to your shortlist (toast confirms; stored when logged in), left skips, ↺ / U undoes; tapping “View profile” opens the org page; when the deck empties a shortlist summary shows.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cron-tab', 'do' => __( 'As an admin, open Settings → Cron Job List & Status.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Every plugin background job is listed (daily maintenance, daily email alerts, monthly NDIS check) with its frequency, last run, next run due and status. “Run now” triggers a job; after the monthly NDIS check the row shows e.g. “12 checked, 1 could not be read”.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cron-status', 'do' => __( 'Click “Run now” on a job, then reload the tab.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The “Last run” time updates and Status shows “Completed OK”. A job that starts but fatals mid-run would instead show “Did not complete”.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'provider-import-preview', 'do' => __( 'Settings → Provider Import (beta): choose “Active providers”, upload the NDIS CSV, Upload & preview.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A preview shows the row count, the auto-detected column mapping and a sample of parsed rows — with NOTHING written (proof of concept is preview-only; no organisations are created).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'logic-tab', 'do' => __( 'Open Settings → Business Logic.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A plain-English list of the plugin’s business rules renders (roles, ABN/TFN, who pays, verification, NDIS, privacy, matching) plus a numbered “Key invariants (never rules)” list.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'menu-editprofile', 'do' => __( 'Put [sssj_menu] on a page and view it logged in.', 'shuffles-social-services-jobs' ), 'expect' => __( 'An “Edit my profile” link appears (opening the worker/candidate profile editor); it is absent when logged out.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'menu-admin-settings', 'do' => __( 'View [sssj_menu] as an administrator and hover/focus “My dashboard”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A “Settings” sub-item drops down (links to the plugin settings page). Non-admins never see it.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'nav-sync', 'do' => __( 'Settings → Pages → “Header menu” → Create header menu; then open Appearance → Menus.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A “Jobs & Engagements” menu exists with the public items (Jobs, Find a worker, Organisations, Participant requests, Post a job, My dashboard, Log in, Register). Re-syncing after a change keeps it current; menu items you add by hand are left untouched.', 'shuffles-social-services-jobs' ) ),
				),
			),
		);
		return apply_filters( 'shuffles_ssj_test_suites', $s );
	}

	/** Total number of test cases. */
	public static function count_cases() {
		$n = 0;
		foreach ( self::suites() as $suite ) {
			$n += count( $suite['cases'] );
		}
		return $n;
	}

	/**
	 * Render the interactive worksheet (progress saved per browser via localStorage).
	 *
	 * @param array $atts title (optional), version (optional).
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$title   = ! empty( $atts['title'] ) ? $atts['title'] : __( 'Testing worksheet', 'shuffles-social-services-jobs' );
		$version = defined( 'SHUFFLES_SSJ_VERSION' ) ? SHUFFLES_SSJ_VERSION : '';
		ob_start();
		?>
		<div class="sssj sssj--tests" data-sssj-tests data-tests-version="<?php echo esc_attr( $version ); ?>">
			<div class="sssj-panel">
				<h2 style="margin-top:0"><?php echo esc_html( $title ); ?> <?php if ( $version ) : ?><span class="sssj-badge"><?php echo esc_html( 'v' . $version ); ?></span><?php endif; ?></h2>
				<p class="description"><?php esc_html_e( 'Give this to a tester. Work through each case, mark Pass or Fail. Progress is saved in your browser; use Print for a paper copy or a PDF.', 'shuffles-social-services-jobs' ); ?></p>
				<div class="sssj-tests__bar">
					<strong data-tests-progress>0 / <?php echo esc_html( (string) self::count_cases() ); ?></strong>
					<span class="sssj-tests__track"><span class="sssj-tests__fill" data-tests-fill></span></span>
					<button type="button" class="sssj-btn sssj-btn--ghost sssj-btn--sm" data-tests-reset><?php esc_html_e( 'Reset', 'shuffles-social-services-jobs' ); ?></button>
					<button type="button" class="sssj-btn sssj-btn--secondary sssj-btn--sm" onclick="window.print()"><?php esc_html_e( 'Print', 'shuffles-social-services-jobs' ); ?></button>
				</div>
			</div>
			<?php foreach ( self::suites() as $suite ) : ?>
				<div class="sssj-panel">
					<h3 style="margin-top:0"><?php echo esc_html( $suite['title'] ); ?></h3>
					<table class="sssj-tests__table">
						<?php foreach ( $suite['cases'] as $c ) : ?>
							<tr data-test-id="<?php echo esc_attr( $c['id'] ); ?>">
								<td class="sssj-tests__do">
									<strong><?php echo esc_html( $c['do'] ); ?></strong>
									<div class="sssj-tests__expect"><?php echo esc_html__( 'Expected:', 'shuffles-social-services-jobs' ) . ' ' . esc_html( $c['expect'] ); ?></div>
								</td>
								<td class="sssj-tests__marks">
									<button type="button" class="sssj-btn sssj-btn--sm sssj-tests__pass" data-mark="pass"><?php esc_html_e( 'Pass', 'shuffles-social-services-jobs' ); ?></button>
									<button type="button" class="sssj-btn sssj-btn--sm sssj-tests__fail" data-mark="fail"><?php esc_html_e( 'Fail', 'shuffles-social-services-jobs' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
