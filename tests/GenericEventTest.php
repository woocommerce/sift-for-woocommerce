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
		$event_type = 'test_event_name';
		add_filter( 'sift_for_woocommerce_pre_send_event_properties', function( $properties, $event_name ) {
			$properties['$user_id'] = 'prefix_' . $properties['$user_id'];
			$properties['some']     = 'data';
			return $properties;
		}, 10, 2 );

		Events::queue_sending_event( $event_type, array( '$user_id' => '12345' ) );
		static::fail_on_error_logged();

		// We see if the event in the stack was modified
		$events = static::filter_events( [ 'event' => $event_type ] );
		static::assertIsArray( $events, 'Failed to find event: ' . $event_type );
		static::assertEquals( 1, count( $events ), 'No ' . $event_type . ' event found' );
		$event = reset( $events );
		static::assertEquals( $event['event'], $event_type, 'Event name not expected.' );
		static::assertEquals( $event['properties.$user_id'], 'prefix_12345', 'Event param $user_id not expected.' );
		static::assertEquals( $event['properties.some'], 'data', 'Custom event parameter was not added' );

		static::reset_events();

		// We check when removing the filter
		remove_all_filters( 'sift_for_woocommerce_pre_send_event_properties' );

		Events::queue_sending_event( $event_type, array( '$user_id' => '12345' ) );
		static::fail_on_error_logged();

		// We see if the event in the stack was modified
		$events = static::filter_events( [ 'event' => $event_type ] );
		static::assertIsArray( $events, 'Failed to find event: ' . $event_type );
		static::assertEquals( 1, count( $events ), 'No ' . $event_type . ' event found' );
		$event = reset( $events );
		static::assertEquals( $event['event'], $event_type, 'Event name not expected.' );
		static::assertEquals( $event['properties.$user_id'], '12345', 'Event param $user_id not expected.' );
		static::assertTrue( ! isset( $event['properties.some'] ), 'Custom event was somehow added' );
	}
}
