<?php
/**
 * Class ExtractDecisionFromResponseTest
 *
 * @package Sift_For_WooCommerce
 */
declare( strict_types=1 );

// phpcs:disable Universal.Arrays.DisallowShortArraySyntax.Found, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

use Sift_For_WooCommerce\Sift_Events\Events;

/**
 * Tests for Events::extract_decision_from_response().
 */
class ExtractDecisionFromResponseTest extends WP_UnitTestCase {

	/**
	 * Clean up filters after each test to prevent leaks.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_all_filters( 'sift_for_woocommerce_decision_ids_by_severity' );
		parent::tear_down();
	}

	/**
	 * Helper to build a SiftResponse with a given body.
	 *
	 * @param array $body The response body.
	 *
	 * @return \SiftResponse
	 */
	private function make_response( array $body ): \SiftResponse {
		$response       = new \SiftResponse( wp_json_encode( $body ), 200, '' );
		$response->body = $body;
		return $response;
	}

	/**
	 * Test returns null when score_response is missing.
	 *
	 * @return void
	 */
	public function test_returns_null_when_no_score_response() {
		$response = $this->make_response( array( 'status' => 0 ) );
		$this->assertNull( Events::extract_decision_from_response( $response ) );
	}

	/**
	 * Test returns null when workflow_statuses is empty.
	 *
	 * @return void
	 */
	public function test_returns_null_when_workflow_statuses_empty() {
		$response = $this->make_response(
			array(
				'score_response' => array(
					'workflow_statuses' => array(),
				),
			)
		);
		$this->assertNull( Events::extract_decision_from_response( $response ) );
	}

	/**
	 * Test returns null when workflow history has no decision entries.
	 *
	 * @return void
	 */
	public function test_returns_null_when_no_decision_in_history() {
		$response = $this->make_response(
			array(
				'score_response' => array(
					'workflow_statuses' => array(
						array(
							'history' => array(
								array(
									'app'    => 'some_other_workflow',
									'config' => array( 'decision_id' => 'irrelevant' ),
								),
							),
						),
					),
				),
			)
		);
		$this->assertNull( Events::extract_decision_from_response( $response ) );
	}

	/**
	 * Test returns the decision ID when a single decision is present.
	 *
	 * @return void
	 */
	public function test_returns_single_decision() {
		$response = $this->make_response(
			array(
				'score_response' => array(
					'workflow_statuses' => array(
						array(
							'history' => array(
								array(
									'app'    => 'decision',
									'config' => array( 'decision_id' => 'block_user_payment_abuse' ),
								),
							),
						),
					),
				),
			)
		);
		$this->assertSame( 'block_user_payment_abuse', Events::extract_decision_from_response( $response ) );
	}

	/**
	 * Test returns first decision when multiple decisions exist but no severity filter is hooked.
	 *
	 * @return void
	 */
	public function test_returns_first_decision_without_severity_filter() {
		$response = $this->make_response(
			array(
				'score_response' => array(
					'workflow_statuses' => array(
						array(
							'history' => array(
								array(
									'app'    => 'decision',
									'config' => array( 'decision_id' => 'watch_user' ),
								),
							),
						),
						array(
							'history' => array(
								array(
									'app'    => 'decision',
									'config' => array( 'decision_id' => 'block_user' ),
								),
							),
						),
					),
				),
			)
		);
		$this->assertSame( 'watch_user', Events::extract_decision_from_response( $response ) );
	}

	/**
	 * Test returns most severe decision when severity filter is hooked.
	 *
	 * @return void
	 */
	public function test_returns_most_severe_decision_with_severity_filter() {
		$severity_callback = function () {
			return array( 'block_user', 'watch_user' );
		};
		add_filter( 'sift_for_woocommerce_decision_ids_by_severity', $severity_callback );

		$response = $this->make_response(
			array(
				'score_response' => array(
					'workflow_statuses' => array(
						array(
							'history' => array(
								array(
									'app'    => 'decision',
									'config' => array( 'decision_id' => 'watch_user' ),
								),
							),
						),
						array(
							'history' => array(
								array(
									'app'    => 'decision',
									'config' => array( 'decision_id' => 'block_user' ),
								),
							),
						),
					),
				),
			)
		);
		$this->assertSame( 'block_user', Events::extract_decision_from_response( $response ) );
	}
}
