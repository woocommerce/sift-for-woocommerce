<?php
/**
 * Class UpdatePasswordEventTest
 *
 * @package Sift_For_WooCommerce
 */
declare( strict_types=1 );

require_once 'EventTest.php';

// phpcs:disable Universal.Arrays.DisallowShortArraySyntax.Found, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound

use Sift_For_WooCommerce\Sift_Events\Events;
use Sift_For_WooCommerce\Sift_Events_Types\Sift_Event_Types;

/**
 * Test case.
 */
class DisabledEventTest extends EventTest {

	/**
	 * Generic function to test event disabling
	 *
	 * @param string   $event_type Event type.
	 * @param callable $callback   Function to call.
	 *
	 * @return void
	 */
	private function test_event( string $event_type, callable $callback ) {
		self::reset_events();

		$callback();
		self::fail_on_error_logged();
		self::assertEventSent( $event_type );

		self::reset_events();

		// Disable the event
		add_filter( Sift_Event_Types::get_filter_for_disabled_event_type( $event_type ), '__return_true' );
		$callback();
		self::fail_on_error_logged();
		self::assertNoEventSent( $event_type );

		// Enable the event
		remove_filter( Sift_Event_Types::get_filter_for_disabled_event_type( $event_type ), '__return_true' );
		$callback();
		self::fail_on_error_logged();
		self::assertEventSent( $event_type );

		self::reset_events();

		// Uncheck the event from the admin
		update_option( Sift_Event_Types::get_option_for_event_type( $event_type ), 'no' );
		$callback();
		self::fail_on_error_logged();
		self::assertNoEventSent( $event_type );
	}

	/**
	 * Test that the $update_password event is triggered.
	 *
	 * @return void
	 */
	public function test_password() {
		$this->test_event(
			Sift_Event_Types::$update_password,
			function () {
				Events::update_password( 'test', '1' );
			}
		);
	}

	/**
	 * Test that the $create_account event is triggered.
	 *
	 * @return void
	 */
	public function test_create_account() {
		$user_id = (string) $this->factory()->user->create();
		$this->test_event(
			Sift_Event_Types::$create_account,
			fn() => Events::create_account( $user_id )
		);

		$this->test_event(
			Sift_Event_Types::$update_account,
			fn() => Events::update_account( $user_id )
		);
	}

	/**
	 * Test that the $login event is triggered.
	 *
	 * @return void
	 */
	public function test_login() {
		$user_id = $this->factory()->user->create();
		$user    = get_user_by( 'ID', $user_id );

		$this->test_event(
			Sift_Event_Types::$login,
			fn() => Events::login_success( $user->login, $user )
		);
	}

	/**
	 * Test that the $login event is triggered.
	 *
	 * @return void
	 */
	public function test_login_failure() {
		$user_id = $this->factory()->user->create();
		$user    = get_user_by( 'ID', $user_id );
		$this->test_event(
			Sift_Event_Types::$login,
			fn() => Events::login_failure( $user->user_login, new WP_Error( 'invalid_email' ) )
		);
	}

	/**
	 * Test that the $logout event is triggered.
	 *
	 * @return void
	 */
	public function test_logout() {
		$user_id = $this->factory()->user->create();
		$this->test_event(
			Sift_Event_Types::$logout,
			fn() => Events::logout( (string) $user_id )
		);
	}

	/**
	 * Test that the should_skip_user filter blocks all events.
	 *
	 * @return void
	 */
	public function test_skip_user_filter() {
		// Events should send normally.
		Events::update_password( 'test', '1' );
		self::fail_on_error_logged();
		self::assertEventSent( Sift_Event_Types::$update_password );

		self::reset_events();

		// Enable the skip filter.
		add_filter( 'sift_for_woocommerce_should_skip_user', '__return_true' );
		Events::update_password( 'test', '1' );
		self::fail_on_error_logged();
		self::assertNoEventSent( Sift_Event_Types::$update_password );

		// Remove the skip filter - events should send again.
		remove_filter( 'sift_for_woocommerce_should_skip_user', '__return_true' );

		self::reset_events();

		Events::update_password( 'test', '1' );
		self::fail_on_error_logged();
		self::assertEventSent( Sift_Event_Types::$update_password );
	}

	/**
	 * Test that the $or event is triggered.
	 *
	 * @return void
	 */
	public function test_order() {
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );
		$_REQUEST['woocommerce-process-checkout-nonce'] = wp_create_nonce( 'woocommerce-process_checkout' );
		add_filter( 'woocommerce_checkout_fields', fn() => [], 10, 0 );
		add_filter( 'woocommerce_cart_needs_payment', '__return_false' );
		// Act
		WC()->cart->add_to_cart( static::$product_id );
		$co = WC_Checkout::instance();
		$co->process_checkout();
		$filters = [ 'event' => '$order_status' ];
		$events  = static::filter_events( $filters );
		// Let's manually change the status of the order by cancelling it.
		$order_id = $events[0]['properties.$order_id'];
		$order    = wc_get_order( $order_id );
		$order->update_status( 'cancelled', '', true );

		$this->test_event(
			Sift_Event_Types::$create_order,
			fn() => Events::create_order( (string) $order->get_id(), $order )
		);

		$this->test_event(
			Sift_Event_Types::$update_order,
			fn() => Events::update_or_create_order( (string) $order->get_id(), $order )
		);

		$this->test_event(
			Sift_Event_Types::$transaction,
			fn() => Events::transaction( $order, '$success', '$sale' )
		);
	}


	/**
	 * Assert $event_type event is triggered.
	 *
	 * @param string $event_type Event type.
	 *
	 * @return void
	 */
	public static function assertEventSent( string $event_type ) {
		$events = static::filter_events(
			[
				'event' => $event_type,
			]
		);
		static::assertGreaterThanOrEqual( 1, count( $events ), 'No ' . $event_type . ' event found' );
	}

	/**
	 * Assert no $event_type event is triggered.
	 *
	 * @param string $event_type Event type.
	 *
	 * @return void
	 */
	public static function assertNoEventSent( string $event_type ) {
		$events = static::filter_events(
			[
				'event' => $event_type,
			]
		);
		static::assertEquals( 0, count( $events ), $event_type . ' event found' );
	}
}
