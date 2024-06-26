<?php
/**
 * Class GF_Dupe_Submissions_Handler
 *
 * @since 2.9.1
 *
 * Provides functionality for handling duplicate submissions while avoiding multiple
 * entries being submitted.
 */

class GF_Dupe_Submissions_Handler {

	/**
	 * The URL parameter used for redirect protection in Safari.
	 */
	const SAFARI_REDIRECT_PARAM = 'gf_protect_submission';

	/**
	 * @var string The base URL for this plugin.
	 */
	private $base_url;

	/**
	 * GF_Dupe_Submissions_Handler constructor.
	 *
	 * @param string $base_url The Base URL for this Plugin.
	 */
	public function __construct( $base_url ) {
		$this->base_url = $base_url;
	}

	/**
	 * Initialize any hooks required for functionality.
	 */
	public function init() {
		$form_id = isset( $_POST['gform_submit'] ) ? absint( $_POST['gform_submit'] ) : 0;

		/**
		 * Allows users to disable duplicate submissions protection, either globally
		 * or on a form-by-form basis.
		 *
		 * @since 2.9.1
		 *
		 * @param bool       Passes a false value by default.
		 * @param int|string Passes the current form ID.
		 */
		$disable = gf_apply_filters( array( 'gform_duplicate_submissions_protection_disable', $form_id ), false, $form_id );

		if ( $disable ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp', array( $this, 'handle_safari_redirect' ), 8, 0 );
	}

	/**
	 * Enqueue the JS file.
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'gf_duplicate_submissions', $this->base_url . 'js/duplicate-submissions.js', array(), true );
		wp_localize_script( 'gf_duplicate_submissions', 'gf_dupe_submissions', $this->get_localized_script_data() );
	}

	/**
	 * Get the correct data to localize to the JS file.
	 *
	 * @return array
	 */
	private function get_localized_script_data() {
		return array(
			'is_gf_submission'      => (int) $this->is_valid_submission(),
			'safari_redirect_param' => self::SAFARI_REDIRECT_PARAM,
		);
	}

	/**
	 * Check if the current submission exists, and is valid.
	 *
	 * @return bool
	 */
	private function is_valid_submission() {
		$form_id = filter_input( INPUT_POST, 'gform_submit', FILTER_SANITIZE_NUMBER_INT );

		if ( empty( $form_id ) || ! class_exists( 'GFFormDisplay' ) ) {
			return false;
		}

		$entry_id = rgars( GFFormDisplay::$submission, $form_id . '/lead/id' );

		if ( empty( $entry_id ) ) {
			return false;
		}

		GFCommon::log_debug( __METHOD__ . sprintf( '(): form #%d. entry #%d.', $form_id, $entry_id ) );

		return true;
	}

	/**
	 * Redirect to a $_GET request if we detect a dupe submission from Safari.
	 */
	public function handle_safari_redirect() {
		if ( is_admin() ) {
			return;
		}

		$needs_protection = filter_input( INPUT_GET, self::SAFARI_REDIRECT_PARAM, FILTER_SANITIZE_STRING );

		if ( empty( $needs_protection ) ) {
			return;
		}

		// Get the submission URL from the $_SERVER, and strip out our redirect param.
		$submission_url = $_SERVER['HTTP_REFERER'];
		$base_url       = remove_query_arg( self::SAFARI_REDIRECT_PARAM, $submission_url );

		// Redirect to the form's page URL as a GET request.
		wp_safe_redirect( $base_url, 303 );
		exit;
	}

}