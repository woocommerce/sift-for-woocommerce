<?php declare( strict_types=1 );

/*
woo_fraud_sentinel_
---
woopayments gateway:
woo_fraud_sentinel_woopayments_payment_gateway_string
woo_fraud_sentinel_woopayments_payment_method_details_from_order
woo_fraud_sentinel_woopayments_charge_details_from_order
woo_fraud_sentinel_woopayments_payment_type_string
woo_fraud_sentinel_woopayments_card_last4
woo_fraud_sentinel_woopayments_card_bin
woo_fraud_sentinel_woopayments_cvv_result_code
woo_fraud_sentinel_woopayments_
woo_fraud_sentinel_woopayments_
woo_fraud_sentinel_woopayments_
woo_fraud_sentinel_woopayments_
woo_fraud_sentinel_woopayments_

woopayments_express_checkout gateway:
woo_fraud_sentinel_woopayments_express_checkout_

woopayments_credit_card gateway:
woo_fraud_sentinel_woopayments_credit_card_

---
payment_gateway_string
payment_method_details_from_order
charge_details_from_order
payment_type_string

card_last4
card_bin
cvv_result_code
sepa_direct_debit_mandate
...
*/

add_filter( 'woo_fraud_sentinel_woopayments_payment_gateway_string', fn() => '$stripe' );

add_filter( 'woo_fraud_sentinel_woopayments_payment_method_details_from_order', function( \WC_Order $order ) {
	return Stripe::get_payment_method_from_order( $order );
} );

add_filter( 'woo_fraud_sentinel_woopayments_charge_details_from_order', function( \WC_Order $order ) {
	return Stripe::get_charge_for_intent_from_order( $order );
} );

add_filter( 'woo_fraud_sentinel_woopayments_payment_type_string', [ Stripe::class, 'convert_payment_type_to_sift_payment_type' ] );

add_filter( 'woo_fraud_sentinel_woopayments_card_last4', fn( $stripe_payment_method ) => $stripe_payment_method->card->last4 );
add_filter( 'woo_fraud_sentinel_woopayments_card_bin', fn( $stripe_payment_method ) => $stripe_payment_method->card->iin );
add_filter( 'woo_fraud_sentinel_woopayments_cvv_result_code', fn( $stripe_charge ) => $stripe_charge->payment_method_details->card->checks->cvc_check );
add_filter( 'woo_fraud_sentinel_woopayments_sepa_direct_debit_mandate', fn( $stripe_charge ) => $stripe_charge->payment_method_details->sepa_debit->mandate );
add_filter( 'woo_fraud_sentinel_woopayments_wallet_type', fn( $stripe_charge ) => $stripe_charge->payment_method_details->card->wallet->type );

add_filter( 'woo_fraud_sentinel_woopayments_paypal_payer_id', fn( $stripe_charge ) => $stripe_charge->payment_method_details->paypal->payer_id );
add_filter( 'woo_fraud_sentinel_woopayments_paypal_payer_email', fn( $stripe_charge ) => $stripe_charge->payment_method_details->paypal->payer_email );

add_filter( 'woo_fraud_sentinel_woopayments_stripe_cvc_check', fn( $stripe_charge ) => $stripe_charge->payment_method_details->card->checks->cvc_check );
add_filter( 'woo_fraud_sentinel_woopayments_stripe_address_line1_check', fn( $stripe_charge ) => $stripe_charge->payment_method_details->card->checks->address_line1_check );
add_filter( 'woo_fraud_sentinel_woopayments_stripe_address_zip_check', fn( $stripe_charge ) => $stripe_charge->payment_method_details->card->checks->address_postal_code_check );
add_filter( 'woo_fraud_sentinel_woopayments_stripe_funding', fn( $stripe_charge ) => $stripe_charge->payment_method_details->card->funding );
add_filter( 'woo_fraud_sentinel_woopayments_stripe_brand', fn( $stripe_charge ) => $stripe_charge->payment_method_details->card->brand );
