<?php
/**
 * Class Fraud_Protection_Flags_Test
 *
 * Tests for fraud protection flags (bypass and fraudster flags).
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
 * Test fraud protection flags (bypass and fraudster flags).
 */
class Fraud_Protection_Flags_Test extends \WP_UnitTestCase {

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $test_user_id;

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->test_user_id = wp_create_user( uniqid( 'testuser_', true ), 'password' );
	}

	/**
	 * Tear down test fixtures.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clean up filters added during tests
		remove_all_filters( 'sift_for_woocommerce_pre_sift_decision' );
		remove_all_actions( 'sift_for_woocommerce_block_wo_review_payment_abuse' );
		remove_all_filters( 'woocommerce_logger_log_message' );

		// Reset option so tests that set it don't bleed into tests that don't
		delete_option( 'wc_sift_for_woocommerce_automated_actions_enabled' );

		if ( $this->test_user_id ) {
			wp_delete_user( $this->test_user_id );
		}
		parent::tearDown();
	}

	/**
	 * Test fraud review bypass flag can be set, checked, and removed.
	 *
	 * @return void
	 */
	public function test_fraud_review_bypass_flag_lifecycle() {
		// Initially no bypass flag
		self::assertFalse( Abuse_Decisions\has_fraud_review_bypass( $this->test_user_id ) );

		// Apply bypass flag
		Abuse_Decisions\apply_fraud_review_bypass( $this->test_user_id, 'test_analyst' );
		self::assertTrue( Abuse_Decisions\has_fraud_review_bypass( $this->test_user_id ) );

		// Remove bypass flag
		Abuse_Decisions\remove_fraud_review_bypass( $this->test_user_id, 'test_analyst' );
		self::assertFalse( Abuse_Decisions\has_fraud_review_bypass( $this->test_user_id ) );
	}

	/**
	 * Test fraudster flag can be set, checked, and removed.
	 *
	 * @return void
	 */
	public function test_fraudster_flag_lifecycle() {
		// Initially no fraudster flag
		self::assertFalse( Abuse_Decisions\has_fraudster_flag( $this->test_user_id ) );

		// Apply fraudster flag
		Abuse_Decisions\apply_fraudster_flag( $this->test_user_id, 'test_analyst' );
		self::assertTrue( Abuse_Decisions\has_fraudster_flag( $this->test_user_id ) );

		// Remove fraudster flag
		Abuse_Decisions\remove_fraudster_flag( $this->test_user_id, 'test_analyst' );
		self::assertFalse( Abuse_Decisions\has_fraudster_flag( $this->test_user_id ) );
	}

	/**
	 * Test trust_list decision sets bypass flag and removes fraudster flag.
	 *
	 * @return void
	 */
	public function test_trust_list_decision_sets_bypass_removes_fraudster() {
		// Set up: user has fraudster flag
		Abuse_Decisions\apply_fraudster_flag( $this->test_user_id, 'setup' );

		// Apply trust_list decision via the function
		Abuse_Decisions\process_manual_fraud_decision(
			(string) $this->test_user_id,
			'trust_list_payment_abuse',
			'Test description',
			'test_analyst'
		);

		// Should have bypass flag, should NOT have fraudster flag
		self::assertTrue( Abuse_Decisions\has_fraud_review_bypass( $this->test_user_id ) );
		self::assertFalse( Abuse_Decisions\has_fraudster_flag( $this->test_user_id ) );
	}

	/**
	 * Test fraud decision sets fraudster flag and removes bypass flag.
	 *
	 * @return void
	 */
	public function test_fraud_decision_sets_fraudster_removes_bypass() {
		// Set up: user has bypass flag
		Abuse_Decisions\apply_fraud_review_bypass( $this->test_user_id, 'setup' );

		// Apply fraud decision via the function
		Abuse_Decisions\process_manual_fraud_decision(
			(string) $this->test_user_id,
			'fraud_payment_abuse',
			'Test description',
			'test_analyst'
		);

		// Should have fraudster flag, should NOT have bypass flag
		self::assertTrue( Abuse_Decisions\has_fraudster_flag( $this->test_user_id ) );
		self::assertFalse( Abuse_Decisions\has_fraud_review_bypass( $this->test_user_id ) );
	}

	/**
	 * Test looks_good decision clears both flags.
	 *
	 * @return void
	 */
	public function test_looks_good_decision_clears_both_flags() {
		// Set up: user has both flags
		Abuse_Decisions\apply_fraud_review_bypass( $this->test_user_id, 'setup' );
		Abuse_Decisions\apply_fraudster_flag( $this->test_user_id, 'setup' );

		// Apply looks_good decision
		Abuse_Decisions\process_manual_fraud_decision(
			(string) $this->test_user_id,
			'looks_good_payment_abuse',
			'Test description',
			'test_analyst'
		);

		// Both flags should be cleared
		self::assertFalse( Abuse_Decisions\has_fraud_review_bypass( $this->test_user_id ) );
		self::assertFalse( Abuse_Decisions\has_fraudster_flag( $this->test_user_id ) );
	}

	/**
	 * Test automated block decision is skipped when bypass flag is set.
	 *
	 * @return void
	 */
	public function test_automated_block_skipped_when_bypass_flag_set() {
		// Set bypass flag
		Abuse_Decisions\apply_fraud_review_bypass( $this->test_user_id, 'setup' );

		// Track if action was fired
		$action_fired = false;
		add_action( 'sift_for_woocommerce_block_wo_review_payment_abuse', function() use ( &$action_fired ) {
			$action_fired = true;
		} );

		// Enable automated actions
		update_option( 'wc_sift_for_woocommerce_automated_actions_enabled', 'yes' );

		// Mock user ID translation to return the test user ID directly
		$test_user_id = $this->test_user_id;
		add_filter( 'sift_for_woocommerce_pre_sift_decision', function( $user_id ) use ( $test_user_id ) {
			return $test_user_id;
		} );

		// Call the function directly to test the flag check
		Abuse_Decisions\process_sift_decision_received(
			null,
			'block_wo_review_payment_abuse',
			(string) $this->test_user_id
		);

		// Action should NOT have fired because bypass flag is set
		self::assertFalse( $action_fired, 'Automated block decision should be skipped when bypass flag is set' );
	}

	/**
	 * Test automated block decision fires when no bypass flag is set.
	 *
	 * @return void
	 */
	public function test_automated_block_fires_when_no_bypass_flag() {
		// Fresh user starts with no flags - no setup needed.

		// Track if action was fired
		$action_fired = false;
		add_action( 'sift_for_woocommerce_block_wo_review_payment_abuse', function() use ( &$action_fired ) {
			$action_fired = true;
		} );

		// Enable automated actions
		update_option( 'wc_sift_for_woocommerce_automated_actions_enabled', 'yes' );

		// Mock user ID translation to return the test user ID directly
		$test_user_id = $this->test_user_id;
		add_filter( 'sift_for_woocommerce_pre_sift_decision', function( $user_id ) use ( $test_user_id ) {
			return $test_user_id;
		} );

		// Call the function directly
		Abuse_Decisions\process_sift_decision_received(
			null,
			'block_wo_review_payment_abuse',
			(string) $this->test_user_id
		);

		// Action SHOULD have fired because no bypass flag is set
		self::assertTrue( $action_fired, 'Automated block decision should fire when no bypass flag is set' );
	}

	/**
	 * Test the fraudster flag skips ALL automated decisions, including blocks.
	 *
	 * A confirmed fraudster has a manual decision on file, so automation must not churn it -
	 * no re-block, no lesser block, no unblock. Same behavior as the bypass flag.
	 *
	 * @return void
	 */
	public function test_fraudster_flag_skips_automated_block() {
		// Set fraudster flag only (no bypass)
		Abuse_Decisions\apply_fraudster_flag( $this->test_user_id, 'setup' );

		// Track if the block action was fired
		$action_fired = false;
		add_action( 'sift_for_woocommerce_block_wo_review_payment_abuse', function() use ( &$action_fired ) {
			$action_fired = true;
		} );

		// Enable automated actions
		update_option( 'wc_sift_for_woocommerce_automated_actions_enabled', 'yes' );

		// Mock user ID translation to return the test user ID directly
		$test_user_id = $this->test_user_id;
		add_filter( 'sift_for_woocommerce_pre_sift_decision', function( $user_id ) use ( $test_user_id ) {
			return $test_user_id;
		} );

		// Call the function directly
		Abuse_Decisions\process_sift_decision_received(
			null,
			'block_wo_review_payment_abuse',
			(string) $this->test_user_id
		);

		// Block should NOT fire - the fraudster flag skips every automated decision.
		self::assertFalse( $action_fired, 'Fraudster flag should skip an automated block_wo_review decision' );
	}

	/**
	 * Test an automated looks_good decision is skipped when the fraudster flag is set.
	 *
	 * looks_good fires no automated action today, so the only proof the guard ran is its
	 * log line. We capture the WC log and check for the skip message - if the guard were
	 * removed, looks_good would log "not handled" instead and this test would fail.
	 *
	 * @return void
	 */
	public function test_automated_looks_good_skipped_when_fraudster_flag_set() {
		// Set fraudster flag
		Abuse_Decisions\apply_fraudster_flag( $this->test_user_id, 'setup' );

		// Enable automated actions
		update_option( 'wc_sift_for_woocommerce_automated_actions_enabled', 'yes' );

		// Mock user ID translation to return the test user ID directly
		$test_user_id = $this->test_user_id;
		add_filter( 'sift_for_woocommerce_pre_sift_decision', function( $user_id ) use ( $test_user_id ) {
			return $test_user_id;
		} );

		// Capture log messages so we can tell the guard ran, not the default "not handled" path.
		// Priority 1 so we read the message before any other filter on this hook can null it.
		$messages = array();
		add_filter( 'woocommerce_logger_log_message', function( $message ) use ( &$messages ) {
			if ( is_string( $message ) ) {
				$messages[] = $message;
			}
			return $message;
		}, 1 );

		Abuse_Decisions\process_sift_decision_received(
			null,
			'looks_good_payment_abuse',
			(string) $this->test_user_id
		);

		$hit_guard = false;
		foreach ( $messages as $message ) {
			if ( false !== strpos( $message, 'fraudster flag active' ) ) {
				$hit_guard = true;
			}
		}
		self::assertTrue( $hit_guard, 'Automated looks_good should be skipped when the fraudster flag is set' );
	}

	/**
	 * Test not_likely_fraud decision clears both flags.
	 *
	 * @return void
	 */
	public function test_not_likely_fraud_decision_clears_both_flags() {
		// Set up: user has both flags
		Abuse_Decisions\apply_fraud_review_bypass( $this->test_user_id, 'setup' );
		Abuse_Decisions\apply_fraudster_flag( $this->test_user_id, 'setup' );

		// Apply not_likely_fraud decision
		Abuse_Decisions\process_manual_fraud_decision(
			(string) $this->test_user_id,
			'not_likely_fraud_payment_abuse',
			'Test description',
			'test_analyst'
		);

		// Both flags should be cleared
		self::assertFalse( Abuse_Decisions\has_fraud_review_bypass( $this->test_user_id ) );
		self::assertFalse( Abuse_Decisions\has_fraudster_flag( $this->test_user_id ) );
	}

	/**
	 * Test likely_fraud decisions clear both flags.
	 *
	 * @return void
	 */
	public function test_likely_fraud_decision_clears_both_flags() {
		// Set up: user has both flags
		Abuse_Decisions\apply_fraud_review_bypass( $this->test_user_id, 'setup' );
		Abuse_Decisions\apply_fraudster_flag( $this->test_user_id, 'setup' );

		// Apply likely_fraud decision
		Abuse_Decisions\process_manual_fraud_decision(
			(string) $this->test_user_id,
			'likely_fraud_no_purchases_payment_abuse_1',
			'Test description',
			'test_analyst'
		);

		// Both flags should be cleared
		self::assertFalse( Abuse_Decisions\has_fraud_review_bypass( $this->test_user_id ) );
		self::assertFalse( Abuse_Decisions\has_fraudster_flag( $this->test_user_id ) );
	}

	/**
	 * Test likely_fraud_keep_purchases decision clears both flags.
	 *
	 * @return void
	 */
	public function test_likely_fraud_keep_purchases_decision_clears_both_flags() {
		// Set up: user has both flags
		Abuse_Decisions\apply_fraud_review_bypass( $this->test_user_id, 'setup' );
		Abuse_Decisions\apply_fraudster_flag( $this->test_user_id, 'setup' );

		// Apply likely_fraud_keep_purchases decision
		Abuse_Decisions\process_manual_fraud_decision(
			(string) $this->test_user_id,
			'likely_fraud_keep_purchases_payment_abuse',
			'Test description',
			'test_analyst'
		);

		// Both flags should be cleared
		self::assertFalse( Abuse_Decisions\has_fraud_review_bypass( $this->test_user_id ) );
		self::assertFalse( Abuse_Decisions\has_fraudster_flag( $this->test_user_id ) );
	}

	/**
	 * Test send_decision_to_sift handles a missing API client without fataling.
	 *
	 * The manual decision flow fires this filter, and with no Sift client configured it
	 * must log and return null - not fatal. Guards the namespaced log call and the
	 * nullable return type.
	 *
	 * @return void
	 */
	public function test_send_decision_to_sift_returns_null_without_client() {
		$result = Abuse_Decisions\send_decision_to_sift(
			(string) $this->test_user_id,
			'trust_list_payment_abuse',
			'Test description',
			'test_analyst'
		);

		self::assertNull( $result, 'send_decision_to_sift should return null when no Sift client is configured' );
	}
}
