<?php declare( strict_types=1 );

namespace Sift_For_WooCommerce\Rest_Api_Webhooks;

/**
 * Register the routes for our webhook catchers with the REST API.
 *
 * @return  void
 */
function register_routes() {
	register_rest_route(
		'sift-for-woocommerce/v1',
		'/decision',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\decision_webhook',
			'permission_callback' => __NAMESPACE__ . '\decision_webhook_auth',
		)
	);
}

/**
 * Handle the permission_callback for our rest webhook.
 *
 * @param \WP_REST_Request $request The request object coming into the endpoint.
 *
 * @return boolean
 */
function decision_webhook_auth( \WP_REST_Request $request ) {
	$key = $request->get_header( 'X-Sift-Science-Signature' );

	if ( $key && hash_equals( get_option( 'wc_sift_for_woocommerce_sift_webhook_key', '' ), $key ) ) {
		return true;
	}

	wc_get_logger()->log(
		'debug',
		'Unauthorized Sift Decision Request. Bad key: `' . $key . '`',
		array(
			'source' => 'sift-for-woocommerce',
		)
	);

	return false;
}

/**
 * Details / documentation:
 *  https://sift.com/resources/tutorials/decisions
 *  https://sift.com/developers/docs/curl/decisions-api
 *
 * @param \WP_REST_Request $request The request object coming into the endpoint.
 *
 * @return object
 */
function decision_webhook( \WP_REST_Request $request ) {
	$json = $request->get_json_params();

	// Validate required fields.
	if ( empty( $json['decision']['id'] ) || empty( $json['entity']['id'] ) || empty( $json['entity']['type'] ) ) {
		wc_get_logger()->log(
			'error',
			'Invalid Sift Decision Webhook payload: ' . wp_json_encode( $json ),
			array( 'source' => 'sift-for-woocommerce' )
		);
		return new \WP_Error( 'invalid_payload', 'Invalid Sift Decision Webhook payload.', array( 'status' => 400 ) );
	}

	// Enable logging of all received webhooks.
	wc_get_logger()->log(
		'info',
		'Received Sift Decision: ' . wp_json_encode( $json ),
		array(
			'source' => 'sift-for-woocommerce',
		)
	);

	$user_id = null;

	// Extract the decision ID.
	$decision_id = $json['decision']['id'] ?? null;

	// Check if the entity type is "user" before extracting the user ID.
	if ( isset( $json['entity']['type'] ) && 'user' === $json['entity']['type'] ) {
		$user_id = $json['entity']['id'] ?? null;
	} else {
		wc_get_logger()->error(
			'Entity type is not "user" or is missing from the JSON payload.',
			array( 'entity_type' => $json['entity']['type'] ?? 'not set' )
		);
	}

	/**
	 * This filter will pass in `null` which can be modified to determine the return data sent to Sift in response to the webhook.
	 */
	$return = apply_filters(
		'sift_decision_received',
		null,
		$json['decision']['id'],
		$json['entity']['id'],
		$json['entity']['type'],
		$json['time']
	);

	return $return;
}
