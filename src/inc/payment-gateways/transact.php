<?php declare( strict_types=1 );

add_filter( 'sift_for_woocommerce_woopayments_payment_gateway_string', fn() => '$stripe' );

add_filter(
	'sift_for_woocommerce_woopayments_payment_type_string',
	function ( $payment_type ) {
		switch ( strtolower( $payment_type ) ) {
			case 'card':
				return '$credit_card';
		}
	}
);

add_filter(
	'sift_for_woocommerce_woopayments_payment_method_details_from_order',
	function ( \WC_Order $order ) {
		return $order;
	}
);

add_filter( 'sift_for_woocommerce_woopayments_card_last4', fn( $order ) => $order->get_meta( 'last4' ) );
