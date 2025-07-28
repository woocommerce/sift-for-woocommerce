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

		//
		self::assertEquals( $last_decision_id, $newest_decision_id, 'Decision retrieval failed' );
		wp_delete_user( $user_id );
	}
}
