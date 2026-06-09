<?php
/**
 * Workflows, step-by-step "how it works" explainers for END USERS (single source of truth).
 *
 * Distinct from Shuffles_SSJ_Guides (which is *advice*, "how to do it well"). Workflows are
 * *procedural*: the exact path through the app to finish a task, written in plain English for
 * members. Rendered by the [sssj_workflows] shortcode and the Settings → How-to Workflows tab.
 *
 * Keep flows() CURRENT: when a member-facing flow changes, update its workflow here so the
 * help never drifts from the product (CLAUDE.md rule).
 *
 * @package Shuffles_SSJ
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shuffles_SSJ_Workflows {

	/**
	 * Explainer workflows. Each:
	 *   id      , stable slug
	 *   roles   , hat keys this flow is most relevant to (for primary-role "For you" highlighting)
	 *   audience, short audience label (badge)
	 *   title   , the task, as the member would say it
	 *   goal    , one line: what you will have achieved
	 *   need    , prerequisites (array of strings)
	 *   steps   , ordered steps; each: { do (action), where (where to find it, optional), note (tip/result, optional) }
	 *   done    , how you know it worked
	 *   start   , optional call-to-action: { label, page (settings key), sc (shortcode) }
	 *
	 * Filterable via `shuffles_ssj_workflows`.
	 *
	 * @return array
	 */
	public static function flows() {
		$w = array(

			array(
				'id'       => 'get-started',
				'roles'    => array(),
				'audience' => __( 'Everyone, start here', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Set up your account', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Get a working account and tell the marketplace how you want to use it, so the right tools and listings show up for you.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'An email address.', 'shuffles-social-services-jobs' ),
					__( 'A few minutes, you can finish your profile later.', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Register or log in.', 'shuffles-social-services-jobs' ),
						'where' => __( 'The “Log in” / “Register” link in the menu.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Browsing is open to everyone; you only need an account to apply, post, message or save searches.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Tick the role(s) that describe you.', 'shuffles-social-services-jobs' ),
						'where' => __( 'Get started (onboarding), or the “My roles” tab in My dashboard.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Worker, job seeker, participant, sole-trader/contractor, organisation, representative or supplier. You can hold more than one and change them any time.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Pick a primary role.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Your dashboard and menu focus on this role first, but nothing is hidden: a “See all” option always reveals everything.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Complete the profile that matches your role.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'A worker profile, an organisation profile, or simply a saved résumé, the more complete it is, the more often you are found and matched.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'You can see your dashboard, and the menu shows the tools for your role.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Get started', 'shuffles-social-services-jobs' ), 'page' => 'page_onboard', 'sc' => '[sssj_onboard]' ),
			),

			array(
				'id'       => 'post-job',
				'roles'    => array( 'employer', 'provider', 'supplier', 'participant', 'representative' ),
				'audience' => __( 'For organisations & advertisers', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Advertise a role (employee, contractor or volunteer)', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Publish a job advert on the correct board so the right workers can find and apply for it.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'A logged-in account that can post jobs.', 'shuffles-social-services-jobs' ),
					__( 'For contractor (ABN) roles: a valid 11-digit ABN on file.', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Open the posting form.', 'shuffles-social-services-jobs' ),
						'where' => __( '“Post a job” in the menu.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Choose the engagement basis first.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'TFN = an employee on wages (tax withheld, no ABN). ABN = a contractor/sole trader who invoices you. Volunteer = unpaid. This decides which board the advert appears on, so choose carefully.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Fill in the details: title, suburb, hours, pay, start date and the real must-have credentials.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Add a closing date so the advert retires itself when it is filled.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'For an employee (TFN) role, choose how applications are handled.', 'shuffles-social-services-jobs' ),
						'note'  => __( '“Full pipeline” lets you track applicants through stages; “Simple” just lists them. You can also add screening questions. You can switch this later.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Optionally link your organisation, or tick “Advertise anonymously”.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Linking shows your logo and other open roles. Anonymous hides your name everywhere, including search engines.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Publish.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'The advert appears on the all-jobs board and on its own board (Employee, Contractor or Volunteer), never on the wrong one.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Post a job', 'shuffles-social-services-jobs' ), 'page' => 'page_post_job', 'sc' => '[sssj_post_job]' ),
			),

			array(
				'id'       => 'apply-tfn',
				'roles'    => array( 'candidate' ),
				'audience' => __( 'For job seekers (employees)', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Apply for an employee (TFN) job', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Send a complete application for a wage-paying job and follow it through to a decision.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'A logged-in account.', 'shuffles-social-services-jobs' ),
					__( 'At least one saved résumé (see “Create and store a résumé”).', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Find a job and open it.', 'shuffles-social-services-jobs' ),
						'where' => __( 'The Jobs board or the Employee (TFN) board.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Check the must-have credentials before you apply.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Click Apply and pick the résumé to send.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Add your availability, earliest start date, and confirm your right to work in Australia.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Right to work is required, the form will not submit without it.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Answer any screening questions and submit.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Track it (and withdraw if you change your mind).', 'shuffles-social-services-jobs' ),
						'where' => __( 'My dashboard → My applications.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'You will be emailed as the employer moves you through the stages. A Withdraw button shows while the application is still active.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'Your application is listed under My applications, and the employer can see your chosen résumé and answers.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Browse employee jobs', 'shuffles-social-services-jobs' ), 'page' => 'page_tfn_board', 'sc' => '[sssj_tfn_board]' ),
			),

			array(
				'id'       => 'respond-abn',
				'roles'    => array( 'contractor', 'provider' ),
				'audience' => __( 'For ABN contractors & sole traders', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Quote for contractor (ABN) work or a participant request', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Express interest in fee-for-service work and start a safe conversation with the advertiser or participant.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'A logged-in account.', 'shuffles-social-services-jobs' ),
					__( 'A valid 11-digit ABN recorded on your profile, you cannot respond to ABN work without it.', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Record your ABN on your profile.', 'shuffles-social-services-jobs' ),
						'where' => __( 'Edit my profile.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'It is validated. If you try to respond without one, you are sent here first.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Browse the right board.', 'shuffles-social-services-jobs' ),
						'where' => __( 'The Contractor (ABN) board, or “Participants seeking workers”.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Contractor work and participant requests never mix with employee (TFN) positions.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Open a listing and send a short expression of interest with your rate.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Say what is included. Funding (NDIS, aged care, privately funded…) is a guide to how you will invoice, never a barrier to making contact.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Continue the conversation inside the platform.', 'shuffles-social-services-jobs' ),
						'where' => __( 'Messages.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Participant requests show a pseudonym and suburb only, build trust before sharing details.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'Your expression of interest is sent and a private message thread is open.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Browse contractor work', 'shuffles-social-services-jobs' ), 'page' => 'page_abn_board', 'sc' => '[sssj_abn_board]' ),
			),

			array(
				'id'       => 'manage-applicants',
				'roles'    => array( 'employer', 'provider' ),
				'audience' => __( 'For organisations & advertisers', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Review applicants and reach a decision', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'See who applied, review their details, and move each person through to hired or declined, keeping them informed automatically.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'A job you posted that has applicants.', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Open your job and its applicants.', 'shuffles-social-services-jobs' ),
						'where' => __( 'My dashboard → My job ads.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Review each applicant.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'You can open the résumé they chose, and see their availability, start date, right-to-work and screening answers. Only you (the advertiser) can open their résumé.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Move them through the stages.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'On a “Full pipeline” job: New → Shortlisted → Interview → Offer → Hired or Declined. A “Simple” job uses a shorter set. Each change is kept in a status history.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Let the system notify them.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'The applicant is emailed automatically whenever you change their status, no need to send a separate message.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'Every applicant has a clear status, a recorded history, and has been told where they stand.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Open my dashboard', 'shuffles-social-services-jobs' ), 'page' => 'page_dashboard', 'sc' => '[sssj_dashboard]' ),
			),

			array(
				'id'       => 'post-need',
				'roles'    => array( 'participant', 'representative' ),
				'audience' => __( 'For participants & their nominees', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Ask for support privately (as a participant)', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Tell workers what support you are looking for, without ever showing your name or contact details.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'A logged-in account (you or someone acting for you).', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Open the request form.', 'shuffles-social-services-jobs' ),
						'where' => __( '“Request support” in the menu.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Describe the support, hours, and any preferences.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Gender, language and cultural preferences help the right worker find you. Use general terms, never personal identifying details.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Submit for review.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Requests are checked by a moderator before they appear, to keep everyone safe.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Reply to interested workers through the relay.', 'shuffles-social-services-jobs' ),
						'where' => __( 'Messages.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Your listing shows only a pseudonym and your suburb. Your email and phone are never shown.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'Once approved, your request appears with a pseudonym + suburb only, and is hidden from search engines.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Request support', 'shuffles-social-services-jobs' ), 'page' => 'page_post_need', 'sc' => '[sssj_post_need]' ),
			),

			array(
				'id'       => 'build-resume',
				'roles'    => array( 'candidate', 'contractor' ),
				'audience' => __( 'For workers & job seekers', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Create and store a résumé', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Keep one or more résumés on file so applying is fast and the right one goes to each employer.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'A logged-in account.', 'shuffles-social-services-jobs' ),
					__( 'A résumé file (PDF, Word, RTF or ODT, up to 8 MB).', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Open My résumés.', 'shuffles-social-services-jobs' ),
						'where' => __( 'My dashboard → My résumés.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Upload a file and give it a clear name.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'For example “Support work – 2026”. You can keep up to five.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Tick one as your default.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'The default is pre-selected when you apply; you can still pick a different one per job.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'Your résumés are stored privately, only you (and an employer you apply to) can open them.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Manage my résumés', 'shuffles-social-services-jobs' ), 'page' => 'page_dashboard', 'sc' => '[sssj_dashboard]' ),
			),

			array(
				'id'       => 'join-org',
				'roles'    => array( 'employer', 'provider', 'representative' ),
				'audience' => __( 'For organisations & their teams', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Join or set up an organisation', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Connect to an existing organisation, or create one, so a team can advertise and manage listings together.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'A logged-in account.', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Search for your organisation.', 'shuffles-social-services-jobs' ),
						'where' => __( 'The Organisations directory.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'If it exists, open its profile and request to join.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'An organisation admin must approve you, nobody joins automatically. You will see the request as pending until then.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'If it does not exist, create it.', 'shuffles-social-services-jobs' ),
						'where' => __( '“Create organisation profile”.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Add your logo, locations and (if you contract) your ABN.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Manage your team.', 'shuffles-social-services-jobs' ),
						'where' => __( 'The Team tab on your organisation / dashboard.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Org admins approve or decline join requests and can remove members.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'You belong to an organisation (or run one), and the team can post and manage listings under it.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Browse organisations', 'shuffles-social-services-jobs' ), 'page' => 'page_org_directory', 'sc' => '[sssj_org_directory]' ),
			),

			array(
				'id'       => 'save-alerts',
				'roles'    => array(),
				'audience' => __( 'Everyone', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Save a search and get alerts', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Let new matching listings come to you, instead of checking back every day.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'A logged-in account (you can set up a search while logged out, but saving needs an account).', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Set up the search you want.', 'shuffles-social-services-jobs' ),
						'where' => __( 'Any board or directory, set the keywords, filters, location and radius.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Click “Save & alert me”.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'If you are logged out, this becomes a log-in prompt that brings you back to the same search.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Manage your saved searches.', 'shuffles-social-services-jobs' ),
						'where' => __( 'My alerts in your dashboard.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'You get a daily email when new listings match, and can remove a search any time.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'Matching new listings are emailed to you automatically.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Browse jobs', 'shuffles-social-services-jobs' ), 'page' => 'page_job_board', 'sc' => '[sssj_job_board]' ),
			),

			array(
				'id'       => 'volunteer',
				'roles'    => array(),
				'audience' => __( 'Everyone', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Find or offer volunteer work', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Give time, or recruit volunteers, on a board kept separate from paid work.', 'shuffles-social-services-jobs' ),
				'need'     => array(
					__( 'A logged-in account to apply (no ABN required).', 'shuffles-social-services-jobs' ),
				),
				'steps'    => array(
					array(
						'do'    => __( 'Open the Volunteer board.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Volunteer roles carry a green “Volunteer” badge and never mix with employee or contractor work.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'To volunteer: open a role and apply, any logged-in member can.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'To recruit volunteers: post a job with the basis set to “Volunteer (unpaid)”.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'Your volunteer application is sent, or your volunteer role is live on the Volunteer board.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Volunteer opportunities', 'shuffles-social-services-jobs' ), 'page' => 'page_volunteer_board', 'sc' => '[sssj_volunteer_board]' ),
			),

			array(
				'id'       => 'stay-safe',
				'roles'    => array(),
				'audience' => __( 'Everyone', 'shuffles-social-services-jobs' ),
				'title'    => __( 'Stay safe: messaging, privacy & verified checks', 'shuffles-social-services-jobs' ),
				'goal'     => __( 'Understand the safety features so you can use the marketplace with confidence.', 'shuffles-social-services-jobs' ),
				'need'     => array(),
				'steps'    => array(
					array(
						'do'    => __( 'Keep first contact inside the platform.', 'shuffles-social-services-jobs' ),
						'where' => __( 'Messages.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'It protects everyone’s privacy and keeps a record. Never share or ask for personal contact details up front.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Look for the ✓ Verified badge.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'It is only shown after an admin confirms a worker’s checks (WWCC, NDIS Worker Screening, Police Check, First Aid). It is never self-claimed.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Get your own checks verified.', 'shuffles-social-services-jobs' ),
						'where' => __( 'My credentials.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Uploaded documents are stored privately and only used for verification.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Respect participant privacy.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Participants appear by pseudonym and suburb only. Their pages are never shown to search engines.', 'shuffles-social-services-jobs' ),
					),
					array(
						'do'    => __( 'Raise a concern if something is wrong.', 'shuffles-social-services-jobs' ),
						'note'  => __( 'Use the complaints/feedback channel, concerns are handled in line with NDIS practice standards.', 'shuffles-social-services-jobs' ),
					),
				),
				'done'     => __( 'You know how to contact people safely, read the trust signals, and get help if needed.', 'shuffles-social-services-jobs' ),
				'start'    => array( 'label' => __( 'Open Messages', 'shuffles-social-services-jobs' ), 'page' => 'page_messages', 'sc' => '[sssj_messages]' ),
			),

		);
		return apply_filters( 'shuffles_ssj_workflows', $w );
	}

	/** Total number of workflows. */
	public static function count_flows() {
		return count( self::flows() );
	}

	/**
	 * Render the workflows as collapsible, numbered-step panels. Reuses the Guides toggle JS
	 * (same data-sssj-guides / data-guide-toggle hooks). When a logged-in member has a primary
	 * role, flows for that role float to the top and carry a "For you" marker.
	 *
	 * @param array $atts title, only (comma list of ids), roles (comma list of hat keys).
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$atts  = is_array( $atts ) ? $atts : array();
		$title = ! empty( $atts['title'] ) ? $atts['title'] : __( 'How it works', 'shuffles-social-services-jobs' );

		$only = array();
		if ( ! empty( $atts['only'] ) ) {
			$only = array_filter( array_map( 'trim', explode( ',', (string) $atts['only'] ) ) );
		}
		$role_filter = array();
		if ( ! empty( $atts['roles'] ) ) {
			$role_filter = array_filter( array_map( 'trim', explode( ',', (string) $atts['roles'] ) ) );
		}

		$flows = self::flows();
		if ( $only ) {
			$flows = array_values( array_filter( $flows, function ( $f ) use ( $only ) {
				return in_array( $f['id'], $only, true );
			} ) );
		}
		if ( $role_filter ) {
			$flows = array_values( array_filter( $flows, function ( $f ) use ( $role_filter ) {
				return (bool) array_intersect( $role_filter, ! empty( $f['roles'] ) ? (array) $f['roles'] : array() );
			} ) );
		}

		// Primary-role focus: float matching flows to the top (stable), mark them "For you".
		$primary = ( is_user_logged_in() && class_exists( 'Shuffles_SSJ_Roles' ) ) ? Shuffles_SSJ_Roles::primary_role( get_current_user_id() ) : '';
		if ( $primary ) {
			$mine   = array();
			$others = array();
			foreach ( $flows as $f ) {
				if ( ! empty( $f['roles'] ) && in_array( $primary, (array) $f['roles'], true ) ) {
					$f['_for_you'] = true;
					$mine[]        = $f;
				} else {
					$others[] = $f;
				}
			}
			$flows = array_merge( $mine, $others );
		}

		ob_start();
		?>
		<div class="sssj sssj--workflows" data-sssj-guides>
			<div class="sssj-panel">
				<h2 style="margin-top:0"><?php echo esc_html( $title ); ?></h2>
				<p class="description"><?php esc_html_e( 'Short, step-by-step walkthroughs for getting things done. Click a workflow to open it, then follow the steps in order.', 'shuffles-social-services-jobs' ); ?></p>
			</div>
			<?php foreach ( $flows as $i => $f ) : ?>
				<div class="sssj-panel sssj-guide sssj-flow<?php echo 0 === $i ? ' is-open' : ''; ?>" data-sssj-guide>
					<button type="button" class="sssj-guide__head" data-guide-toggle aria-expanded="<?php echo 0 === $i ? 'true' : 'false'; ?>">
						<span class="sssj-guide__heading">
							<span class="sssj-guide__title"><?php echo esc_html( $f['title'] ); ?></span>
							<?php if ( ! empty( $f['_for_you'] ) ) : ?>
								<span class="sssj-badge sssj-badge--verified sssj-flow__foryou"><?php esc_html_e( 'For you', 'shuffles-social-services-jobs' ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $f['audience'] ) ) : ?>
								<span class="sssj-badge sssj-guide__audience"><?php echo esc_html( $f['audience'] ); ?></span>
							<?php endif; ?>
						</span>
						<span class="sssj-guide__chev" aria-hidden="true">▾</span>
					</button>
					<div class="sssj-guide__body">
						<?php if ( ! empty( $f['goal'] ) ) : ?>
							<p class="sssj-guide__intro"><strong><?php esc_html_e( 'Goal:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( $f['goal'] ); ?></p>
						<?php endif; ?>

						<?php if ( ! empty( $f['need'] ) ) : ?>
							<div class="sssj-flow__need">
								<strong><?php esc_html_e( 'Before you start', 'shuffles-social-services-jobs' ); ?></strong>
								<ul>
									<?php foreach ( $f['need'] as $n ) : ?>
										<li><?php echo esc_html( $n ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $f['steps'] ) ) : ?>
							<ol class="sssj-guide__steps sssj-flow__steps">
								<?php foreach ( $f['steps'] as $step ) : ?>
									<li>
										<strong><?php echo esc_html( $step['do'] ); ?></strong>
										<?php if ( ! empty( $step['where'] ) ) : ?>
											<span class="sssj-flow__where">📍 <?php echo esc_html( $step['where'] ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $step['note'] ) ) : ?>
											<span class="sssj-flow__note"><?php echo esc_html( $step['note'] ); ?></span>
										<?php endif; ?>
									</li>
								<?php endforeach; ?>
							</ol>
						<?php endif; ?>

						<?php if ( ! empty( $f['done'] ) ) : ?>
							<p class="sssj-flow__done">✅ <strong><?php esc_html_e( 'Done:', 'shuffles-social-services-jobs' ); ?></strong> <?php echo esc_html( $f['done'] ); ?></p>
						<?php endif; ?>

						<?php
						if ( ! empty( $f['start'] ) && ! empty( $f['start']['label'] ) && class_exists( 'Shuffles_SSJ_Shortcodes' ) ) {
							$start_url = Shuffles_SSJ_Shortcodes::page_link(
								isset( $f['start']['page'] ) ? $f['start']['page'] : '',
								isset( $f['start']['sc'] ) ? $f['start']['sc'] : ''
							);
							if ( $start_url ) {
								echo '<p class="sssj-flow__cta"><a class="sssj-btn sssj-btn--primary sssj-btn--sm" href="' . esc_url( $start_url ) . '">' . esc_html( $f['start']['label'] ) . ' →</a></p>';
							}
						}
						?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
