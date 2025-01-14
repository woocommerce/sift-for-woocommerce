<?php declare( strict_types=1 );

namespace Sift_For_WooCommerce;

/**
 * A class representing a Woo order which normalizes order-related data according to expectations in the Sift API.
 */
class Sift_Order {

	private \WC_Order $wc_order;
	private Sift_Payment_Gateway $payment_gateway;
	private $gateway_payment_type;
	private $payment_method_details;
	private $charge_details;

	/**
	 * A class which words as an abstraction layer for Sift representing a WooCommerce Order.
	 *
	 * @param \WC_Order $wc_order A WooCommerce Order object.
	 */
	public function __construct( \WC_Order $wc_order ) {
		$this->wc_order = $wc_order;

		$this->payment_gateway = new Sift_Payment_Gateway( $this->wc_order->get_payment_method(), $this->wc_order );
	}

	/**
	 * Return a value which subsequent hooks can use to obtain information related to a payment method used from an order.
	 *
	 * This acts as an abstraction layer between WooCommerce and the various payment gateway plugins. This method, along
	 * with `get_charge_details_from_order` call the filter
	 * `sift_for_woocommerce_PAYMENT_GATEWAY_ID_payment_method_details_from_order` which accepts a WC_Order object and is
	 * expected to return an object which will then be passed to Payment_Method functions. These Payment_Method functions
	 * call a similar filter (e.g., `sift_for_woocommerce_PAYMENT_GATEWAY_ID_card_last4` to obtain the last4 digits of the
	 * card) which accepts the object returned by this function, then selects and returns the value from that object. This
	 * allows each payment gateway to work with their own data objects to obtain the data needed for Sift.
	 *
	 * @return mixed A value which contains the information that subsequent functions will use to extract payment method info.
	 */
	private function get_payment_method_details_from_order() {
		if ( ! empty( $this->payment_method_details ) ) {
			return $this->payment_method_details;
		}
		$this->payment_method_details = apply_filters( sprintf( 'sift_for_woocommerce_%s_payment_method_details_from_order', $this->payment_gateway->get_woo_gateway_id() ), null, $this->wc_order ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		return $this->payment_method_details;
	}

	/**
	 * Return a value which subsequent hooks can use to obtain information related to the charge details / transaction
	 * used from an order.
	 *
	 * This acts as an abstraction layer between WooCommerce and the various payment gateway plugins. This method, along
	 * with `get_payment_method_details_from_order` call the filter
	 * `sift_for_woocommerce_PAYMENT_GATEWAY_ID_charge_details_from_order` which accepts a WC_Order object and is
	 * expected to return an object which will then be passed to Payment_Method functions. These Payment_Method functions
	 * call a similar filter (e.g., `sift_for_woocommerce_PAYMENT_GATEWAY_ID_card_last4` to obtain the last4 digits of the
	 * card) which accepts the object returned by this function, then selects and returns the value from that object. This
	 * allows each payment gateway to work with their own data objects to obtain the data needed for Sift.
	 *
	 * @return mixed A value which contains the information that subsequent functions will use to extract charge / transaction info.
	 */
	private function get_charge_details_from_order() {
		if ( ! empty( $this->charge_details ) ) {
			return $this->charge_details;
		}
		$this->charge_details = apply_filters( sprintf( 'sift_for_woocommerce_%s_charge_details_from_order', $this->payment_gateway->get_woo_gateway_id() ), null, $this->wc_order ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		return $this->charge_details;
	}

	/**
	 * Get the payment methods associated with this order.
	 *
	 * @return array An array of payment methods associated with this order. Each payment method is in the format Sift expects.
	 */
	public function get_payment_methods(): array {
		$order_payment_method = array(
			'$payment_type'                  => Sift_Payment_Method::get_payment_type_string( $this->payment_gateway, $this->gateway_payment_type ),
			'$payment_gateway'               => Sift_Payment_Method::get_payment_gateway_string( $this->payment_gateway, $this->wc_order ),
			'$card_bin'                      => Sift_Payment_Method::get_card_bin( $this->payment_gateway, $this->get_payment_method_details_from_order() ),
			'$card_last4'                    => Sift_Payment_Method::get_card_last4( $this->payment_gateway, $this->get_payment_method_details_from_order() ),
			'$avs_result_code'               => Sift_Payment_Method::get_avs_result_code( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$cvv_result_code'               => Sift_Payment_Method::get_cvv_result_code( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$verification_status'           => Sift_Payment_Method::get_verification_status( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$routing_number'                => Sift_Payment_Method::get_routing_number( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$shortened_iban_first6'         => Sift_Payment_Method::get_shortened_iban_first6( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$shortened_iban_last4'          => Sift_Payment_Method::get_shortened_iban_last4( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$sepa_direct_debit_mandate'     => Sift_Payment_Method::get_sepa_direct_debit_mandate( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$decline_reason_code'           => Sift_Payment_Method::get_decline_reason_code( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$wallet_address'                => Sift_Payment_Method::get_wallet_address( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$wallet_type'                   => Sift_Payment_Method::get_wallet_type( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$paypal_payer_id'               => Sift_Payment_Method::get_paypal_payer_id( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$paypal_payer_email'            => Sift_Payment_Method::get_paypal_payer_email( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$paypal_payer_status'           => Sift_Payment_Method::get_paypal_payer_status( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$paypal_address_status'         => Sift_Payment_Method::get_paypal_address_status( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$paypal_protection_eligibility' => Sift_Payment_Method::get_paypal_protection_eligibility( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$paypal_payment_status'         => Sift_Payment_Method::get_paypal_payment_status( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$stripe_cvc_check'              => Sift_Payment_Method::get_stripe_cvc_check( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$stripe_address_line1_check'    => Sift_Payment_Method::get_stripe_address_line1_check( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$stripe_address_line2_check'    => Sift_Payment_Method::get_stripe_address_line2_check( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$stripe_address_zip_check'      => Sift_Payment_Method::get_stripe_address_zip_check( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$stripe_funding'                => Sift_Payment_Method::get_stripe_funding( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$stripe_brand'                  => Sift_Payment_Method::get_stripe_brand( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$account_holder_name'           => Sift_Payment_Method::get_account_holder_name( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$account_number_last5'          => Sift_Payment_Method::get_account_number_last5( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$bank_name'                     => Sift_Payment_Method::get_bank_name( $this->payment_gateway, $this->get_charge_details_from_order() ),
			'$bank_country'                  => Sift_Payment_Method::get_bank_country( $this->payment_gateway, $this->get_charge_details_from_order() ),
		);

		return array_filter(
			array(
				array_filter( $order_payment_method, fn( $val ) => ! empty( $val ) ),
			)
		);
	}
}
