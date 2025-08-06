<?php
/**
 * Class AddsItemToCartEventTest
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
class AddsItemToCartEventTest extends EventTest {
	/**
	 * Test that the $add_item_to_cart event is triggered.
	 *
	 * @return void
	 */
	public function test_adds_item_to_cart() {
		\WC()->cart->add_to_cart( static::$product_id );
		$this->assertAddsItemToCartEventTriggered();
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
				'event'                       => '$add_item_to_cart',
				'properties.$item.product_id' => $product->get_id(),
			]
		);
		static::assertGreaterThanOrEqual( 1, count( $events ), 'No $add_item_to_cart event found.' );
	}
}
