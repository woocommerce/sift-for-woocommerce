<?php declare( strict_types=1 );

add_action(
	'woocommerce_init',
	function () {
		if ( class_exists( 'WC_Gateway_Stripe' ) ) {
			require_once __DIR__ . '/lib/stripe.php';
			require_once __DIR__ . '/stripe.php';
		}

		if ( class_exists( 'WooCommerce\PayPalCommerce\PPCP' ) ) {
			require_once __DIR__ . '/ppcp-gateway.php';
		}

		if ( defined( 'TRANSACT_GATEWAY_PLUGIN_DIR' ) ) {
			require_once __DIR__ . '/transact.php';
		}

		if ( class_exists( 'WC_Payments_Features' ) ) {
			require_once __DIR__ . '/lib/stripe.php';
			require_once __DIR__ . '/woocommerce-payments.php';
		}
	},
	1000
);
