<?php
/**
 * Guides — plain-language how-to content (single source of truth) for the four sides of the
 * marketplace. Rendered by the [sssj_guides] shortcode and the Settings → Guides tab.
 * Keep sections() CURRENT: when a flow changes, update its guide here so the help never drifts.
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Guides {

	/**
	 * Guide sections. Each: [ id, audience, title, intro, steps[ {h, p} ], tips[] ]. Filterable.
	 *
	 * @return array
	 */
	public static function sections() {
		$g = array(
			array(
				'id'       => 'write-job-post',
				'audience' => __( 'For organisations & advertisers', 'shuffles-social-services-jobs' ),
				'title'    => __( 'How to write a successful job post', 'shuffles-social-services-jobs' ),
				'intro'    => __( 'A clear, specific advert attracts the right workers and fewer mismatched applications. Spend ten minutes here and you will save hours of screening.', 'shuffles-social-services-jobs' ),
				'steps'    => array(
					array(
						'h' => __( 'Pick the right engagement basis first', 'shuffles-social-services-jobs' ),
						'p' => __( 'Choose TFN if you are hiring an employee (wages, tax withheld, no ABN). Choose ABN if you are engaging a sole trader or contractor who invoices you. This decides which board your advert appears on, so get it right.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Write a specific title', 'shuffles-social-services-jobs' ),
						'p' => __( 'Name the role and the setting: “Support Worker — evenings, Penrith” beats “Worker wanted”. Include the suburb so the location and radius search can find you.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Describe the work, not just the person', 'shuffles-social-services-jobs' ),
						'p' => __( 'Say what the day looks like, who the participant is (in general terms), the hours, and the pattern (one-off, ongoing, casual pool). Workers self-select better when they can picture the job.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Set the rate and the credentials honestly', 'shuffles-social-services-jobs' ),
						'p' => __( 'Add a rate range and the real must-haves (WWCC, NDIS Worker Screening, First Aid). The matcher uses these — listing a credential you do not truly need will filter out good people.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Attach your organisation', 'shuffles-social-services-jobs' ),
						'p' => __( 'Link the job to your organisation profile so your logo, locations and other open roles appear together. A branded advert reads as trustworthy.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Set a closing date', 'shuffles-social-services-jobs' ),
						'p' => __( 'Give the advert a “Closes on” date so it retires itself when filled. Stale adverts waste applicants’ time and hurt your response rate next time.', 'shuffles-social-services-jobs' ),
					),
				),
				'tips'     => array(
					__( 'Add languages and cultural focus (e.g. LGBTQIA+, Auslan, Arabic) when they matter — participants and workers filter on these.', 'shuffles-social-services-jobs' ),
					__( 'Reply to applicants quickly through the internal messages — never ask for personal contact details up front.', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'id'       => 'respond-to-job',
				'audience' => __( 'For workers', 'shuffles-social-services-jobs' ),
				'title'    => __( 'How to respond to a job', 'shuffles-social-services-jobs' ),
				'intro'    => __( 'A short, relevant message beats a long generic one. Show you read the advert and that you hold what they asked for.', 'shuffles-social-services-jobs' ),
				'steps'    => array(
					array(
						'h' => __( 'Check you meet the must-haves', 'shuffles-social-services-jobs' ),
						'p' => __( 'Read the required credentials and the engagement basis. If the role is ABN-based, you need a recorded ABN on file before you can respond.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Write a three-line cover note', 'shuffles-social-services-jobs' ),
						'p' => __( 'Line one: why this role suits you. Line two: the relevant experience or credential. Line three: your availability. That is enough to start a conversation.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Make your profile do the heavy lifting', 'shuffles-social-services-jobs' ),
						'p' => __( 'Advertisers click through to your worker profile. Make sure your services, location, rate and a photo are up to date before you apply.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Keep talking inside the platform', 'shuffles-social-services-jobs' ),
						'p' => __( 'Use the internal messages for first contact. It protects everyone’s privacy and keeps a record if anything needs sorting out.', 'shuffles-social-services-jobs' ),
					),
				),
				'tips'     => array(
					__( 'Apply once per job — duplicate applications do not help and are not recorded twice.', 'shuffles-social-services-jobs' ),
					__( 'If you do not hear back, it is fine to send one polite follow-up after a few days.', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'id'       => 'abn-contractor-work',
				'audience' => __( 'For ABN contractors & sole traders', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Working as an ABN contractor', 'shuffles-social-services-jobs' ),
				'intro'    => __( 'ABN work covers subcontracting, fee-for-service and direct engagements with self-managed or plan-managed participants. You invoice for your work, so a few things are set up differently.', 'shuffles-social-services-jobs' ),
				'steps'    => array(
					array(
						'h' => __( 'Record your ABN', 'shuffles-social-services-jobs' ),
						'p' => __( 'Add your 11-digit ABN to your profile. It is validated, and you cannot respond to ABN jobs or participant requests until it is on file. Keep your business name and status current.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Find ABN work on the right board', 'shuffles-social-services-jobs' ),
						'p' => __( 'ABN engagements and participant requests live on their own boards — they never mix with employee (TFN) positions. Filter by sector, funding and location to find a good fit.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Understand funding', 'shuffles-social-services-jobs' ),
						'p' => __( 'A participant may be NDIS self-managed, plan-managed, aged-care or privately funded. Funding is a guide to how you will be paid and invoice — it is never a barrier to making contact.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Be clear about your service and rate', 'shuffles-social-services-jobs' ),
						'p' => __( 'As a contractor you set your own rate. State it, state what is included, and keep your insurances and credentials verified so you stand out as low-risk to engage.', 'shuffles-social-services-jobs' ),
					),
				),
				'tips'     => array(
					__( 'Respect participant privacy: requests show a pseudonym and a suburb only. Build trust through the platform before sharing details.', 'shuffles-social-services-jobs' ),
					__( 'Get your credentials admin-verified — the ✓ Verified badge is the single biggest trust signal on your profile.', 'shuffles-social-services-jobs' ),
				),
			),
			array(
				'id'       => 'standing-profile',
				'audience' => __( 'For workers & contractors', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Building a standing profile that gets matched', 'shuffles-social-services-jobs' ),
				'intro'    => __( 'Your profile works for you around the clock — it surfaces in searches and the matcher even when you are not applying. A complete profile gets found far more often.', 'shuffles-social-services-jobs' ),
				'steps'    => array(
					array(
						'h' => __( 'Complete every field', 'shuffles-social-services-jobs' ),
						'p' => __( 'Add your services, years of experience, rate, languages and cultural focus. Each filled field is another way the matcher and the directory filters can find you.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Set your location and availability', 'shuffles-social-services-jobs' ),
						'p' => __( 'A geocoded suburb lets employers find you by distance. Tick “available now” when you are open to work — only your suburb is shown publicly.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Add a photo and a gallery', 'shuffles-social-services-jobs' ),
						'p' => __( 'A friendly profile photo, and a few photos of your work where relevant (e.g. gardening, support activities), make your profile feel real and trustworthy.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Choose who can see you', 'shuffles-social-services-jobs' ),
						'p' => __( 'Pick public (search-engine visible), logged-in members only, or “Do not display” to pause your profile entirely. You stay in control of your visibility.', 'shuffles-social-services-jobs' ),
					),
					array(
						'h' => __( 'Keep it fresh', 'shuffles-social-services-jobs' ),
						'p' => __( 'Update your availability and rates as they change, and renew credentials before they expire so your ✓ Verified badge never lapses.', 'shuffles-social-services-jobs' ),
					),
				),
				'tips'     => array(
					__( 'Set your status to “employed — open to more” if you already work but want extra shifts; your current employer is never notified.', 'shuffles-social-services-jobs' ),
					__( 'Mention cultural focus and languages — many participants search specifically for these.', 'shuffles-social-services-jobs' ),
				),
			),
		);
		return apply_filters( 'shuffles_ssj_guides', $g );
	}

	/** Total number of guides. */
	public static function count_guides() {
		return count( self::sections() );
	}

	/**
	 * Render the guides as collapsible panels (open the first by default).
	 *
	 * @param array $atts title (optional), only (optional comma list of ids).
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$title = ! empty( $atts['title'] ) ? $atts['title'] : __( 'Guides', 'shuffles-social-services-jobs' );
		$only  = array();
		if ( ! empty( $atts['only'] ) ) {
			$only = array_filter( array_map( 'trim', explode( ',', (string) $atts['only'] ) ) );
		}
		$sections = self::sections();
		if ( $only ) {
			$sections = array_values( array_filter( $sections, function ( $s ) use ( $only ) {
				return in_array( $s['id'], $only, true );
			} ) );
		}

		ob_start();
		?>
		<div class="sssj sssj--guides" data-sssj-guides>
			<div class="sssj-panel">
				<h2 style="margin-top:0"><?php echo esc_html( $title ); ?></h2>
				<p class="description"><?php esc_html_e( 'Short, plain-language guides for getting the most out of the marketplace. Click a guide to open it.', 'shuffles-social-services-jobs' ); ?></p>
			</div>
			<?php foreach ( $sections as $i => $s ) : ?>
				<div class="sssj-panel sssj-guide<?php echo 0 === $i ? ' is-open' : ''; ?>" data-sssj-guide>
					<button type="button" class="sssj-guide__head" data-guide-toggle aria-expanded="<?php echo 0 === $i ? 'true' : 'false'; ?>">
						<span class="sssj-guide__heading">
							<span class="sssj-guide__title"><?php echo esc_html( $s['title'] ); ?></span>
							<?php if ( ! empty( $s['audience'] ) ) : ?>
								<span class="sssj-badge sssj-guide__audience"><?php echo esc_html( $s['audience'] ); ?></span>
							<?php endif; ?>
						</span>
						<span class="sssj-guide__chev" aria-hidden="true">▾</span>
					</button>
					<div class="sssj-guide__body">
						<?php if ( ! empty( $s['intro'] ) ) : ?>
							<p class="sssj-guide__intro"><?php echo esc_html( $s['intro'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $s['steps'] ) ) : ?>
							<ol class="sssj-guide__steps">
								<?php foreach ( $s['steps'] as $step ) : ?>
									<li>
										<strong><?php echo esc_html( $step['h'] ); ?></strong>
										<span><?php echo esc_html( $step['p'] ); ?></span>
									</li>
								<?php endforeach; ?>
							</ol>
						<?php endif; ?>
						<?php if ( ! empty( $s['tips'] ) ) : ?>
							<div class="sssj-guide__tips">
								<strong><?php esc_html_e( 'Quick tips', 'shuffles-social-services-jobs' ); ?></strong>
								<ul>
									<?php foreach ( $s['tips'] as $tip ) : ?>
										<li><?php echo esc_html( $tip ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
