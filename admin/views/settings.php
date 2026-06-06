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
				echo '<pre style="background:#0f172a;color:#e2e8f0;padding:10px 12px;border-radius:6px;overflow:auto;font-size:12px;line-height:1.5"><code>' . esc_html( $code ) . '</code></pre>';
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

			echo '<tr><td colspan="2"><h3 style="margin:6px 0 0">' . esc_html__( 'Browse (public)', 'shuffles-social-services-jobs' ) . '</h3></td></tr>';
			$this->page_picker_field( 'page_job_board', __( 'All jobs board', 'shuffles-social-services-jobs' ), '[sssj_job_board]', __( 'Both bases in a labelled split.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_tfn_board', __( 'TFN (employee) board', 'shuffles-social-services-jobs' ), '[sssj_tfn_board]', __( 'Employee positions only.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_abn_board', __( 'ABN (contractor) board', 'shuffles-social-services-jobs' ), '[sssj_abn_board]', __( 'Contractor / ABN engagements only.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_worker_directory', __( 'Worker directory', 'shuffles-social-services-jobs' ), '[sssj_worker_directory]', __( 'Find a worker (public).', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_org_directory', __( 'Organisations directory', 'shuffles-social-services-jobs' ), '[sssj_org_directory]', __( 'Browse employers/companies (SEO-able).', 'shuffles-social-services-jobs' ) );

			echo '<tr><td colspan="2"><h3 style="margin:16px 0 0">' . esc_html__( 'Participants (members only)', 'shuffles-social-services-jobs' ) . '</h3></td></tr>';
			$this->page_picker_field( 'page_need_board', __( 'Participant requests', 'shuffles-social-services-jobs' ), '[sssj_need_board]', __( 'Members-only; pseudonymous.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_post_need', __( 'Request support (form)', 'shuffles-social-services-jobs' ), '[sssj_post_need]', __( 'Participant/nominee request form (moderated).', 'shuffles-social-services-jobs' ) );

			echo '<tr><td colspan="2"><h3 style="margin:16px 0 0">' . esc_html__( 'Post / create (member forms)', 'shuffles-social-services-jobs' ) . '</h3></td></tr>';
			$this->page_picker_field( 'page_post_job', __( 'Post a job', 'shuffles-social-services-jobs' ), '[sssj_post_job]', __( 'Advertiser posting form.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_post_worker', __( 'Create worker profile', 'shuffles-social-services-jobs' ), '[sssj_post_worker]', __( 'Worker profile form.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_post_org', __( 'Create organisation profile', 'shuffles-social-services-jobs' ), '[sssj_post_org]', __( 'Employer profile form.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_credentials', __( 'My credentials', 'shuffles-social-services-jobs' ), '[sssj_credentials]', __( 'Workers upload checks for verification.', 'shuffles-social-services-jobs' ) );

			echo '<tr><td colspan="2"><h3 style="margin:16px 0 0">' . esc_html__( 'Member account', 'shuffles-social-services-jobs' ) . '</h3></td></tr>';
			$this->page_picker_field( 'page_my_listings', __( 'Member dashboard', 'shuffles-social-services-jobs' ), '[sssj_my_listings]', __( 'Applications, your listings + applicants.', 'shuffles-social-services-jobs' ) );
			$this->page_picker_field( 'page_messages', __( 'Messages (inbox)', 'shuffles-social-services-jobs' ), '[sssj_messages]', __( 'Private relay inbox.', 'shuffles-social-services-jobs' ) );

			echo '</table>';
			submit_button();
			echo '</form>';
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
