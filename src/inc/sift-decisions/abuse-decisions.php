<?php declare( strict_types=1 );

namespace Sift_For_WooCommerce\Abuse_Decisions;

require_once __DIR__ . '/sift-decision-rest-api-webhooks.php';

const USER_FRAUD_DECISION_META_KEY = 'sfw_fraud_risk_decision';

/**
 * Process the Sift decision received.
 *
 * @param mixed   $return_value The return value.
 * @param string  $decision_id  The ID of the Sift decision.
 * @param integer $user_id      The user ID the decision corresponds to.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
 *
 * @return mixed
 */
function process_sift_decision_received( $return_value, $decision_id, $user_id ) {
	// Apply a filter to get the correct user ID.
	$woocommerce_user_id = apply_filters( 'sift_for_woocommerce_pre_sift_decision', $user_id );

	if ( is_wp_error( $woocommerce_user_id ) ) {
		// Log the decision.
		wc_get_logger()->log(
			'debug',
			'Sift Decision Failed: Missing User',
			array(
				'source'       => 'sift-for-woocommerce',
				'decision_id'  => $decision_id,
				'sift_user_id' => $user_id,
				'error'        => $woocommerce_user_id->get_error_message(),
			)
		);

		return $woocommerce_user_id;
	}

	$automated_actions_enabled = get_option( 'wc_sift_for_woocommerce_automated_actions_enabled' );

	switch ( $decision_id ) {
		case 'looks_good_payment_abuse':
			if ( $automated_actions_enabled ) {
				do_action( 'sift_for_woocommerce_looks_good_payment_abuse', $woocommerce_user_id );
			}
			break;

		case 'likely_fraud_keep_purchases_payment_abuse':
			if ( $automated_actions_enabled ) {
				do_action( 'sift_for_woocommerce_likely_fraud_keep_purchases_payment_abuse', $woocommerce_user_id );
			}
			break;

		case 'block_wo_review_payment_abuse':
			if ( $automated_actions_enabled ) {
				do_action( 'sift_for_woocommerce_block_wo_review_payment_abuse', $woocommerce_user_id );
			}
			break;

		case 'trust_list_payment_abuse':
		case 'not_likely_fraud_payment_abuse':
		case 'likely_fraud_refundno_renew_payment_abuse':
		case 'fraud_payment_abuse':
		case 'fraud_no_review_ticket_payment_abuse':
		case 'looks_ok_payment_abuse':
		case 'looks_suspicious_payment_abuse':
		case 'order_looks_ok_payment_abuse':
		case 'order_looks_suspicious_payment_abuse':
		default:
			wc_get_logger()->log(
				'info',
				"Decision ID '{$decision_id}' not handled.",
				array(
					'source'              => 'sift-for-woocommerce',
					'decision_id'         => $decision_id,
					'sift_user_id'        => $user_id,
					'woocommerce_user_id' => $woocommerce_user_id,
				)
			);
			return $return_value;
	}

	// Log the decision (even if automated actions are disabled).
	wc_get_logger()->log(
		'debug',
		'Sift Decision Filter Applied',
		array(
			'source'              => 'sift-for-woocommerce',
			'decision_id'         => $decision_id,
			'sift_user_id'        => $user_id,
			'woocommerce_user_id' => $woocommerce_user_id,
		)
	);

	if ( $automated_actions_enabled ) {
		// Store the last fraud decision applied to the user.
		apply_filters(
			'sift_for_woocommerce_save_last_fraud_decision',
			$woocommerce_user_id,
			$decision_id
		);
	}

	return $return_value;
}
add_filter( 'sift_decision_received', __NAMESPACE__ . '\process_sift_decision_received', 10, 3 );

/**
 * Processes a manual fraud decision for a specific user and triggers the corresponding actions.
 *
 * @param string $woocommerce_user_id ID of the WooCommerce user to whom the decision applies.
 * @param string $decision_id         The fraud decision ID to be processed.
 * @param string $description         Freeform text description of the fraud decision.
 * @param string $analyst             Username of the fraud analyst applying the decision.
 *
 * @return \WP_Error|boolean Returns a WP_Error if the user ID is invalid or if the decision ID is not recognized, otherwise true.
 */
function process_manual_fraud_decision( string $woocommerce_user_id, string $decision_id, string $description, string $analyst ): \WP_Error|bool {
	if ( empty( $woocommerce_user_id ) ) {
		wc_get_logger()->log(
			'info',
			"Bad User ID '{$woocommerce_user_id}'",
			array(
				'source'              => 'sift-for-woocommerce',
				'decision_id'         => $decision_id,
				'woocommerce_user_id' => $woocommerce_user_id,
			)
		);

		return new \WP_Error( 'user_not_found', array( 'status' => 404 ) );
	}

	switch ( $decision_id ) {
		case 'trust_list_payment_abuse':
			do_action( 'sift_for_woocommerce_trust_list_payment_abuse', $woocommerce_user_id );
			break;

		case 'looks_good_payment_abuse':
			do_action( 'sift_for_woocommerce_looks_good_payment_abuse', $woocommerce_user_id );
			break;

		case 'not_likely_fraud_payment_abuse':
			do_action( 'sift_for_woocommerce_not_likely_fraud_payment_abuse', $woocommerce_user_id );
			break;

		case 'likely_fraud_refundno_renew_payment_abuse':
			do_action( 'sift_for_woocommerce_likely_fraud_refundno_renew_payment_abuse', $woocommerce_user_id );
			break;

		case 'likely_fraud_keep_purchases_payment_abuse':
			do_action( 'sift_for_woocommerce_likely_fraud_keep_purchases_payment_abuse', $woocommerce_user_id );
			break;

		case 'fraud_payment_abuse':
			do_action( 'sift_for_woocommerce_fraud_payment_abuse', $woocommerce_user_id );
			break;

		default:
			wc_get_logger()->log(
				'info',
				"Decision ID '{$decision_id}' not handled.",
				array(
					'source'              => 'sift-for-woocommerce',
					'decision_id'         => $decision_id,
					'woocommerce_user_id' => $woocommerce_user_id,
				)
			);

			return new \WP_Error( 'fraud_decision_not_found', 'Invalid Fraud Decision', array( 'status' => 404 ) );
	}

	// Send the decision to sift.
	apply_filters(
		'sift_for_woocommerce_send_decision_to_sift',
		$woocommerce_user_id,
		$decision_id,
		$description,
		$analyst
	);

	// Log the decision.
	wc_get_logger()->log(
		'debug',
		'Manual Sift Decision Applied',
		array(
			'source'              => 'sift-for-woocommerce',
			'decision_id'         => $decision_id,
			'woocommerce_user_id' => $woocommerce_user_id,
			'by_user_id'          => $analyst,
		)
	);

	/**
	 * Store the last fraud decision for a user.
	 */
	apply_filters(
		'sift_for_woocommerce_save_last_fraud_decision',
		$woocommerce_user_id,
		$decision_id
	);

	return true;
}
add_filter( 'sift_for_woocommerce_process_manual_fraud_decision', __NAMESPACE__ . '\process_manual_fraud_decision', 10, 4 );

/**
 * Retrieves the last fraud decision associated with a given WooCommerce user ID.
 *
 * @param integer $woocommerce_user_id The ID of the WooCommerce user for whom the fraud decision should be retrieved.
 *
 * @return string The ID of the last recorded fraud decision for the specified user.
 */
function get_last_fraud_decision( int $woocommerce_user_id ): string {
	$decision_id = get_user_meta( $woocommerce_user_id, USER_FRAUD_DECISION_META_KEY, true );
	if ( empty( $decision_id ) ) {
		$decision_id = '';
	}
	return $decision_id;
}
add_filter( 'sift_for_woocommerce_get_last_fraud_decision', __NAMESPACE__ . '\get_last_fraud_decision', 10, 1 );


/**
 * Save the last fraud decision made for a user.
 *
 * @param integer $woocommerce_user_id ID of the WooCommerce user the decision applies to.
 * @param string  $decision_id         The decision ID to be saved.
 *
 * @return void
 */
function save_last_fraud_decision( int $woocommerce_user_id, string $decision_id ): void {
	update_user_meta( $woocommerce_user_id, USER_FRAUD_DECISION_META_KEY, $decision_id );
}
add_filter( 'sift_for_woocommerce_save_last_fraud_decision', __NAMESPACE__ . '\save_last_fraud_decision', 10, 2 );

/**
 * Alert Sift of a decision made by a human.
 *
 * @param string $woocommerce_user_id User to whom the decision applies.
 * @param string $decision_id         Decision ID as configured in the Sift dashboard.
 * @param string $description         Freeform text description of the decision.
 * @param string $analyst             Username of the fraud analyst.
 *
 * @return \SiftResponse
 *
 * @throws \Exception If either the decision ID is not valid.
 */
function send_decision_to_sift(
	string $woocommerce_user_id,
	string $decision_id,
	string $description,
	string $analyst
): \SiftResponse {
	$client = \Sift_For_WooCommerce\Sift_For_WooCommerce::get_api_client();
	if ( empty( $client ) ) {
		Sift_For_WooCommerce::log(
			'Failed to get the Sift API client.',
			'error',
			array(
				'source' => 'sift-for-woocommerce',
			)
		);
		return null;
	}

	$options = array( 'analyst' => $analyst );

	// The description parameter is optional; don't send it if it's empty.
	if ( ! empty( $description ) ) {
		$options['description'] = $description;
	}

	$response = $client->applyDecisionToUser(
		$woocommerce_user_id,
		$decision_id,
		'MANUAL_REVIEW',
		$options
	);

	wc_get_logger()->log(
		'info',
		"Decision ID '{$decision_id}' sent to sift.",
		array(
			'source'              => 'sift-for-woocommerce',
			'decision_id'         => $decision_id,
			'woocommerce_user_id' => $woocommerce_user_id,
			'options'             => $options,
			'response'            => $response?->rawResponse, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		)
	);

	return $response;
}
add_filter( 'sift_for_woocommerce_send_decision_to_sift', __NAMESPACE__ . '\send_decision_to_sift', 10, 4 );
