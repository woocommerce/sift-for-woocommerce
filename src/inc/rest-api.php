<?php

namespace Sift_For_WooCommerce;

/**
 * Class REST_API
 *
 * Handles the registration and management of custom REST API endpoints related to fraud decisions.
 * Implements a singleton pattern to ensure a single instance of the class is created.
 */
class REST_API {

	const ROUTE_NAMESPACE = 'sift-for-woocommerce/v1';

	private static $instance;
	protected $api_secret;

	/**
	 * Initialize the singleton class by setting the API secret and registering REST API endpoints.
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );

		// If you need to regenerate the $api_secret, you can use bin2hex(random_bytes(48)).
		if ( getenv( 'SIFT_FOR_WOOCOMMERCE_API_SECRET' ) ) {
			$this->api_secret = getenv( 'SIFT_FOR_WOOCOMMERCE_API_SECRET' );
		} elseif ( defined( 'SIFT_FOR_WOOCOMMERCE_API_SECRET' ) ) {
			$this->api_secret = SIFT_FOR_WOOCOMMERCE_API_SECRET;
		}
	}

	/**
	 * Prevent cloning.
	 *
	 * @return void
	 */
	public function __clone(): void {
		/* Empty on purpose. */
	}

	/**
	 *  Prevent unserializing.
	 *
	 * @return void
	 */
	public function __wakeup(): void {
		/* Empty on purpose. */
	}

	/**
	 * REST API instance, ensure only 1 instance is loaded.
	 *
	 * @return object
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register REST API endpoints for managing fraud decisions.
	 *
	 * This method registers endpoints for both GET and POST requests to the same route:
	 * - A POST endpoint for applying a manual fraud decision to a specific user.
	 * - A GET endpoint for retrieving the fraud decision for a specific user.
	 *
	 * @return void
	 */
	public function register_endpoints(): void {
		register_rest_route(
			self::ROUTE_NAMESPACE,
			'/fraud/users/(?P<user_id>[\d]+)/decision',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'handle_get_fraud_decision' ),
					'permission_callback' => array( $this, 'verify_authorization' ),
					'args'                => array(
						'user_id' => array(
							'description' => __( 'Unique identifier for the user.', 'sift-for-woocommerce' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handle_apply_manual_fraud_decision' ),
					'permission_callback' => array( $this, 'verify_authorization' ),
					'args'                => array(
						'user_id'     => array(
							'description' => __( 'Unique identifier for the user.', 'sift-for-woocommerce' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'decision_id' => array(
							'description' => __( 'The fraud decision ID to apply.', 'sift-for-woocommerce' ),
							'type'        => 'string',
							'required'    => true,
						),
						'description' => array(
							'description' => __( 'Optional description of the fraud decision.', 'sift-for-woocommerce' ),
							'type'        => 'string',
							'required'    => false,
						),
						'analyst'     => array(
							'description' => __( 'Username of the analyst making the decision.', 'sift-for-woocommerce' ),
							'type'        => 'string',
							'required'    => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Handle the permission_callback for API.
	 *
	 * @param \WP_REST_Request $request The request object coming into the endpoint.
	 *
	 * @return boolean|\WP_Error True if authorized, WP_Error if not.
	 */
	public function verify_authorization( \WP_REST_Request $request ): bool|\WP_Error {
		if ( ! $this->api_secret ) {
			return new \WP_Error( 'disabled', 'Endpoint disabled.', array( 'status' => 503 ) );
		}

		$current_time = time();
		$auth         = $request->get_header( 'authorization' );
		$parts        = explode( '|', $auth );

		if ( 3 !== count( $parts ) ) {
			return new \WP_Error( 'invalid_authorization', 'Request could not be authenticated', array( 'status' => 400 ) );
		}

		$time       = (int) $parts[1];
		$remote_key = $parts[2];

		if ( MINUTE_IN_SECONDS < abs( $current_time - $time ) ) {
			return new \WP_Error( 'bad_timestamp', 'Timestamp is out of range', array( 'status' => 401 ) );
		}

		$endpoint      = str_replace( '/' . self::ROUTE_NAMESPACE, '', $request->get_route() );
		$expected_hash = hash_hmac( 'sha256', "{$endpoint}|{$time}", $this->api_secret );
		if ( ! hash_equals( $expected_hash, $remote_key ) ) {
			return new \WP_Error( 'bad_key', 'Bad key', array( 'status' => 401 ) );
		}

		return true;
	}

	/**
	 * Apply a fraud decision to a user.
	 *
	 * @param \WP_REST_Request $request The request object coming into the endpoint.
	 *
	 * @return boolean|\WP_Error true or error object on failure.
	 */
	public function handle_apply_manual_fraud_decision( \WP_REST_Request $request ): bool|\WP_Error {
		$user_id     = absint( $request->get_param( 'user_id' ) );
		$decision_id = sanitize_text_field( $request->get_param( 'decision_id' ) );
		$description = sanitize_text_field( $request->get_param( 'description' ) );
		$analyst     = sanitize_text_field( $request->get_param( 'analyst' ) );

		// Validate required parameters.
		if ( empty( $user_id ) || empty( $decision_id ) ) {
			wc_get_logger()->log(
				'error',
				'Invalid POST fraud decision request: missing user ID or decision',
				array( 'source' => 'sift-for-woocommerce' )
			);
			return new \WP_Error( 'invalid_parameters', 'Missing required parameters: user ID and decision.', array( 'status' => 400 ) );
		}

		/**
		 * This filter allows customization of the manual fraud decision application.
		 */
		$return = apply_filters(
			'sift_for_woocommerce_process_manual_fraud_decision',
			$user_id,
			$decision_id,
			$description,
			$analyst
		);

		return $return;
	}

	/**
	 * Retrieve the latest fraud decision for a user.
	 *
	 * @param \WP_REST_Request $request The request object coming into the endpoint.
	 *
	 * @return mixed|\WP_Error Response data or error object.
	 */
	public function handle_get_fraud_decision( \WP_REST_Request $request ) {
		$user_id = absint( $request->get_param( 'user_id' ) );

		// Validate required parameters.
		if ( empty( $user_id ) ) {
			wc_get_logger()->log(
				'error',
				'Invalid GET fraud decision request: missing user ID',
				array( 'source' => 'sift-for-woocommerce' )
			);
			return new \WP_Error( 'invalid_parameters', 'Missing required parameters: user ID', array( 'status' => 400 ) );
		}

		/**
		 * This filter allows customization of retrieving the last fraud decision for a user.
		 */
		$return = apply_filters(
			'sift_for_woocommerce_get_last_fraud_decision',
			$user_id
		);

		return $return;
	}
}
