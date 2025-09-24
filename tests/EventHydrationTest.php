<?php
/**
 * Class EventHydrationTest
 *
 * @package Sift_For_WooCommerce
 */
declare( strict_types=1 );

require_once 'EventTest.php';

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

use Sift_For_WooCommerce\Sift_Events\Events;

/**
 * Test case for event hydration methods.
 */
class EventHydrationTest extends EventTest {

	/**
	 * Test that hydrate_cart_properties correctly hydrates cart data.
	 *
	 * @return void
	 */
	public function test_hydrate_cart_properties_adds_product_details() {
		// Arrange cart properties like what gets queued
		$properties = array(
			'$user_id'      => '123',
			'$user_email'   => 'test@example.com',
			'$session_id'   => 'test_session_123',
			'$item'         => array(
				'$item_id'   => 'cart_item_123',
				'product_id' => static::$product_id, // Internal field for lookup
				'$quantity'  => 2,
			),
			'$browser'      => array( '$user_agent' => 'Test Agent' ),
			'$site_domain'  => 'example.com',
			'$site_country' => 'US',
			'$ip'           => '192.168.1.1',
			'$time'         => 1234567890000,
		);

		// hydrate the properties
		$hydrated_properties = Events::hydrate_cart_properties( '$add_item_to_cart', $properties );

		// check that product details were added
		$product = wc_get_product( static::$product_id );

		// Original properties should be preserved
		static::assertEquals( '123', $hydrated_properties['$user_id'] );
		static::assertEquals( 'test@example.com', $hydrated_properties['$user_email'] );
		static::assertEquals( 2, $hydrated_properties['$item']['$quantity'] );

		// Product details should be added to $item
		static::assertEquals( $product->get_sku(), $hydrated_properties['$item']['$sku'] );
		static::assertEquals( $product->get_title(), $hydrated_properties['$item']['$product_title'] );
		static::assertEquals( Events::get_transaction_micros( floatval( $product->get_price() ) ), $hydrated_properties['$item']['$price'] );
		static::assertEquals( get_woocommerce_currency(), $hydrated_properties['$item']['$currency_code'] );
		static::assertArrayHasKey( '$category', $hydrated_properties['$item'] );
		static::assertArrayHasKey( '$tags', $hydrated_properties['$item'] );

		// Internal product_id field should be removed
		static::assertArrayNotHasKey( 'product_id', $hydrated_properties['$item'] );
	}

	/**
	 * Test hydrate_cart_properties with non-existent product.
	 *
	 * @return void
	 */
	public function test_hydrate_cart_properties_with_missing_product() {
		// Properties with non-existent product ID
		$properties = array(
			'$user_id' => '123',
			'$item'    => array(
				'$item_id'   => 'cart_item_123',
				'product_id' => 99999, // Non-existent product
				'$quantity'  => 1,
			),
		);

		// hydrate the properties
		$hydrated_properties = Events::hydrate_cart_properties( '$add_item_to_cart', $properties );

		// original properties preserved, product details not added, internal field removed
		static::assertEquals( '123', $hydrated_properties['$user_id'] );
		static::assertEquals( 1, $hydrated_properties['$item']['$quantity'] );
		static::assertArrayNotHasKey( '$sku', $hydrated_properties['$item'] );
		static::assertArrayNotHasKey( '$product_title', $hydrated_properties['$item'] );
		static::assertArrayNotHasKey( 'product_id', $hydrated_properties['$item'] );
	}

	/**
	 * Test hydrate_cart_properties with missing product_id.
	 *
	 * @return void
	 */
	public function test_hydrate_cart_properties_with_missing_product_id() {
		// Arrange cart properties without product_id
		$properties = array(
			'$user_id' => '123',
			'$item'    => array(
				'$item_id'  => 'cart_item_123',
				'$quantity' => 1,
				// No product_id field
			),
		);

		// hydrate the properties
		$hydrated_properties = Events::hydrate_cart_properties( '$add_item_to_cart', $properties );

		// properties returned unchanged since no product_id to lookup
		static::assertEquals( $properties, $hydrated_properties );
	}

	/**
	 * Test that hydrate_order_properties correctly hydrates order data.
	 *
	 * @return void
	 */
	public function test_hydrate_order_properties_adds_order_details() {
		$user_id = $this->factory()->user->create();

		// Create an order for testing
		$order = wc_create_order();
		$order->add_product( wc_get_product( static::$product_id ), 2 );
		$order->set_billing_email( 'test@example.com' );
		$order->set_billing_phone( '+1234567890' );
		$order->set_billing_first_name( 'Test' );
		$order->set_billing_last_name( 'User' );
		$order->set_customer_ip_address( '192.168.1.100' );
		$order->set_customer_id( $user_id );
		$order->calculate_totals();
		$order->save();

		// Arrange order properties like what gets queued
		$properties = array(
			'$session_id' => 'test_session_123',
			'$order_id'   => (string) $order->get_id(),
			'$user_id'    => $user_id,
			'$browser'    => array( '$user_agent' => 'Test Agent' ),
			'$ip'         => '192.168.1.1',
			'$time'       => 1234567890000,
		);

		// hydrate the properties
		$hydrated_properties = Events::hydrate_order_properties( '$create_order', $properties );

		// check that order details were added
		// Original properties should be preserved
		static::assertEquals( 'test_session_123', $hydrated_properties['$session_id'] );
		static::assertEquals( (string) $order->get_id(), $hydrated_properties['$order_id'] );

		// Order details should be added
		static::assertEquals( (string) $order->get_user_id(), $hydrated_properties['$user_id'] );
		static::assertEquals( 'test@example.com', $hydrated_properties['$user_email'] );
		static::assertEquals( '+1234567890', $hydrated_properties['$verification_phone_number'] );
		static::assertEquals( Events::get_transaction_micros( floatval( $order->get_total() ) ), $hydrated_properties['$amount'] );
		static::assertEquals( $order->get_currency(), $hydrated_properties['$currency_code'] );
		static::assertArrayHasKey( '$billing_address', $hydrated_properties );
		static::assertArrayHasKey( '$shipping_address', $hydrated_properties );
		static::assertArrayHasKey( '$items', $hydrated_properties );
		static::assertIsArray( $hydrated_properties['$items'] );
		static::assertNotEmpty( $hydrated_properties['$items'] );

		// IP should be overridden with order's IP if available
		static::assertEquals( '192.168.1.100', $hydrated_properties['$ip'] );

		// Clean up
		$order->delete( true );
		wp_delete_user( $user_id );
	}

	/**
	 * Test hydrate_order_properties with non-existent order.
	 *
	 * @return void
	 */
	public function test_hydrate_order_properties_with_missing_order() {
		// Properties with non-existent order ID
		$properties = array(
			'$session_id' => 'test_session_123',
			'$order_id'   => '99999', // Non-existent order
			'$browser'    => array( '$user_agent' => 'Test Agent' ),
			'$ip'         => '192.168.1.1',
			'$time'       => 1234567890000,
		);

		// hydrate the properties
		$hydrated_properties = Events::hydrate_order_properties( '$create_order', $properties );

		// properties returned unchanged since order not found
		static::assertEquals( $properties, $hydrated_properties );
	}

	/**
	 * Test hydrate_order_properties with missing order_id.
	 *
	 * @return void
	 */
	public function test_hydrate_order_properties_with_missing_order_id() {
		// Arrange order properties without order_id
		$properties = array(
			'$session_id' => 'test_session_123',
			'$browser'    => array( '$user_agent' => 'Test Agent' ),
			'$ip'         => '192.168.1.1',
			'$time'       => 1234567890000,
			// No $order_id field
		);

		// hydrate the properties
		$hydrated_properties = Events::hydrate_order_properties( '$create_order', $properties );

		// properties returned unchanged since no order_id to lookup
		static::assertEquals( $properties, $hydrated_properties );
	}

	/**
	 * Test validate_event_properties with valid cart event.
	 *
	 * @return void
	 */
	public function test_validate_event_properties_valid_cart_event() {
		$properties = array(
			'$user_id'    => '123',
			'$session_id' => 'test_session',
			'$item'       => array(
				'$item_id'       => 'item_123',
				'$sku'           => 'test-sku',
				'$product_title' => 'Test Product',
				'$price'         => 1000000,
				'$quantity'      => 1,
				'$currency_code' => 'USD',
				'$category'      => 'Test Category',
			),
			'$browser'    => array( '$user_agent' => 'Test Agent' ),
			'$ip'         => '192.168.1.1',
			'$time'       => 1234567890000,
		);

		static::assertTrue( Events::validate_event_properties( '$add_item_to_cart', $properties ) );
	}

	/**
	 * Test validate_event_properties with invalid cart event.
	 *
	 * @return void
	 */
	public function test_validate_event_properties_invalid_cart_event() {
		$properties = array(
			'$user_id' => '123',
			'$item'    => array(
				'$item_id' => 'item_123',
				'$price'   => 'invalid_price', // Invalid price type
			),
		);

		static::assertFalse( Events::validate_event_properties( '$add_item_to_cart', $properties ) );
	}

	/**
	 * Test validate_event_properties with valid order event.
	 *
	 * @return void
	 */
	public function test_validate_event_properties_valid_order_event() {
		$properties = array(
			'$user_id'       => '123',
			'$session_id'    => 'test_session',
			'$order_id'      => 'order_123',
			'$user_email'    => 'test@example.com',
			'$amount'        => 5000000,
			'$currency_code' => 'USD',
			'$items'         => array(
				array(
					'$item_id'       => 'item_123',
					'$product_title' => 'Test Product',
					'$price'         => 1000000,
					'$quantity'      => 1,
				),
			),
			'$browser'       => array( '$user_agent' => 'Test Agent' ),
			'$ip'            => '192.168.1.1',
			'$time'          => 1234567890000,
		);

		static::assertTrue( Events::validate_event_properties( '$create_order', $properties ) );
	}

	/**
	 * Test validate_event_properties with unknown event type.
	 *
	 * @return void
	 */
	public function test_validate_event_properties_unknown_event() {
		$properties = array( '$user_id' => '123' );

		static::assertTrue( Events::validate_event_properties( '$unknown_event', $properties ) );
	}
}
