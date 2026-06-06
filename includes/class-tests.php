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
					array( 'id' => 'job-radius', 'do' => __( 'Type a suburb in the location field and set a radius (with no Google key set).', 'shuffles-social-services-jobs' ), 'expect' => __( 'Results narrow to jobs within the radius, nearest first (keyless geocoding).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'job-expire', 'do' => __( 'Set a job’s “Closes on” date in the past and wait for the daily cron (or run it).', 'shuffles-social-services-jobs' ), 'expect' => __( 'The job moves to draft and drops off the board.', 'shuffles-social-services-jobs' ) ),
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
					array( 'id' => 'worker-hidden', 'do' => __( 'Set a worker profile to “Do not display”, then check the directory and the page source.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The profile is removed from the directory entirely and its page is noindex.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Participants (privacy-critical)', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'need-post', 'do' => __( 'Post a participant support request.', 'shuffles-social-services-jobs' ), 'expect' => __( 'It is held for admin moderation; once approved it shows only a pseudonym + suburb — never a name or contact detail.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'need-gate', 'do' => __( 'Open the Participant requests board as a logged-out guest.', 'shuffles-social-services-jobs' ), 'expect' => __( 'You are prompted to log in; no requests are shown.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'need-noindex', 'do' => __( 'View the page source / robots of a participant request.', 'shuffles-social-services-jobs' ), 'expect' => __( 'It is noindex and excluded from sitemaps.', 'shuffles-social-services-jobs' ) ),
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
				),
			),
			array(
				'title' => __( 'Compliance & verification', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'cred-upload', 'do' => __( 'As a worker, add a credential (e.g. WWCC) with an evidence PDF.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Saved as Pending; the evidence file has no public URL.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cred-verify', 'do' => __( 'As an admin, open Jobs & Engagements → Verification, view the evidence, Approve it.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The worker gains the ✓ Verified badge (and the verified checks show on their card).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cred-serve', 'do' => __( 'Copy an evidence link and open it while logged out (or as a different non-admin).', 'shuffles-social-services-jobs' ), 'expect' => __( 'Access is denied — only the owner or an admin can open it.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'cred-expire', 'do' => __( 'Set a verified credential’s expiry in the past; run the daily cron.', 'shuffles-social-services-jobs' ), 'expect' => __( 'It expires, the badge drops, and a reminder email is sent.', 'shuffles-social-services-jobs' ) ),
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
					array( 'id' => 'studio', 'do' => __( 'In Settings → Appearance, change colours and a preset; watch the live preview; Save.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Preview updates live; saved styles apply on the front end; the theme is untouched.', 'shuffles-social-services-jobs' ) ),
				),
			),
			array(
				'title' => __( 'Navigation, selects & responsive', 'shuffles-social-services-jobs' ),
				'cases' => array(
					array( 'id' => 'menu', 'do' => __( 'View [sssj_menu] logged out, then logged in.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Logged-out shows browse + Log in/Register; logged-in shows dashboard/messages/log out + capability links.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'auto-menu', 'do' => __( 'In Settings → General, tick “Show navigation menu at the top of every page”, save, and open any front-end page.', 'shuffles-social-services-jobs' ), 'expect' => __( 'The navigation bar appears at the very top of every page; un-ticking removes it.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'pills', 'do' => __( 'Open any form/filter with a dropdown or multi-pick.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Single selects are searchable; multi-picks show removable pills (no bare checkbox grids).', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'dynamic-filter', 'do' => __( 'On any directory, change a category, tick a chip or drag the radius — without clicking any button.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Results update automatically (no “Filter” button needed). Typing in the search box updates shortly after you stop.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'clear-all', 'do' => __( 'With several filters applied, click “Clear all”.', 'shuffles-social-services-jobs' ), 'expect' => __( 'Every filter resets and the full list returns.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'use-my-location', 'do' => __( 'Click “Use my location” next to the location field and allow the browser prompt.', 'shuffles-social-services-jobs' ), 'expect' => __( 'A default radius is applied and results re-sort to nearest first; denying access shows a friendly message.', 'shuffles-social-services-jobs' ) ),
					array( 'id' => 'mobile', 'do' => __( 'Open the boards, directories and forms on a phone (or a narrow window).', 'shuffles-social-services-jobs' ), 'expect' => __( 'Filter controls stack full-width, cards go single-column, nothing overflows; it looks professional.', 'shuffles-social-services-jobs' ) ),
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
