<?php declare( strict_types=1 );

class Stripe {
	public static function get_all_payment_methods_for_customer_from_order( \WC_Order $order ) {
		$stripe_customer_id = $order->get_meta( '_stripe_customer_id', true );

		$stripe_customer = new \WC_Stripe_Customer();
		$stripe_customer->set_id( $stripe_customer_id );

		$sources = array_merge(
			$stripe_customer->get_payment_methods( 'card' ),
			$stripe_customer->get_payment_methods( 'sepa_debit' )
		);

		if ( $sources ) {
			return $sources;
		}
	}

	public static function get_payment_method_from_order( \WC_Order $order ) {
		$stripe_source_id   = $order->get_meta( '_stripe_source_id', true );
		$sources = static::get_all_payment_methods_for_customer_from_order( $order );

		if ( $sources ) {
			foreach ( $sources as $source ) {
				if ( $source->id === $stripe_source_id ) {
					return $source;
				}
			}
		}
	}

	public static function get_intent_from_order( \WC_Order $order ) {
		$intent_id = $order->get_meta( '_stripe_intent_id' );

		if ( $intent_id ) {
			return static::get_intent( 'payment_intents', $intent_id );
		}

		// The order doesn't have a payment intent, but it may have a setup intent.
		$intent_id = $order->get_meta( '_stripe_setup_intent' );

		if ( $intent_id ) {
			return static::get_intent( 'setup_intents', $intent_id );
		}

		return false;
	}

	public static function get_intent( string $intent_type, string $intent_id ) {
		if ( ! in_array( $intent_type, [ 'payment_intents', 'setup_intents' ], true ) ) {
			throw new Exception( "Failed to get intent of type $intent_type. Type is not allowed" );
		}

		$response = WC_Stripe_API::request( [], "$intent_type/$intent_id?expand[]=payment_method", 'GET' );

		if ( $response && isset( $response->{ 'error' } ) ) {
			return false;
		}

		return $response;
	}

	public static function get_charge_for_intent_from_order( \WC_Order $order ) {
		$intent = Stripe::get_intent_from_order( $order );
		if ( ! empty( $intent ) ) {
			$result = WC_Stripe_API::request(
				[],
				'payment_intents/' . $intent->id
			);
			if ( empty( $result->error ) ) {
				return end( $result->charges->data );
			}
		}
	}

	public static function get_payment_type_from_order( \WC_Order $order ) {
		return $order->get_meta( '_stripe_upe_payment_type' );
	}

	public static function convert_payment_method_to_sift_payment_gateway( string $payment_method ): ?string {
		switch ( $payment_method ) {
			case 'affirm':
				return '$affirm';
			case 'afterpay_clearpay':
			case 'afterpay':
				return '$afterpay';
			case 'bancontact':
				return '$bancontact';
			case 'boleto':
				return '$boleto';
			case 'card':
				return '$stripe';
			case 'clearpay':
				return '$afterpay';
			case 'eps':
				return '$eps';
			case 'giropay':
				return '$giropay';
			case 'ideal':
				return '$ideal';
			case 'link':
				return '$stripe';
			case 'klarna':
				return '$klarna';
			case 'oxxo':
				return '$cash';
			case 'p24':
			case 'przelewy24':
				return '$przelewy24';
			case 'sepa_debit':
			case 'sepa':
				return '$sepa';
			case 'sofort':
				return '$sofort';
			case 'stripe_alipay':
				return '$alipay';
			case 'stripe_bancontact':
				return '$bancontact';
			case 'stripe_boleto':
				return '$boleto';
			case 'stripe_eps':
				return '$eps';
			case 'stripe_giropay':
				return '$giropay';
			case 'stripe_ideal':
				return '$ideal';
			case 'stripe_multibanco':
				return '$multibanco';
			case 'stripe_oxxo':
				return '$oxxo';
			case 'stripe_p24':
				return '$przelewy24';
			case 'stripe_sepa':
				return '$sepa';
			case 'stripe_sofort':
				return '$sofort';
		}
		return null;
	}

	public static function convert_payment_type_to_sift_payment_type( string $payment_type ): ?string {
		switch ( $payment_type ) {
			case 'affirm':
				return '$financing';
			case 'afterpay_clearpay':
			case 'afterpay':
				return '$financing';
			case 'alipay':
			case 'stripe_alipay':
				return '$digital_wallet';
			// case 'stripe_bancontact':
			// case 'bancontact':
			// 	return '$electronic_fund_transfer';
			case 'stripe_boleto':
			case 'boleto':
				return '$voucher';
			case 'card':
				return '$credit_card';
			case 'clearpay':
				return '$financing';
			// case 'stripe_eps':
			// case 'eps':
			// 	return '$electronic_fund_transfer';
			case 'stripe_giropay':
			case 'giropay':
				return '$electronic_fund_transfer';
			case 'stripe_ideal':
			// case 'ideal':
			// 	return '$electronic_fund_transfer';
			case 'klarna':
				return '$financing';
			// case 'link':
			// 	return '$third_party_processor';
			case 'stripe_multibanco':
				return '$voucher';
			case 'stripe_oxxo':
			case 'oxxo':
				return '$voucher';
			// case 'stripe_p24':
			// case 'p24':
			// case 'przelewy24':
			// 	return '$electronic_fund_transfer';
			case 'stripe_sepa':
			case 'sepa_debit':
			case 'sepa':
				return '$sepa_direct_debit';
			// case 'stripe_sofort':
			// case 'sofort':
			// 	return '$electronic_fund_transfer';
		}
		return null;
	}

}