<?php
/**
 * Class RemoveItemFromCartEventTest
 *
 * @package Sift_For_WooCommerce
 */
declare( strict_types=1 );

require_once 'EventTest.php';

// phpcs:disable Universal.Arrays.DisallowShortArraySyntax.Found, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

use Sift_For_WooCommerce\Sift_Events\Events;

/**
 * Test case.
 */
class RemoveItemFromCartEventTest extends EventTest {
	/**
	 * Test that the $remove_item_from_cart event is triggered.
	 *
	 * @return void
	 */
	public function test_remove_item_from_cart() {
		// Arrange
		$cart_item_key      = '';
		$grab_cart_key_func = function ( $_cart_item_key ) use ( &$cart_item_key ) {
			$cart_item_key = $_cart_item_key;
		};
		add_action( 'woocommerce_add_to_cart', $grab_cart_key_func, 10, 1 );

		// Act
		\WC()->cart->add_to_cart( static::$product_id );
		\WC()->cart->remove_cart_item( $cart_item_key );

		// Assert
		$this->assertAddsItemToCartEventTriggered();

		// Clean up
		remove_action( 'woocommerce_add_to_cart', $grab_cart_key_func );
	}

	/**
	 * Test that the $remove_item_from_cart event is triggered for a product that has already been removed.
	 *
	 * @return void
	 */
	public function test_remove_item_from_cart_with_removed_product_data() {
		// Arrange
		$cart_item_key      = '';
		$grab_cart_key_func = function ( $_cart_item_key ) use ( &$cart_item_key ) {
			$cart_item_key = $_cart_item_key;
		};
		add_action( 'woocommerce_add_to_cart', $grab_cart_key_func, 10, 1 );

		// Act
		\WC()->cart->add_to_cart( static::$product_id );
		$sku = wc_get_product( static::$product_id )->get_sku();
		\WC()->cart->cart_contents[ $cart_item_key ]['data'] = null;
		\WC()->cart->remove_cart_item( $cart_item_key );

		// Assert
		$events  = static::filter_events(
			[
				'event'                 => '$remove_item_from_cart',
				'properties.$item.$sku' => $sku,
			]
		);
		static::assertEquals( 0, count( $events ), 'No $remove_item_from_cart event should be found for removed product.' );
		// Clean up
		remove_action( 'woocommerce_add_to_cart', $grab_cart_key_func );
	}

	/**
	 * Assert $add_item_to_cart event is triggered.
	 *
	 * @param integer $product_id Product ID.
	 *
	 * @return void
	 */
	public static function assertAddsItemToCartEventTriggered( $product_id = null ) {
		$product = wc_get_product( $product_id ?? static::$product_id );
		$events  = static::filter_events(
			[
				'event'                 => '$remove_item_from_cart',
				'properties.$item.$sku' => $product->get_sku(),
			]
		);
		static::assertGreaterThanOrEqual( 1, count( $events ), 'No $remove_item_from_cart event found.' );
	}
}
