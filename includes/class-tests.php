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
					array( 'id' => 'job-search', 'do' => __( 'On the Jobs board, search a keyword and pick a category.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Results filter to matching jobs; the count updates.', 'shuffles-social-services-jobs' ) ),
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
					array( 'id' => 'worker-find', 'do' => __( 'On the Worker directory, search + tick “Available now” + set a location and radius.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Only available, in-radius workers show, nearest first.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'worker-vis', 'do' => __( 'Set a worker profile to “Logged-in members” and view the directory as a guest.', 'shuffles-social-services-jobs' ), 'expect' => __( 'That profile is hidden from guests but visible to logged-in members.', 'shuffles-social-services-jobs' ) ),
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
					array( 'id' => 'org-counters', 'do' => __( 'Check an org card’s counters.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Shows current open jobs and total placed (all-time Offers).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-jobs', 'do' => __( 'Attach a job to an org, open the org page.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The org page lists its open positions (browse jobs by company) + Organization structured data.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-hidden', 'do' => __( 'Tick “Do not display” on an org profile, then check the Organisations directory and the page source.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The org drops off the directory and its page is noindex with no Organization structured data.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-ndis', 'do' => __( 'On an org profile tick “We are a registered NDIS provider” and enter a registration number.', 'shuffles-social-services-jobs' ), 'expect' => __( 'An “🛡️ NDIS Registered · #number” badge links to the NDIS Commission register on the card + profile; status/groups show when set (admin or auto-scan hook).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-seed-fields', 'do' => __( 'Settings → Profile Fields → “Add recommended provider fields”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A Shuffles-style organisation field set is added (specialisations, service delivery, ages supported, accepting clients, …); the banner-flagged ones become filters on the Organisations directory and appear on org profiles.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'org-autofill', 'do' => __( 'On the organisation form enter your website URL and click “Fetch details from my website”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Empty name / description / phone fields are pre-filled from your site for review (only empty fields are touched).', 'shuffles-social-services-jobs' ) ),
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
				),
			),
			array(
				'title' => __( 'Monetisation', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'mon-off', 'do' => __( 'With monetisation OFF, post jobs and respond.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Everything is free / ungated.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'mon-cap', 'do' => __( 'Turn monetisation ON; set a free-listing cap; post beyond it without a subscription.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Posting is blocked with an upgrade prompt; a subscriber posts unlimited + gets featured placement.', 'shuffles-social-services-jobs' ) ),
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
					array( 'id' => 'disp-hero', 'do' => __( 'Put [sssj_hero title="…" button_text="Browse jobs" button_url="/jobs"] on a page.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A gradient hero banner fades in with the headline and call-to-action button(s).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-stats', 'do' => __( 'Add [sssj_stats] and scroll it into view.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The counters animate up from zero to the live totals (open jobs, available workers, organisations, people placed).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-recent', 'do' => __( 'Add [sssj_recent type="jobs" count="6"] and [sssj_recent type="orgs" layout="list"].', 'shuffles-social-services-jobs' ), 'expect' => __( 'Recent cards reveal with a staggered fade; list layout is a compact sidebar list. Participant requests only show to logged-in members.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'disp-featured', 'do' => __( 'Add [sssj_featured]. Promote a job (mark it featured) and reload.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Featured roles show with a subtle shine; with none promoted it falls back to the newest jobs.', 'shuffles-social-services-jobs' ) ),
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
