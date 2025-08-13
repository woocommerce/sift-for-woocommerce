<?php

namespace Sift_For_WooCommerce;

use Sift_For_WooCommerce\Sift_Events\Events;

require_once __DIR__ . '/inc/wc-admin-settings-tab.php';
require_once __DIR__ . '/inc/tracking-js.php';
require_once __DIR__ . '/inc/payment-gateways/load.php';
require_once __DIR__ . '/inc/sift-events/sift-events.php';
require_once __DIR__ . '/inc/sift-decisions/abuse-decisions.php';
require_once __DIR__ . '/inc/rest-api.php';

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
class Sift_For_WooCommerce {

	private static $sift_orders = array();

	// region MAGIC METHODS

	/**
	 * Plugin constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	protected function __construct() {
		add_action( 'rest_api_init', __NAMESPACE__ . '\Rest_Api_Webhooks\register_routes' );

		add_filter( 'woocommerce_settings_tabs_array', __NAMESPACE__ . '\WC_Settings_Tab\add_settings_tab', 50 );
		add_action( 'woocommerce_settings_tabs_sift_for_woocommerce', __NAMESPACE__ . '\WC_Settings_Tab\settings_tab' );
		add_action( 'woocommerce_update_options_sift_for_woocommerce', __NAMESPACE__ . '\WC_Settings_Tab\update_settings' );
		add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\WC_Settings_Tab\enqueue_checkboxes_sync_js' );

		add_action( 'wp_body_open', __NAMESPACE__ . '\Tracking_Js\print_sift_tracking_js' ); // Core's implementation! https://make.wordpress.org/themes/2019/03/29/addition-of-new-wp_body_open-hook/
		add_action( 'genesis_before', __NAMESPACE__ . '\Tracking_Js\print_sift_tracking_js' ); // Genesis
		add_action( 'tha_body_top', __NAMESPACE__ . '\Tracking_Js\print_sift_tracking_js' ); // Theme Hook Alliance
		add_action( 'body_top', __NAMESPACE__ . '\Tracking_Js\print_sift_tracking_js' ); // THA Unprefixed
		add_action( 'wp_footer', __NAMESPACE__ . '\Tracking_Js\print_sift_tracking_js' ); // Fallback!

		add_action( 'login_header', __NAMESPACE__ . '\Tracking_Js\print_sift_tracking_js' ); // Allow Sift tracking of login page visits.

		Events::init_hooks();

		/**
		 * Action to load the sidecar plugin.
		 */
		do_action( 'sift_for_woocommerce_load_sidecar_plugin' );

		// Load the REST API
		REST_API::instance();
	}

	/**
	 * Prevent cloning.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	private function __clone() {
		/* Empty on purpose. */
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function __wakeup() {
		/* Empty on purpose. */
	}

	// endregion

	// region METHODS

	/**
	 * Returns the singleton instance of the plugin.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  Plugin
	 */
	public static function get_instance(): self {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Get the SiftClient instantiated api client.
	 *
	 * Store it internally as a static to simplify if needed multiple times.
	 *
	 * @return \SiftClient|null
	 */
	public static function get_api_client() {
		static $client = null;

		if ( null === $client ) {
			$api_key    = get_option( 'wc_sift_for_woocommerce_sift_api_key' );
			$account_id = get_option( 'wc_sift_for_woocommerce_sift_account_id' );

			if ( ! $account_id || ! $api_key ) {
				wc_get_logger()->log(
					'error',
					'Attempting to instantiate Sift API client, but missing `account_id` or `api_key`.',
					array(
						'source' => 'sift-for-woocommerce',
					)
				);

				// If not set, should we maybe return a WP_Error?
				return null;
			}

			$client = new \SiftClient(
				array(
					'api_key'    => $api_key,
					'account_id' => $account_id,
					'version'    => '205', // Hardcode this so new sift versions don't break us.
				)
			);
		}

		return $client;
	}

	/**
	 * Get a Sift_Order object from a WC_Order object.
	 * Allows a small speed up in performance due to caching the orders.
	 *
	 * @param \WC_Order $order The WC_Order object.
	 *
	 * @return Sift_Order The Sift_Order object.
	 */
	public static function get_sift_order_from_wc_order( \WC_Order $order ): Sift_Order {
		if ( ! array_key_exists( $order->get_order_key(), static::$sift_orders ) ) {
			static::$sift_orders[ $order->get_order_key() ] = new Sift_Order( $order );
		}
		return static::$sift_orders[ $order->get_order_key() ];
	}

	// endregion

	/**
	 * Log a message using WooCommerce's logger if available
	 *
	 * @param string $message The message to log.
	 * @param string $level   The logging level (debug, info, error, etc.).
	 * @param array  $context Additional context data to log.
	 *
	 * @return void
	 */
	public static function log( string $message, string $level = 'debug', array $context = array() ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		// Ensure source is always set
		$context['source'] = $context['source'] ?? 'sift-events';

		wc_get_logger()->log( $level, $message, $context );
	}
}
