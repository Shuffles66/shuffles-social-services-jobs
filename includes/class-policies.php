<?php
/**
 * Policies, the PUBLISHED, member-facing layer of the platform's policies (single source of truth).
 *
 * These are the plain-English, easy-read versions members read on the site (via [sssj_policies] and
 * Settings → Policies). The full, formal templates live in /docs/*.md and must be reviewed and
 * adopted by the organisation before relying on them, these summaries link to that intent.
 *
 * Keep items() CURRENT: when a policy changes, update its summary here and its /docs template.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Policies {

	/**
	 * Member-facing policies. Each:
	 *   id, title, status ('published'|'draft'), summary (one paragraph, plain English),
	 *   points[] (key bullet points), doc (the formal /docs filename for reference).
	 *
	 * Filterable via `shuffles_ssj_policies`.
	 *
	 * @return array
	 */
	public static function items() {
		$p = array(

			array(
				'id'      => 'complaints',
				'title'   => __( 'Complaints & feedback', 'shuffles-social-services-jobs' ),
				'doc'     => 'COMPLAINTS-POLICY.md',
				'summary' => __( 'Not happy about something? You can tell us, and we will sort it out fairly and quickly. You can complain about a service, a worker or provider, safety, conduct, or how we handled a past complaint.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'Complain online (Support), by email or phone, through someone you trust, or anonymously.', 'shuffles-social-services-jobs' ),
					__( 'We acknowledge complaints quickly and aim to resolve most within 21 days.', 'shuffles-social-services-jobs' ),
					__( 'You will never be treated unfairly for speaking up (no detriment).', 'shuffles-social-services-jobs' ),
					__( 'You can contact the NDIS Quality and Safeguards Commission any time on 1800 035 544.', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'privacy',
				'title'   => __( 'Privacy', 'shuffles-social-services-jobs' ),
				'doc'     => 'PRIVACY-POLICY.md',
				'summary' => __( 'We collect only the information we need to run the marketplace, and we handle it under the Privacy Act and the Australian Privacy Principles. Participants are always pseudonymous, your real name and contact details are never shown publicly.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'Participants appear by a pseudonym and suburb only; never a name or contact detail.', 'shuffles-social-services-jobs' ),
					__( 'First contact runs through our internal relay, your email and phone are not shared between members.', 'shuffles-social-services-jobs' ),
					__( 'Credential and résumé files are stored privately and shown only to you (and, for a résumé, an employer you apply to).', 'shuffles-social-services-jobs' ),
					__( 'You can ask to access, correct, export or erase your personal information.', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'code-of-conduct',
				'title'   => __( 'NDIS Code of Conduct', 'shuffles-social-services-jobs' ),
				'doc'     => 'NDIS-CODE-OF-CONDUCT.md',
				'summary' => __( 'Workers and providers on the platform agree to follow the NDIS Code of Conduct, the standard of behaviour expected when delivering supports and services.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'Respect people’s rights, privacy, choices and dignity.', 'shuffles-social-services-jobs' ),
					__( 'Deliver supports safely and competently, with honesty and integrity.', 'shuffles-social-services-jobs' ),
					__( 'Raise and act on concerns about quality or safety promptly.', 'shuffles-social-services-jobs' ),
					__( 'Prevent and respond to all forms of violence, abuse, neglect, exploitation and sexual misconduct.', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'incident',
				'title'   => __( 'Incident management & reportable incidents', 'shuffles-social-services-jobs' ),
				'doc'     => 'INCIDENT-MANAGEMENT-POLICY.md',
				'summary' => __( 'If something goes wrong, safety comes first. We record incidents, act on them, and report serious (“reportable”) incidents to the NDIS Commission as required.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'If anyone is in danger, call 000 first.', 'shuffles-social-services-jobs' ),
					__( 'Every incident is recorded and acted on; the person involved is supported.', 'shuffles-social-services-jobs' ),
					__( 'Reportable incidents are notified to the NDIS Commission within the required timeframes.', 'shuffles-social-services-jobs' ),
					__( 'No one is disadvantaged for reporting an incident in good faith.', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'safeguarding',
				'title'   => __( 'Safeguarding & risk', 'shuffles-social-services-jobs' ),
				'doc'     => 'SAFEGUARDING-RISK-POLICY.md',
				'summary' => __( 'We design the platform to keep participants and other vulnerable people safe, with zero tolerance for abuse, neglect, exploitation or discrimination.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'Worker screening (WWCC/Blue Card, NDIS Worker Screening, Police Check, First Aid) is admin-verified, never self-claimed.', 'shuffles-social-services-jobs' ),
					__( 'Participant pseudonymity, suburb-only location and relay messaging protect identities.', 'shuffles-social-services-jobs' ),
					__( 'Participant requests are moderated before they appear, and reviews are moderated too.', 'shuffles-social-services-jobs' ),
					__( 'Don’t move off-platform to avoid these safeguards.', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'terms',
				'title'   => __( 'Terms of use & acceptable use', 'shuffles-social-services-jobs' ),
				'doc'     => 'TERMS-OF-USE.md',
				'summary' => __( 'The rules for using the marketplace. We connect people; the engagement or employment itself is between the members involved.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'Give accurate information and keep your credentials current.', 'shuffles-social-services-jobs' ),
					__( 'No false listings, impersonation, harvesting data, spam or harassment.', 'shuffles-social-services-jobs' ),
					__( 'Responsibilities for tax, super and insurance sit with the parties (ABN contractor vs TFN employee).', 'shuffles-social-services-jobs' ),
					__( 'Breaking the rules can lead to suspension or removal.', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'screening',
				'title'   => __( 'Worker screening & verification', 'shuffles-social-services-jobs' ),
				'doc'     => 'WORKER-SCREENING-VERIFICATION-POLICY.md',
				'summary' => __( 'How the “✓ Verified” badge works, and how we protect the evidence you upload.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'You upload evidence → it sits as Pending → an admin approves or rejects it.', 'shuffles-social-services-jobs' ),
					__( 'The badge is granted only by an admin, only with at least one approved, in-date credential.', 'shuffles-social-services-jobs' ),
					__( 'Evidence is stored privately and shown only to you or an admin.', 'shuffles-social-services-jobs' ),
					__( 'Reminders go out before a credential expires, and the badge drops if it lapses.', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'retention',
				'title'   => __( 'Data retention & destruction', 'shuffles-social-services-jobs' ),
				'doc'     => 'DATA-RETENTION-POLICY.md',
				'summary' => __( 'We keep your information only as long as we need it, then securely delete or de-identify it.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'Credential evidence is kept up to 7 years (ATO guidance), then purged.', 'shuffles-social-services-jobs' ),
					__( 'Résumés stay until you delete them or close your account (plus a short grace period).', 'shuffles-social-services-jobs' ),
					__( 'You can request erasure of your personal information.', 'shuffles-social-services-jobs' ),
					__( 'Some records are kept longer where the law requires (e.g. complaints, financial).', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'cookies',
				'title'   => __( 'Cookies & consent', 'shuffles-social-services-jobs' ),
				'doc'     => 'COOKIE-CONSENT-POLICY.md',
				'summary' => __( 'We default to the most privacy-preserving option and keep tracking to a minimum.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'Only strictly-necessary cookies (login, security) are used without consent.', 'shuffles-social-services-jobs' ),
					__( 'Your accessibility and saved-search preferences are stored in your own browser, not for tracking.', 'shuffles-social-services-jobs' ),
					__( 'Any analytics or advertising cookies are used only with your consent.', 'shuffles-social-services-jobs' ),
					__( 'Participant pages are never used for ad targeting.', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'advertising',
				'title'   => __( 'Advertising & media production', 'shuffles-social-services-jobs' ),
				'doc'     => 'ADVERTISING-POLICY.md',
				'summary' => __( 'How advertising, marketing and any media made with our brand is done respectfully, honestly and safely, with people’s dignity and consent at the centre.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'We prefer not to use children in advertising or commercial media. If a child must appear, strict safeguards and a parent or guardian’s written consent apply.', 'shuffles-social-services-jobs' ),
					__( 'Anyone who appears in media made for commercial purposes is paid fairly for their time. No one is asked to work for free or “for exposure”.', 'shuffles-social-services-jobs' ),
					__( 'Written, informed consent is obtained from everyone shown (or their guardian) before publishing, covering how the content is used and any payment agreed.', 'shuffles-social-services-jobs' ),
					__( 'Advertising is truthful, inclusive and accessible, and never identifies a participant.', 'shuffles-social-services-jobs' ),
				),
			),

			array(
				'id'      => 'inclusion',
				'title'   => __( 'Anti-discrimination & inclusion', 'shuffles-social-services-jobs' ),
				'doc'     => 'ANTI-DISCRIMINATION-INCLUSION-POLICY.md',
				'summary' => __( 'Everyone deserves a fair, accessible and respectful marketplace. We do not tolerate discrimination, harassment or vilification.', 'shuffles-social-services-jobs' ),
				'points'  => array(
					__( 'No discrimination on disability, race, culture, sex, gender identity, sexual orientation, age or religion.', 'shuffles-social-services-jobs' ),
					__( 'A participant choosing a worker of a particular gender, language or culture for personal reasons is a legitimate choice, not discrimination.', 'shuffles-social-services-jobs' ),
					__( 'Accessibility and CALD features (read-aloud, larger text, high contrast, languages) are built in.', 'shuffles-social-services-jobs' ),
					__( 'You can report discrimination to us or to the Australian Human Rights Commission (1300 656 419).', 'shuffles-social-services-jobs' ),
				),
			),

		);
		return apply_filters( 'shuffles_ssj_policies', $p );
	}

	/** Total number of published policies. */
	public static function count_items() {
		return count( self::items() );
	}

	/**
	 * Render the policies as collapsible panels (reuses the Guides toggle JS). Optional atts:
	 * title, only (comma list of policy ids).
	 *
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$atts  = is_array( $atts ) ? $atts : array();
		$title = ! empty( $atts['title'] ) ? $atts['title'] : __( 'Our policies', 'shuffles-social-services-jobs' );

		$only = array();
		if ( ! empty( $atts['only'] ) ) {
			$only = array_filter( array_map( 'trim', explode( ',', (string) $atts['only'] ) ) );
		}
		$items = self::items();
		if ( $only ) {
			$items = array_values( array_filter( $items, function ( $i ) use ( $only ) {
				return in_array( $i['id'], $only, true );
			} ) );
		}

		ob_start();
		?>
		<div class="sssj sssj--policies" data-sssj-guides>
			<div class="sssj-panel">
				<h2 style="margin-top:0"><?php echo esc_html( $title ); ?></h2>
				<p class="description"><?php esc_html_e( 'Plain-English summaries of the policies that keep this a safe, fair and private marketplace. Click a policy to read it.', 'shuffles-social-services-jobs' ); ?></p>
			</div>
			<?php foreach ( $items as $idx => $it ) : ?>
				<div class="sssj-panel sssj-guide sssj-policy<?php echo 0 === $idx ? ' is-open' : ''; ?>" data-sssj-guide>
					<button type="button" class="sssj-guide__head" data-guide-toggle aria-expanded="<?php echo 0 === $idx ? 'true' : 'false'; ?>">
						<span class="sssj-guide__heading">
							<span class="sssj-guide__title"><?php echo esc_html( $it['title'] ); ?></span>
						</span>
						<span class="sssj-guide__chev" aria-hidden="true">▾</span>
					</button>
					<div class="sssj-guide__body">
						<?php if ( ! empty( $it['summary'] ) ) : ?>
							<p class="sssj-guide__intro"><?php echo esc_html( $it['summary'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $it['points'] ) ) : ?>
							<ul class="sssj-policy__points">
								<?php foreach ( $it['points'] as $pt ) : ?>
									<li><?php echo esc_html( $pt ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="sssj-panel sssj-policy__help">
				<p class="description">
					<?php esc_html_e( 'Need help or want to raise a concern?', 'shuffles-social-services-jobs' ); ?>
					<br /><?php esc_html_e( 'NDIS Quality and Safeguards Commission: 1800 035 544 · ndiscommission.gov.au', 'shuffles-social-services-jobs' ); ?>
					<br /><?php esc_html_e( 'Privacy concerns (OAIC): 1300 363 992 · oaic.gov.au', 'shuffles-social-services-jobs' ); ?>
					<br /><?php esc_html_e( 'Discrimination (Australian Human Rights Commission): 1300 656 419 · humanrights.gov.au', 'shuffles-social-services-jobs' ); ?>
					<br /><?php esc_html_e( 'Interpreter (TIS National): 131 450 · National Relay Service: 133 677', 'shuffles-social-services-jobs' ); ?>
				</p>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}
