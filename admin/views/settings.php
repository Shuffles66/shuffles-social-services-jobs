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
			$this->text_field( 'brand_url', __( 'Brand website URL', 'shuffles-social-services-jobs' ), __( 'Powers the "Shuffles website" link on the Plugins screen. Leave default to point at shuffles.com.au.', 'shuffles-social-services-jobs' ), 'url' );
			$this->text_field( 'focus_programs', __( 'Focus programs (branding & SEO)', 'shuffles-social-services-jobs' ), __( 'A comma-separated list of the funding programs/sectors this site covers — e.g. NDIS, Aged Care, DVA, Foundational Supports, Thriving Kids. Used as SEO keywords on your job, worker and organisation pages, and as the default sub-text on the [sssj_hero] banner. Leave blank to omit.', 'shuffles-social-services-jobs' ) );
			$this->checkbox_field( 'auto_header_menu', __( 'Show navigation menu at the top of every page (for testing)', 'shuffles-social-services-jobs' ), __( 'Automatically outputs the [sssj_menu] navigation bar at the very top of every front-end page (via wp_body_open), so you can navigate the marketplace while testing without editing your theme header. Turn off once you place the menu where you want it. Requires a theme that supports wp_body_open (almost all modern themes do).', 'shuffles-social-services-jobs' ) );
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'shortcodes':
			echo '<h2>' . esc_html__( 'Shortcodes', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description" style="max-width:780px">' . wp_kses_post( __( 'Place these on your pages to build the marketplace. In the block editor add a <strong>Shortcode</strong> block; in Elementor use the <strong>Shortcode</strong> widget; in the classic editor paste the code directly. Each shortcode is self-contained and loads its own styles. A typical set of pages: Jobs · Post a job · Find a worker · Create worker profile · My credentials · Participant requests · Request support · Organisations · Create organisation profile · My dashboard · Messages.', 'shuffles-social-services-jobs' ) ) . '</p>';
			echo '<p class="description" style="max-width:780px">' . esc_html__( 'Tip: pages marked “logged-in” should sit behind your membership/menu so guests are routed to log in; the participant pages are privacy-sensitive (pseudonymous + moderated) and should not be in your public sitemap.', 'shuffles-social-services-jobs' ) . '</p>';

			$access_labels = array(
				'public'       => array( __( 'Anyone', 'shuffles-social-services-jobs' ), '#0d9488' ),
				'members'      => array( __( 'Logged-in members', 'shuffles-social-services-jobs' ), '#2563eb' ),
				'advertisers'  => array( __( 'Advertisers', 'shuffles-social-services-jobs' ), '#ea580c' ),
				'workers'      => array( __( 'Workers', 'shuffles-social-services-jobs' ), '#6366f1' ),
				'participants' => array( __( 'Participants / nominees', 'shuffles-social-services-jobs' ), '#db2777' ),
			);

			$groups = array();
			foreach ( Shuffles_SSJ_Shortcodes::reference() as $sc ) {
				$groups[ $sc['group'] ][] = $sc;
			}
			foreach ( $groups as $gname => $items ) {
				echo '<h3 style="margin:22px 0 6px">' . esc_html( $gname ) . '</h3>';
				echo '<table class="widefat striped" style="max-width:900px"><tbody>';
				foreach ( $items as $sc ) {
					$al = isset( $access_labels[ $sc['access'] ] ) ? $access_labels[ $sc['access'] ] : array( ucfirst( $sc['access'] ), '#64748b' );
					echo '<tr><td style="padding:12px 14px">';
					echo '<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">';
					echo '<code style="font-size:13px;background:#0f172a;color:#f8fafc;padding:4px 10px;border-radius:6px">[' . esc_html( $sc['tag'] ) . ']</code>';
					echo '<strong>' . esc_html( $sc['title'] ) . '</strong>';
					echo '<span style="font-size:11px;font-weight:600;color:#fff;background:' . esc_attr( $al[1] ) . ';padding:2px 8px;border-radius:10px">' . esc_html( $al[0] ) . '</span>';
					echo '</div>';
					echo '<p style="margin:8px 0 4px">' . esc_html( $sc['what'] ) . '</p>';
					echo '<p style="margin:2px 0;color:#475569"><strong>' . esc_html__( 'Where to use:', 'shuffles-social-services-jobs' ) . '</strong> ' . esc_html( $sc['where'] ) . '</p>';
					if ( ! empty( $sc['atts'] ) ) {
						echo '<p style="margin:6px 0 2px;color:#475569"><strong>' . esc_html__( 'Optional attributes:', 'shuffles-social-services-jobs' ) . '</strong></p><ul style="margin:0 0 2px 18px;list-style:disc">';
						foreach ( $sc['atts'] as $a => $adesc ) {
							echo '<li><code>' . esc_html( $a ) . '</code> — ' . esc_html( $adesc ) . '</li>';
						}
						echo '</ul>';
					}
					echo '</td></tr>';
				}
				echo '</tbody></table>';
			}
			break;

		case 'guides':
			echo '<h2>' . esc_html__( 'Guides', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description" style="max-width:800px">' . esc_html__( 'Plain-language how-to guides for each side of the marketplace. The same content is available on the front end via the [sssj_guides] shortcode — put it on a public "Guides" or "Help" page. Keep the content current in includes/class-guides.php as flows change.', 'shuffles-social-services-jobs' ) . '</p>';
			echo Shuffles_SSJ_Guides::render( array( 'title' => __( 'Guides', 'shuffles-social-services-jobs' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;

		case 'workflows':
			echo '<h2>' . esc_html__( 'How-to Workflows', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description" style="max-width:820px">' . wp_kses_post( __( 'Step-by-step <strong>explainer workflows for end users</strong> — the exact path through the app to finish each task. This is the same content members read via the <code>[sssj_workflows]</code> shortcode; put it on a public “How it works” / “Help” page and link it from the menu. Keep it current in <code>includes/class-workflows.php</code> as member-facing flows change.', 'shuffles-social-services-jobs' ) ) . '</p>';
			echo Shuffles_SSJ_Workflows::render( array( 'title' => __( 'How it works', 'shuffles-social-services-jobs' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;

		case 'policies':
			echo '<h2>' . esc_html__( 'Policies', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description" style="max-width:820px">' . wp_kses_post( __( 'These are the <strong>plain-English, member-facing</strong> policy summaries shown on the site via <code>[sssj_policies]</code> — publish them on a public “Policies” page and link it in the footer/menu. The full, formal templates live in <code>/docs/*.md</code> (e.g. Privacy, NDIS Code of Conduct, Incident Management) and must be reviewed and formally adopted by your organisation before you rely on them — they are starting templates, not legal advice. Keep both in sync; the source of the summaries is <code>includes/class-policies.php</code>.', 'shuffles-social-services-jobs' ) ) . '</p>';
			echo Shuffles_SSJ_Policies::render( array( 'title' => __( 'Our policies', 'shuffles-social-services-jobs' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;

		case 'marketing':
			echo '<h2>' . esc_html__( 'Marketing master', 'shuffles-social-services-jobs' ) . '</h2>';
			$mk_has = class_exists( 'Shuffles_SSJ_Marketing' ) && '' !== trim( Shuffles_SSJ_Marketing::markdown() );
			echo '<p class="description" style="max-width:860px">' . wp_kses_post( __( 'This is the living <strong>marketing and product master</strong>: the positioning, the business logic, the full functional spec, and the out-of-the-box audience analysis. The single source is the document <code>docs/MARKETING-MASTER.md</code> in the plugin folder. Publish it as a readable page with <code>[sssj_marketing]</code> (use the button below to create that page in one click), and link it where partners or your team can read it. It deliberately names no third-party tools, describing everything as our internally curated and constructed tech stack and customised workflows.', 'shuffles-social-services-jobs' ) ) . '</p>';
			if ( ! $mk_has ) {
				echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'The document file was not found on this site. Re-deploy so docs/MARKETING-MASTER.md is present, then the page and this preview will fill in.', 'shuffles-social-services-jobs' ) . '</p></div>';
			}
			// One-click create / link the public page.
			$mk_pid = (int) $this->settings()->get( 'page_marketing', 0 );
			echo '<p>';
			if ( $mk_pid && 'publish' === get_post_status( $mk_pid ) ) {
				echo '<a class="button" target="_blank" rel="noopener" href="' . esc_url( (string) get_permalink( $mk_pid ) ) . '">' . esc_html__( 'View the Marketing page', 'shuffles-social-services-jobs' ) . '</a> ';
				echo '<a class="button" href="' . esc_url( get_edit_post_link( $mk_pid ) ) . '">' . esc_html__( 'Edit the page', 'shuffles-social-services-jobs' ) . '</a>';
			} else {
				echo '<span class="sssj-page-picker" data-key="page_marketing" data-shortcode="[sssj_marketing]">';
				echo '<button type="button" class="button button-primary sssj-create-page" data-title="' . esc_attr__( 'Marketing', 'shuffles-social-services-jobs' ) . '">' . esc_html__( 'Create the Marketing page', 'shuffles-social-services-jobs' ) . '</button>';
				echo '<span class="sssj-page-links" style="margin-left:8px"></span>';
				echo '</span>';
				echo '<span class="description" style="display:block;margin-top:6px">' . esc_html__( 'After it is created, View and Edit links appear next to the button. Reload this tab to swap in the permanent buttons.', 'shuffles-social-services-jobs' ) . '</span>';
			}
			echo '</p>';
			echo '<hr /><h3>' . esc_html__( 'Preview', 'shuffles-social-services-jobs' ) . '</h3>';
			if ( $mk_has ) {
				echo Shuffles_SSJ_Marketing::render(); // phpcs:ignore WordPress.Security.EscapeOutput
			}
			break;

		case 'fields':
			$fstatus  = isset( $_GET['sssj_field'] ) ? sanitize_key( wp_unslash( $_GET['sssj_field'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$edit_key = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$all_fields = Shuffles_SSJ_Field_Registry::fields();
			$editing    = null;
			foreach ( $all_fields as $ef ) {
				if ( $ef['key'] === $edit_key ) {
					$editing = $ef;
				}
			}
			?>
			<div class="sssj-help-card" style="background:#fff;border:1px solid #dcdcde;border-left:4px solid #6366f1;border-radius:8px;padding:16px 20px;margin:8px 0 18px;max-width:920px">
				<h2 style="margin-top:0"><?php esc_html_e( 'Profile Fields', 'shuffles-social-services-jobs' ); ?></h2>
				<p><?php esc_html_e( 'Define extra fields shown on the worker/contractor, organisation and participant-request profile forms, beyond the built-in ones. Pick the input type, mark a field required, and tick “show on banner filters” for select/multi-select fields. Select and multi-select options can be mapped to FluentCRM tags/lists on the CRM Sync tab.', 'shuffles-social-services-jobs' ); ?></p>
				<p class="description"><?php esc_html_e( 'Values are saved on each profile. Deleting a field here does not delete values already saved.', 'shuffles-social-services-jobs' ); ?></p>
			</div>
			<?php
			if ( 'saved' === $fstatus ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Field saved.', 'shuffles-social-services-jobs' ) . '</p></div>';
			} elseif ( 'deleted' === $fstatus ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Field deleted.', 'shuffles-social-services-jobs' ) . '</p></div>';
			} elseif ( 'error' === $fstatus ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please provide at least a label.', 'shuffles-social-services-jobs' ) . '</p></div>';
			} elseif ( 'seeded' === $fstatus ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Recommended provider fields added (existing fields were left untouched).', 'shuffles-social-services-jobs' ) . '</p></div>';
			}

			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0 0 12px">';
			echo '<input type="hidden" name="action" value="sssj_seed_provider_fields" />' . wp_nonce_field( 'sssj_seed_provider_fields', '_wpnonce', true, false );
			echo '<button class="button">' . esc_html__( 'Add recommended provider fields', 'shuffles-social-services-jobs' ) . '</button> <span class="description">' . esc_html__( 'One-shot: adds a Shuffles-style organisation field set (specialisations, service delivery, ages supported, accepting clients, accessibility, languages, years operating, accreditations). Skips any you already have.', 'shuffles-social-services-jobs' ) . '</span></form>';

			echo '<h3>' . esc_html__( 'Your custom fields', 'shuffles-social-services-jobs' ) . '</h3>';
			if ( $all_fields ) {
				echo '<table class="widefat striped" style="max-width:920px"><thead><tr><th>' . esc_html__( 'Label', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Key', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Type', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Shows on', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Banner', 'shuffles-social-services-jobs' ) . '</th><th></th></tr></thead><tbody>';
				foreach ( $all_fields as $f ) {
					$editurl = add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'fields', 'edit' => $f['key'] ), admin_url( 'admin.php' ) );
					echo '<tr><td><strong>' . esc_html( $f['label'] ) . '</strong>' . ( $f['required'] ? ' <span class="description">(' . esc_html__( 'required', 'shuffles-social-services-jobs' ) . ')</span>' : '' ) . '</td>';
					echo '<td><code>' . esc_html( $f['key'] ) . '</code></td><td>' . esc_html( $f['type'] ) . '</td><td>' . esc_html( implode( ', ', $f['entities'] ) ) . '</td><td>' . ( $f['banner'] ? '&#10003;' : '&mdash;' ) . '</td>';
					echo '<td style="white-space:nowrap"><a class="button button-small" href="' . esc_url( $editurl ) . '">' . esc_html__( 'Edit', 'shuffles-social-services-jobs' ) . '</a> ';
					echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline" onsubmit="return confirm(\'' . esc_js( __( 'Delete this field?', 'shuffles-social-services-jobs' ) ) . '\')"><input type="hidden" name="action" value="sssj_delete_field" /><input type="hidden" name="key" value="' . esc_attr( $f['key'] ) . '" />' . wp_nonce_field( 'sssj_field_delete', '_wpnonce', true, false ) . '<button class="button button-small button-link-delete">' . esc_html__( 'Delete', 'shuffles-social-services-jobs' ) . '</button></form></td></tr>';
				}
				echo '</tbody></table>';
			} else {
				echo '<p class="description">' . esc_html__( 'No custom fields yet — add one below.', 'shuffles-social-services-jobs' ) . '</p>';
			}

			echo '<h3 style="margin-top:20px">' . ( $editing ? esc_html__( 'Edit field', 'shuffles-social-services-jobs' ) : esc_html__( 'Add a field', 'shuffles-social-services-jobs' ) ) . '</h3>';
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj">
				<input type="hidden" name="action" value="sssj_save_field" />
				<?php wp_nonce_field( 'sssj_field' ); ?>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><label for="sssj-fld-label"><?php esc_html_e( 'Label', 'shuffles-social-services-jobs' ); ?></label></th>
						<td><input type="text" class="regular-text" id="sssj-fld-label" name="label" value="<?php echo esc_attr( $editing ? $editing['label'] : '' ); ?>" required /></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Key', 'shuffles-social-services-jobs' ); ?></th>
						<td><input type="text" class="regular-text" name="key" value="<?php echo esc_attr( $editing ? $editing['key'] : '' ); ?>" <?php echo $editing ? 'readonly' : ''; ?> placeholder="<?php esc_attr_e( 'auto from label', 'shuffles-social-services-jobs' ); ?>" />
							<p class="description"><?php esc_html_e( 'Unique id; leave blank to generate from the label. Cannot be changed after creation.', 'shuffles-social-services-jobs' ); ?></p></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Shows on', 'shuffles-social-services-jobs' ); ?></th>
						<td><?php foreach ( array( 'worker' => __( 'Workers / contractors', 'shuffles-social-services-jobs' ), 'org' => __( 'Organisations', 'shuffles-social-services-jobs' ), 'need' => __( 'Participant requests', 'shuffles-social-services-jobs' ) ) as $ev => $el ) { $chk = $editing ? in_array( $ev, $editing['entities'], true ) : ( 'worker' === $ev ); echo '<label style="margin-right:16px"><input type="checkbox" name="entities[]" value="' . esc_attr( $ev ) . '" ' . checked( $chk, true, false ) . ' /> ' . esc_html( $el ) . '</label>'; } ?></td></tr>
					<tr><th scope="row"><label for="sssj-fld-type"><?php esc_html_e( 'Type', 'shuffles-social-services-jobs' ); ?></label></th>
						<td><select id="sssj-fld-type" name="type" data-no-enhance><?php foreach ( array( 'text' => __( 'Text', 'shuffles-social-services-jobs' ), 'textarea' => __( 'Paragraph', 'shuffles-social-services-jobs' ), 'number' => __( 'Number', 'shuffles-social-services-jobs' ), 'select' => __( 'Single select (searchable)', 'shuffles-social-services-jobs' ), 'multiselect' => __( 'Multi-select (searchable pills)', 'shuffles-social-services-jobs' ), 'toggle' => __( 'Yes / No toggle', 'shuffles-social-services-jobs' ) ) as $tv => $tl ) { echo '<option value="' . esc_attr( $tv ) . '" ' . selected( $editing ? $editing['type'] : 'text', $tv, false ) . '>' . esc_html( $tl ) . '</option>'; } ?></select></td></tr>
					<tr><th scope="row"><label for="sssj-fld-options"><?php esc_html_e( 'Options', 'shuffles-social-services-jobs' ); ?></label></th>
						<td><textarea id="sssj-fld-options" name="options" rows="4" class="large-text" placeholder="<?php esc_attr_e( 'One option per line (for single/multi-select only)', 'shuffles-social-services-jobs' ); ?>"><?php echo esc_textarea( $editing ? implode( "\n", $editing['options'] ) : '' ); ?></textarea></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Flags', 'shuffles-social-services-jobs' ); ?></th>
						<td><label><input type="checkbox" name="required" value="1" <?php checked( $editing ? $editing['required'] : false ); ?> /> <?php esc_html_e( 'Required', 'shuffles-social-services-jobs' ); ?></label><br />
							<label><input type="checkbox" name="banner" value="1" <?php checked( $editing ? $editing['banner'] : false ); ?> /> <?php esc_html_e( 'Show on directory banner filters (single/multi-select only)', 'shuffles-social-services-jobs' ); ?></label></td></tr>
				</table>
				<?php submit_button( $editing ? __( 'Update field', 'shuffles-social-services-jobs' ) : __( 'Add field', 'shuffles-social-services-jobs' ) ); ?>
			</form>
			<?php
			break;

		case 'crm':
			$cstatus = isset( $_GET['sssj_crm'] ) ? sanitize_key( wp_unslash( $_GET['sssj_crm'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$has_crm = $this->integrations->has( 'fluentcrm' );
			?>
			<div class="sssj-help-card" style="background:#fff;border:1px solid #dcdcde;border-left:4px solid #2563eb;border-radius:8px;padding:16px 20px;margin:8px 0 18px;max-width:920px">
				<h2 style="margin-top:0"><?php esc_html_e( 'CRM Sync', 'shuffles-social-services-jobs' ); ?></h2>
				<p><?php esc_html_e( 'Keep each member’s FluentCRM tags & lists in step with the choices on their profile. Map a value — a funding source like NDIS, a sector, a culture/language, or a custom-field option — to a FluentCRM tag and/or list. Then, whenever a worker, organisation or participant profile is saved, ticking that value adds the tag/list to that person’s contact, and un-ticking removes it. Every change is recorded in a per-user log.', 'shuffles-social-services-jobs' ); ?></p>
				<p><?php esc_html_e( 'This syncs to the FluentCRM on THIS website. It only runs while the master switch below is on and FluentCRM is active. If a mapped tag/list is later deleted in FluentCRM, you’ll get an alert here so you can fix the mapping (the plugin maps to existing tags/lists — it never silently creates them).', 'shuffles-social-services-jobs' ); ?></p>
			</div>
			<?php
			if ( 'saved' === $cstatus ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Mappings saved.', 'shuffles-social-services-jobs' ) . '</p></div>';
			} elseif ( 'cleared' === $cstatus ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Missing-target alerts cleared.', 'shuffles-social-services-jobs' ) . '</p></div>';
			} elseif ( 'resynced' === $cstatus ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'User re-synced.', 'shuffles-social-services-jobs' ) . '</p></div>';
			}
			if ( ! $has_crm ) {
				echo '<div class="notice notice-warning"><p>' . esc_html__( 'FluentCRM is not active on this site, so sync is paused. Activate FluentCRM to enable it.', 'shuffles-social-services-jobs' ) . '</p></div>';
			}

			$open_form( 'crm' );
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'crm_sync_enabled', __( 'Enable CRM sync', 'shuffles-social-services-jobs' ), __( 'Master switch. When on (and FluentCRM is active), profile saves apply/remove the mapped tags & lists.', 'shuffles-social-services-jobs' ) );
			$this->checkbox_field( 'crm_create_contact', __( 'Create a contact if one does not exist', 'shuffles-social-services-jobs' ), __( 'If a member has no FluentCRM contact yet, create one (status: subscribed) so the tags/lists have somewhere to land. Off = skip that member and log it.', 'shuffles-social-services-jobs' ) );
			echo '</table>';
			submit_button();
			echo '</form>';

			$miss = Shuffles_SSJ_CRM_Sync::missing();
			if ( $miss ) {
				echo '<div class="notice notice-warning" style="max-width:920px"><p><strong>' . esc_html__( 'Some mapped tags/lists no longer exist in FluentCRM:', 'shuffles-social-services-jobs' ) . '</strong></p><ul style="list-style:disc;margin-left:22px">';
				foreach ( $miss as $m ) {
					echo '<li>' . esc_html( ucfirst( $m['type'] ) . ' #' . $m['id'] . ' — ' . __( 'last seen', 'shuffles-social-services-jobs' ) . ' ' . $m['last'] ) . '</li>';
				}
				echo '</ul><p class="description">' . esc_html__( 'Fix the mapping below (point it at a tag/list that exists), then dismiss.', 'shuffles-social-services-jobs' ) . '</p>';
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="sssj_crm_clear_missing" />' . wp_nonce_field( 'sssj_crm_missing', '_wpnonce', true, false ) . '<button class="button">' . esc_html__( 'Dismiss alerts', 'shuffles-social-services-jobs' ) . '</button></form></div>';
			}

			echo '<h3 style="margin-top:18px">' . esc_html__( 'Value &rarr; CRM tag / list mappings', 'shuffles-social-services-jobs' ) . '</h3>';
			$mvals  = Shuffles_SSJ_CRM_Sync::mappable_values();
			$ftags  = Shuffles_SSJ_CRM_Sync::fluent_tags();
			$flists = Shuffles_SSJ_CRM_Sync::fluent_lists();
			$rows   = Shuffles_SSJ_CRM_Sync::get_map();
			if ( ! $ftags && ! $flists ) {
				echo '<p class="description">' . esc_html__( 'No FluentCRM tags or lists were found on this site yet. Create some in FluentCRM first (or activate FluentCRM), then map them here.', 'shuffles-social-services-jobs' ) . '</p>';
			}
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sssj">
				<input type="hidden" name="action" value="sssj_save_crm_map" />
				<?php wp_nonce_field( 'sssj_crm_map' ); ?>
				<table class="widefat" style="max-width:1000px"><thead><tr><th style="width:34%"><?php esc_html_e( 'Profile value', 'shuffles-social-services-jobs' ); ?></th><th><?php esc_html_e( 'FluentCRM tags', 'shuffles-social-services-jobs' ); ?></th><th><?php esc_html_e( 'FluentCRM lists', 'shuffles-social-services-jobs' ); ?></th></tr></thead><tbody>
				<?php
				$render_row = function ( $i, $token, $sel_tags, $sel_lists ) use ( $mvals, $ftags, $flists ) {
					echo '<tr><td><select name="token[' . (int) $i . ']" class="sssj-select" data-placeholder="' . esc_attr__( 'Choose a value…', 'shuffles-social-services-jobs' ) . '"><option value=""></option>';
					foreach ( $mvals as $v ) {
						echo '<option value="' . esc_attr( $v['token'] ) . '" ' . selected( $token, $v['token'], false ) . '>' . esc_html( $v['label'] ) . '</option>';
					}
					echo '</select></td><td><select name="tags[' . (int) $i . '][]" multiple class="sssj-select" data-placeholder="' . esc_attr__( 'Tags…', 'shuffles-social-services-jobs' ) . '">';
					foreach ( $ftags as $tid => $tt ) {
						echo '<option value="' . esc_attr( $tid ) . '" ' . ( in_array( (int) $tid, array_map( 'intval', (array) $sel_tags ), true ) ? 'selected' : '' ) . '>' . esc_html( $tt ) . '</option>';
					}
					echo '</select></td><td><select name="lists[' . (int) $i . '][]" multiple class="sssj-select" data-placeholder="' . esc_attr__( 'Lists…', 'shuffles-social-services-jobs' ) . '">';
					foreach ( $flists as $lid => $lt ) {
						echo '<option value="' . esc_attr( $lid ) . '" ' . ( in_array( (int) $lid, array_map( 'intval', (array) $sel_lists ), true ) ? 'selected' : '' ) . '>' . esc_html( $lt ) . '</option>';
					}
					echo '</select></td></tr>';
				};
				$ri = 0;
				foreach ( $rows as $r ) {
					$render_row( $ri, $r['token'], $r['tags'] ?? array(), $r['lists'] ?? array() );
					$ri++;
				}
				for ( $b = 0; $b < 4; $b++ ) {
					$render_row( $ri, '', array(), array() );
					$ri++;
				}
				?>
				</tbody></table>
				<p class="description"><?php esc_html_e( 'Pick a value, then the tag(s) and/or list(s) to apply when it is chosen. A row with no tags and no lists is ignored. To remove a mapping, clear its tags + lists. Four blank rows are shown for adding more (save, then more appear).', 'shuffles-social-services-jobs' ); ?></p>
				<?php submit_button( __( 'Save mappings', 'shuffles-social-services-jobs' ) ); ?>
			</form>
			<?php
			$logs = Shuffles_SSJ_CRM_Sync::get_logs( array( 'limit' => 50 ) );
			echo '<h3 style="margin-top:18px">' . esc_html__( 'Recent CRM sync activity (all users)', 'shuffles-social-services-jobs' ) . '</h3>';
			if ( $logs ) {
				echo '<table class="widefat striped" style="max-width:1000px"><thead><tr><th>' . esc_html__( 'When', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'User', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Action', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Tag / List', 'shuffles-social-services-jobs' ) . '</th><th>' . esc_html__( 'Profile', 'shuffles-social-services-jobs' ) . '</th></tr></thead><tbody>';
				foreach ( $logs as $l ) {
					echo '<tr><td>' . esc_html( $l->created_at ) . '</td><td>' . esc_html( $l->email ) . '</td><td>' . esc_html( $l->action ) . '</td><td>' . esc_html( $l->object_type . ': ' . $l->object_label ) . '</td><td>' . esc_html( $l->entity ) . '</td></tr>';
				}
				echo '</tbody></table>';
			} else {
				echo '<p class="description">' . esc_html__( 'No CRM sync activity yet.', 'shuffles-social-services-jobs' ) . '</p>';
			}
			break;

		case 'alerts':
			$astatus = isset( $_GET['sssj_alerts'] ) ? sanitize_key( wp_unslash( $_GET['sssj_alerts'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<div class="sssj-help-card" style="background:#fff;border:1px solid #dcdcde;border-left:4px solid #ea580c;border-radius:8px;padding:16px 20px;margin:8px 0 18px;max-width:920px">
				<h2 style="margin-top:0"><?php esc_html_e( 'Email Alerts', 'shuffles-social-services-jobs' ); ?></h2>
				<p><?php esc_html_e( 'A daily digest emails members about new matches. There are three opt-in alert types — nobody is emailed unless they opt in:', 'shuffles-social-services-jobs' ); ?></p>
				<ol>
					<li><strong><?php esc_html_e( 'Workers → new matching jobs', 'shuffles-social-services-jobs' ); ?></strong> — <?php esc_html_e( 'a worker ticks “Email me when new jobs match my profile” on their profile. They get new roles matching their services/area.', 'shuffles-social-services-jobs' ); ?></li>
					<li><strong><?php esc_html_e( 'Advertisers → new candidates', 'shuffles-social-services-jobs' ); ?></strong> — <?php esc_html_e( 'when posting a job, the advertiser ticks “Email me when new candidates match this job”. They get new worker profiles that match.', 'shuffles-social-services-jobs' ); ?></li>
					<li><strong><?php esc_html_e( 'Saved searches', 'shuffles-social-services-jobs' ); ?></strong> — <?php esc_html_e( 'members click “Save & alert me” on any directory; they get new listings matching that search. Manage them with the [sssj_saved_searches] shortcode.', 'shuffles-social-services-jobs' ); ?></li>
				</ol>
				<p><?php echo wp_kses_post( __( 'Matching reuses the matching engine; “new” means published since the last alert. Email is sent via the site mailer (FluentSMTP/wp_mail) unless a FluentCRM automation claims it via the <code>shuffles_ssj_alert_sent</code> action / <code>shuffles_ssj_alert_suppress_default</code> filter. Frequency is daily.', 'shuffles-social-services-jobs' ) ); ?></p>
			</div>
			<?php
			if ( 'ran' === $astatus ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Alerts run complete (digests sent to opted-in members with new matches).', 'shuffles-social-services-jobs' ) . '</p></div>';
			}
			$open_form( 'alerts' );
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'alerts_enabled', __( 'Enable email alerts', 'shuffles-social-services-jobs' ), __( 'Master switch for the daily alert digests. Off = no alert emails are sent (opt-ins are remembered).', 'shuffles-social-services-jobs' ) );
			echo '</table>';
			submit_button();
			echo '</form>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:8px">';
			echo '<input type="hidden" name="action" value="sssj_alerts_run_now" />' . wp_nonce_field( 'sssj_alerts_run', '_wpnonce', true, false );
			echo '<button class="button">' . esc_html__( 'Run alerts now', 'shuffles-social-services-jobs' ) . '</button> <span class="description">' . esc_html__( 'Send today’s digests immediately (for testing).', 'shuffles-social-services-jobs' ) . '</span>';
			echo '</form>';
			break;

		case 'testing':
			echo '<h2>' . esc_html__( 'Testing worksheet', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description" style="max-width:800px">' . esc_html__( 'Hand this to a tester to confirm the plugin works as it should. Mark each case Pass or Fail — progress is saved in this browser, and Print gives a paper/PDF copy. The same checklist is available on the front end via the [sssj_tests] shortcode, and is kept up to date as the plugin changes.', 'shuffles-social-services-jobs' ) . '</p>';
			echo Shuffles_SSJ_Tests::render( array( 'title' => __( 'Testing worksheet', 'shuffles-social-services-jobs' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			break;

		case 'demo':
			echo '<h2>' . esc_html__( 'Demo / test users', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description" style="max-width:820px">' . wp_kses_post( __( 'These are the demo accounts created by the seeder (<code>dist/seed-demo.php</code>) — one per functional role, each with a sample listing. Click <strong>“View as”</strong> to instantly browse the site as that user; a “Return to admin” link appears in the toolbar to switch back. <strong>For testing only:</strong> the initial passwords are stored in plain text so they can be shared here, and “View as” works only for demo accounts — remove these accounts before going to production (a one-line cleanup is in the seeder file).', 'shuffles-social-services-jobs' ) ) . '</p>';
			$demo_users = get_users( array( 'meta_key' => '_sssj_demo', 'meta_value' => 1, 'orderby' => 'login', 'order' => 'ASC' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
			if ( empty( $demo_users ) ) {
				echo '<div class="notice notice-info inline" style="margin:8px 0;max-width:820px"><p>' . wp_kses_post( __( 'No demo users yet. Run the seeder on the server with <code>wp eval-file dist/seed-demo.php</code> (from the plugin folder), then reload this tab.', 'shuffles-social-services-jobs' ) ) . '</p></div>';
			} else {
				$hat_defs = class_exists( 'Shuffles_SSJ_Roles' ) ? Shuffles_SSJ_Roles::hats() : array();
				echo '<table class="widefat striped" style="max-width:980px"><thead><tr>';
				echo '<th>' . esc_html__( 'Username', 'shuffles-social-services-jobs' ) . '</th>';
				echo '<th>' . esc_html__( 'Initial password', 'shuffles-social-services-jobs' ) . '</th>';
				echo '<th>' . esc_html__( 'Email', 'shuffles-social-services-jobs' ) . '</th>';
				echo '<th>' . esc_html__( 'Function(s)', 'shuffles-social-services-jobs' ) . '</th>';
				echo '<th>' . esc_html__( 'View as', 'shuffles-social-services-jobs' ) . '</th>';
				echo '</tr></thead><tbody>';
				foreach ( $demo_users as $du ) {
					$pass = (string) get_user_meta( $du->ID, '_sssj_demo_pass', true );
					$hats = class_exists( 'Shuffles_SSJ_Roles' ) ? Shuffles_SSJ_Roles::member_roles( $du->ID ) : array();
					$fns  = array();
					foreach ( $hats as $h ) {
						$fns[] = isset( $hat_defs[ $h ]['label'] ) ? $hat_defs[ $h ]['label'] : $h;
					}
					echo '<tr>';
					echo '<td><strong>' . esc_html( $du->user_login ) . '</strong></td>';
					echo '<td>' . ( '' !== $pass ? '<code>' . esc_html( $pass ) . '</code>' : '<span class="description">' . esc_html__( '(not recorded — reset via Users)', 'shuffles-social-services-jobs' ) . '</span>' ) . '</td>';
					echo '<td>' . esc_html( $du->user_email ) . '</td>';
					echo '<td>' . esc_html( $fns ? implode( ', ', $fns ) : '—' ) . '</td>';
					echo '<td><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0">';
					echo '<input type="hidden" name="action" value="sssj_view_as" />';
					echo '<input type="hidden" name="user_id" value="' . esc_attr( $du->ID ) . '" />';
					echo wp_nonce_field( 'sssj_view_as', '_wpnonce', true, false ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo '<button type="submit" class="button button-small">👁 ' . esc_html__( 'View as', 'shuffles-social-services-jobs' ) . '</button>';
					echo '</form></td>';
					echo '</tr>';
				}
				echo '</tbody></table>';
				echo '<p class="description" style="margin-top:10px">' . esc_html__( 'Tip: log in via a private/incognito window so you stay signed in as admin in this one.', 'shuffles-social-services-jobs' ) . '</p>';
			}
			break;

		case 'maps':
			$open_form( 'maps' );
			echo '<table class="form-table" role="presentation">';
			$this->key_field(
				'google_maps_api_key',
				__( 'Google Maps API key', 'shuffles-social-services-jobs' ),
				__( 'In the <a href="https://console.cloud.google.com/google/maps-apis/" target="_blank" rel="noopener">Google Cloud Console</a>, create or select a project and <strong>enable these three APIs</strong>:<ul><li><strong>Maps JavaScript API</strong> — draws the interactive map on the boards</li><li><strong>Places API</strong> — suburb/address autocomplete in the search and posting forms</li><li><strong>Geocoding API</strong> — turns a suburb or postcode into coordinates for radius search</li></ul>Then open <strong>APIs &amp; Services → Credentials → Create credentials → API key</strong>, restrict the key by <strong>HTTP referrer</strong> to your site, and paste it here.', 'shuffles-social-services-jobs' ),
				__( 'Optional. A key adds Google address autocomplete + the Google map. It is NOT required for radius search — the plugin has its own geocoder (below) and Geo my WP is never used.', 'shuffles-social-services-jobs' )
			);
			$this->select_field(
				'geocoder_provider',
				__( 'Location lookup (geocoder)', 'shuffles-social-services-jobs' ),
				array(
					'osm' => __( 'OpenStreetMap — keyless, free (recommended)', 'shuffles-social-services-jobs' ),
					'off' => __( 'Off — rely on Google autocomplete coordinates only', 'shuffles-social-services-jobs' ),
				),
				__( 'How the plugin turns a typed suburb/postcode into coordinates for radius search when no Google autocomplete coordinates are present. <strong>OpenStreetMap</strong> needs no API key (a bundled list of Australian cities resolves instantly; the rest is looked up free via OpenStreetMap and cached). This is what makes the geo a self-contained custom build — no Geo my WP, no mandatory Google.', 'shuffles-social-services-jobs' ),
				'osm'
			);
			$this->number_field( 'default_radius_km', __( 'Default search radius (km)', 'shuffles-social-services-jobs' ), '', 1, 500 );
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'cald':
			$open_form( 'cald' );
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'cald_enabled', __( 'CALD & accessibility layer', 'shuffles-social-services-jobs' ), __( 'Master switch for voice search, multilingual interface, read-aloud and display modes. Browser-side — $0 to run. (UI lands with the first public board, plus a floating bar site-wide.)', 'shuffles-social-services-jobs' ) );
			$this->text_field( 'cald_languages', __( 'Offered languages (this site)', 'shuffles-social-services-jobs' ), __( 'Comma-separated language codes to show in the picker, e.g. en, ar, zh, vi. Leave blank to offer all built-ins (en, ar, zh, el, it, id, pa). English is always available. Built-in codes: ar=Arabic, zh=Chinese, el=Greek, it=Italian, id=Indonesian, pa=Punjabi.', 'shuffles-social-services-jobs' ) );
			$this->textarea_field( 'cald_custom_langs', __( 'Add custom languages', 'shuffles-social-services-jobs' ), __( 'One per line: <code>code | Endonym | rtl</code> (the rtl flag is optional). Example: <code>vi | Tiếng Việt</code> or <code>fa | فارسی | rtl</code>. Added languages appear in the picker immediately (the switch, page language and right-to-left layout all work); supply their wording below.', 'shuffles-social-services-jobs' ), 4, "vi | Tiếng Việt\nfa | فارسی | rtl" );
			$this->textarea_field( 'cald_lang_overrides', __( 'Translation overrides (JSON)', 'shuffles-social-services-jobs' ), __( 'Optional. Provide or override interface wording per language as JSON, e.g. <code>{ "vi": { "filter": "Lọc", "view_job": "Xem việc làm", "apply": "Ứng tuyển" } }</code>. Keys map to the on-screen labels; anything not supplied falls back to English. Built-in languages already include their core labels.', 'shuffles-social-services-jobs' ), 6, '{ "vi": { "filter": "Lọc", "apply": "Ứng tuyển" } }' );
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
			?>
			<div class="sssj-help-card" style="background:#fff;border:1px solid #dcdcde;border-left:4px solid #3897e0;border-radius:8px;padding:16px 20px;margin:8px 0 18px;max-width:900px">
				<h2 style="margin-top:0"><?php esc_html_e( 'How monetisation works', 'shuffles-social-services-jobs' ); ?></h2>
				<p><?php esc_html_e( 'The plugin is free and fully usable out of the box. Monetisation is entirely optional — nothing is gated until you tick “Enable monetisation gates” below. When it is on, there are two independent ways to earn:', 'shuffles-social-services-jobs' ); ?></p>
				<ol>
					<li><strong><?php esc_html_e( 'Advertiser (employer) subscription', 'shuffles-social-services-jobs' ); ?></strong> — <?php esc_html_e( 'organisations can post a set number of free listings (the “Free active listings” number). Beyond that, posting more — and getting featured/promoted placement — requires a paid advertiser subscription.', 'shuffles-social-services-jobs' ); ?></li>
					<li><strong><?php esc_html_e( 'Provider (worker/contractor) application-fee subscription', 'shuffles-social-services-jobs' ); ?></strong> — <?php esc_html_e( 'responding to a participant request or an ABN task requires a paid provider subscription. This is checked on the server when they apply, so it can’t be bypassed in the browser.', 'shuffles-social-services-jobs' ); ?></li>
				</ol>
				<p><?php echo wp_kses_post( __( 'Admins and the site’s resale-licence holder <strong>always bypass</strong> every gate, so your own testing is never blocked.', 'shuffles-social-services-jobs' ) ); ?></p>

				<h3 style="margin-bottom:4px"><?php esc_html_e( 'Choosing your billing tool: PMPro or FluentCart', 'shuffles-social-services-jobs' ); ?></h3>
				<p><?php esc_html_e( 'The plugin doesn’t handle payments itself — it reads “does this user hold an active subscription?” from whichever billing tool you already run. Pick one:', 'shuffles-social-services-jobs' ); ?></p>
				<table class="widefat striped" style="max-width:860px;margin:6px 0 10px">
					<thead><tr>
						<th><?php esc_html_e( 'Option', 'shuffles-social-services-jobs' ); ?></th>
						<th><?php esc_html_e( 'Best when…', 'shuffles-social-services-jobs' ); ?></th>
						<th><?php esc_html_e( 'How you configure it', 'shuffles-social-services-jobs' ); ?></th>
						<th><?php esc_html_e( 'Needs', 'shuffles-social-services-jobs' ); ?></th>
					</tr></thead>
					<tbody>
						<tr>
							<td><strong><?php esc_html_e( 'Paid Memberships Pro (PMPro)', 'shuffles-social-services-jobs' ); ?></strong></td>
							<td><?php esc_html_e( 'You sell access as recurring membership levels (e.g. “Advertiser”, “Provider”), and want member directories, content gating and levels in one place.', 'shuffles-social-services-jobs' ); ?></td>
							<td><?php esc_html_e( 'Enter the PMPro level ID that represents the advertiser tier and the one for the provider tier (below). A user on that level passes the gate.', 'shuffles-social-services-jobs' ); ?></td>
							<td><?php esc_html_e( 'The free Paid Memberships Pro plugin active.', 'shuffles-social-services-jobs' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'FluentCart', 'shuffles-social-services-jobs' ); ?></strong></td>
							<td><?php esc_html_e( 'You already sell with FluentCart (carts, products, one-off + subscription products) and want subscriptions to live alongside your other products.', 'shuffles-social-services-jobs' ); ?></td>
							<td><?php esc_html_e( 'Enter the FluentCart subscription product ID for advertisers and for providers (below), or leave 0 so any active FluentCart subscription qualifies.', 'shuffles-social-services-jobs' ); ?></td>
							<td><?php esc_html_e( 'The free FluentCart core plugin active (not just FluentCart Pro). Until it’s active, no FluentCart subscriber is detected.', 'shuffles-social-services-jobs' ); ?></td>
						</tr>
					</tbody>
				</table>
				<p class="description"><?php echo wp_kses_post( __( 'Run neither yet? Leave monetisation off — everything stays free. Run something else? You can wire any logic in via the <code>shuffles_ssj_has_advertiser_sub</code> and <code>shuffles_ssj_has_provider_sub</code> filters (return true/false for a user). Whichever you choose, the matching fields below are the ones that apply; the others are simply ignored.', 'shuffles-social-services-jobs' ) ); ?></p>
			</div>
			<?php
			$open_form( 'monetisation' );
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'monetisation_enabled', __( 'Enable monetisation gates', 'shuffles-social-services-jobs' ), __( 'Employer advertising subscription + provider application-fee subscription. OFF = the whole site is free / ungated.', 'shuffles-social-services-jobs' ) );
			$this->number_field( 'free_active_listings', __( 'Free active listings per advertiser', 'shuffles-social-services-jobs' ), __( '0 = unlimited. Beyond this, the advertiser needs the subscription below.', 'shuffles-social-services-jobs' ), 0, 999 );

			$this->select_field(
				'gating_provider',
				__( 'Subscription provider', 'shuffles-social-services-jobs' ),
				array(
					'pmpro'      => __( 'Paid Memberships Pro (PMPro)', 'shuffles-social-services-jobs' ),
					'fluentcart' => __( 'FluentCart', 'shuffles-social-services-jobs' ),
				),
				__( 'Which billing tool decides who holds a paid subscription. <strong>FluentCart</strong> needs the free <em>FluentCart</em> core plugin active (not just FluentCart Pro) — until then no FluentCart subscriber is detected. <strong>PMPro</strong> needs Paid Memberships Pro active.', 'shuffles-social-services-jobs' ),
				'pmpro'
			);

			echo '<tr><th colspan="2" style="padding-top:8px"><strong>' . esc_html__( 'PMPro level IDs (used when provider = PMPro)', 'shuffles-social-services-jobs' ) . '</strong></th></tr>';
			$this->number_field( 'advertiser_pmpro_level', __( 'Advertiser subscription — PMPro level ID', 'shuffles-social-services-jobs' ), __( 'Find it at Memberships → Membership Levels (the ID column). Unlocks unlimited posting + featured placement. 0 = none.', 'shuffles-social-services-jobs' ), 0, 99999 );
			$this->number_field( 'provider_pmpro_level', __( 'Provider subscription — PMPro level ID', 'shuffles-social-services-jobs' ), __( 'PMPro level required to respond to participant needs / ABN tasks. 0 = none.', 'shuffles-social-services-jobs' ), 0, 99999 );

			echo '<tr><th colspan="2" style="padding-top:8px"><strong>' . esc_html__( 'FluentCart product IDs (used when provider = FluentCart)', 'shuffles-social-services-jobs' ) . '</strong></th></tr>';
			$this->number_field( 'advertiser_fc_product', __( 'Advertiser subscription — FluentCart product ID', 'shuffles-social-services-jobs' ), __( 'The subscription product in FluentCart → Products (open it; the ID is in the URL/post ID). An active subscription unlocks unlimited posting + featured placement. 0 = any active FluentCart subscription qualifies.', 'shuffles-social-services-jobs' ), 0, 99999999 );
			$this->number_field( 'provider_fc_product', __( 'Provider subscription — FluentCart product ID', 'shuffles-social-services-jobs' ), __( 'FluentCart subscription product required to respond to participant needs / ABN tasks. 0 = any active FluentCart subscription qualifies.', 'shuffles-social-services-jobs' ), 0, 99999999 );

			echo '<tr><td colspan="2"><p class="description">' . wp_kses_post( __( 'Gates are OFF unless "Enable monetisation gates" is ticked. The site resale licence and admins always bypass. You can also plug in custom logic via the <code>shuffles_ssj_has_advertiser_sub</code>, <code>shuffles_ssj_has_provider_sub</code> and <code>shuffles_ssj_fluentcart_active</code> filters.', 'shuffles-social-services-jobs' ) ) . '</p></td></tr>';

			// --- Affiliate / referral earning (FluentAffiliate) ---
			$aff_active = class_exists( 'Shuffles_SSJ_Affiliate' ) && Shuffles_SSJ_Affiliate::is_active();
			echo '<tr><th colspan="2" style="padding-top:14px"><strong>' . esc_html__( 'Affiliate / referral earning (FluentAffiliate)', 'shuffles-social-services-jobs' ) . '</strong>';
			echo ' ' . ( $aff_active
				? '<span class="sssj-badge sssj-badge--verified">' . esc_html__( 'FluentAffiliate detected', 'shuffles-social-services-jobs' ) . '</span>'
				: '<span class="sssj-badge">' . esc_html__( 'FluentAffiliate not detected', 'shuffles-social-services-jobs' ) . '</span>' );
			echo '</th></tr>';
			echo '<tr><td colspan="2"><p class="description" style="max-width:820px">' . wp_kses_post( __( 'Invite members — <strong>especially participants</strong> — to earn a little income by referring others. A friendly promo card appears in onboarding (and via <code>[sssj_affiliate]</code>) that links to your affiliate sign-up and clearly tells members they need a <strong>PayPal account</strong> to be paid, and that they can set it up <strong>later</strong> — it never blocks onboarding. With FluentAffiliate active we auto-link to the page running <code>[fluent_affiliate_portal]</code>; set a URL below only to override.', 'shuffles-social-services-jobs' ) ) . '</p></td></tr>';
			$this->checkbox_field( 'affiliate_enabled', __( 'Show the “earn by referring” promo', 'shuffles-social-services-jobs' ), __( 'Master switch for the referral promo card (onboarding + [sssj_affiliate]). Off = it never appears.', 'shuffles-social-services-jobs' ) );
			$aff_url = (string) $this->settings()->get( 'affiliate_url', '' );
			echo '<tr><th scope="row">' . esc_html__( 'Affiliate area URL (optional override)', 'shuffles-social-services-jobs' ) . '</th><td>';
			echo '<input type="url" class="regular-text" name="' . esc_attr( Shuffles_SSJ_Settings::OPTION_KEY . '[affiliate_url]' ) . '" value="' . esc_attr( $aff_url ) . '" placeholder="https://justtasks.com.au/affiliates/" />';
			echo '<p class="description">' . esc_html__( 'Where members sign up / manage referrals. Leave blank to auto-use your FluentAffiliate portal page.', 'shuffles-social-services-jobs' ) . '</p>';
			echo '</td></tr>';

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
				__( 'After purchasing on <a href="https://shuffles.com.au" target="_blank" rel="noopener">shuffles.com.au</a>, find your key under <strong>My Account → Licences</strong> and paste it here. Save, then click <strong>Activate</strong> below. Your own primary site does not require a key.', 'shuffles-social-services-jobs' ),
				__( 'Unlocks the premium / white-label features (monetisation, AI bridge, sector duplication). The core job boards work without it.', 'shuffles-social-services-jobs' )
			);
			$this->text_field( 'license_item_id', __( 'Product / item ID', 'shuffles-social-services-jobs' ), __( 'The FluentCart product ID for this plugin on the vendor store. Save the key + ID, then Activate.', 'shuffles-social-services-jobs' ) );
			$this->text_field( 'vendor_url', __( 'Licence vendor store URL', 'shuffles-social-services-jobs' ), __( 'Where licences are validated (the FluentCart store). Default: https://shuffles.com.au.', 'shuffles-social-services-jobs' ), 'url' );
			echo '</table>';
			submit_button( __( 'Save licence settings', 'shuffles-social-services-jobs' ) );
			echo '</form>';

			if ( isset( $_GET['sssj_lic'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Licence updated.', 'shuffles-social-services-jobs' ) . '</p></div>';
			}
			echo '<h3>' . esc_html__( 'Licence status', 'shuffles-social-services-jobs' ) . '</h3>';
			$is_pro = Shuffles_SSJ_License::is_pro();
			echo '<p><strong>' . esc_html( Shuffles_SSJ_License::status_label() ) . '</strong> '
				. ( $is_pro
					? '<span class="sssj-badge sssj-badge--ok">' . esc_html__( 'Pro active', 'shuffles-social-services-jobs' ) . '</span>'
					: '<span class="sssj-badge sssj-badge--off">' . esc_html__( 'Pro inactive', 'shuffles-social-services-jobs' ) . '</span>' )
				. '</p>';
			$ap = esc_url( admin_url( 'admin-post.php' ) );
			echo '<form method="post" action="' . $ap . '" style="display:inline-block;margin-right:8px">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_nonce_field( 'sssj_license' );
			echo '<input type="hidden" name="action" value="sssj_license_activate" />';
			submit_button( __( 'Activate licence', 'shuffles-social-services-jobs' ), 'primary', 'submit', false );
			echo '</form>';
			echo '<form method="post" action="' . $ap . '" style="display:inline-block">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_nonce_field( 'sssj_license' );
			echo '<input type="hidden" name="action" value="sssj_license_deactivate" />';
			submit_button( __( 'Deactivate', 'shuffles-social-services-jobs' ), 'secondary', 'submit', false );
			echo '</form>';
			echo '<p class="description">' . esc_html__( 'Premium features (monetisation, white-label, AI bridge) require an active licence. Your own primary site can bypass by defining SHUFFLES_SSJ_PRO = true in wp-config.php. Core boards always work. A grace window keeps a valid licence working if the vendor store is briefly unreachable.', 'shuffles-social-services-jobs' ) . '</p>';
			break;

		case 'ads':
			$ads_active = class_exists( 'Shuffles_SSJ_Ads' ) && Shuffles_SSJ_Ads::is_active();
			?>
			<div class="sssj-help-card" style="background:#fff;border:1px solid #dcdcde;border-left:4px solid #ea580c;border-radius:8px;padding:16px 20px;margin:8px 0 18px;max-width:920px">
				<h2 style="margin-top:0"><?php esc_html_e( 'Ads (Advanced Ads)', 'shuffles-social-services-jobs' ); ?></h2>
				<p><?php echo wp_kses_post( __( 'Show banner ads from the <strong>Advanced Ads</strong> plugin inside the marketplace. This is a light, optional integration — we never bundle or require Advanced Ads. If it is not active, everything here simply renders nothing.', 'shuffles-social-services-jobs' ) ); ?></p>
				<p>
					<?php if ( $ads_active ) : ?>
						<span class="sssj-badge sssj-badge--verified"><?php esc_html_e( 'Advanced Ads detected — active', 'shuffles-social-services-jobs' ); ?></span>
					<?php else : ?>
						<span class="sssj-badge"><?php esc_html_e( 'Advanced Ads not detected', 'shuffles-social-services-jobs' ); ?></span>
						<span class="description"><?php esc_html_e( 'Install & activate the Advanced Ads plugin, then create your ads/placements.', 'shuffles-social-services-jobs' ); ?></span>
					<?php endif; ?>
				</p>
				<p class="description"><?php echo wp_kses_post( __( 'Place an ad anywhere with <code>[sssj_ad placement="your-placement-slug"]</code>, <code>[sssj_ad id="123"]</code> or <code>[sssj_ad group="4"]</code>. Or map a placement to a slot below and the boards / single-listing pages show it automatically. A slot value can be a placement slug, or <code>id:123</code> to target a single ad.', 'shuffles-social-services-jobs' ) ); ?></p>
			</div>
			<?php
			$open_form( 'ads' );
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'ads_enabled', __( 'Show Advanced Ads in the marketplace', 'shuffles-social-services-jobs' ), __( 'Master switch. Off = no plugin-managed ad slots render and [sssj_ad] outputs nothing (your other Advanced Ads placements elsewhere on the site are unaffected).', 'shuffles-social-services-jobs' ) );
			if ( class_exists( 'Shuffles_SSJ_Ads' ) ) {
				foreach ( Shuffles_SSJ_Ads::slots() as $skey => $slabel ) {
					$okey = 'ad_slot_' . $skey;
					$val  = (string) $this->settings()->get( $okey, '' );
					echo '<tr><th scope="row">' . esc_html( $slabel ) . '</th><td>';
					echo '<input type="text" class="regular-text" name="' . esc_attr( Shuffles_SSJ_Settings::OPTION_KEY . '[' . $okey . ']' ) . '" value="' . esc_attr( $val ) . '" placeholder="' . esc_attr__( 'placement-slug or id:123', 'shuffles-social-services-jobs' ) . '" />';
					echo '<p class="description">' . esc_html__( 'Advanced Ads placement slug to show in this slot (leave blank for none).', 'shuffles-social-services-jobs' ) . '</p>';
					echo '</td></tr>';
				}
			}
			echo '</table>';
			submit_button();
			echo '</form>';
			break;

		case 'reviews':
			?>
			<div class="sssj-help-card" style="background:#fff;border:1px solid #dcdcde;border-left:4px solid #d97706;border-radius:8px;padding:16px 20px;margin:8px 0 18px;max-width:920px">
				<h2 style="margin-top:0"><?php esc_html_e( 'Reviews & Ratings', 'shuffles-social-services-jobs' ); ?></h2>
				<p><?php esc_html_e( 'Members can leave a 1–5 star rating and a written review on a contractor (worker profile) or a provider (organisation). Three protections keep reviews trustworthy:', 'shuffles-social-services-jobs' ); ?></p>
				<ol>
					<li><strong><?php esc_html_e( 'Engagement-gated', 'shuffles-social-services-jobs' ); ?></strong> — <?php esc_html_e( 'only a member who has actually engaged through the platform (a relay message exists between them — applying starts one) can review. You cannot review yourself.', 'shuffles-social-services-jobs' ); ?></li>
					<li><strong><?php esc_html_e( 'Pre-moderated', 'shuffles-social-services-jobs' ); ?></strong> — <?php esc_html_e( 'every review is held as Pending and only appears once you Approve it below.', 'shuffles-social-services-jobs' ); ?></li>
					<li><strong><?php esc_html_e( 'Right of reply', 'shuffles-social-services-jobs' ); ?></strong> — <?php esc_html_e( 'the reviewed party may post one public response, and the approved average feeds the matching “trust” signal.', 'shuffles-social-services-jobs' ); ?></li>
				</ol>
				<p class="description"><?php esc_html_e( 'Reviews show automatically on the contractor’s and provider’s profile pages. One (editable) review per member per subject.', 'shuffles-social-services-jobs' ); ?></p>
			</div>
			<?php
			$open_form( 'reviews' );
			echo '<table class="form-table" role="presentation">';
			$this->checkbox_field( 'reviews_enabled', __( 'Enable reviews & ratings', 'shuffles-social-services-jobs' ), __( 'Master switch. Off = the reviews section disappears from all profiles and no new reviews can be left (existing ones are kept).', 'shuffles-social-services-jobs' ) );
			echo '</table>';
			submit_button();
			echo '</form>';

			if ( class_exists( 'Shuffles_SSJ_Reviews' ) ) {
				$mod_nonce = wp_create_nonce( 'sssj_review_moderate' );
				$ap        = esc_url( admin_url( 'admin-post.php' ) );
				$render_rows = function ( $rows ) use ( $ap, $mod_nonce ) {
					if ( empty( $rows ) ) {
						echo '<p class="description">' . esc_html__( 'Nothing here.', 'shuffles-social-services-jobs' ) . '</p>';
						return;
					}
					echo '<table class="widefat striped"><thead><tr>'
						. '<th>' . esc_html__( 'Rating', 'shuffles-social-services-jobs' ) . '</th>'
						. '<th>' . esc_html__( 'Review', 'shuffles-social-services-jobs' ) . '</th>'
						. '<th>' . esc_html__( 'Reviewer', 'shuffles-social-services-jobs' ) . '</th>'
						. '<th>' . esc_html__( 'Subject', 'shuffles-social-services-jobs' ) . '</th>'
						. '<th>' . esc_html__( 'Status', 'shuffles-social-services-jobs' ) . '</th>'
						. '<th>' . esc_html__( 'Actions', 'shuffles-social-services-jobs' ) . '</th>'
						. '</tr></thead><tbody>';
					foreach ( $rows as $r ) {
						$ru   = get_userdata( (int) $r->reviewer_user_id );
						$name = $ru ? $ru->display_name : ( '#' . (int) $r->reviewer_user_id );
						$slbl = Shuffles_SSJ_Reviews::type_label( $r->subject_type );
						$stit = get_the_title( (int) $r->subject_id );
						$surl = get_permalink( (int) $r->subject_id );
						echo '<tr>';
						echo '<td>' . Shuffles_SSJ_Reviews::stars_html( (int) $r->rating ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<td>' . ( $r->title ? '<strong>' . esc_html( $r->title ) . '</strong><br/>' : '' ) . esc_html( wp_trim_words( wp_strip_all_tags( (string) $r->body ), 40 ) ) . '</td>';
						echo '<td>' . esc_html( $name ) . '</td>';
						echo '<td>' . esc_html( $slbl ) . ': ' . ( $surl ? '<a href="' . esc_url( $surl ) . '" target="_blank" rel="noopener">' . esc_html( $stit ) . '</a>' : esc_html( $stit ) ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '<td><span class="sssj-badge">' . esc_html( ucfirst( (string) $r->status ) ) . '</span></td>';
						echo '<td>';
						$btn = function ( $op, $label ) use ( $ap, $mod_nonce, $r ) {
							echo '<form method="post" action="' . $ap . '" style="display:inline-block;margin:0 4px 4px 0">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '<input type="hidden" name="action" value="sssj_review_moderate" />';
							echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $mod_nonce ) . '" />';
							echo '<input type="hidden" name="review_id" value="' . esc_attr( $r->id ) . '" />';
							echo '<input type="hidden" name="op" value="' . esc_attr( $op ) . '" />';
							echo '<button class="button button-small">' . esc_html( $label ) . '</button>';
							echo '</form>';
						};
						if ( 'approved' !== $r->status ) {
							$btn( 'approve', __( 'Approve', 'shuffles-social-services-jobs' ) );
						}
						if ( 'rejected' !== $r->status ) {
							$btn( 'reject', __( 'Reject', 'shuffles-social-services-jobs' ) );
						}
						echo '</td></tr>';
					}
					echo '</tbody></table>';
				};
				echo '<h2 style="margin-top:22px">' . esc_html__( 'Awaiting moderation', 'shuffles-social-services-jobs' ) . '</h2>';
				$render_rows( Shuffles_SSJ_Reviews::pending( 100 ) );
				echo '<h2 style="margin-top:22px">' . esc_html__( 'Recent reviews', 'shuffles-social-services-jobs' ) . '</h2>';
				$render_rows( Shuffles_SSJ_Reviews::recent( 50 ) );
			}
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

		case 'appearance':
			echo '<h2>' . esc_html__( 'Style Studio', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description" style="max-width:780px">' . esc_html__( 'Re-skin the public-facing plugin for this install. Adjust the controls and watch the live preview update — nothing is saved until you click Save Changes. Everything is scoped to the plugin (.sssj), so your wider theme is untouched.', 'shuffles-social-services-jobs' ) . '</p>';
			$open_form( 'appearance' );
			echo '<div class="sssj-studio"><div class="sssj-studio__controls">';
			echo '<table class="form-table" role="presentation">';
			$this->text_field( 'color_primary', __( 'Primary colour', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->text_field( 'color_primary_deep', __( 'Primary (hover / active)', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->text_field( 'color_ink', __( 'Headings / strong text', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->text_field( 'color_text', __( 'Body text', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->text_field( 'color_line', __( 'Borders / dividers', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->text_field( 'color_bg', __( 'Surface background', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->text_field( 'color_bg_soft', __( 'Sunken background', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->text_field( 'color_abn', __( 'ABN (contractor) accent', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->text_field( 'color_tfn', __( 'TFN (employee) accent', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->text_field( 'color_need', __( 'Participant-need accent', 'shuffles-social-services-jobs' ), '', 'color' );
			$this->number_field( 'ui_radius', __( 'Corner radius (px)', 'shuffles-social-services-jobs' ), __( 'Rounding for cards, inputs and buttons.', 'shuffles-social-services-jobs' ), 0, 40 );
			$this->text_field( 'font_family', __( 'Font family (CSS)', 'shuffles-social-services-jobs' ), __( 'e.g. "Poppins", sans-serif. Leave blank to inherit the theme font.', 'shuffles-social-services-jobs' ) );
			$this->text_field( 'font_size', __( 'Base font size', 'shuffles-social-services-jobs' ), __( 'e.g. 1rem or 16px. Leave blank to inherit.', 'shuffles-social-services-jobs' ) );
			$this->select_field( 'heading_weight', __( 'Heading weight', 'shuffles-social-services-jobs' ), array( '' => __( 'Theme default', 'shuffles-social-services-jobs' ), '500' => '500', '600' => '600', '700' => '700', '800' => '800' ), '' );
			$this->select_field( 'ui_density', __( 'Density', 'shuffles-social-services-jobs' ), array( 'compact' => __( 'Compact', 'shuffles-social-services-jobs' ), 'normal' => __( 'Normal', 'shuffles-social-services-jobs' ), 'comfortable' => __( 'Comfortable', 'shuffles-social-services-jobs' ) ), __( 'Spacing for cards and lists.', 'shuffles-social-services-jobs' ), 'normal' );
			$css_val = (string) $this->settings->get( 'custom_css', '' );
			echo '<tr><th scope="row"><label for="sssj-custom_css">' . esc_html__( 'Custom CSS', 'shuffles-social-services-jobs' ) . '</label></th><td>';
			echo '<textarea id="sssj-custom_css" class="large-text code" rows="8" name="' . esc_attr( $this->field_name( 'custom_css' ) ) . '">' . esc_textarea( $css_val ) . '</textarea>';
			echo '<p class="description">' . esc_html__( 'Scope rules to ".sssj …" to target plugin surfaces. HTML tags are stripped for safety.', 'shuffles-social-services-jobs' ) . '</p>';
			echo '</td></tr>';
			echo '</table></div>'; // /controls

			// Live preview + presets + saved looks + width toggle.
			echo '<div class="sssj-studio__preview-wrap">';
			echo '<div class="sssj-studio__bar"><strong>' . esc_html__( 'Presets:', 'shuffles-social-services-jobs' ) . '</strong> ';
			echo '<button type="button" class="button" data-sssj-preset="soft">' . esc_html__( 'Soft', 'shuffles-social-services-jobs' ) . '</button> ';
			echo '<button type="button" class="button" data-sssj-preset="bold">' . esc_html__( 'Bold', 'shuffles-social-services-jobs' ) . '</button> ';
			echo '<button type="button" class="button" data-sssj-preset="calm">' . esc_html__( 'Calm', 'shuffles-social-services-jobs' ) . '</button> ';
			echo '&nbsp; <strong>' . esc_html__( 'Width:', 'shuffles-social-services-jobs' ) . '</strong> ';
			echo '<button type="button" class="button" data-sssj-width="0">' . esc_html__( 'Desktop', 'shuffles-social-services-jobs' ) . '</button> ';
			echo '<button type="button" class="button" data-sssj-width="380">' . esc_html__( 'Mobile', 'shuffles-social-services-jobs' ) . '</button>';
			echo '</div>';
			echo '<div class="sssj-studio__bar"><strong>' . esc_html__( 'Saved looks:', 'shuffles-social-services-jobs' ) . '</strong> ';
			echo '<select data-sssj-themes><option value="">' . esc_html__( '— select —', 'shuffles-social-services-jobs' ) . '</option></select> ';
			echo '<button type="button" class="button" data-sssj-theme-load>' . esc_html__( 'Load', 'shuffles-social-services-jobs' ) . '</button> ';
			echo '<button type="button" class="button" data-sssj-theme-save>' . esc_html__( 'Save current…', 'shuffles-social-services-jobs' ) . '</button> ';
			echo '<button type="button" class="button" data-sssj-theme-del>' . esc_html__( 'Delete', 'shuffles-social-services-jobs' ) . '</button>';
			echo '</div>';
			echo '<input type="hidden" id="sssj-appearance_themes" name="' . esc_attr( $this->field_name( 'appearance_themes' ) ) . '" value="' . esc_attr( (string) $this->settings->get( 'appearance_themes', '' ) ) . '" />';
			echo '<p class="description">' . esc_html__( 'Live preview — presets and edits apply here instantly but are only stored when you Save Changes.', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<div class="sssj-studio__stage"><div id="sssj-studio-preview" class="sssj">' . $this->studio_preview_html() . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '</div></div>'; // /preview-wrap /studio

			submit_button();
			echo '</form>';

			// --- Custom CSS guide: tokens, classes, examples, rules ---
			echo '<details open style="margin-top:18px;max-width:880px"><summary style="cursor:pointer;font-weight:600;font-size:14px">' . esc_html__( 'Custom CSS guide — tokens, classes & examples', 'shuffles-social-services-jobs' ) . '</summary>';
			echo '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-top:10px">';
			echo '<p>' . wp_kses_post( __( 'Two ways to restyle, easiest first: <strong>1)</strong> change the colour / radius / font controls above — they set the design tokens for you. <strong>2)</strong> write Custom CSS for anything else. Everything the plugin renders sits inside a <code>.sssj</code> wrapper, so <strong>prefix every rule with <code>.sssj</code></strong> and the rest of your theme is never touched.', 'shuffles-social-services-jobs' ) ) . '</p>';

			echo '<h4 style="margin:14px 0 6px">' . esc_html__( 'Design tokens (the easy way)', 'shuffles-social-services-jobs' ) . '</h4>';
			echo '<p class="description">' . esc_html__( 'Override these CSS variables on .sssj to recolour/resize everything at once:', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<table class="widefat striped" style="max-width:760px"><tbody>';
			$tokens = array(
				'--sssj-blue'      => __( 'Primary colour — buttons, links, focus ring', 'shuffles-social-services-jobs' ),
				'--sssj-blue-deep' => __( 'Primary hover / active', 'shuffles-social-services-jobs' ),
				'--sssj-ink'       => __( 'Headings / strong text', 'shuffles-social-services-jobs' ),
				'--sssj-text'      => __( 'Body text', 'shuffles-social-services-jobs' ),
				'--sssj-line'      => __( 'Borders / dividers', 'shuffles-social-services-jobs' ),
				'--sssj-bg'        => __( 'Surface background (cards / panels)', 'shuffles-social-services-jobs' ),
				'--sssj-bg-soft'   => __( 'Sunken background', 'shuffles-social-services-jobs' ),
				'--sssj-abn'       => __( 'ABN (contractor) accent', 'shuffles-social-services-jobs' ),
				'--sssj-tfn'       => __( 'TFN (employee) accent', 'shuffles-social-services-jobs' ),
				'--sssj-need'      => __( 'Participant-need accent', 'shuffles-social-services-jobs' ),
				'--sssj-radius'    => __( 'Corner radius (e.g. 16px)', 'shuffles-social-services-jobs' ),
				'--sssj-font'      => __( 'Font family', 'shuffles-social-services-jobs' ),
			);
			foreach ( $tokens as $t => $d ) {
				echo '<tr><td style="width:160px"><code>' . esc_html( $t ) . '</code></td><td>' . esc_html( $d ) . '</td></tr>';
			}
			echo '</tbody></table>';

			echo '<h4 style="margin:16px 0 6px">' . esc_html__( 'Main classes to target', 'shuffles-social-services-jobs' ) . '</h4>';
			echo '<ul class="ul-disc" style="max-width:760px">';
			echo '<li><code>.sssj</code> — ' . esc_html__( 'the wrapper around everything (always prefix your rules with it).', 'shuffles-social-services-jobs' ) . '</li>';
			echo '<li><code>.sssj-panel</code> — ' . esc_html__( 'a bordered content panel.', 'shuffles-social-services-jobs' ) . '</li>';
			echo '<li><code>.sssj-card</code> <code>--abn</code> <code>--tfn</code> <code>--need</code> <code>--featured</code> <code>--banned</code> — ' . esc_html__( 'result cards + their accents.', 'shuffles-social-services-jobs' ) . '</li>';
			echo '<li><code>.sssj-btn</code> <code>--primary</code> <code>--secondary</code> <code>--ghost</code> <code>--danger</code> <code>--sm</code> — ' . esc_html__( 'buttons.', 'shuffles-social-services-jobs' ) . '</li>';
			echo '<li><code>.sssj-badge</code> <code>--verified</code> <code>--featured</code> <code>--pending</code> <code>--rejected</code> <code>--expired</code> — ' . esc_html__( 'status chips.', 'shuffles-social-services-jobs' ) . '</li>';
			echo '<li><code>.sssj-input</code> <code>.sssj-select</code> <code>.sssj-textarea</code> <code>.sssj-field</code> <code>.sssj-row</code> <code>.sssj-stack</code> <code>.sssj-grid</code> — ' . esc_html__( 'form fields + layout helpers.', 'shuffles-social-services-jobs' ) . '</li>';
			echo '<li><code>.sssj-nav</code> <code>.sssj-nav__item</code> <code>--cta</code> — ' . esc_html__( 'the [sssj_menu] navigation bar.', 'shuffles-social-services-jobs' ) . '</li>';
			echo '</ul>';

			echo '<h4 style="margin:16px 0 6px">' . esc_html__( 'Copy-paste examples', 'shuffles-social-services-jobs' ) . '</h4>';
			$examples = array(
				__( 'Brand font + softer corners + brand blue (via tokens)', 'shuffles-social-services-jobs' ) => ".sssj{\n  --sssj-font:'Poppins',sans-serif;\n  --sssj-radius:16px;\n  --sssj-blue:#0ea5e9;\n  --sssj-blue-deep:#0369a1;\n}",
				__( 'Bigger, bolder primary buttons', 'shuffles-social-services-jobs' )               => ".sssj .sssj-btn--primary{\n  font-size:1.05rem;\n  padding:14px 22px;\n  letter-spacing:.2px;\n}",
				__( 'Make featured jobs really stand out', 'shuffles-social-services-jobs' )           => ".sssj .sssj-card--featured{\n  border-left:6px solid #f59e0b;\n  background:#fffbeb;\n}",
				__( 'Wider cards / fewer columns', 'shuffles-social-services-jobs' )                   => ".sssj .sssj-grid{\n  grid-template-columns:repeat(auto-fill,minmax(360px,1fr));\n}",
				__( 'Pill-shaped navigation links', 'shuffles-social-services-jobs' )                  => ".sssj .sssj-nav__item a{\n  border-radius:999px;\n}",
			);
			foreach ( $examples as $title => $code ) {
				echo '<p style="margin:10px 0 2px"><strong>' . esc_html( $title ) . '</strong></p>';
				echo '<pre style="background:#0f172a;color:#e2e8f0;padding:12px 14px;border-radius:6px;overflow:auto;font-size:13px;line-height:1.6;white-space:pre;margin:0 0 4px"><code style="background:transparent;color:#e2e8f0;padding:0;margin:0;box-shadow:none;border:0;font-family:Menlo,Consolas,monospace;font-size:inherit;white-space:pre">' . esc_html( $code ) . '</code></pre>';
			}

			echo '<h4 style="margin:16px 0 6px">' . esc_html__( 'Rules of thumb', 'shuffles-social-services-jobs' ) . '</h4>';
			echo '<ul class="ul-disc" style="max-width:760px">';
			echo '<li>' . wp_kses_post( __( 'Always prefix selectors with <code>.sssj</code>.', 'shuffles-social-services-jobs' ) ) . '</li>';
			echo '<li>' . esc_html__( 'Prefer changing tokens over hard-coding values — one change recolours everything consistently.', 'shuffles-social-services-jobs' ) . '</li>';
			echo '<li>' . wp_kses_post( __( 'Avoid <code>!important</code> — it can stop the High-contrast / No-colour accessibility modes from working.', 'shuffles-social-services-jobs' ) ) . '</li>';
			echo '<li>' . esc_html__( 'Changes apply only to plugin surfaces; your theme and other pages are unaffected. HTML typed in the box is stripped for safety.', 'shuffles-social-services-jobs' ) . '</li>';
			echo '</ul>';
			echo '</div></details>';
			break;

		case 'boards':
			echo '<h2>' . esc_html__( 'ABN vs TFN — segregated boards', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p>' . esc_html__( 'Every job carries an engagement basis. The boards are kept strictly apart in the query layer:', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<ul class="ul-disc">';
			echo '<li>' . wp_kses_post( __( '<strong>TFN board</strong> — employee positions only; never shows ABN work.', 'shuffles-social-services-jobs' ) ) . '</li>';
			echo '<li>' . wp_kses_post( __( '<strong>ABN board</strong> — contractor / sole-trader work only.', 'shuffles-social-services-jobs' ) ) . '</li>';
			echo '<li>' . wp_kses_post( __( '<strong>Participant-needs board</strong> — always ABN; never shows TFN positions.', 'shuffles-social-services-jobs' ) ) . '</li>';
			echo '</ul>';
			echo '<p class="description">' . esc_html__( 'The board (and all other) pages are mapped in the Pages tab.', 'shuffles-social-services-jobs' ) . '</p>';
			echo '<p><a class="button" href="' . esc_url( $this->tab_url( 'pages' ) ) . '">' . esc_html__( 'Go to the Pages tab →', 'shuffles-social-services-jobs' ) . '</a></p>';
			break;

		case 'pages':
			echo '<h2>' . esc_html__( 'Pages', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description" style="max-width:780px">' . esc_html__( 'Map each feature to a WordPress page. Pick an existing page, or use “Create page” (the matching shortcode is inserted for you), then Edit or View it. These mappings power the [sssj_menu] navigation and the board links.', 'shuffles-social-services-jobs' ) . '</p>';
			$open_form( 'pages' );
			echo '<table class="form-table" role="presentation">';

			echo '<tr><td colspan="2"><h3 style="margin:6px 0 0">' . esc_html__( 'Home page hero ([sssj_hero])', 'shuffles-social-services-jobs' ) . '</h3>'
				. '<p class="description" style="max-width:780px">' . esc_html__( 'Edit the hero headline and main blurb here. These are used by [sssj_hero] whenever its title / subtitle attributes are left blank, so you can change the wording without touching the shortcode on the page.', 'shuffles-social-services-jobs' ) . '</p></td></tr>';
			$hero_heading = (string) $this->settings()->get( 'hero_heading', '' );
			$hero_blurb   = (string) $this->settings()->get( 'hero_blurb', '' );
			echo '<tr><th scope="row">' . esc_html__( 'Hero headline', 'shuffles-social-services-jobs' ) . '</th><td>';
			echo '<input type="text" class="large-text" name="' . esc_attr( Shuffles_SSJ_Settings::OPTION_KEY . '[hero_heading]' ) . '" value="' . esc_attr( $hero_heading ) . '" placeholder="' . esc_attr__( 'Find the right support work, and the right people', 'shuffles-social-services-jobs' ) . '" />';
			echo '<p class="description">' . esc_html__( 'Leave blank to use the built-in default.', 'shuffles-social-services-jobs' ) . '</p></td></tr>';
			echo '<tr><th scope="row">' . esc_html__( 'Hero main blurb', 'shuffles-social-services-jobs' ) . '</th><td>';
			echo '<textarea rows="3" class="large-text" name="' . esc_attr( Shuffles_SSJ_Settings::OPTION_KEY . '[hero_blurb]' ) . '" placeholder="' . esc_attr__( 'A safe, accessible marketplace for disability, aged care and social-services work.', 'shuffles-social-services-jobs' ) . '">' . esc_textarea( $hero_blurb ) . '</textarea>';
			echo '<p class="description">' . esc_html__( 'The sub-text under the headline. Leave blank to use your focus programs, or the built-in default.', 'shuffles-social-services-jobs' ) . '</p></td></tr>';

			echo '<tr><td colspan="2"><h3 style="margin:16px 0 0">' . esc_html__( 'Browse (public)', 'shuffles-social-services-jobs' ) . '</h3></td></tr>';
			$this->page_picker_field( 'page_job_board', __( 'All jobs board', 'shuffles-social-services-jobs' ), '[sssj_job_board]', __( 'Both bases in a labelled split.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_tfn_board', __( 'TFN (employee) board', 'shuffles-social-services-jobs' ), '[sssj_tfn_board]', __( 'Employee positions only.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_abn_board', __( 'ABN (contractor) board', 'shuffles-social-services-jobs' ), '[sssj_abn_board]', __( 'Contractor / ABN engagements only.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_volunteer_board', __( 'Volunteer opportunities board', 'shuffles-social-services-jobs' ), '[sssj_volunteer_board]', __( 'Unpaid volunteer roles only.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_worker_directory', __( 'Worker directory', 'shuffles-social-services-jobs' ), '[sssj_worker_directory]', __( 'Find a worker (public).', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_org_directory', __( 'Organisations directory', 'shuffles-social-services-jobs' ), '[sssj_org_directory]', __( 'Browse employers/companies (SEO-able).', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_swipe', __( 'Discover providers (swipe)', 'shuffles-social-services-jobs' ), '[sssj_swipe]', __( 'Tinder-style swipe deck of providers.', 'shuffles-social-services-jobs' ) );

			echo '<tr><td colspan="2"><h3 style="margin:16px 0 0">' . esc_html__( 'Participants (members only)', 'shuffles-social-services-jobs' ) . '</h3></td></tr>';
			$this->page_picker_field( 'page_need_board', __( 'Participant requests', 'shuffles-social-services-jobs' ), '[sssj_need_board]', __( 'Members-only; pseudonymous.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_post_need', __( 'Request support (form)', 'shuffles-social-services-jobs' ), '[sssj_post_need]', __( 'Participant/nominee request form (moderated).', 'shuffles-social-services-jobs' ) );

			echo '<tr><td colspan="2"><h3 style="margin:16px 0 0">' . esc_html__( 'Post / create (member forms)', 'shuffles-social-services-jobs' ) . '</h3></td></tr>';
			$this->page_picker_field( 'page_post_job', __( 'Post a job', 'shuffles-social-services-jobs' ), '[sssj_post_job]', __( 'Advertiser posting form.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_post_worker', __( 'Create worker profile', 'shuffles-social-services-jobs' ), '[sssj_post_worker]', __( 'Worker profile form.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_post_org', __( 'Create organisation profile', 'shuffles-social-services-jobs' ), '[sssj_post_org]', __( 'Employer profile form.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_credentials', __( 'My credentials', 'shuffles-social-services-jobs' ), '[sssj_credentials]', __( 'Workers upload checks for verification.', 'shuffles-social-services-jobs' ) );

			echo '<tr><td colspan="2"><h3 style="margin:16px 0 0">' . esc_html__( 'Member account', 'shuffles-social-services-jobs' ) . '</h3></td></tr>';
			$this->page_picker_field( 'page_onboard', __( 'Get started (onboarding)', 'shuffles-social-services-jobs' ), '[sssj_onboard]', __( 'Guided first-run: pick hats → next steps. Send new members here.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_dashboard', __( 'Member dashboard (all-in-one hub)', 'shuffles-social-services-jobs' ), '[sssj_dashboard]', __( 'The tabbed hub that reveals sections by the member’s hats.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_my_listings', __( 'My listings (legacy)', 'shuffles-social-services-jobs' ), '[sssj_my_listings]', __( 'Just applications + listings (the dashboard above includes this).', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_messages', __( 'Messages (inbox)', 'shuffles-social-services-jobs' ), '[sssj_messages]', __( 'Private relay inbox.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_tests', __( 'Plugin tests (admin/testing)', 'shuffles-social-services-jobs' ), '[sssj_tests]', __( 'The interactive Pass/Fail test worksheet (also at Settings → Testing).', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_why_us', __( 'Why us (benefits)', 'shuffles-social-services-jobs' ), '[sssj_why_us]', __( 'A point-form “why choose us” page — interconnectivity, community, purpose-built, privacy, fair pricing, etc.', 'shuffles-social-services-jobs' ) );
			$wu_points = (string) $this->settings()->get( 'why_us_points', '' );
			if ( '' === trim( $wu_points ) && class_exists( 'Shuffles_SSJ_Display' ) ) {
				$wu_points = Shuffles_SSJ_Display::why_us_points_text();
			}
			echo '<tr><th scope="row">' . esc_html__( 'Why us — benefit points', 'shuffles-social-services-jobs' ) . '</th><td>';
			echo '<textarea name="why_us_points" rows="12" class="large-text code" style="font-family:inherit">' . esc_textarea( $wu_points ) . '</textarea>';
			echo '<p class="description" style="max-width:780px">' . wp_kses_post( __( 'These are the points shown by <code>[sssj_why_us]</code>. <strong>One benefit per line</strong>, in the form <code>icon | Heading | Blurb</code> — e.g. <code>🔗 | Everything connected | Jobs, workers and providers in one place.</code> The icon is optional (a two-part line <code>Heading | Blurb</code> uses a default tick). The box is pre-filled with the current points — edit, reorder, add or remove lines. Clear it completely to restore the built-in defaults.', 'shuffles-social-services-jobs' ) ) . '</p>';
			echo '</td></tr>';
			$this->page_picker_field( 'page_join', __( 'Join (welcome / get started)', 'shuffles-social-services-jobs' ), '[sssj_join]', __( 'A friendly “Join” landing page that funnels people into onboarding, with sign-up / log-in.', 'shuffles-social-services-jobs' ) );

			echo '<tr><td colspan="2"><h3 style="margin:16px 0 0">' . esc_html__( 'Help & content', 'shuffles-social-services-jobs' ) . '</h3></td></tr>';
			$this->page_picker_field( 'page_workflows', __( 'How it works (step-by-step)', 'shuffles-social-services-jobs' ), '[sssj_workflows]', __( 'Plain-English explainer workflows for end users — set up, advertise, apply, quote, manage applicants, request support, store a résumé, join an org, alerts, volunteer, stay safe. Also at Settings → How-to Workflows; the “Guides” advice content lives at Settings → Guides ([sssj_guides]).', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_policies', __( 'Policies (safety & privacy)', 'shuffles-social-services-jobs' ), '[sssj_policies]', __( 'Plain-English summaries of all platform policies — Complaints, Privacy, NDIS Code of Conduct, Incident Management, Safeguarding, Terms, Worker Screening, Data Retention, Cookies, Inclusion, Advertising. Also at Settings → Policies. Link it in your footer.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_marketing', __( 'Marketing master', 'shuffles-social-services-jobs' ), '[sssj_marketing]', __( 'The living marketing + product master (business logic, functional spec, audience analysis) as a readable page. Often partner-facing or internal. Also at Settings → Marketing.', 'shuffles-social-services-jobs' ) );

			echo '</table>';
			submit_button();
			echo '</form>';

			// --- Header menu (Appearance → Menus) ------------------------------------------------
			echo '<hr /><h3 style="margin-top:18px">' . esc_html__( 'Header menu (Appearance → Menus)', 'shuffles-social-services-jobs' ) . '</h3>';
			echo '<p class="description" style="max-width:780px">' . esc_html__( 'Create a real WordPress menu — “Jobs & Engagements” — that mirrors the plugin navigation, so it appears under Appearance → Menus and can be placed in your theme header. The plugin keeps it maintained: once created it re-syncs on each update, adding/removing items as features change (it only touches items it created — anything you add by hand is left alone). Note: a WordPress menu is the same for everyone, so it carries the public items (Jobs, Find a worker, Organisations, Participant requests, Post a job, My dashboard, Log in, Register); the login-aware, capability-gated version (with admin Settings) stays available via the [sssj_menu] shortcode.', 'shuffles-social-services-jobs' ) . '</p>';

			if ( ! empty( $_GET['sssj_nav'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$nav_s = sanitize_text_field( wp_unslash( $_GET['sssj_nav'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( 'error' === $nav_s ) {
					echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Could not create the menu.', 'shuffles-social-services-jobs' ) . '</p></div>';
				} else {
					echo '<div class="notice notice-success inline"><p>' . esc_html__( 'Header menu synced.', 'shuffles-social-services-jobs' ) . '</p></div>';
				}
			}

			if ( class_exists( 'Shuffles_SSJ_Nav_Sync' ) ) {
				$exists   = Shuffles_SSJ_Nav_Sync::menu_exists();
				$cur_loc  = Shuffles_SSJ_Nav_Sync::assigned_location();
				$regs     = get_registered_nav_menus();
				echo '<p>' . ( $exists
					? '<strong>' . esc_html__( 'Status:', 'shuffles-social-services-jobs' ) . '</strong> ' . esc_html__( 'menu exists.', 'shuffles-social-services-jobs' ) . ( $cur_loc ? ' ' . esc_html( sprintf( __( 'Assigned to location: %s.', 'shuffles-social-services-jobs' ), isset( $regs[ $cur_loc ] ) ? $regs[ $cur_loc ] : $cur_loc ) ) : ' ' . esc_html__( 'Not yet assigned to a theme location.', 'shuffles-social-services-jobs' ) )
					: '<strong>' . esc_html__( 'Status:', 'shuffles-social-services-jobs' ) . '</strong> ' . esc_html__( 'not created yet.', 'shuffles-social-services-jobs' ) ) . '</p>';

				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
				echo '<input type="hidden" name="action" value="sssj_sync_nav" />';
				wp_nonce_field( 'sssj_sync_nav' );
				if ( $regs ) {
					echo '<p><label>' . esc_html__( 'Also assign to theme location:', 'shuffles-social-services-jobs' ) . ' <select name="assign_location"><option value="">' . esc_html__( '— don’t change —', 'shuffles-social-services-jobs' ) . '</option>';
					foreach ( $regs as $slug => $desc ) {
						echo '<option value="' . esc_attr( $slug ) . '" ' . selected( $cur_loc, $slug, false ) . '>' . esc_html( $desc ) . '</option>';
					}
					echo '</select></label></p>';
				}
				submit_button( $exists ? __( 'Sync header menu now', 'shuffles-social-services-jobs' ) : __( 'Create header menu', 'shuffles-social-services-jobs' ), 'secondary' );
				echo '</form>';
				echo '<p class="description"><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">' . esc_html__( 'Open Appearance → Menus', 'shuffles-social-services-jobs' ) . '</a></p>';
			}
			break;

		case 'logic':
			echo '<h2>' . esc_html__( 'Business Logic', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description">' . esc_html__( 'How this plugin decides things — the rules behind gating, visibility, verification, segregation, privacy and the automated checks. Written in plain English.', 'shuffles-social-services-jobs' );
			if ( class_exists( 'Shuffles_SSJ_Business_Rules' ) ) {
				echo ' ' . esc_html( sprintf( __( 'Last updated: %s.', 'shuffles-social-services-jobs' ), Shuffles_SSJ_Business_Rules::last_updated() ) );
			}
			echo '</p>';
			if ( class_exists( 'Shuffles_SSJ_Business_Rules' ) ) {
				foreach ( Shuffles_SSJ_Business_Rules::sections() as $i => $sec ) {
					echo '<h3 style="margin:18px 0 4px">' . esc_html( ( $i + 1 ) . '. ' . $sec['title'] ) . '</h3>';
					if ( ! empty( $sec['intro'] ) ) {
						echo '<p class="description" style="margin-top:0">' . esc_html( $sec['intro'] ) . '</p>';
					}
					if ( ! empty( $sec['rules'] ) ) {
						echo '<ul class="ul-disc" style="margin-left:18px">';
						foreach ( $sec['rules'] as $rule ) {
							echo '<li>' . esc_html( $rule ) . '</li>';
						}
						echo '</ul>';
					}
				}
				$inv = Shuffles_SSJ_Business_Rules::invariants();
				if ( $inv ) {
					echo '<h3 style="margin:22px 0 4px">' . esc_html__( 'Key invariants — the “never” rules', 'shuffles-social-services-jobs' ) . '</h3>';
					echo '<ol style="margin-left:18px">';
					foreach ( $inv as $rule ) {
						echo '<li>' . esc_html( $rule ) . '</li>';
					}
					echo '</ol>';
				}
				echo '<p class="description" style="margin-top:16px">' . esc_html__( 'The full technical version (with the functions/hooks that enforce each rule) lives in docs/business_rules_and_logic.md in the plugin repository.', 'shuffles-social-services-jobs' ) . '</p>';
			}
			break;

		case 'import':
			echo '<h2>' . esc_html__( 'Provider Import (beta)', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description">' . wp_kses_post( sprintf(
				/* translators: 1: active providers dataset URL, 2: compliance actions dataset URL */
				__( 'Proof of concept — <strong>preview only</strong>. This tool reads the NDIS Commission’s official bulk datasets and shows what it found; <strong>it does not write anything</strong> (no organisations are created, no data is changed). Get the <strong>Active providers</strong> CSV from the <a href="%1$s" target="_blank" rel="noopener">NDIS provider datasets</a>, and the <strong>Compliance actions</strong> CSV from <a href="%2$s" target="_blank" rel="noopener">data.gov.au</a>. Columns are auto-detected so you can confirm the mapping before any future import is enabled.', 'shuffles-social-services-jobs' ),
				esc_url( 'https://dataresearch.ndis.gov.au/datasets/provider-datasets' ),
				esc_url( 'https://www.data.gov.au/' )
			) ) . '</p>';
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Import is disabled in this proof of concept — uploads are previewed only. Nothing is saved.', 'shuffles-social-services-jobs' ) . '</p></div>';

			$rep = class_exists( 'Shuffles_SSJ_Provider_Import' ) ? Shuffles_SSJ_Provider_Import::pull_report() : null;
			if ( is_array( $rep ) ) {
				if ( '' !== $rep['error'] ) {
					echo '<div class="notice notice-error inline"><p>' . esc_html( $rep['error'] ) . '</p></div>';
				} else {
					$mode = $rep['dry_run'] ? esc_html__( 'Preview (dry run — nothing written)', 'shuffles-social-services-jobs' ) : esc_html__( 'Imported', 'shuffles-social-services-jobs' );
					echo '<div class="notice notice-success inline"><p><strong>' . $mode . '.</strong> ';
					echo esc_html( sprintf( __( '%1$s data rows read%2$s.', 'shuffles-social-services-jobs' ), number_format_i18n( $rep['total'] ), $rep['truncated'] ? '+' : '' ) );
					if ( ! $rep['dry_run'] ) {
						if ( 'compliance' === $rep['kind'] ) {
							echo ' ' . esc_html( sprintf( __( 'Compliance rows passed to integrations: %d.', 'shuffles-social-services-jobs' ), $rep['fired'] ) );
						} else {
							echo ' ' . esc_html( sprintf( __( 'Created %1$d, updated %2$d, skipped %3$d (cap %4$d).', 'shuffles-social-services-jobs' ), $rep['created'], $rep['updated'], $rep['skipped'], $rep['cap'] ) );
						}
					}
					echo '</p></div>';

					// Detected column mapping.
					echo '<h3>' . esc_html__( 'Detected columns', 'shuffles-social-services-jobs' ) . '</h3><p class="description">';
					$pairs = array();
					foreach ( $rep['mapping'] as $field => $idx ) {
						$col = ( null !== $idx && isset( $rep['headers'][ $idx ] ) ) ? $rep['headers'][ $idx ] : '—';
						$pairs[] = $field . ' → ' . $col;
					}
					echo esc_html( implode( '  ·  ', $pairs ) ) . '</p>';

					// Sample of parsed rows.
					if ( ! empty( $rep['sample'] ) ) {
						echo '<h3>' . esc_html__( 'Sample rows', 'shuffles-social-services-jobs' ) . '</h3>';
						echo '<table class="widefat striped"><thead><tr><th>Name</th><th>ABN</th><th>Reg&nbsp;ID</th><th>Status</th><th>State</th><th>Suburb</th></tr></thead><tbody>';
						foreach ( $rep['sample'] as $s ) {
							$nm = '' !== $s['legal_name'] ? $s['legal_name'] : $s['trading_name'];
							echo '<tr><td>' . esc_html( $nm ) . '</td><td>' . esc_html( $s['abn'] ) . '</td><td>' . esc_html( $s['register_id'] ) . '</td><td>' . esc_html( $s['status'] ) . '</td><td>' . esc_html( $s['state'] ) . '</td><td>' . esc_html( $s['suburb'] ) . '</td></tr>';
						}
						echo '</tbody></table>';
					}
				}
			}

			echo '<h3 style="margin-top:18px">' . esc_html__( 'Upload a CSV to preview', 'shuffles-social-services-jobs' ) . '</h3>';
			echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			echo '<input type="hidden" name="action" value="sssj_provider_import" />';
			wp_nonce_field( 'sssj_provider_import' );
			echo '<table class="form-table" role="presentation">';
			echo '<tr><th scope="row">' . esc_html__( 'Dataset', 'shuffles-social-services-jobs' ) . '</th><td><select name="kind"><option value="active">' . esc_html__( 'Active providers → organisations', 'shuffles-social-services-jobs' ) . '</option><option value="compliance">' . esc_html__( 'Compliance / enforcement actions → integrations', 'shuffles-social-services-jobs' ) . '</option></select></td></tr>';
			echo '<tr><th scope="row">' . esc_html__( 'CSV file', 'shuffles-social-services-jobs' ) . '</th><td><input type="file" name="csv" accept=".csv,text/csv" required /></td></tr>';
			echo '</table>';
			submit_button( __( 'Upload & preview', 'shuffles-social-services-jobs' ) );
			echo '</form>';
			break;

		case 'cron':
			echo '<h2>' . esc_html__( 'Cron Job List & Status', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p class="description">' . esc_html__( 'The plugin’s scheduled background jobs (WordPress cron). For each: how often it runs, when it last ran, when it is next due, and whether the last run completed. “Run now” triggers a job immediately (useful for testing).', 'shuffles-social-services-jobs' ) . '</p>';
			if ( ! empty( $_GET['sssj_cron_ran'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				echo '<div class="notice notice-success inline"><p>' . esc_html( sprintf( __( 'Ran “%s” now.', 'shuffles-social-services-jobs' ), sanitize_text_field( wp_unslash( $_GET['sssj_cron_ran'] ) ) ) ) . '</p></div>'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			}
			if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
				echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Note: DISABLE_WP_CRON is on for this site, so jobs run from a real system cron hitting wp-cron.php rather than on page loads. Next-run times below are still the scheduled times.', 'shuffles-social-services-jobs' ) . '</p></div>';
			}
			$rows     = class_exists( 'Shuffles_SSJ_Cron_Monitor' ) ? Shuffles_SSJ_Cron_Monitor::rows() : array();
			$fmt      = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			$state_lbl = array(
				'ok'         => array( __( 'Completed OK', 'shuffles-social-services-jobs' ), '#166534', '#dcfce7' ),
				'running'    => array( __( 'Running…', 'shuffles-social-services-jobs' ), '#92400e', '#fef3c7' ),
				'never'      => array( __( 'Not run yet', 'shuffles-social-services-jobs' ), '#475569', '#e2e8f0' ),
				'incomplete' => array( __( 'Did not complete', 'shuffles-social-services-jobs' ), '#b91c1c', '#fee2e2' ),
				'failed'     => array( __( 'Reported errors', 'shuffles-social-services-jobs' ), '#b91c1c', '#fee2e2' ),
			);
			echo '<table class="widefat striped"><thead><tr>'
				. '<th>' . esc_html__( 'Job', 'shuffles-social-services-jobs' ) . '</th>'
				. '<th>' . esc_html__( 'Frequency', 'shuffles-social-services-jobs' ) . '</th>'
				. '<th>' . esc_html__( 'Last run', 'shuffles-social-services-jobs' ) . '</th>'
				. '<th>' . esc_html__( 'Next run due', 'shuffles-social-services-jobs' ) . '</th>'
				. '<th>' . esc_html__( 'Status', 'shuffles-social-services-jobs' ) . '</th>'
				. '<th>' . esc_html__( 'Action', 'shuffles-social-services-jobs' ) . '</th>'
				. '</tr></thead><tbody>';
			foreach ( $rows as $r ) {
				$sl   = isset( $state_lbl[ $r['state'] ] ) ? $state_lbl[ $r['state'] ] : $state_lbl['never'];
				$last = $r['last'] ? date_i18n( $fmt, $r['last'] + ( (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) : '—';
				$next = $r['next'] ? date_i18n( $fmt, $r['next'] + ( (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) : esc_html__( 'Not scheduled', 'shuffles-social-services-jobs' );
				$run  = wp_nonce_url( admin_url( 'admin-post.php?action=sssj_cron_run&hook=' . rawurlencode( $r['hook'] ) ), 'sssj_cron_run_' . $r['hook'] );
				echo '<tr>';
				echo '<td><strong>' . esc_html( $r['label'] ) . '</strong><br /><span class="description">' . esc_html( $r['desc'] ) . '</span><br /><code style="font-size:11px">' . esc_html( $r['hook'] ) . '</code></td>';
				echo '<td>' . esc_html( $r['frequency'] ) . '</td>';
				echo '<td>' . esc_html( $last ) . '</td>';
				echo '<td>' . esc_html( $next ) . '</td>';
				echo '<td><span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;color:' . esc_attr( $sl[1] ) . ';background:' . esc_attr( $sl[2] ) . '">' . esc_html( $sl[0] ) . '</span>';
				if ( '' !== $r['note'] ) {
					echo '<br /><span class="description">' . esc_html( $r['note'] ) . '</span>';
				}
				echo '</td>';
				echo '<td><a class="button button-small" href="' . esc_url( $run ) . '">' . esc_html__( 'Run now', 'shuffles-social-services-jobs' ) . '</a></td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
			break;

		case 'compliance':
			echo '<h2>' . esc_html__( 'Compliance & credentials', 'shuffles-social-services-jobs' ) . '</h2>';
			echo '<p>' . sprintf(
				/* translators: %s: current compliance profile name */
				esc_html__( 'Current default profile: %s. Workers upload their checks (WWCC, NDIS Worker Screening, police check, certifications) via the [sssj_credentials] shortcode; an administrator reviews the evidence and approves or rejects. The ✓ Verified badge is set ONLY by admin approval — never from user input. Evidence files are stored privately and served only to the owner and admins.', 'shuffles-social-services-jobs' ),
				'<strong>' . esc_html( (string) $this->settings()->get( 'compliance_profile', '' ) ) . '</strong>'
			) . '</p>';
			$pend_c = class_exists( 'Shuffles_SSJ_Credentials' ) ? Shuffles_SSJ_Credentials::count_by_status( 'pending' ) : 0;
			echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=shuffles-ssj-verify' ) ) . '">' . esc_html( sprintf( __( 'Open verification queue (%d pending)', 'shuffles-social-services-jobs' ), $pend_c ) ) . '</a> ';
			echo '<a class="button" href="' . esc_url( admin_url( 'edit-tags.php?taxonomy=sssjt_compliance_profile&post_type=sssj_job' ) ) . '">' . esc_html__( 'Manage compliance profiles', 'shuffles-social-services-jobs' ) . '</a></p>';
			$open_form( 'compliance' );
			echo '<table class="form-table" role="presentation">';
			$this->number_field( 'credential_reminder_days', __( 'Expiry reminder lead time (days)', 'shuffles-social-services-jobs' ), __( 'Workers are emailed this many days before a credential expires, and again on the expiry day. An expired credential automatically drops the verified badge.', 'shuffles-social-services-jobs' ), 1, 365 );
			$this->key_field(
				'abr_guid',
				__( 'ABR Web Services GUID (ABN verification)', 'shuffles-social-services-jobs' ),
				__( 'Register free for an authentication GUID at the <a href="https://abr.business.gov.au/Tools/WebServices" target="_blank" rel="noopener">ABR Web Services</a> site (you receive the GUID by email). Paste it here. When set, any ABN entered on a job, worker or organisation is <strong>checked against the Australian Business Register</strong> on save — the entity name + ABN status are stored and shown as an “ABR Active · &lt;Name&gt;” badge. Leave blank to keep the offline checksum validation only.', 'shuffles-social-services-jobs' )
			);
			echo '<tr><td colspan="2"><h3 style="margin:16px 0 0">' . esc_html__( 'NDIS provider register auto-check', 'shuffles-social-services-jobs' ) . '</h3>'
				. '<p class="description">' . esc_html__( 'When an organisation enters its NDIS Registration No (the number after ?id= in its provider-register URL), the plugin reads that public listing and stores the Registration status, the approved registration groups and the “in force until” date — shown as a table on the organisation’s profile. A monthly background check re-reads each listing and emails you if anything changes (status, groups, or the expiry date), so you can re-verify. Nothing is ever emailed to the provider. This reads the NDIS Commission’s own public register; keep it on unless you prefer to verify entirely by hand.', 'shuffles-social-services-jobs' ) . '</p></td></tr>';
			$this->checkbox_field( 'ndis_scan_enabled', __( 'Monthly NDIS register check', 'shuffles-social-services-jobs' ), __( 'Read each registered organisation’s NDIS Commission listing on save and once a month, and alert on any change.', 'shuffles-social-services-jobs' ) );
			$this->text_field( 'ndis_alert_email', __( 'Change-alert email', 'shuffles-social-services-jobs' ), __( 'Where to send “NDIS registration changed” alerts. Leave blank to use the site admin email. Alerts go to staff only — never to the provider.', 'shuffles-social-services-jobs' ), 'email' );
			echo '</table>';
			submit_button();
			echo '</form>';
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
				<h3>v0.96.0 · 2026-06-07 · feature spotlight: stop / reverse the ball of light</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The “Today’s Highlighted Site Feature” tile now has a small control: tap once to stop or start the orbiting ball of light, double-tap to reverse its direction. A real focusable button serves keyboard and screen-reader users (Space or Enter to stop/start, R to reverse), and the control is hidden when the OS prefers reduced motion.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.95.0 · 2026-06-07 · feature spotlight: a real ball of light tracing the border</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The “Today’s Highlighted Site Feature” tile now shows a bright ball of light that continuously traces around the outside of the border (a soft glowing comet over a static rainbow edge), instead of the previous brief, hard-to-see effect. It still respects reduce-motion.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.94.0 · 2026-06-07 · Home menu item + feature spotlight link fix</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Added a “Home” item to the navigation (both the [sssj_menu] bar and the synced “Jobs & Engagements” menu), shown first.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Fixed the “Today’s Highlighted Site Feature” tile: the “Learn more” link now points to the feature’s own page, or, if that is not published yet, to the Marketing, Why us or How it works page. If none of those exist it hides the link instead of going nowhere. Tip: publish the Marketing (or Why us / How it works) page so every spotlight link lands on rich content.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Settings → Marketing “Create page” now shows View and Edit links for the new page straight after creating it (reload the tab for the permanent buttons).', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.93.0 · 2026-06-07 · marketing page, image guidance + video, advertising policy</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The marketing master is now a readable page: new [sssj_marketing] shortcode renders docs/MARKETING-MASTER.md, with a new Settings → Marketing tab to preview it and create the page in one click. The document file is the single source (location: docs/MARKETING-MASTER.md in the plugin folder).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Every photo and logo upload (worker profile, organisation, post a job) now shows an “Image guide” with preferred dimensions and what makes good, valuable, consented content.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New promotional video field on worker, organisation and job forms: paste a YouTube or Vimeo link to super-sell your brand or service. It embeds responsively and privacy-friendly on the single page; only trusted video hosts are accepted.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New Advertising and Media Production policy (the 11th): prefer not to use children, fair pay for commercial media, and written informed consent including any agreed remuneration. Published in [sssj_policies] and as a /docs template.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Testing, Business Logic and the Shortcodes reference updated. No em dashes in new content.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.92.0 · 2026-06-07 · editable hero blurb + daily feature spotlight</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Hero headline and main blurb are now editable in Settings → Pages → “Home page hero”. [sssj_hero] uses them whenever its title/subtitle attributes are blank, so you can change the wording without touching the shortcode.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New [sssj_feature_today] shortcode and matching Elementor widget: a full-width tile titled “Today’s Highlighted Site Feature” that highlights one built-in feature per day (rotating daily, the same for everyone), with a short explanation and a “Learn more” link to the relevant page, and the invitation to come back tomorrow.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The spotlight tile has a rainbow light that traces around its border briefly on load, then settles, and respects reduce-motion. Single source of truth in includes/class-spotlight.php.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Testing and the Shortcodes reference updated.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.91.0 · 2026-06-07 · easier participant advertising, listing lifecycle, dashboard tabs, marketing master</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Participant “Request a worker” form rebuilt to be super easy and professional: clear numbered steps (What you need, Where, The kind of support, When and preferences, Close date), friendly plain-English help, one required field, and a reassuring privacy intro.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Listing lifecycle: every participant request (and job ad) has a close date. If it is not filled by then it auto-closes. Owners get a “closing soon” reminder and a “now closed” reminder, and can reopen (“rebirth”) it in one click from My dashboard, which gives it a fresh close date. Requests with no date set default to about two months out.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'My Dashboard now includes an “Earn” tab (referrals, with the PayPal note and the referral dashboard) and a “Support” tab (help desk), each shown only when that capability is available, separated by tabs.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New living marketing master document (docs/MARKETING-MASTER.md) combining the business logic, the functional spec, and an out-of-the-box audience analysis. It names no third-party tools, describing everything as our internally curated and constructed tech stack and customised workflows.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'House style: em dashes are no longer used anywhere in new content. Testing and Business Logic updated.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.90.0 — 2026-06-07 · earn by referring (FluentAffiliate in onboarding)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Onboarding now invites members — especially participants — to earn money by referring others. A friendly “Earn money by referring others” card appears at the end of onboarding (extra encouragement for participants), links to the affiliate sign-up, and never blocks finishing onboarding.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Clearly tells members they need a PayPal account to be paid (with a link to create one) and that they can set it up later.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Standalone-safe FluentAffiliate integration (never bundled/required): auto-links to your [fluent_affiliate_portal] page, or a URL you set. New [sssj_affiliate] shortcode places the same card anywhere (e.g. the dashboard). Configure + master switch in Settings → Monetisation.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Renders nothing (no errors) when FluentAffiliate is inactive or the promo is switched off. Testing + Shortcodes reference + FEATURES updated.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.89.0 — 2026-06-07 · Advanced Ads integration (banners in the marketplace)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Optional, standalone-safe integration with the Advanced Ads plugin (never bundled or required). New [sssj_ad] shortcode places a banner anywhere by placement slug, ad id or group id.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Named ad slots — Board top, Board bottom and Single listing (below content) — map an Advanced Ads placement to each in the new Settings → Ads tab; the boards and listing pages then show them automatically (a slot value can be a placement slug or id:123).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Detection + master on/off switch: shows whether Advanced Ads is active; if it is inactive or ads are switched off, nothing renders and nothing breaks. Ads are labelled “Advertisement”.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Testing + Shortcodes reference updated. (Migrating existing banners from another site is a separate WordPress/Advanced Ads export-import — see the migration runbook.)', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.88.0 — 2026-06-07 · policies completed & published</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'All ten platform policies drafted as formal /docs templates: Privacy, NDIS Code of Conduct, Incident Management & Reportable Incidents, Safeguarding & Risk, Terms of Use / Acceptable Use, Worker Screening & Verification, Data Retention & Destruction, Cookie & Consent, and Anti-Discrimination & Inclusion (Complaints was already drafted).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Published, member-facing layer: new [sssj_policies] shortcode + Settings → Policies tab — plain-English, easy-read summaries of every policy with key points and the NDIS / OAIC / AHRC + interpreter contacts. Single source: includes/class-policies.php.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New “Policies (safety & privacy)” page mapping with one-click create (Settings → Pages → Help & content) and a “Policies” item in the navigation (shortcode menu + synced Appearance menu).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Policy register (docs/POLICIES.md) updated — all ten now show a formal template + a published summary; the remaining work is your review, bracket fill-in and formal adoption (Terms liability/governing-law needs legal review). Testing + Shortcodes reference updated.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.87.0 — 2026-06-07 · member reviews & ratings (contractors + providers)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New star-rating + written-review system for contractors (worker profiles) and providers (organisations). Reviews show automatically on the profile page with an average summary, individual reviews and the owner’s public response.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Trust by design: you can only review someone you have genuinely engaged with (a relay message exists between you — applying starts one); you cannot review yourself; one editable review per member per subject.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Every review is pre-moderated — held as Pending until an admin approves it in the new Settings → Reviews & Ratings tab (Approve / Reject, with a master on/off switch).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The approved average is cached on the profile and feeds the matching “trust” signal (well-rated workers rank a little higher with a “Rated X★” reason). New sssj_review table (DB v8) — load wp-admin once after updating so the table is created.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Testing: new “Reviews & ratings” suite (engagement gate, pre-moderation, approve/reject, response, trust signal, master switch). Business Logic updated.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.86.0 — 2026-06-07 · explainer workflows for end users (“How it works”)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New [sssj_workflows] shortcode and Settings → How-to Workflows tab: eleven plain-English, step-by-step walkthroughs that show end users the exact path through the app — set up your account, advertise a role, apply for an employee (TFN) job, quote for contractor (ABN) work, review applicants, request support privately, store a résumé, join an organisation, save alerts, volunteer, and stay safe.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Each workflow has a Goal, a “Before you start” checklist, numbered steps with location hints, a “Done” outcome, and a self-healing “Start here” button. For logged-in members, workflows matching their primary role float to the top with a “For you” marker (nothing is hidden). Optional only="…" and roles="…" attributes filter the list.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Single source of truth in includes/class-workflows.php (distinct from the advice-style Guides). Added a “How it works” page mapping with one-click create (Settings → Pages → Help & content) and a “How it works” item in the navigation (shortcode menu + synced Appearance menu).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Testing updated: the “Guides, help & explainer workflows” suite now covers rendering, the Start-here links, primary-role “For you” ordering, attribute filtering, and the navigation item.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.85.0 — 2026-06-07 · application pipeline phase 3 (Hired/Declined · history · withdraw · notifications)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Application stages now include Hired and Declined. Employers move applicants through the pipeline in My listings; jobs set to “Full pipeline” show all stages plus a Status history, while “Simple” jobs show a minimal set.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Candidates can Withdraw their own application from My dashboard → (My applications).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Applicants are emailed automatically when an advertiser changes their status (e.g. shortlisted, offer, hired). Turn off with the shuffles_ssj_send_application_email filter.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.84.0 — 2026-06-07 · testing worksheet — per-area objectives</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The Testing worksheet (Settings → Testing and [sssj_tests]) now states an overall Objective for each area of checks, and shows the number of checks per group — so testers know what each group is proving, not just the steps.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.83.0 — 2026-06-07 · primary role (focus) + menu “See all”</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Members can choose a primary role in “My roles”. The dashboard then opens to that role’s tab, and the menu focuses on its items — keeping the experience clean for single-purpose members.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Nothing is locked away: a “See all” dropdown in the [sssj_menu] reveals every item the member can use, and the dashboard still shows all their tabs. Leave the primary role on “No preference” to show everything by default.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.82.0 — 2026-06-07 · Demo Users settings tab + “View as”</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New Settings → Demo Users tab lists the seeded demo/test accounts with their username, initial password and function(s), so you can test each side of the marketplace. The seeder now records each initial password for this list.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( '“View as” — one click to browse the site as a demo user, with a “Return to admin” link in the toolbar to switch back. Admins only, and only for demo accounts (safe-gated).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'For testing only — passwords are shown in plain text; remove the demo accounts before production (one-line cleanup in dist/seed-demo.php). Run the seeder with: wp eval-file dist/seed-demo.php', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.81.0 — 2026-06-07 · advertise anonymously (employers)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Employers can tick “Advertise anonymously” when posting a job. The listing then shows an “🕶️ Anonymous” badge instead of the organisation name/logo, the organisation isn’t revealed (it’s also kept off that organisation’s public “open positions”), and the advertiser’s name is kept out of search engines (structured data shows “Private advertiser”).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Participant requests are already anonymous by design — pseudonymous, members-only and never indexed — so this option is built in for participants.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.80.0 — 2026-06-07 · Volunteer roles as their own opportunity type</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Volunteer (unpaid) is now a third engagement type alongside TFN and ABN. Choose “Volunteer” when posting a job (no ABN, no pay).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New [sssj_volunteer_board] shows only volunteer roles — segregated in the query layer, just like the TFN and ABN boards (set its page in Settings → Pages). Volunteer roles show a green “Volunteer” badge.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Applying to a volunteer role is open to any logged-in member (no ABN required).', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.79.0 — 2026-06-07 · “Open to…” options (visa sponsorship · work placements · volunteers)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Jobs and organisations can now flag what they’re open to: ✈️ open to overseas applicants / visa sponsorship, and 🎓 work-placement (student placement) enquiries. Organisations can also flag 🤝 “volunteers welcome”.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'These show as badges on job cards, the jobs board, organisation profiles and the organisations directory.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Next: Volunteer roles as a full opportunity type (their own board), and (separately) the application pipeline phase 3.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.78.0 — 2026-06-07 · TFN apply form + screening questions + per-job mode (phase 2)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Applying to an employee (TFN) job now captures more: the candidate picks one of their stored résumés, enters availability + earliest start date, confirms right-to-work, and answers the employer’s screening questions.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'When posting a job, the employer can add screening questions (one per line, up to 12) and choose how applications are handled — Full pipeline (track stages) or Simple — changeable later.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Employers see all of this on each applicant in My dashboard → My listings (résumé link, availability, start date, right-to-work, and the screening answers). The chosen résumé opens only for the employer who was applied to.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Next (phase 3): Hired/Declined stages, status history, candidate withdraw, and email notifications.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.77.0 — 2026-06-07 · stored résumés (TFN application — phase 1)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Candidates can now store one or more named résumés against their profile (PDF / Word / RTF / ODT, up to 5), set a default, and remove old ones — via the new “My résumés” tab in the member dashboard, or the [sssj_resumes] shortcode.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Résumés are private: stored securely and served only to the owner, admins, and (next phase) the employer you apply to. Same safe storage approach as credential files.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'This is phase 1 of the TFN employment application flow. Next: the apply form picks a stored résumé and adds availability / start date / right-to-work / employer screening questions, with a per-job application-handling mode.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.76.0 — 2026-06-07 · “Save & alert me” visible to logged-out visitors</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The “🔔 Save & alert me” button on the boards is now visible to logged-out visitors too — so they can see the feature exists. It isn’t active for them: clicking it sends them to log in and returns to the exact same search, where they can then save it. Saving alerts still requires being logged in.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.75.0 — 2026-06-07 · auto-scrolling “Why us” carousel · title = your site name</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The [sssj_why_us] carousel now auto-scrolls (one card at a time, looping), pauses when you hover or touch it, and the raw scrollbar is hidden. Control it with autoscroll="on|off|4000" (milliseconds between steps).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The default “Why us” heading now uses your site name (e.g. “Why Just Tasks”) instead of a fixed brand. Override any time with title="…".', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.74.0 — 2026-06-07 · edit the “Why us” points in Settings</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'You can now edit the “Why us” benefit points without code: Settings → Pages → “Why us — benefit points”. One benefit per line as “icon | Heading | Blurb”. The box is pre-filled with the current points to edit, reorder, add or remove. Clear it to restore the built-in defaults.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.73.0 — 2026-06-07 · “Why us” layout options (carousel / columns / rows / font)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( '[sssj_why_us] now supports layout="grid" (default) or layout="carousel" (a horizontal, snap-scrolling row).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'per_row="N" sets the number of columns (grid) or cards in view (carousel); rows="N" limits how many rows show (caps to per_row × rows benefits). Leave blank for a responsive auto layout.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'font="theme" (default) makes the block use the same font as the page/theme; font="brand" uses the plugin’s configured font. On small screens grids collapse to one column so cards stay readable.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.72.0 — 2026-06-07 · Join page · hero buttons fix · location layout</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New [sssj_join] custom “Join” page — a friendly welcome that funnels people into onboarding, with Create-account / Log-in and quick links (browse jobs, find a worker, browse organisations). Add it via Settings → Pages → “Join (welcome / get started)”.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Fixed: the hero banner (and other shortcodes) only showing the first button when several were set. The editor’s “smart quotes” were corrupting the later attributes; the plugin now keeps straight quotes inside its shortcodes so all buttons (up to four) appear. Tip: if a button still misbehaves, avoid apostrophes/curly quotes in the label.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Board layout: the location / radius / “use my location” controls now sit on their own row, directly below the search and filter inputs, on the Jobs, Worker, Organisations and Participant-request boards.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.71.0 — 2026-06-07 · “Why us” benefits page</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New [sssj_why_us] shortcode — a point-form “Why us” / benefits page, each point with a short blurb: everything connected in one place, plugged into the community Facebook groups, purpose-built for social services, employment + contracting side by side, résumé/flyer creation, you control your privacy (hide your profile), high visibility, and giving back with fair pricing (participants free).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Add it via Settings → Pages → “Why us (benefits)” (Create page inserts the shortcode), or place [sssj_why_us] on any page. Edit the list from one place with the shuffles_ssj_why_us filter.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.70.0 — 2026-06-07 · more hero buttons · featured-role teaser</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Hero banner [sssj_hero] now supports up to FOUR call-to-action buttons (button_text/url, button2_*, button3_*, button4_*). The first is the primary button; the rest are outline buttons.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Featured roles [sssj_featured] now show a short teaser (about 40 characters) from each advertised position’s description, under the rate.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.69.0 — 2026-06-07 · request to join an organisation (admin approves)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Members can now ask to join an organisation. On any organisation’s profile, a logged-in member sees a “Request to join this organisation” button (with an optional message). They can cancel a pending request.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Org admins review requests in My dashboard → Team: a “Requests to join” list with Approve (as Member or Admin) and Decline. Approving adds them to the team; no one joins without admin approval. The Team tab shows a count of pending requests.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Org admins are emailed when a new request comes in (can be turned off with the shuffles_ssj_send_join_request_email filter).', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.68.0 — 2026-06-07 · full ABR record (trading names) · menu repoint · best-practice guide</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'ABN / ABR: when an ABN is saved (and an ABR GUID is set in Settings → Compliance), the FULL Australian Business Register response is now recorded — entity name, trading / business names, ABN status & date, entity type, ACN, GST registration and main business location — in one read-only “recorded details” field on the worker and organisation forms, and shown on the organisation’s public profile. (The ABR key field and the on-save check already existed in Settings → Compliance.)', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Header menu repoint: menu/links now self-heal to the page that actually contains the right shortcode — so “My dashboard” resolves to the all-in-one dashboard hub and “Jobs” resolves to our jobs board, even if an old/legacy page was previously mapped. The header menu re-syncs automatically on this update.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New guide “Best practice: creating a flyer or résumé (social services)” — national, plain-English, person-centred and privacy-safe guidance, available now under Settings → Guides and via [sssj_guides]. It will also appear inline when the asset creator is built.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.67.0 — 2026-06-07 · location autocomplete fixes + apply links</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Worker profile: the “Your location” field now has address autocomplete and records the precise lat/long behind the scenes (filling Suburb / State / Postcode), so the profile works correctly in the directory and in radius matching. (The location map/autocomplete script wasn’t being loaded on the worker form.)', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Boards: choosing a location from the autocomplete now recenters and zooms the results map to that place AND refreshes the results via AJAX (a sensible default radius is applied so the location actually narrows results). Previously the map stayed put and the filter ignored the chosen place.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Apply for a contractor (ABN) job, or respond to a participant request, when your profile has no ABN: the “add a valid ABN” message now includes an “Edit my profile” button/link straight to your profile.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.66.0 — 2026-06-07 · D · multi-user organisations (teams)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Organisations can now have a team. The owner (creator) plus any number of members — each a “Member” or an “Admin” — can belong to one organisation.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New “Team” tab in the member dashboard (and a [sssj_org_team] shortcode): an org admin can add an existing person by email or username, change a member’s role, or remove someone. The owner can never be removed or demoted.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Safety: accounts are never created here (the person must already be registered — otherwise you’re prompted to ask them to sign up first), and removing a member only unlinks them from the organisation; their account is not deleted.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Added team members gain the ability to post jobs for the organisation.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.65.0 — 2026-06-07 · C5 · smart synonym-aware search</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Search now understands sector language. A search for “support work” also finds “Disability Support Worker”, “carer”, “DSW”, “PCA” and similar; “aged care” matches “home care” and “elderly care”; “OT” matches “occupational therapist”, and so on across the Jobs, Worker and Organisations boards.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Synonyms broaden a search rather than narrowing it — related terms are matched with OR, so a sensible search never collapses to zero results.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Built deterministically now and ready for a smarter (AI) expander later via a single hook — with no change to the boards and nothing vendor-named shown to members.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.64.0 — 2026-06-07 · C4 · per-field privacy masking (“members only”)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New privacy control on the worker and organisation profile forms: tick a sensitive field to show it only to logged-in members. Logged-out visitors then see a “🔒 Log in to view” note instead of the value; signed-in members, the owner and admins still see it.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Maskable fields: a worker’s pay rate; an organisation’s phone number and website. The profile itself stays findable in the directory — only the chosen field’s value is hidden from guests.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Applies everywhere the field shows — profile pages and the directory cards (including the instant-filter results) — from one setting.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Safety: masking is additive and can never reveal NDIS-register data, which stays read-only; participant privacy and worker visibility rules are unchanged.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.63.0 — 2026-06-07 · instant AJAX filtering · “I need support” first</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Directory filtering is now instant. When you change the search box, location, radius, categories or any filter on the Jobs, Employee, Contractor, Worker, Organisations and Participant-request boards, only the result tiles refresh — the page no longer reloads or jumps to the top, and your cursor stays in the search box. The address bar still updates so the filtered view is shareable.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Pagination (Next / Previous / page numbers) on those boards also loads in place via AJAX, with the Shuffles spinner while results load.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Graceful fallback: if a board hasn’t opted in or JavaScript is unavailable, filtering still works the old way (a normal page reload).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Onboarding (Get started) now lists “I need support” as the first group of hats, ahead of “I’m looking for work” and “I offer work or services”.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.62.0 — 2026-06-07 · page mappings for onboarding / dashboard / swipe / tests</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Settings → Pages now includes mappings (lookup / create / edit) for the Get started (onboarding), Member dashboard (all-in-one hub), Discover providers (swipe) and Plugin tests pages — so they can be created and placed like the other pages.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.61.0 — 2026-06-07 · onboarding wizard · provider size/structure · sole-trader listings</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'C1 — Get started: a new [sssj_onboard] guided wizard. New members tick the hats that apply, then get tailored next-step buttons (set up profile, create organisation/provider listing, post a job, request support, go to dashboard).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'C2 — Provider size & structure: organisations can record a size (Sole trader / Small / Medium / Large) and a legal structure (Sole trader / Partnership / Company / Not-for-profit / Government). Both are shown as badges and are new filters on the Organisations directory.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'C3 — Sole traders in the providers directory: a member with the “Available for contracting (sole trader / ABN)” hat can also create a provider listing (defaulted to size = Sole trader) so they appear in the Organisations/Providers directory as well as the worker directory.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.60.0 — 2026-06-07 · “hats” onboarding + dashboard reveal (employer vs contractor, one account)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'My roles is now a clear “hat picker”: one account can tick the hats that apply — Employer / company, NDIS / service provider, Supplier, Available for contracting (sole trader / ABN), Looking for employee work (PAYG / TFN), Participant, or Participant representative / nominee — grouped under “I offer work”, “I’m looking for work”, “I need support”, each with a plain-English description.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The dashboard now reveals only the sections that match your hats — so an employer who also contracts manages both from one place without confusion. Members who haven’t set hats yet see everything (capability fallback), so nothing disappears.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Foundation for the application-process expansion (basis-aware apply) and the legislation-aware guidance coming next. Existing roles still work; the legacy “worker” role is recognised.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.59.0 — 2026-06-07 · provider swipe deck · section accent borders</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New [sssj_swipe] — a Tinder-style swipe deck for browsing providers: swipe right (♥ / →) to save a provider to your shortlist, left (✕ / ←) to skip, tap to view the profile. Works on touch, mouse and keyboard; saving is stored to the member’s shortlist. Drop it on a “Discover providers” page.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Form section cards now carry a tasteful left accent border (a muted brand-colour per group) for clearer, more professional grouping.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.58.0 — 2026-06-07 · profile form UI polish (section cards, completeness, sticky save)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The worker/organisation/participant forms are grouped into clean “section cards” (Basic information, Photos, Availability & status, Skills & services, Location & travel, Experience & rates, Business & credentials, Visibility & notifications) with icons, and fade in as you scroll.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Worker form: a live “Profile completeness” meter, an Available-for-work pill toggle, an “About you” character counter, friendlier file-upload buttons with an instant profile-photo preview.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'All sssj forms: a sticky “Save now” bar on scroll, Ctrl/Cmd+S to save, an unsaved-changes dot in the browser tab title, and toast notifications. Brand-styled (uses the existing design tokens / Style Studio) and reuses the existing Tom Select pickers, suburb autocomplete, NDIS “Scan now” and the Shuffles spinner — no field names or submission logic changed.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.57.0 — 2026-06-07 · header menu mirrored into Appearance → Menus</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New: the plugin can create and maintain a real WordPress menu (“Jobs & Engagements”) under Appearance → Menus that mirrors the [sssj_menu] navigation — so it shows in your theme header and is editable there. Settings → Pages → “Header menu”: click to create it, optionally assign it to a theme location.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Self-maintaining: once created, the menu re-syncs on each plugin update — adding/removing items as features change. It only manages items it created (tagged internally); any menu items you add by hand are never touched. A WordPress menu is the same for all visitors, so it carries the public items; the login-aware, capability-gated version (with admin Settings) stays in the [sssj_menu] shortcode.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.56.0 — 2026-06-07 · Provider Import (beta) — bulk NDIS CSV importer</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New “Provider Import (beta)” tab: a proof-of-concept reader for the NDIS Commission’s official bulk datasets — the “Active providers” CSV and the “Compliance/enforcement actions” CSV. It demonstrates the recommended bulk route (official datasets, not scraping the register).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'PREVIEW ONLY: this proof of concept never writes anything — uploads are parsed, the columns auto-detected, and the row count + mapping + a sample shown, with nothing saved. Admin-only and nonce-checked. (The import/write path exists in code behind a hard “preview only” switch for when bulk import is actually wanted.)', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.55.4 — 2026-06-07 · NDIS outlets & phone · red “Revoked/Banned” status</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The NDIS register check now also captures the listing’s outlets and phone number (from the listing footer) and shows them on the profile + “Scan now” preview. All register-sourced details are read-only — they can’t be edited by the member.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'A “Revoked” or “Banned” registration status now shows on a RED background (never green) — both on the profile and in the “Scan now” preview (which previously always showed green). Negative statuses are matched first so “Registration revoked” can’t be mistaken for active.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.55.3 — 2026-06-07 · fuller NDIS details + ABN mismatch flag</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The NDIS register check now also captures and shows the listing’s legal name, ABN, head-office location and website (alongside status, registration groups and expiry) — on the profile table and in the “Scan now” preview.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'ABN cross-check: if the ABN on the NDIS register differs from the ABN on file (the organisation’s ABN, or the sole trader’s ABN), a red warning note is shown so it can be checked.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.55.2 — 2026-06-07 · NDIS “Scan now” on the forms · mic button width fix</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'NDIS “Scan now”: a button next to the NDIS Registration No field (organisation + sole-trader worker forms) checks the number against the public NDIS Commission register on the spot and shows the live status, registration groups and expiry — before you save. Uses the Shuffles spinner while it checks.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Fix: the voice-input microphone button on location fields was stretching full-width inside the profile forms — it’s now constrained to a normal button size.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.55.1 — 2026-06-07 · admin Settings sub-item in the menu</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The [sssj_menu] now supports sub-menus, and shows an admin-only “Settings” item nested under “My dashboard” (links straight to the plugin settings). Only users who can manage options see it; it opens as a dropdown on desktop and indents inline on mobile.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.55.0 — 2026-06-07 · Business Logic tab · “Edit my profile” in the menu</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New “Business Logic” settings tab: a plain-English, in-app record of how the plugin decides things — members & roles, ABN/TFN segregation, who pays vs free, organisation categories & sponsorship, verification & trust, the NDIS register check, participant privacy, matching/alerts/CRM, and a “Key invariants (never rules)” list. Mirrors docs/business_rules_and_logic.md.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The login-aware [sssj_menu] now includes an “Edit my profile” link for logged-in members (opens their worker/candidate profile editor).', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.54.0 — 2026-06-06 · Cron Jobs tab · My profile in the dashboard · home-page hero</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Cron Job List & Status tab: a new admin tab listing every scheduled background job (daily maintenance, daily email alerts, monthly NDIS check) with its frequency, last run, next run due, completion status, any reported errors, and a “Run now” button. Last-run/status are recorded automatically (a job that starts but never finishes is flagged “Did not complete”).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'My profile in the dashboard: the member dashboard now has a “My profile” tab that lets you edit your personal worker/candidate profile right there, with one-click links to manage an organisation or a participant support request.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Home-page hero safety strip: the [sssj_hero] banner now carries a “Safety, built in” strip listing the platform’s privacy & verification guardrails (toggle with safety="off"). The wording comes from one place (shuffles_ssj_hero_guardrails filter).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Counters that wait until you’re impressive: the [sssj_stats] counters take a new min="…" attribute that hides any counter below that number — so small early totals stay hidden and simply appear as the marketplace grows (e.g. [sssj_stats min="25"]).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Marketing: a docs/SAFETY-GUARDRAILS.md reference (not shipped publicly) collects all the trust & safety guardrails with copy-ready “sales angle” lines, and is the single source for the hero strip wording.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Sole-trader NDIS registration: individuals registered with the NDIS Commission in their own right can now add their NDIS Registration No on their worker profile — the same live status / registration-groups / expiry table and monthly auto-check that organisations get.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Shuffles spinner: lookups and saves that take a few seconds (the website auto-fill, profile saves that check the NDIS register / ABR / location, and the “Re-check NDIS register now” button) now show the branded Shuffles spinner — the site logo when one is set, otherwise a brand-blue ring.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Tidy-up: the organisation form’s two NDIS number fields are merged into one — “NDIS Registration No”. (The value is still mirrored to the old field internally, so existing badges/links keep working with no migration.)', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.53.0 — 2026-06-06 · live NDIS provider-register check + monthly change alerts</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'NDIS Registration No: organisations can enter the number after ?id= in their provider-register URL (e.g. 902439). On save, the plugin reads that public listing and stores the live Registration status, the approved registration groups and the “in force until” date.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Registration table on the profile: a neat “NDIS provider registration” table shows the status (colour-coded), the approved registration groups, the expiry date, a link to the Commission register and a “Last checked” date. Admins get a “Re-check NDIS register now” button.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Monthly auto-check + alerts: a background check re-reads every registered organisation’s listing once a month and emails staff (Settings → Compliance → change-alert email, default the site admin) only when something changes — status, groups, or the expiry date. The provider is never emailed.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Safe-by-design: a fetch or parse failure never overwrites the stored details and instead alerts staff (so a register-page layout change can’t silently read as “no change”). Reads only the NDIS Commission’s own public data, on a gentle cadence. Toggle the whole feature on/off in Settings → Compliance.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Hooks for integrators: shuffles_ssj_ndis_changed (fires with the change list), shuffles_ssj_ndis_alert_email, shuffles_ssj_ndis_suppress_default_email, shuffles_ssj_ndis_base_url, shuffles_ssj_ndis_user_agent.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
				<h3>v0.52.0 — 2026-06-06 · member roles · organisation categories · participant-free posting · provider directory fee &amp; sponsorship</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Member roles: a member can wear many hats. New [sssj_roles] form (and a “My roles” tab in the dashboard) lets each member tick the roles that apply — worker, candidate, participant, sole-trader provider, provider representative, or supplier — which grants the matching posting capabilities and tailors their dashboard. Change it any time.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Organisation categories: organisations now choose a type — Support provider, Supplier / services to the sector, SDA / housing, Real estate, Professional services, or Other. It shows as a badge on the card + profile and is a filter (“All organisation types”) on the Organisations directory.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Participant-free posting: participants employing directly, or seeking workers or providers, are never charged — even when monetisation is on. Providers seeking staff, sponsorship, or a directory listing are the paid side.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Provider directory fee + sponsorship: when monetisation is on, only providers who hold a listing subscription (or admins) appear in the Organisations directory. Admins can grant a “Sponsored placement” (Verification box) — sponsored organisations sort to the top and show a ★ Sponsored badge.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Fix: the recommended provider field set (Settings → Profile Fields → “Add recommended provider fields”) now also mirrors “Services Provided” (multi-select of NDIS/aged-care services) and “Peak Bodies” (multi-select of industry memberships such as NDS, ACCPA, DIA) — both were missing and so never synced. Both are banner filters on the Organisations directory. Re-run the button to add just the two new fields (existing fields are left untouched).', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.51.0 — 2026-06-06 · NDIS provider registration · provider field seed · website auto-fill</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Organisations can mark themselves a registered NDIS provider with a registration number — shown as an “NDIS Registered · #number” badge linking to the NDIS Commission register. Registration status + groups are set by an admin or an auto-scan integration hook (there is no official public API, so it is best-effort/manual).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New “Add recommended provider fields” button (Settings → Profile Fields) seeds a Shuffles-style organisation field set (specialisations, service delivery, ages supported, accepting clients, accessibility, languages, years operating, accreditations) — the banner-flagged ones become directory filters.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Website auto-fill: on the organisation form, “Fetch details from my website” reads the site and pre-fills empty name/description/phone fields for review (our AI; only empty fields are touched). An AI/web-read integration can enrich it via the shuffles_ssj_autofill filter.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.50.0 — 2026-06-06 · ABR verification · ABN required for businesses · “what are you seeking”</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'ABR check: set a free ABR Web Services GUID (Settings → Compliance) and any ABN entered on a job, worker or organisation is verified against the Australian Business Register on save — the entity name + status are stored and shown as an “🏢 ABR Active · <Name>” badge on cards/profiles. Without a GUID the offline checksum still runs.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'A valid ABN is now required for organisations (they are non-TFN businesses), in addition to ABN-basis job ads which already required it. TFN (employee) job ads never ask for an ABN.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Participant requests now have a “What are you seeking?” option — ongoing support (a worker), a one-off task, or a provider/organisation — shown as a badge on the request card.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.49.0 — 2026-06-06 · Job funding filter · clearer menu · interactive map markers</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Jobs can now carry funding source(s) (NDIS, Aged Care, DVA, Foundational Supports, …) — set on the posting form — and the Jobs board has funding tick-box filter chips so job-seekers can narrow by funding.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The navigation menu’s participant link is now labelled “Participants seeking workers” (providers are under “Organisations”), and “My dashboard” points at the all-in-one dashboard when present.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Map markers are now interactive (where a Google Maps API key is set): single-click shows an info box with a summary + View link; double-click scrolls to that result’s card and surrounds it with an animated rainbow “tracer” highlight.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.48.0 — 2026-06-06 · “Willing to travel” radius + distance pills on cards</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New “How far are you willing to travel?” field on worker profiles (and a “Service area radius” on organisations, and “How far can a worker be?” on participant requests). The worker value also sets the default radius on the Jobs board for that member.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'When you search a directory with a location, each result card now shows a highlighted “X km away” distance pill (the nearest location for organisations). Travel radius is shown on worker and organisation profiles.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.47.0 — 2026-06-06 · All-in-one member dashboard</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New [sssj_dashboard] shortcode — a single tabbed hub for logged-in members that ties everything together: an Overview (quick stats + actions), My listings & applicants (advertisers), Matched jobs and My credentials (workers), Saved searches, and Messages. Each tab appears only if it applies to that member.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'It composes the existing feature shortcodes (single source of truth per feature). Progressive enhancement: tabs switch panels with JavaScript; with JS off every section is still shown.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.46.0 — 2026-06-06 · Email alerts (job matches, new candidates, saved searches)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Workers can opt in (on their profile) to a daily email when new jobs match their profile; advertisers can opt in (when posting a job) to a daily email when new candidates match the role.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Saved searches: a “Save & alert me” button on every directory saves the current filters and emails the member when new listings match. Manage via the new [sssj_saved_searches] shortcode.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'All driven by a daily cron and the matching engine; “new” = published since the last alert. New Settings → Email Alerts tab with a master switch + “Run alerts now”. Emails go via the site mailer unless a FluentCRM automation claims them (shuffles_ssj_alert_sent / shuffles_ssj_alert_suppress_default).', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.45.0 — 2026-06-06 · Smart matching engine (“Best matches” panels)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New real-time matching: job pages show “Workers who may suit this role”, worker profiles show “Open roles this worker may suit”, and the new [sssj_matches] shortcode shows “Jobs matched to you” for a logged-in worker. Each match lists short reasons.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Matches are ranked on shared services, location proximity, availability, engagement basis (ABN/TFN — an ABN role favours workers with a recorded ABN), rate compatibility and trust (verified / blue tick). Respects worker visibility.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.44.0 — 2026-06-06 · Custom field “banner filters” now work on the directories</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Custom Profile Fields marked “show on banner filters” now appear as searchable filter dropdowns on the relevant directory (worker, organisation, participant-request) and actually filter the results — completing that option. Multi-select fields match within their stored values; “Clear all” resets them along with the other filters.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.43.0 — 2026-06-06 · Account verification “blue tick”</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New account-level “blue tick” verification for workers and organisations — an admin-granted trust mark, separate from the green ✓ Verified credential badge. Grant it from the “Verification (blue tick)” box on the worker/organisation edit screen.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The blue tick shows next to the name on directory cards and profiles (and on a job from a verified organisation). Use it for accounts whose identity and key checks you have confirmed.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Single job pages now show a Google map of the job’s suburb/town (suburb-level only — no exact address). Works without a Google Maps API key.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Participant request cards now have a “View full request” expander showing the full details in place. Participant requests stay private (pseudonymous, no public page), so the drill-down expands on the card rather than linking to a separate URL.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.42.0 — 2026-06-06 · Per-directory “read me” notes + job logos (inherit from organisation)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Each directory (jobs, workers, organisations, participant requests) now has a collapsible “Read me — things to know” note under the banner, with tips specific to that directory. Plain, accessible, no JavaScript.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Jobs now show a logo: by default a job inherits its organisation’s logo automatically; you can upload a per-job logo on the posting form to override it. Logos appear on the job board cards.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.41.0 — 2026-06-06 · Fix: auto header menu rendering twice</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'The “Show navigation menu at the top of every page” option could output the menu twice on themes that fire the wp_body_open hook more than once. It now renders at most once per page. (If you also placed an [sssj_menu] manually, switch this testing option off in Settings → General.)', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Organisation logos are now smaller on cards and profiles (less dominant).', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.40.0 — 2026-06-06 · Elementor widget pack for the front-page blocks</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New “Shuffles Jobs” Elementor widget category with drag-and-drop widgets for the home-page blocks: Hero banner, Animated stats, Featured roles, Recent items, and the Navigation menu. Each has visual controls (headings, button text/links, counts, type, layout) and previews live in the Elementor editor.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Standalone-first: the widgets only load when Elementor is active (no hard dependency). Without Elementor, use the [sssj_hero], [sssj_stats], [sssj_featured], [sssj_recent] and [sssj_menu] shortcodes as before. Elementor now also appears on the Integrations tab.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.39.0 — 2026-06-06 · Worker profile pages now show details + photos · compact accessibility language pill</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Worker / contractor profile pages now display the full details below the bio — availability, services, rate, location, languages & cultural focus, verified checks and any custom fields — instead of appearing blank.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Worker profiles can now have a profile photo (shown as a headshot on the card and profile) plus an optional photo gallery that displays as a swipeable strip. Add them on the worker profile form.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The accessibility-bar language picker is now a compact rounded pill sized to its text, matching the other accessibility controls, instead of a full-width box.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Added breathing room between the filter/header panel and the cards on every directory (panels now have consistent spacing below them).', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.38.0 — 2026-06-06 · Custom Profile Fields + FluentCRM sync (with per-user log)</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New “Profile Fields” settings tab: define your own custom fields on worker/contractor, organisation and participant-request profiles — text, paragraph, number, searchable single-select, searchable multi-select (pills) or yes/no toggle. Mark a field required or “show on banner filters”. Fields render on the relevant profile forms and save automatically.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New “CRM Sync” settings tab: map any profile value — a funding source like NDIS, a sector, a culture/language, or a custom-field option — to a FluentCRM tag and/or list. When a member ticks that value on their profile, the tag/list is added to their contact; un-ticking removes it. Syncs to the FluentCRM on this site, gated by a master switch, and only ever maps to tags/lists that already exist.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Per-user CRM sync log: every attach/remove is recorded and viewable both on each user’s profile screen (with a “Re-sync now” button) and on the CRM Sync tab. If a mapped tag/list is later deleted in FluentCRM, an admin alert + a “missing” log entry appear so you can fix the mapping — a profile save is never broken by a CRM error.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.37.0 — 2026-06-06 · Branding (Foundational Supports + Thriving Kids) &amp; SEO · auto header menu · readable CSS examples</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'Branding & SEO: a new “Focus programs” setting (General tab) lists the funding programs/sectors the site covers — defaulting to NDIS, Aged Care, DVA, Foundational Supports and Thriving Kids. It is emitted as SEO keywords on your job, worker and organisation pages, and becomes the default sub-text on the [sssj_hero] banner. “Foundational Supports” and “Thriving Kids” are also added to the seeded Funding Sources list (re-seed, or add via the taxonomy screen, to use them on existing sites).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New “Show navigation menu at the top of every page” option (General tab) — outputs the [sssj_menu] bar at the top of every front-end page via wp_body_open, so you can navigate the marketplace while testing without editing your theme header.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'Fixed the Custom CSS examples in the Appearance tab — the code samples were unreadable (the admin theme was painting light boxes behind the text). They now render as clean light-on-dark code blocks.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.36.0 — 2026-06-06 · Dynamic filters + “Use my location” + animated front-page shortcodes + monetisation explainer</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'All directory filters now apply automatically as you change them — no “Filter” button required. A “Clear all” button resets every filter in one click, and a “Use my location” button next to the location field finds nearby results using the browser’s location (a sensible default radius is applied).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New animated front-page display shortcodes for the home page: [sssj_hero] (gradient hero banner with call-to-action buttons), [sssj_stats] (counters that count up as they scroll into view — open jobs, available workers, organisations, people placed), [sssj_featured] (featured roles with a shine effect), and [sssj_recent] (latest jobs / workers / organisations / participant requests with a staggered fade-in; layout="list" makes a compact sidebar widget). All animations respect the visitor’s “reduce motion” setting and are documented in the Shortcodes tab.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The Monetisation settings tab now opens with a plain-English explanation of how it works (the two subscription types and the free-listings cap) and a side-by-side comparison of the PMPro vs FluentCart billing choice — what each is best for, how to configure it, and what it needs.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.35.0 — 2026-06-06 · Guides section + “Do not display” + English hot key moved out</h3>
					<ul class="ul-disc">
						<li><?php esc_html_e( 'New Guides section: plain-language how-to guides for each side of the marketplace — writing a successful job post, responding to a job, working as an ABN contractor, and building a standing profile. Available as the [sssj_guides] shortcode (collapsible panels, with an optional only="…" attribute) and a new Settings → Guides tab, from a single source of truth (Shuffles_SSJ_Guides::sections()).', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'New “Do not display” privacy option on worker and organisation profiles: hides the profile from search engines (noindex) and removes it from the public directory entirely.', 'shuffles-social-services-jobs' ); ?></li>
						<li><?php esc_html_e( 'The “English” hot key now sits outside the accessibility bar, directly next to the ♿ Accessibility button, so members can return to English in one tap without opening the toolbar.', 'shuffles-social-services-jobs' ); ?></li>
					</ul>
					<h3>v0.34.0 — 2026-06-06 · Culture & Language fields (shared, preseeded, multi-select)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'New Cultural/community-focus and Language taxonomies, preseeded (CALD communities, LGBTQIA+, First Nations… and 40+ community languages incl. Auslan). One shared lookup used across jobs, workers/contractors and participant requests.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Added to the job, worker and request forms as searchable multi-selects (typeahead pills). Manage the lists under the plugin menu’s taxonomy screens.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.33.0 — 2026-06-06 · Accessibility toggle + centred map + multi-select filters</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'The accessibility toolbar is now hidden behind a single “♿ Accessibility” button (same position) and only opens when clicked — reclaiming page space.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'The directory map is now half-width and centred, with tasteful spacing between the filter banner and the cards.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Directory category/service filters are now multi-select (search-and-add pills) — filter by several categories at once.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.32.0 — 2026-06-06 · Wider language coverage on the directories</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'More of each directory now translates: headings, the “Available now / Within” controls, the “Only with open placements” toggle, and the search/location field placeholders (the language switcher now translates placeholders too). New strings added to all six languages.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Note: user-typed content (names, bios, job text) and items inside the new search-pickers are not auto-translated — that needs a paid translation service or the browser’s built-in “Translate page”.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.31.0 — 2026-06-06 · Testing worksheet (shortcode + Settings tab)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'A tester checklist covering every feature — work through each case and mark Pass/Fail (progress saved per browser; printable). Available as the [sssj_tests] shortcode and the new Settings → Testing tab, from a single source of truth (Shuffles_SSJ_Tests::suites()) kept current as the plugin changes.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.30.0 — 2026-06-06 · Searchable multi-selects + tighter filter banner + org counters + responsive</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Checkbox grids are now searchable multi-selects (type-ahead pills): worker “Services you offer”, and the participant request’s support types + funding sources. Search and add, instead of scanning a long list.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Org cards show two counters: open jobs now, and total placed (all-time applicants the org marked as an Offer).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Tighter filter banners — the location field is no longer over-wide — and a mobile/responsive pass (controls stack full-width, single-column cards) for a more professional small-screen layout.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.29.0 — 2026-06-06 · Organisation sectors + funding sources (pills + filters)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Organisations can now select the sectors they cover and the funding sources they accept (NDIS, Aged Care, DVA, etc.). These show as pills on the org card and are emitted in the org profile.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'The Organisations directory filter panel gains Sector and Funding filters plus an “Only with open placements” toggle (organisations that currently have at least one open job).', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.28.0 — 2026-06-06 · Worker location + worker-directory finder</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Workers can now add their location (suburb/state/postcode, geocoded server-side). The worker directory gains a location search + radius slider + map, matching the job and organisation finders. Only the suburb is shown publicly.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.27.0 — 2026-06-06 · Keyless location finder on the job boards + friendlier English hot-key</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'The Jobs / TFN / ABN boards now show the location search + a radius slider even without a Google Maps key (geocoding runs server-side). The map still needs a key; the radius search itself is keyless.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'The English hot-key now reads “English Hot Key” with a friendly note: “Have you chosen a language you can’t read, and want to go back to English? We got you! Hit the Hot Key.”', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.26.0 — 2026-06-06 · Select2-style pill pickers everywhere</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Every plugin dropdown is now a searchable, professional select2-style picker; multi-selects show removable pills. Powered by a self-hosted Tom Select (no jQuery), themed to your design system / Style Studio colours.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Loaded only on pages with plugin content; the accessibility language picker is left untouched. Opt a select out with a data-no-enhance attribute.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.25.0 — 2026-06-06 · Organisation finder — location + radius + map</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'The Organisations directory gains a location search with a radius slider and a map of org locations. An organisation matches if ANY of its locations is within the radius, ordered by nearest.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Every saved location (primary + additional) is geocoded server-side (keyless), so multi-location orgs are found by distance from all their sites. Map needs a Google key; the radius search itself works with no key.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.24.0 — 2026-06-06 · Organisation branding — logo + social links</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Organisations can upload a logo (shown on their profile, directory cards and search results, and added to the Organization structured data for SEO).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Social + profile links: Facebook, LinkedIn, Instagram, X, YouTube and a Shuffles profile link, shown as branded icons and emitted as schema.org sameAs for richer search results.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.23.0 — 2026-06-06 · Appearance "Style Studio" (live preview)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'The Appearance tab is now a Style Studio: visual controls (colours, corner radius, font, base size, heading weight, density) alongside a live preview of a realistic mock board (nav, search, ABN/TFN/featured/need cards) that updates as you edit — nothing saved until you click Save.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'One-click presets (Soft / Bold / Calm), a desktop/mobile width toggle, and Save / Load named looks. The raw Custom CSS box edits live too, scoped to the preview.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'New tokens wired through to the front end: base font size (--sssj-fs), heading weight (--sssj-weight-heading) and a density control that scales the spacing.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.22.0 — 2026-06-06 · Pages tab + Custom CSS guide</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'New Pages tab gathers ALL page mappings in one place, grouped (Browse / Participants / Post-create / Member account), each with lookup + create + edit. The Boards tab now points here.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Appearance tab now includes a Custom CSS guide: the design tokens you can override, the main classes to target, five copy-paste examples, and rules of thumb (scope to .sssj, prefer tokens, avoid !important so accessibility modes still win).', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.21.0 — 2026-06-06 · Login-aware navigation menu + remaining page pickers</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'New [sssj_menu] shortcode: a responsive navigation bar that adapts to the visitor. Logged-out users see browse links + Log in / Register; logged-in users see their dashboard, messages, participant requests, log out, plus action links (Post a job / My credentials / Request support) shown only when their account can use them.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'It maintains itself — links resolve from your configured pages (Boards tab) or by finding the page that contains each shortcode, and it re-renders for each visitor. Fully customisable via the shuffles_ssj_menu_items filter.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Added the remaining page pickers (worker directory, create worker profile, participant requests, request support, my credentials) so every page has a lookup / create / edit control — and the menu can link to all of them.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.20.0 — 2026-06-06 · Shortcodes reference tab</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'New Settings → Shortcodes tab: every public shortcode listed and explained, grouped by area (Job ads, Workers, Participants, Organisations, Member account), with a copy-ready code, what it does, where to use it, who can see it, and any optional attributes.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Driven by a single source of truth (Shuffles_SSJ_Shortcodes::reference()) co-located with the shortcode registration, so adding a shortcode documents it automatically.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.19.0 — 2026-06-06 · Compliance & verification (credentials → admin approval → ✓ Verified)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Workers add their checks (NDIS Worker Screening, WWCC, police check, First Aid, qualifications, insurance) with the [sssj_credentials] shortcode — type, reference, issue/expiry dates and an evidence file (PDF/JPG/PNG).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Evidence is stored in the database — no file on disk and no URL, so it is never directly fetchable even on Nginx (which ignores .htaccess). Server-side MIME + size checks (PDF/JPG/PNG, 8 MB). Served only to its owner or an admin through a nonce-signed handler.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'New admin Verification queue (Jobs & Engagements → Verification, with a pending count): review each document and Approve or Reject with a note. The ✓ Verified badge is set ONLY by admin approval — never from user input.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Daily expiry sweep: a lapsed credential auto-expires and drops the verified badge; workers get an email reminder at the configurable lead time (Compliance tab) and on the expiry day. Worker cards show which checks are verified (labels only).', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.18.0 — 2026-06-06 · Self-hosted geolocation (no Geo my WP, no mandatory Google)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'The geo is now a fully self-contained custom build. Geo my WP is never called (it was only ever an optional status row). New Shuffles_SSJ_Geo engine: true great-circle (Haversine) distance with nearest-first ordering, replacing the bounding-box-only filter.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Keyless geocoding: typing a suburb or postcode now resolves to coordinates with no Google key — a bundled list of Australian cities resolves instantly and the rest is looked up free via OpenStreetMap (cached). Radius search and stored listing coordinates work with no API key. Choose the geocoder in the Maps tab.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Listings are geocoded server-side from their suburb/state/postcode when posted without autocomplete. A Google key remains optional (it only adds Google autocomplete + the Google map). Pluggable via shuffles_ssj_geocode / shuffles_ssj_geo_dataset.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.17.0 — 2026-06-06 · Languages by site + FluentCart/PMPro provider choice</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Languages are now per-site: choose which built-in languages appear in the picker (CALD tab → Offered languages), add your own (code | Endonym | rtl) including right-to-left ones, and supply/override their wording via a small JSON box. The English hot-key and live switching are unchanged.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Monetisation can now run on FluentCart or PMPro — pick the provider in the Monetisation tab. PMPro uses level IDs; FluentCart uses product IDs (active subscription = access). Featured placement and both gates follow the chosen provider.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'FluentCart support is built against its subscription model and is safe when the FluentCart core plugin is not yet active (no detection = treated as no subscription; never errors). Pluggable via shuffles_ssj_fluentcart_active. Standalone-first as ever — removing WooCommerce / WP Job Manager does not affect this plugin.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.16.0 — 2026-06-06 · Featured placement for paid advertisers</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Advertisers with the advertising subscription get featured placement: their jobs float to the top of every board (rides the existing menu_order sort — no change to the segregation/query layer) and show a ★ Featured badge.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Featured state is stamped at post time and re-synced daily, so a lapsed subscription un-features automatically and a new one features without a re-save. Per-advertiser only — the site-wide resale licence never blanket-features every job.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Only active when monetisation is enabled (boards stay neutral by default). Pluggable via the shuffles_ssj_is_job_featured filter; ★ Featured badge translated into all six CALD languages; advertiser dashboard shows which listings are featured.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.15.0 — 2026-06-06 · Monetisation subscriptions</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Employer advertising subscription: free-tier active-listing cap enforced at posting; an active subscription (PMPro level) unlocks unlimited. Over-limit advertisers see an upgrade prompt.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Provider application-fee subscription: required to respond to participant needs / ABN tasks (rides the existing response gate).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Both gates are OFF until enabled; PMPro level IDs configurable; FluentCart/custom logic pluggable via filters. Enforced server-side.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.14.0 — 2026-06-06 · Organisation / employer profiles</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Permanent, SEO-able organisation profiles (Organization structured data) with multiple locations: [sssj_post_org] to create/edit, [sssj_org_directory] to browse, and a company page that lists all that employer\'s open positions.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Jobs can be attached to an organisation (browse jobs by company). Page-pickers added for the directory + profile pages.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Participants stay private by design — their listings remain pseudonymous and noindex; only named businesses get public profiles.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.13.0 — 2026-06-06 · Appearance — per-install re-skin</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'New Appearance tab: set the primary/hover/ink/text/border/background colours, the ABN/TFN/participant accents, corner radius and font family with colour pickers — each install can be fully re-skinned without code.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'A Custom CSS box for anything else. Settings are output as inline CSS variables scoped to the plugin (.sssj), so the rest of your theme is untouched.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.12.0 — 2026-06-06 · Configurable vendor/brand URLs + domain-neutral</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Licence vendor store URL (Licensing tab) and brand website URL (General tab) are now editable settings — repoint them without touching code.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Confirmed the plugin hardcodes no site domain (everything follows the WordPress site URL), so a domain change needs no code edit; docs made domain-neutral.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.11.0 — 2026-06-06 · Internal messaging relay</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Private inbox [sssj_messages]. Applying to a job or responding to a participant request starts a conversation with the listing owner (carrying your message); both sides reply in-app.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Relay only — no email addresses are shown; participants appear by pseudonym to workers; recipients get a no-content "you have a message" email.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Page-pickers added for the Member dashboard and Messages pages.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.10.0 — 2026-06-06 · Site-wide language + English hot-key</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Language choice is now site-wide: the accessibility/language toolbar appears on every page (a floating bar where there is no board), and the chosen language + translations persist and re-apply everywhere.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Always-visible English hot-key in the toolbar whenever a non-English language is active — one tap back to English.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.9.0 — 2026-06-06 · Resale licensing (FluentCart)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Licence client: enter key + product ID, Activate/Deactivate, live status on the Licensing tab; daily re-validation with a grace window so a vendor-store outage never disables a valid licence.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'is_pro() gate for premium features (filter shuffles_ssj_is_pro); your own site bypasses via the SHUFFLES_SSJ_PRO constant in wp-config.php. Core boards always work.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.8.0 — 2026-06-06 · Interface translation (CALD)</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Language picker in the accessibility toolbar — switches the interface into Arabic (RTL), Mandarin, Greek, Italian, Indonesian or Punjabi, live, no reload. Auto-translated, pending native review.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Translations extend/override via the shuffles_ssj_i18n and shuffles_ssj_languages filters; choice remembered in the browser.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.7.0 — 2026-06-06 · Accessibility / CALD toolbar</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Browser-side accessibility toolbar on every board/form: larger text, high-contrast, no-colour, Easy-Read, read-aloud (speech), and voice input on search fields. $0 to run.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Preferences remembered (localStorage). Master-gated by the CALD & Access switch. Display-mode filters apply to content blocks, never the page root.', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( '(7-language interface translation + RTL to follow.)', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
				<h3>v0.6.0 — 2026-06-06 · Google Maps + radius</h3>
				<ul class="ul-disc">
					<li><?php esc_html_e( 'Places autocomplete on the job + participant posting forms — fills suburb/state/postcode and stores coordinates (manual entry still works without a key).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Radius (distance) filter on the job board and participant board; a results map on the job board. Participant needs are radius-searchable but never plotted (privacy).', 'shuffles-social-services-jobs' ); ?></li>
					<li><?php esc_html_e( 'Maps settings now list the exact Google APIs to enable: Maps JavaScript API, Places API, Geocoding API.', 'shuffles-social-services-jobs' ); ?></li>
				</ul>
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
