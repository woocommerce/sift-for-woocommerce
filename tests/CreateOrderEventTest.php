<?php
/**
 * Class CreateOrderEventTest
 *
 * @package Sift_Decisions
 */

require_once 'EventTest.php';

// phpcs:disable Universal.Arrays.DisallowShortArraySyntax.Found, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

use WPCOMSpecialProjects\SiftDecisions\WooCommerce_Actions\Events;

/**
 * Test case.
 */
class CreateOrderEventTest extends EventTest {
	/**
	 * Test that the $create_order event is triggered.
	 *
	 * @return void
	 */
	public function test_create_account() {
		// Arrange
		// - create a user and log them in
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );

		$_REQUEST['woocommerce-process-checkout-nonce'] = wp_create_nonce( 'woocommerce-process_checkout' );
		add_filter( 'woocommerce_checkout_fields', fn() => [], 10, 0 );
		add_filter( 'woocommerce_cart_needs_payment', '__return_false' );

		// Act
		WC()->cart->add_to_cart( static::$product_id );
		$co = WC_Checkout::instance();
		$co->process_checkout();

		// Assert
		static::fail_on_error_logged();
		static::assertCreateOrderEventTriggered();

		// Clean up
		wp_delete_user( $user_id );
	}

	/**
	 * Assert $create_order event is triggered.
	 *
	 * @return void
	 */
	public static function assertCreateOrderEventTriggered() {
		$events = static::filter_events( [ 'event' => '$create_order' ] );
		static::assertGreaterThanOrEqual( 1, count( $events ), 'No $create_order event found' );
	}
}