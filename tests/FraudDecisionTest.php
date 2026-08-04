<?php declare( strict_types=1 );

// phpcs:disable

use Sift_For_WooCommerce\Abuse_Decisions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Fraud_Decision_Test extends \WP_UnitTestCase {

	//Test adding and then fetching the latest user fraud decision
	public function test_last_fraud_decision() {
		//Create a user
		$user_id = wp_create_user( 'testuser', 'password' );
		wp_set_current_user( $user_id );
		$decision_id = 'test_fraud_decision' . time();

		//Assign a decision to the user
		Abuse_Decisions\save_last_fraud_decision( $user_id, $decision_id );

		//Add a second decision to the user
		$newest_decision_id = 'newest_'.$decision_id;
		Abuse_Decisions\save_last_fraud_decision( $user_id, $newest_decision_id );

		//Get the latest decision for the user
		$last_decision_id = Abuse_Decisions\get_last_fraud_decision( $user_id );
		self::assertEquals( $last_decision_id, $newest_decision_id, 'Decision retrieval failed' );
		wp_delete_user( $user_id );
	}

	// Capture every `sift_for_woocommerce_fraud_decision_applied` payload into the given array.
	private function capture_applied_decisions( array &$events ): void {
		add_action(
			'sift_for_woocommerce_fraud_decision_applied',
			function ( ...$args ) use ( &$events ) {
				$events[] = $args;
			},
			10,
			5
		);
	}

	// First decision fires the action once with an empty previous_decision_id.
	public function test_first_decision_fires_once_with_empty_previous() {
		$user_id = wp_create_user( 'firstdecisionuser', 'password' );
		$events  = array();
		$this->capture_applied_decisions( $events );

		apply_filters( 'sift_for_woocommerce_process_manual_fraud_decision', (string) $user_id, 'looks_good_payment_abuse', 'first decision', 'analyst_jane' );

		self::assertCount( 1, $events, 'First decision should fire the action exactly once' );
		// Payload order: user_id, decision_id, previous_decision_id, analyst, source.
		self::assertSame( $user_id, $events[0][0], 'user_id in payload' );
		self::assertSame( 'looks_good_payment_abuse', $events[0][1], 'decision_id in payload' );
		self::assertSame( '', $events[0][2], 'previous_decision_id is empty on the first decision' );
		self::assertSame( 'analyst_jane', $events[0][3], 'analyst in payload' );
		self::assertSame( Abuse_Decisions\FRAUD_DECISION_SOURCE_MANUAL, $events[0][4], 'manual source in payload' );

		wp_delete_user( $user_id );
	}

	// Re-applying the same decision is suppressed - the action does not fire again.
	public function test_repeat_decision_is_suppressed() {
		$user_id = wp_create_user( 'repeatdecisionuser', 'password' );
		$events  = array();
		$this->capture_applied_decisions( $events );

		apply_filters( 'sift_for_woocommerce_process_manual_fraud_decision', (string) $user_id, 'looks_good_payment_abuse', '', 'analyst_jane' );
		apply_filters( 'sift_for_woocommerce_process_manual_fraud_decision', (string) $user_id, 'looks_good_payment_abuse', '', 'analyst_jane' );

		self::assertCount( 1, $events, 'Re-applying an unchanged decision should not fire the action again' );

		wp_delete_user( $user_id );
	}

	// A change after a suppressed repeat carries the correct previous_decision_id.
	public function test_change_after_repeat_carries_previous_decision() {
		$user_id = wp_create_user( 'changedecisionuser', 'password' );
		$events  = array();
		$this->capture_applied_decisions( $events );

		apply_filters( 'sift_for_woocommerce_process_manual_fraud_decision', (string) $user_id, 'looks_good_payment_abuse', '', 'analyst_jane' );
		apply_filters( 'sift_for_woocommerce_process_manual_fraud_decision', (string) $user_id, 'looks_good_payment_abuse', '', 'analyst_jane' );
		apply_filters( 'sift_for_woocommerce_process_manual_fraud_decision', (string) $user_id, 'trust_list_payment_abuse', '', 'analyst_jane' );

		self::assertCount( 2, $events, 'Only the two distinct decisions should fire the action' );
		self::assertSame( 'trust_list_payment_abuse', $events[1][1], 'second fire carries the new decision_id' );
		self::assertSame( 'looks_good_payment_abuse', $events[1][2], 'second fire carries the prior decision as previous_decision_id' );

		wp_delete_user( $user_id );
	}

	// The normalized decision_id (not the raw input) reaches the payload on the manual path.
	public function test_manual_decision_id_is_normalized_in_payload() {
		$user_id = wp_create_user( 'normalizedecisionuser', 'password' );
		$events  = array();
		$this->capture_applied_decisions( $events );

		apply_filters( 'sift_for_woocommerce_process_manual_fraud_decision', (string) $user_id, 'likely_fraud_keep_purchases_payment_abuse', '', 'analyst_jane' );

		self::assertCount( 1, $events, 'The decision should fire the action once' );
		self::assertSame( 'likely_fraud_block_keep_purch_payment_abuse', $events[0][1], 'payload carries the normalized decision_id' );
		self::assertSame( '', $events[0][2], 'previous_decision_id is empty on the first decision' );

		wp_delete_user( $user_id );
	}

	// The automated path announces source=automated with an empty analyst.
	public function test_automated_decision_uses_automated_source_and_empty_analyst() {
		$user_id  = wp_create_user( 'automateddecisionuser', 'password' );
		$previous = get_option( 'wc_sift_for_woocommerce_automated_actions_enabled' );
		update_option( 'wc_sift_for_woocommerce_automated_actions_enabled', 'yes' );

		$events = array();
		$this->capture_applied_decisions( $events );

		apply_filters( 'sift_decision_received', null, 'block_wo_review_payment_abuse', $user_id );

		// Restore the option regardless of the assertions below.
		if ( false === $previous ) {
			delete_option( 'wc_sift_for_woocommerce_automated_actions_enabled' );
		} else {
			update_option( 'wc_sift_for_woocommerce_automated_actions_enabled', $previous );
		}

		self::assertCount( 1, $events, 'The automated decision should fire the action once' );
		self::assertSame( 'block_wo_review_payment_abuse', $events[0][1], 'decision_id in payload' );
		self::assertSame( '', $events[0][2], 'previous_decision_id is empty on the first decision' );
		self::assertSame( '', $events[0][3], 'automated path carries no analyst' );
		self::assertSame( Abuse_Decisions\FRAUD_DECISION_SOURCE_AUTOMATED, $events[0][4], 'automated source in payload' );

		wp_delete_user( $user_id );
	}

	// A throwing action consumer must not skip the decision save - record_fraud_decision fires the
	// announcement before the caller saves, so a leaked exception would leave the stored decision stale.
	public function test_throwing_consumer_does_not_skip_the_save() {
		$user_id = wp_create_user( 'throwingconsumeruser', 'password' );
		add_action(
			'sift_for_woocommerce_fraud_decision_applied',
			function () {
				throw new \RuntimeException( 'boom' );
			},
			10,
			5
		);

		apply_filters( 'sift_for_woocommerce_process_manual_fraud_decision', (string) $user_id, 'looks_good_payment_abuse', '', 'analyst_jane' );

		self::assertSame( 'looks_good_payment_abuse', Abuse_Decisions\get_last_fraud_decision( $user_id ), 'Decision must still be saved despite a throwing consumer' );

		wp_delete_user( $user_id );
	}
}
