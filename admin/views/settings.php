<?php
/**
 * Settings page view. Rendered inside Shuffles_SSJ_Admin::render_page() so $this is the admin object.
 *
 * @package Shuffles_SSJ
 * @var Shuffles_SSJ_Admin $this
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current = $this->current_tab();
$tabs    = $this->tabs();
$group   = Shuffles_SSJ_Settings::SETTINGS_GROUP;

/** Open an options.php form bound to the current tab (so unchecked boxes save correctly). */
$open_form = function ( $tab_slug ) use ( $group ) {
	echo '<form method="post" action="options.php" class="sssj-form">';
	settings_fields( $group );
	echo '<input type="hidden" name="' . esc_attr( Shuffles_SSJ_Settings::OPTION_KEY . '[_tab]' ) . '" value="' . esc_attr( $tab_slug ) . '" />';
};
?>
<div class="wrap sssj-admin">
	<h1><span class="dashicons dashicons-businessperson"></span> <?php esc_html_e( 'Shuffles Social Services Jobs and Engagements', 'shuffles-social-services-jobs' ); ?>
		<span class="sssj-ver">v<?php echo esc_html( SHUFFLES_SSJ_VERSION ); ?></span>
	</h1>
	<p class="sssj-sub"><?php esc_html_e( 'Phase 0 scaffold — entities, seeded taxonomies and settings. Front-end boards, forms, matching and SEO arrive in later phases.', 'shuffles-social-services-jobs' ); ?></p>

	<h2 class="nav-tab-wrapper sssj-tabs">
		<?php foreach ( $tabs as $slug => $def ) : ?>
			<a class="nav-tab <?php echo $current === $slug ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $this->tab_url( $slug ) ); ?>">
				<span class="sssj-dot" style="background:<?php echo esc_attr( $this->dot_colour( $def[2] ) ); ?>"></span>
				<span class="sssj-code"><?php echo esc_html( $def[0] ); ?></span> <?php echo esc_html( $def[1] ); ?>
			</a>
		<?php endforeach; ?>
	</h2>

	<div class="sssj-panel">
	<?php
	switch ( $current ) :

		case 'general':
			$open_form( 'general' );
			echo '<table class="form-table" role="presentation">';
			$this->compliance_select();
			$this->number_field( 'default_radius_km', __( 'Default search radius (km)', 'shuffles-social-services-jobs' ), __( 'Used when a user has not chosen a radius.', 'shuffles-social-services-jobs' ), 1, 500 );
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'maps':
			$open_form( 'maps' );
			echo '<table class="form-table" role="presentation">';
			$this->key_field(
				'google_maps_api_key',
				__( 'Google Maps API key', 'shuffles-social-services-jobs' ),
				__( 'In the <a href="https://console.cloud.google.com/google/maps-apis/" target="_blank" rel="noopener">Google Cloud Console</a>: create or select a project → enable <strong>Maps JavaScript API</strong> and <strong>Places API</strong> → <strong>Credentials → Create credentials → API key</strong>. Restrict the key by HTTP referrer to your site, then paste it here.', 'shuffles-social-services-jobs' ),
				__( 'Powers location autocomplete and the map / radius search on the boards. Optional — without it, boards fall back to manual suburb/postcode entry and a list view (radius still works from stored coordinates).', 'shuffles-social-services-jobs' )
			);
			$this->number_field( 'default_radius_km', __( 'Default search radius (km)', 'shuffles-social-services-jobs' ), '', 1, 500 );
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'cald':
			$open_form( 'cald' );
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'cald_enabled', __( 'CALD & accessibility layer', 'shuffles-social-services-jobs' ), __( 'Master switch for voice search, 7-language interface, read-aloud and display modes. Browser-side — $0 to run. (UI lands with the first public board.)', 'shuffles-social-services-jobs' ) );
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'seo':
			$open_form( 'seo' );
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'seo_enabled', __( 'Strong SEO for jobs', 'shuffles-social-services-jobs' ), __( 'JobPosting structured data + indexable job/category pages (Phase 1). Participant needs always stay noindex.', 'shuffles-social-services-jobs' ) );
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'monetisation':
			$open_form( 'monetisation' );
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'monetisation_enabled', __( 'Enable monetisation gates', 'shuffles-social-services-jobs' ), __( 'Employer advertising subscription + provider application-fee subscription (full billing in Phase 7).', 'shuffles-social-services-jobs' ) );
			$this->number_field( 'free_active_listings', __( 'Free active listings per advertiser', 'shuffles-social-services-jobs' ), __( '0 = unlimited.', 'shuffles-social-services-jobs' ), 0, 999 );
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'licensing':
			$open_form( 'licensing' );
			echo '<table class="form-table" role="presentation">';
			$this->key_field(
				'licence_key',
				__( 'Resale licence key', 'shuffles-social-services-jobs' ),
				__( 'After purchasing on <a href="https://shuffles.com.au" target="_blank" rel="noopener">shuffles.com.au</a>, find your key under <strong>My Account → Licences</strong> and paste it here. Your own primary site does not require a key.', 'shuffles-social-services-jobs' ),
				__( 'Unlocks the premium / white-label features (monetisation, AI bridge, sector duplication). The core job boards work without it.', 'shuffles-social-services-jobs' )
			);
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'privacy':
			$open_form( 'privacy' );
			echo '<p>' . esc_html__( 'Participant needs are pseudonymous, suburb-only, contact-gated, noindex, and require admin approval before publish. These protections are structural and cannot be switched off.', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'delete_data_on_uninstall', __( 'Delete plugin options + custom tables on uninstall', 'shuffles-social-services-jobs' ), __( 'Off by default. CPT posts and taxonomy terms are preserved even when this is on.', 'shuffles-social-services-jobs' ) );
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'taxonomies':
			echo '<h2>' . esc_html__( 'Seeded taxonomies', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p>' . esc_html__( 'Pre-loaded with industry-standard terms on activation. Edit, add or remove freely.', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Taxonomy', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Terms', 'shuffles-social-services-jobs' ) . '</th><th></th></tr></thead><tbody>';
			$tax_objects = array(
				'sssjt_category'           => 'sssj_job',
				'sssjt_role'               => 'sssj_job',
				'sssjt_qualification'      => 'sssj_worker',
				'sssjt_compliance_profile' => 'sssj_job',
				'sssjt_mode'               => 'sssj_job',
				'sssjt_employment_type'    => 'sssj_job',
				'sssjt_support_category'   => 'sssj_need',
				'sssjt_funding_source'     => 'sssj_need',
			);
			foreach ( $tax_objects as $tax => $pt ) {
				$obj   = get_taxonomy( $tax );
				$count = (int) wp_count_terms( array( 'taxonomy' => $tax, 'hide_empty' => false ) );
				$url   = admin_url( 'edit-tags.php?taxonomy=' . $tax . '&post_type=' . $pt );
				echo '<tr><td><strong>' . esc_html( $obj ? $obj->labels->name : $tax ) . '</strong><br><code>' . esc_html( $tax ) . '</code></td><td>' . esc_html( (string) $count ) . '</td><td><a class="button button-small" href="' . esc_url( $url ) . '">' . esc_html__( 'Manage', 'shuffles-social-services-jobs' ) . '</a></td></tr>';
			}
			echo '</tbody></table>';
			break;

		case 'boards':
			echo '<h2>' . esc_html__( 'ABN vs TFN — segregated boards', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p>' . esc_html__( 'Every job carries an engagement basis. The boards are kept strictly apart in the query layer:', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<ul class="ul-disc">';
			echo '<li>' . wp_kses_post( __( '<strong>TFN board</strong> — employee positions only; never shows ABN work.', 'shuffles-social-services-jobs' ) ) . '</li>';
			echo '<li>' . wp_kses_post( __( '<strong>ABN board</strong> — contractor / sole-trader work only.', 'shuffles-social-services-jobs' ) ) . '</li>';
			echo '<li>' . wp_kses_post( __( '<strong>Participant-needs board</strong> — always ABN; never shows TFN positions.', 'shuffles-social-services-jobs' ) ) . '</li>';
			echo '</ul>';
			echo '<h3>' . esc_html__( 'Board pages', 'shuffles-social-services-jobs' ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Pick an existing page, or create one (the matching shortcode is inserted for you), then edit or view it.', 'shuffles-social-services-jobs' ) . '</p>';
			$open_form( 'boards' );
			echo '<table class="form-table" role="presentation">';
			$this->page_picker_field( 'page_job_board', __( 'All jobs board', 'shuffles-social-services-jobs' ), '[sssj_job_board]', __( 'Both bases in a labelled split.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_tfn_board', __( 'TFN (employee) board', 'shuffles-social-services-jobs' ), '[sssj_tfn_board]', __( 'Employee positions only.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_abn_board', __( 'ABN (contractor) board', 'shuffles-social-services-jobs' ), '[sssj_abn_board]', __( 'Contractor / ABN engagements only.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_post_job', __( 'Post-a-job page', 'shuffles-social-services-jobs' ), '[sssj_post_job]', __( 'Advertiser posting form.', 'shuffles-social-services-jobs' ) );
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'compliance':
			echo '<h2>' . esc_html__( 'Compliance & credentials', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p>' . sprintf(
				/* translators: %s: current compliance profile name */
				esc_html__( 'Current default profile: %s. WWCC / NDIS Worker Screening / police checks / certifications and the admin verification queue arrive in Phase 5; verified badges are only ever set by an admin.', 'shuffles-social-services-jobs' ),
				'<strong>' . esc_html( (string) $this->settings()->get( 'compliance_profile', '' ) ) . '</strong>'
			) . '</p>';
			echo '<p><a class="button" href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=sssjt_compliance_profile&post_type=sssj_job' ) ) . '">' . esc_html__( 'Manage compliance profiles', 'shuffles-social-services-jobs' ) . '</a></p>';
			break;

		case 'funding':
			$count = (int) wp_count_terms( array( 'taxonomy' => 'sssjt_funding_source', 'hide_empty' => false ) );
			echo '<h2>' . esc_html__( 'Funding sources', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p>' . esc_html__( 'Participants attach one, many, or no funding source. Funding is a soft match signal — never a hard filter.', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<p>' . sprintf( esc_html__( '%d funding sources seeded.', 'shuffles-social-services-jobs' ), $count ) . ' <a class="button button-small" href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=sssjt_funding_source&post_type=sssj_need' ) ) . '">' . esc_html__( 'Manage', 'shuffles-social-services-jobs' ) . '</a></p>';
			break;

		case 'matching':
			echo '<h2>' . esc_html__( 'Matching', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description">' . esc_html__( 'Real-time matching (category + radius + availability + compliance readiness) arrives in Phase 3; scored matching in Phase 8.', 'shuffles-social-services-jobs' ) . '</p>';
			break;

		case 'integrations':
			echo '<h2>' . esc_html__( 'Detected integrations', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p>' . esc_html__( 'This plugin runs standalone. Each integration below is optional; when absent, the listed fallback is used.', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Integration', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Status', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Standalone fallback', 'shuffles-social-services-jobs' ) . '</th></tr></thead><tbody>';
			foreach ( $this->integrations()->status_rows() as $row ) {
				$badge = $row['present']
					? '<span class="sssj-badge sssj-badge--ok">' . esc_html__( 'Detected', 'shuffles-social-services-jobs' ) . '</span>'
					: '<span class="sssj-badge sssj-badge--off">' . esc_html__( 'Not detected', 'shuffles-social-services-jobs' ) . '</span>';
				echo '<tr><td><strong>' . esc_html( $row['label'] ) . '</strong></td><td>' . $badge . '</td><td>' . esc_html( $row['present'] ? '—' : $row['fallback'] ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</tbody></table>';
			break;

		case 'diagnostics':
			global $wpdb;
			echo '<h2>' . esc_html__( 'Diagnostics', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<table class="widefat striped"><tbody>';
			$rows = array(
				__( 'Plugin version', 'shuffles-social-services-jobs' )  => SHUFFLES_SSJ_VERSION,
				__( 'DB schema version', 'shuffles-social-services-jobs' ) => (string) get_option( 'shuffles_ssj_db_version', '—' ),
				__( 'Installed at', 'shuffles-social-services-jobs' )     => (string) get_option( 'shuffles_ssj_installed_at', '—' ),
				__( 'WordPress', 'shuffles-social-services-jobs' )        => get_bloginfo( 'version' ),
				__( 'PHP', 'shuffles-social-services-jobs' )              => PHP_VERSION,
			);
			foreach ( $rows as $k => $v ) {
				echo '<tr><th style="width:240px">' . esc_html( $k ) . '</th><td><code>' . esc_html( (string) $v ) . '</code></td></tr>';
			}
			foreach ( array( 'sssj_job', 'sssj_worker', 'sssj_need' ) as $pt ) {
				$counts = wp_count_posts( $pt );
				$total  = $counts ? array_sum( (array) $counts ) : 0;
				echo '<tr><th>' . esc_html( $pt ) . '</th><td>' . esc_html( (string) $total ) . ' ' . esc_html__( 'posts', 'shuffles-social-services-jobs' ) . '</td></tr>';
			}
			foreach ( array( 'match_score', 'application', 'message', 'credential' ) as $t ) {
				$table  = $wpdb->prefix . 'sssj_' . $t;
				$exists = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ); // phpcs:ignore WordPress.DB
				echo '<tr><th><code>' . esc_html( $table ) . '</code></th><td>' . ( $exists ? '<span class="sssj-badge sssj-badge--ok">' . esc_html__( 'present', 'shuffles-social-services-jobs' ) . '</span>' : '<span class="sssj-badge sssj-badge--off">' . esc_html__( 'missing', 'shuffles-social-services-jobs' ) . '</span>' ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</tbody></table>';
			break;

		case 'changelog':
			?>
			<div id="sssj-tab-changelog">
				<h2><?php esc_html_e( 'Changelog', 'shuffles-social-services-jobs' ); ?></h2>
				<h3>v0.5.0 — 2026-06-06 · Apply / respond flow</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Apply to jobs (panel on each job page) and respond to participant needs (button on the needs board), recorded in the applications table — duplicates blocked by the unique key.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Gating: TFN jobs accept any member; ABN jobs and participant needs require a recorded, valid ABN (with a filter hook for the upcoming provider subscription).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Member dashboard [sssj_my_listings] — your applications, your job ads with applicants + status control, your participant requests with responses.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.4.0 — 2026-06-05 · Phase 1 (Participants) — four sides live</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Participant-need board [sssj_need_board] — logged-in only, shows published (admin-moderated) requests with pseudonym + suburb only; no names or contact details.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Request form [sssj_post_need] — participant/nominee posts a need; pseudonym generated server-side; multi-select support types + funding (one/many/none); always saved as pending for moderation.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.3.0 — 2026-06-05 · Phase 1 (Workers)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Worker directory [sssj_worker_directory] with visibility enforced in the query layer (guests see public only; members also see logged-in profiles; verified-only is never over-exposed).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Worker profile form [sssj_post_worker] — create/edit your own profile (one per user), with services, availability, status, rate, optional ABN (checksum-validated) and visibility.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.2.2 — 2026-06-05 · Database hardening (speed + accuracy)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Custom tables retuned: UNIQUE key stops duplicate applications; composite indexes match the real query patterns (applicants-by-status, thread view, by-context messages, worker credentials by kind, expiry/verification sweeps).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'NOT NULL + sensible defaults on key columns for accuracy.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'In-place schema upgrade (dbDelta) runs automatically on already-installed sites — no reactivation needed.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.2.1 — 2026-06-05 · Settings conventions (page-pickers + key instructions)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Page settings now use a lookup + create (auto-inserts the shortcode) + edit/view picker — Boards tab links the board and post-a-job pages.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'API-key fields (Google Maps, licence key) now include how-to-get + what-it-does instructions and mask the stored value (blank submission keeps it).', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.2.0 — 2026-06-05 · Phase 1 (Advertisers, slice 1)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Segregated job boards: [sssj_job_board], [sssj_tfn_board] (no ABN), [sssj_abn_board] (no TFN) — ABN/TFN separation enforced in the query layer.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Advertiser posting form [sssj_post_job] with engagement basis, one-off/ongoing, category, location, rate and ABN checksum validation (ABN stored only for ABN engagements).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'JobPosting JSON-LD (Google for Jobs) on job pages; participant needs + non-public workers forced noindex.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Daily expiry cron closes past-dated job ads. ABN-recorded hook fires for the banning cross-match.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.1.1 — 2026-06-05 · Plugin row links</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Added a Settings action link on the Plugins screen (next to Activate / Delete).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Added row-meta links: Shuffles website, Documentation, and View details (→ GitHub repository).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Added Author URI and Plugin URI to the plugin header.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.1.0 — 2026-06-05 · Phase 0 (Scaffold)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Plugin skeleton, activator/deactivator/uninstaller, singleton bootstrap.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'CPTs: sssj_job (public), sssj_worker (public), sssj_need (private/noindex by design).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( '8 taxonomies registered + seeded with industry-standard terms (categories, roles, qualifications, compliance profiles, modes, employment types, support categories, funding sources).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Structured meta registered incl. engagement_basis (ABN/TFN), engagement_type, organisation_type, advertiser ABN, worker employment_status + ABN.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Custom tables: match_score, application, message, credential.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'ABN helper (normalise + modulus-89 checksum), runtime integrations registry, custom capabilities.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Tabbed settings page (16 tabs) + design-system token stylesheet. Standalone-first: no required plugins.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
			</div>
			<?php
			break;

		case 'pm':
			echo '<h2>' . esc_html__( 'Project Management — index', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p>' . esc_html__( 'Mirrors docs/INDEX-SYSTEM.md. Settings tabs by domain colour:', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Code', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Tab', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Domain', 'shuffles-social-services-jobs' ) . '</th></tr></thead><tbody>';
			foreach ( $tabs as $def ) {
				echo '<tr><td><code>' . esc_html( $def[0] ) . '</code></td><td>' . esc_html( $def[1] ) . '</td><td><span class="sssj-dot" style="background:' . esc_attr( $this->dot_colour( $def[2] ) ) . '"></span> ' . esc_html( $def[2] ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</tbody></table>';
			echo '<p><strong>' . esc_html__( 'Entities:', 'shuffles-social-services-jobs' ) . '</strong> CPT-01 sssj_job · CPT-02 sssj_worker · CPT-03 sssj_need · TBL-01 match_score · TBL-02 application · TBL-03 message · TBL-04 credential.</p>';
			break;

	endswitch;
	?>
	</div>
</div>
