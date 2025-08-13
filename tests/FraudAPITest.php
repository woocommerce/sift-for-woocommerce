<?php
/**
 * Class Fraud_API_Test
 *
 * @package Sift_For_WooCommerce
 */

declare( strict_types=1 );

// phpcs:disable

use Sift_For_WooCommerce\Abuse_Decisions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API test case for fraud decision endpoints.
 */
class Fraud_API_Test extends \WP_UnitTestCase {

	/**
	 * API secret for authentication.
	 *
	 * @var string
	 */
	private $api_secret;

	/**
	 * REST API instance.
	 *
	 * @var \Sift_For_WooCommerce\REST_API
	 */
	private $api;

	/**
	 * Test user ID for API tests.
	 *
	 * @var int
	 */
	private $test_user_id;

	/**
	 * Set up test fixtures before each test method.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->api_secret = $this->get_api_secret();
		if ( empty( $this->api_secret ) ) {
			$this->markTestSkipped( 'API SECRET not found in environment' );
		}

		$this->api = Sift_For_WooCommerce\REST_API::instance();

		// Create a test user for each test
		$this->test_user_id = wp_create_user( 'testuser_' . time(), 'password' );
	}

	/**
	 * Clean up test fixtures after each test method.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clean up test user
		if ( $this->test_user_id ) {
			wp_delete_user( $this->test_user_id );
		}

		parent::tearDown();
	}

	/**
	 * Get API secret from environment or constants.
	 *
	 * @return string API secret or empty string if not found.
	 */
	protected function get_api_secret(): string {
		if ( getenv( 'SIFT_FOR_WOOCOMMERCE_API_SECRET' ) ) {
			return getenv( 'SIFT_FOR_WOOCOMMERCE_API_SECRET' );
		} elseif ( defined( 'SIFT_FOR_WOOCOMMERCE_API_SECRET' ) ) {
			return SIFT_FOR_WOOCOMMERCE_API_SECRET;
		}
		return '';
	}

	/**
	 * Create an authenticated REST API request.
	 *
	 * @param string   $method   HTTP method (GET, POST, etc.).
	 * @param string   $endpoint API endpoint path.
	 * @param int|null $time     Optional timestamp for authentication. Defaults to current time.
	 *
	 * @return \WP_REST_Request Authenticated REST request.
	 */
	private function create_authenticated_request( string $method, string $endpoint, int $time = null ): \WP_REST_Request {
		if ( $time === null ) {
			$time = time();
		}

		$hash = hash_hmac( 'sha256', "{$endpoint}|{$time}", $this->api_secret );
		$auth = "{$endpoint}|{$time}|{$hash}";

		$request = new \WP_REST_Request( $method, '/' . Sift_For_WooCommerce\REST_API::ROUTE_NAMESPACE . $endpoint );
		$request->set_header( 'Authorization', $auth );

		return $request;
	}

	/**
	 * Test that valid authorization headers are accepted.
	 *
	 * @return void
	 */
	public function test_verify_authorization() {
		$endpoint = '/fraud/users/1/decision';
		$request = $this->create_authenticated_request( 'GET', $endpoint );

		$response = $this->api->verify_authorization( $request );
		self::assertTrue( $response, 'Authorization failed' );
	}

	/**
	 * Test that expired timestamp authorization headers are rejected.
	 *
	 * @return void
	 */
	public function test_verify_authorization_expired_timestamp() {
		$endpoint = '/fraud/users/1/decision';
		$expired_time = time() - 120; // 2 minutes ago (beyond MINUTE_IN_SECONDS limit)
		$request = $this->create_authenticated_request( 'GET', $endpoint, $expired_time );

		$response = $this->api->verify_authorization( $request );

		self::assertInstanceOf( 'WP_Error', $response, 'Expected WP_Error for expired timestamp' );
		self::assertEquals( 'bad_timestamp', $response->get_error_code(), 'Expected bad_timestamp error code' );
	}

	/**
	 * Test that POST requests without required decision_id parameter are rejected.
	 *
	 * @return void
	 */
	public function test_handle_apply_manual_fraud_decision_missing_decision_id() {
		$endpoint = '/fraud/users/1/decision';
		$request = $this->create_authenticated_request( 'POST', $endpoint );
		$request->set_param( 'user_id', 1 );
		// Intentionally not setting decision_id parameter

		$response = $this->api->handle_apply_manual_fraud_decision( $request );

		self::assertInstanceOf( 'WP_Error', $response, 'Expected WP_Error for missing decision_id' );
		self::assertEquals( 'invalid_parameters', $response->get_error_code(), 'Expected invalid_parameters error code' );
	}

	/**
	 * Test that GET endpoint successfully retrieves stored fraud decisions.
	 *
	 * @return void
	 */
	public function test_handle_get_fraud_decision() {
		$decision_id = 'test_decision_' . time();

		// Set up user meta directly (using the tested function from FraudDecisionTest)
		update_user_meta( $this->test_user_id, 'sfw_fraud_risk_decision', $decision_id );

		$endpoint = "/fraud/users/{$this->test_user_id}/decision";
		$request = $this->create_authenticated_request( 'GET', $endpoint );
		$request->set_param( 'user_id', $this->test_user_id );

		$response = $this->api->handle_get_fraud_decision( $request );

		self::assertEquals( $decision_id, $response, 'Should return the stored fraud decision' );
	}

	/**
	 * Test that GET requests without user_id parameter are rejected.
	 *
	 * @return void
	 */
	public function test_handle_get_fraud_decision_missing_user_id() {
		$endpoint = '/fraud/users//decision'; // Empty user_id in URL
		$request = $this->create_authenticated_request( 'GET', $endpoint );
		// user_id parameter will be empty from URL parsing

		$response = $this->api->handle_get_fraud_decision( $request );

		self::assertInstanceOf( 'WP_Error', $response, 'Expected WP_Error for missing user_id' );
		self::assertEquals( 'invalid_parameters', $response->get_error_code(), 'Expected invalid_parameters error code' );
	}
}
