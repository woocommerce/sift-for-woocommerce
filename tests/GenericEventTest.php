<?php declare( strict_types = 1 );

// phpcs:disable

namespace SiftApi;

use Sift_For_WooCommerce\Sift_Events\Events;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GenericEventTest extends \EventTest {


	/**
	 * Assert that an event is properly modified with sift_for_woocommerce_pre_send_event_properties
	 */
	public function test_sift_for_woocommerce_pre_send_event_properties() {
		add_filter( 'sift_for_woocommerce_pre_send_event_properties', function( $properties, $event_name ) {
			$properties['$user_id'] = 'prefix_' . $properties['$user_id'];
			$properties['some']     = 'data';
			return $properties;
		}, 10, 2 );

		Events::queue_sending_event( 'test_event_name', array( '$user_id' => '12345' ) );
		static::fail_on_error_logged();

		// We see if the event in the stack was modified
		$event = Events::$to_send[0];
		static::assertEquals( $event['event'], 'test_event_name', 'Event name not expected.');
		static::assertEquals( $event['properties']['$user_id'], 'prefix_12345', 'Event param $user_id not expected.');
		static::assertEquals( $event['properties']['some'], 'data', 'Custom event parameter was not added');

		static::reset_events();

		// We check when removing the filter
		remove_all_filters( 'sift_for_woocommerce_pre_send_event_properties' );

		Events::queue_sending_event( 'test_event_name', array( '$user_id' => '12345' ) );
		static::fail_on_error_logged();

		// We see if the event in the stack was modified
		$event = Events::$to_send[0];
		static::assertEquals( $event['event'], 'test_event_name', 'Event name not expected.');
		static::assertEquals( $event['properties']['$user_id'], '12345', 'Event param $user_id not expected.');
		static::assertTrue( ! isset( $event['properties']['some'] ), 'Custom event was somehow added');

	}
}
