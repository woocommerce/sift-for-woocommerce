<?php
/**
 * Class LinkSessionToUserEventTest
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
class LinkSessionToUserEventTest extends EventTest {
	/**
	 * Test that the $link_session_to_user event is triggered.
	 *
	 * @return void
	 */
	public function test_link_session_to_user_event() {
		// Arrange
		// - create a user and log them in
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $user_id );
		// We'll trap the cookie and set it manually
		$f = function ( $enabled, $name, $value ) {
			$_COOKIE[ $name ] = $value;
			return false;
		};
		add_filter( 'woocommerce_set_cookie_enabled', $f, 10, 3 );
		WC()->session->set_customer_session_cookie( true );

		// Act
		// - (re)init the session cookie (checks for user and links session to user)
		WC()->session->init_session_cookie();

		// Assert
		static::fail_on_error_logged();
		static::assertLinkSessionToUserEvent( $user_id );

		// Clean up
		remove_filter( 'woocommerce_set_cookie_enabled', $f );
		wp_set_current_user( 0 );
		wp_delete_user( $user_id );
	}

	/**
	 * Assert $link_session_to_user event is triggered.
	 *
	 * @param integer $user_id User ID.
	 *
	 * @return void
	 */
	public static function assertLinkSessionToUserEvent( $user_id ) {
		$events = static::filter_events( [ 'event' => '$link_session_to_user' ] );
		static::assertGreaterThanOrEqual( 1, count( $events ), 'No $link_session_to_user event found' );
	}
}
