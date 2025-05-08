<?php declare( strict_types=1 );

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

namespace Sift_For_WooCommerce\Sift_Events;

require_once __DIR__ . '/class-sift-event-types.php';
require_once __DIR__ . '/normalizers/sift-property.php';
require_once __DIR__ . '/normalizers/sift-payment-method.php';
require_once __DIR__ . '/normalizers/sift-verification-status.php';
require_once __DIR__ . '/normalizers/sift-payment-gateway.php';
require_once __DIR__ . '/normalizers/sift-order.php';
require_once __DIR__ . '/sift-events-validator.php';

use Sift_For_WooCommerce\Sift_Events_Types\Sift_Event_Types;
use WC_Order_Item_Product;

use Sift_For_WooCommerce\Sift_Order;
use Sift_For_WooCommerce\Sift\SiftEventsValidator;
use Sift_For_WooCommerce\Sift_For_WooCommerce;
use WC_Product;

/**
 * Class Events
 */
class Events {
	public static $to_send = array();

	const SUPPORTED_WOO_ORDER_STATUS_CHANGES = array(
		'pending',
		'processing',
		'on-hold',
		'completed',
		'cancelled',
		'refunded',
		'failed',
	);

	/**
	 * Set up the integration hooks for messages we want to send to Sift.
	 *
	 * @return void
	 */
	public static function init_hooks(): void {
		add_action( 'wp_logout', array( static::class, 'logout' ), 100 );
		add_action( 'wp_login', array( static::class, 'login_success' ), 100, 2 );
		add_action( 'wp_login_failed', array( static::class, 'login_failure' ), 100, 2 );
		add_action( 'user_register', array( static::class, 'create_account' ), 100 );
		add_action( 'profile_update', array( static::class, 'update_account' ), 100, 3 );
		add_action( 'wp_set_password', array( static::class, 'update_password' ), 100, 2 );
		add_action( 'woocommerce_add_to_cart', array( static::class, 'add_to_cart' ), 100 );
		add_action( 'woocommerce_remove_cart_item', array( static::class, 'remove_item_from_cart' ), 100, 2 );
		add_action( 'woocommerce_new_order', array( static::class, 'create_order' ), 100, 2 );
		add_action( 'woocommerce_update_order', array( static::class, 'update_or_create_order' ), 100, 2 );
		add_action( 'woocommerce_applied_coupon', array( static::class, 'add_promotion' ), 100, 2 );

		/**
		 * We need to break this out into separate actions so we have the $status_transition available.
		 *
		 * This limits the number of supported status transitions so if we have an unsupported transition we need to
		 * log it.
		 */
		foreach ( self::SUPPORTED_WOO_ORDER_STATUS_CHANGES as $status ) {
			add_action( 'woocommerce_order_status_' . $status, array( static::class, 'change_order_status' ), 100, 3 );
		}
		// For unsupported actions.
		add_action( 'woocommerce_order_status_changed', array( static::class, 'maybe_log_change_order_status' ), 100, 3 );

		/**
		 * This action merged in to WooCommerce and shipped via 8.8.0
		 * https://github.com/woocommerce/woocommerce/pull/45146
		 */
		add_action( 'woocommerce_guest_session_to_user_id', array( static::class, 'link_session_to_user' ), 10, 2 );

		// On shutdown, send any queued events.
		add_action( 'shutdown', array( static::class, 'send' ) );
	}

	/**
	 * Adds logout event
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/logout
	 *
	 * @param string $user_id User ID.
	 *
	 * @return void
	 */
	public static function logout( string $user_id ): void {
		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$logout ) ) {
			return;
		}

		self::add(
			'$logout',
			array(
				'$user_id' => self::format_user_id( intval( $user_id ) ),
				'$browser' => self::get_client_browser(), // alternately, `$app` for details of the app if not a browser.
				'$ip'      => self::get_client_ip(),
				'$time'    => intval( 1000 * microtime( true ) ),
			)
		);
	}

	/**
	 * Adds $add_promotion event
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/add-promotion
	 *
	 * @param string $coupon_code Coupon used.
	 *
	 * @return void
	 */
	public static function add_promotion( string $coupon_code ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$add_promotion ) ) {
			return;
		}

		$user       = wp_get_current_user();
		$properties = array(
			'$user_id'    => self::format_user_id( $user->ID ?? 0 ),
			'$session_id' => \WC()->session?->get_customer_unique_id() ?? '',
			'$promotions' => array(
				array(
					'$promotion_id' => $coupon_code,
					'$status'       => '$success',
				),
			),
			'$ip'         => self::get_client_ip(),
			'$browser'    => self::get_client_browser(),
			'$time'       => intval( 1000 * microtime( true ) ),
		);

		try {
			SiftEventsValidator::validate_add_promotion( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add( Sift_Event_Types::$add_promotion, $properties );
	}

	/**
	 * Adds the login success event
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/login
	 *
	 * @param string $username Name of the user.
	 * @param object $user     User object.
	 *
	 * @return void
	 */
	public static function login_success( string $username, object $user ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$login ) ) {
			return;
		}

		$properties = array(
			'$user_id'       => self::format_user_id( $user->ID ),
			'$login_status'  => '$success',
			'$session_id'    => \WC()->session?->get_customer_unique_id() ?? '',
			'$user_email'    => $user->user_email ?? null,
			'$browser'       => self::get_client_browser(), // alternately, `$app` for details of the app if not a browser.
			'$username'      => $username,
			'$account_types' => $user->roles,
			'$ip'            => self::get_client_ip(),
			'$time'          => intval( 1000 * microtime( true ) ),
		);

		if ( empty( $properties['$session_id'] ) ) {
			unset( $properties['$session_id'] );
		}

		try {
			SiftEventsValidator::validate_login( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add( Sift_Event_Types::$login, $properties );
	}

	/**
	 * Adds the login failure event
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/login
	 *
	 * @param string    $username Username.
	 * @param \WP_Error $error    The error indicating why the login failed.
	 *
	 * @return void
	 */
	public static function login_failure( string $username, \WP_Error $error ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$login ) ) {
			return;
		}

		$attempted_user = get_user_by( 'login', $username );
		$user_id        = 0;
		if ( is_object( $attempted_user ) ) {
			$user_id = $attempted_user->ID ?? 0;
		}

		switch ( $error->get_error_code() ) {
			case 'invalid_email':
			case 'invalid_username':
				$failure_reason = '$account_unknown';
				break;
			case 'incorrect_password':
				$failure_reason = '$wrong_password';
				break;
			case 'spammer_account':
				$failure_reason = '$account_disabled';
				break;
			default:
				// Only other accepted failure reason is $account_suspended... We shouldn't set the failure reason.
				$failure_reason = null;
		}
		$properties = array(
			'$user_id'      => self::format_user_id( $user_id ),
			'$login_status' => '$failure',
			'$session_id'   => \WC()->session?->get_customer_unique_id() ?? '',
			'$browser'      => self::get_client_browser(), // alternately, `$app` for details of the app if not a browser.
			'$username'     => $username,
			'$ip'           => self::get_client_ip(),
			'$time'         => intval( 1000 * microtime( true ) ),
		);

		try {
			SiftEventsValidator::validate_login( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		if ( ! empty( $failure_reason ) ) {
			$properties['$failure_reason'] = $failure_reason;
		}

		self::add( Sift_Event_Types::$login, $properties );
	}

	/**
	 * Adds account creation event
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/create-account
	 *
	 * @param string $user_id User ID.
	 *
	 * @return void
	 */
	public static function create_account( string $user_id ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$create_account ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );

		$properties = array(
			'$user_id'          => self::format_user_id( $user->ID ),
			'$session_id'       => \WC()->session?->get_customer_unique_id() ?? '',
			'$user_email'       => $user->user_email ? $user->user_email : null,
			'$name'             => $user->display_name,
			'$phone'            => $user ? get_user_meta( $user->ID, 'billing_phone', true ) : null,
			// '$referrer_user_id' => ??? -- required for detecting referral fraud, but non-standard to woocommerce.
			'$payment_methods'  => self::get_customer_payment_methods( $user->ID ),
			'$billing_address'  => self::get_customer_address( $user->ID, 'billing' ),
			'$shipping_address' => self::get_customer_address( $user->ID, 'shipping' ),
			'$browser'          => self::get_client_browser(),
			'$account_types'    => $user->roles,
			'$site_domain'      => wp_parse_url( site_url(), PHP_URL_HOST ),
			'$site_country'     => wc_get_base_location()['country'],
			'$ip'               => self::get_client_ip(),
			'$time'             => intval( 1000 * microtime( true ) ),
		);

		try {
			SiftEventsValidator::validate_create_account( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add(
			Sift_Event_Types::$create_account,
			$properties
		);
	}

	/**
	 * Adds event for an account getting updated
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/update-account
	 *
	 * @param string        $user_id       User's ID.
	 * @param \WP_User|null $old_user_data The old user data.
	 * @param array|null    $new_user_data The new user data.
	 *
	 * @return void
	 */
	public static function update_account( string $user_id, ?\WP_User $old_user_data = null, ?array $new_user_data = null ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$update_account ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );

		// check if the password changed
		if ( ! empty( $new_user_data['user_pass'] ) && $old_user_data->user_pass !== $new_user_data['user_pass'] ) {
			self::update_password( '', $user_id );
		}

		$properties = array(
			'$user_id'          => self::format_user_id( $user->ID ),
			'$user_email'       => $user->user_email ? $user->user_email : null,
			'$name'             => $user->display_name,
			'$phone'            => $user ? get_user_meta( $user->ID, 'billing_phone', true ) : null,
			// '$referrer_user_id' => ??? -- required for detecting referral fraud, but non-standard to woocommerce.
			'$payment_methods'  => self::get_customer_payment_methods( $user->ID ),
			'$billing_address'  => self::get_customer_address( $user->ID, 'billing' ),
			'$shipping_address' => self::get_customer_address( $user->ID, 'shipping' ),
			'$browser'          => self::get_client_browser(),
			'$account_types'    => $user->roles,
			'$site_domain'      => wp_parse_url( site_url(), PHP_URL_HOST ),
			'$site_country'     => wc_get_base_location()['country'],
			'$ip'               => self::get_client_ip(),
			'$time'             => intval( 1000 * microtime( true ) ),
		);

		if ( empty( $properties['$payment_methods'] ) ) {
			unset( $properties['$payment_methods'] );
		}

		try {
			SiftEventsValidator::validate_update_account( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add( Sift_Event_Types::$update_account, $properties );
	}

	/**
	 * Notification of a password change.
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/update-password
	 *
	 * @param string $new_password The new password in plaintext. Do not use this.
	 * @param string $user_id      User's ID.
	 *
	 * @return void
	 */
	public static function update_password( string $new_password, string $user_id ): void {

		// We are immediately setting this to null, so that it is not inadvertently shared or disclosed.
		$new_password = null;

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$update_password ) ) {
			return;
		}

		$user = get_user_by( 'id', $user_id );

		$properties = array(
			'$user_id'      => self::format_user_id( $user->ID ),
			'$reason'       => '$user_update', // Can alternately be `$forgot_password` or `$forced_reset` -- no real way to set those yet.
			'$status'       => '$success', // This action only fires after the change is done.
			'$browser'      => self::get_client_browser(),
			'$site_domain'  => wp_parse_url( site_url(), PHP_URL_HOST ),
			'$site_country' => wc_get_base_location()['country'],
			'$ip'           => self::get_client_ip(),
			'$time'         => intval( 1000 * microtime( true ) ),
		);

		try {
			SiftEventsValidator::validate_update_password( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add( Sift_Event_Types::$update_password, $properties );
	}

	/**
	 * Transition from the prior session id to the user's customer id.
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/link-session-to-user
	 *
	 * @param string $session_id User's former session id.
	 * @param string $user_id    User's id.
	 *
	 * @return void
	 */
	public static function link_session_to_user( string $session_id, string $user_id ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$link_session_to_user ) ) {
			return;
		}

		$properties = array(
			'$user_id'    => self::format_user_id( intval( $user_id ) ),
			'$session_id' => $session_id,
			'$ip'         => self::get_client_ip(),
			'$time'       => intval( 1000 * microtime( true ) ),
		);

		try {
			SiftEventsValidator::validate_link_session_to_user( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add( Sift_Event_Types::$link_session_to_user, $properties );
	}

	/**
	 * Adds event for item added to cart
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/add-item-to-cart
	 *
	 * @param string $cart_item_key The Cart Key.
	 *
	 * @return void
	 */
	public static function add_to_cart( string $cart_item_key ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$add_item_to_cart ) ) {
			return;
		}

		$cart_item = \WC()->cart->get_cart_item( $cart_item_key );
		// phpcs:ignore
		/** @var WC_Product $product */
		$product = $cart_item['data'] ?? null;
		$user    = wp_get_current_user();

		if ( ! $product ) {
			return;
		}

		$properties = array(
			'$user_id'      => self::format_user_id( $user->ID ?? 0 ),
			'$user_email'   => $user->user_email ?? null,
			'$session_id'   => \WC()->session?->get_customer_unique_id() ?? '',
			'$item'         => array(
				'$item_id'       => (string) $cart_item_key,
				'$sku'           => $product->get_sku(),
				'$product_title' => $product->get_title(),
				'$price'         => self::get_transaction_micros( floatval( $product->get_price() ) ),
				'$currency_code' => get_woocommerce_currency(),
				'$quantity'      => $cart_item['quantity'],
				'$category'      => self::get_product_category( $product ),
				'$tags'          => wp_list_pluck( get_the_terms( $product->get_id(), 'product_tag' ), 'name' ),
			),
			'$browser'      => self::get_client_browser(),
			'$site_domain'  => wp_parse_url( site_url(), PHP_URL_HOST ),
			'$site_country' => wc_get_base_location()['country'],
			'$ip'           => self::get_client_ip(),
			'$time'         => intval( 1000 * microtime( true ) ),
		);

		try {
			SiftEventsValidator::validate_add_item_to_cart( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add(
			Sift_Event_Types::$add_item_to_cart,
			$properties
		);
	}

	/**
	 * Adds event for item removed from cart
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/remove-item-from-cart
	 *
	 * @param string   $cart_item_key The key of the cart item.
	 * @param \WC_Cart $cart          The WC_Cart object.
	 *
	 * @return void
	 */
	public static function remove_item_from_cart( string $cart_item_key, \WC_Cart $cart ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$remove_item_from_cart ) ) {
			return;
		}

		$cart_item = $cart->get_cart_item( $cart_item_key );
		$product   = $cart_item['data'] ?? null;
		if ( empty( $product ) ) {
			// The item removed from cart no longer exists as a product or has missing product data. Skip sending this event to Sift.
			return;
		}
		$user = wp_get_current_user();

		$properties = array(
			'$user_id'      => self::format_user_id( $user->ID ?? 0 ),
			'$user_email'   => $user->user_email ? $user->user_email : null,
			'$session_id'   => \WC()->session?->get_customer_unique_id() ?? '',
			'$item'         => array(
				'$item_id'       => (string) $product->get_id(),
				'$sku'           => $product->get_sku(),
				'$product_title' => $product->get_title(),
				'$price'         => self::get_transaction_micros( floatval( $product->get_price() ) ),
				'$currency_code' => get_woocommerce_currency(),
				'$quantity'      => $cart_item['quantity'],
				'$category'      => self::get_product_category( $product ),
				'$tags'          => wp_list_pluck( get_the_terms( $product->get_id(), 'product_tag' ), 'name' ),
			),
			'$browser'      => self::get_client_browser(),
			'$site_domain'  => wp_parse_url( site_url(), PHP_URL_HOST ),
			'$site_country' => wc_get_base_location()['country'],
			'$ip'           => self::get_client_ip(),
			'$time'         => intval( 1000 * microtime( true ) ),
		);

		self::add( Sift_Event_Types::$remove_item_from_cart, $properties );
	}

	/**
	 * Adds event for order creation
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/create-order
	 *
	 * @param string    $order_id Order id.
	 * @param \WC_Order $order    The Order object.
	 *
	 * @return void
	 */
	public static function create_order( string $order_id, \WC_Order $order ): void {
		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$create_order ) ) {
			return;
		}

		static::update_or_create_order( $order_id, $order, true );
	}

	/**
	 * Adds event for order creation/update.
	 *
	 * The $create_order and $update_order events are identical.  When an $update_order event is called it will overwrite
	 * any existing $create_order event with the same $order_id, so we'll combine the two into a single function.
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/update-order
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/create-order
	 *
	 * @param string    $order_id     Order id.
	 * @param \WC_Order $order        The Order object.
	 * @param boolean   $create_order True if this is called as part of the order creation.
	 *
	 * @return void
	 */
	public static function update_or_create_order( string $order_id, \WC_Order $order, bool $create_order = false ): void {
		// Check for unsupported statuses and log error
		if ( ! in_array( $order->get_status(), self::SUPPORTED_WOO_ORDER_STATUS_CHANGES, true ) ) {
			Sift_For_WooCommerce::log(
				sprintf( 'Unsupported status change from cancelled to %s', $order->get_status() ),
				'error',
				array( 'source' => 'sift-order-events' )
			);
			return;
		}

		$event = $create_order ? Sift_Event_Types::$create_order : Sift_Event_Types::$update_order;

		if ( ! Sift_Event_Types::can_event_be_sent( $event ) ) {
			return;
		}

		$sift_order = Sift_For_WooCommerce::get_sift_order_from_wc_order( $order );

		$browser = self::get_client_browser();
		$ip      = $order->get_customer_ip_address() ?? self::get_client_ip();

		$properties = array(
			'$user_id'            => self::format_user_id( $order->get_user_id() ),
			'$session_id'         => WC()->session?->get_customer_unique_id() ?? '',
			'$order_id'           => $order_id,
			'$user_email'         => $order->get_billing_email(),
			'$verification_phone_number'
				=> str_starts_with( $order->get_billing_phone(), '+' ) ? preg_replace( '/[^0-9+]/', '', $order->get_billing_phone() ) : null,
			'$amount'             => self::get_transaction_micros( floatval( $order->get_total() ) ),
			'$payment_methods'    => $sift_order->get_payment_methods(),
			'$currency_code'      => $order->get_currency(),
			'$billing_address'    => self::get_order_address( $order_id, 'billing' ),
			'$shipping_address'   => self::get_order_address( $order_id, 'shipping' ),
			'$expedited_shipping' => false,
			'$items'              => array(),
			'$browser'            => $browser,
			'$ip'                 => $ip,
			'$time'               => intval( 1000 * microtime( true ) ),
		);

		/**
		* Add the meta data.
		*
		* @var WC_Order_Item_Product $item
		*/
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();

			if ( ! ( $product instanceof WC_Product ) ) {
				Sift_For_WooCommerce::log(
					sprintf( 'Product cannot be found for WC_Order_Item_Product: %s', $item->get_id() ),
					'debug',
					array( 'source' => 'sift-order-product' )
				);
				continue;
			}

			$sku = empty( $product->get_sku() ) ? $product->get_id() : $product->get_sku();

			// Construct item with ONLY fields specified in Sift API documentation.
			$properties['$items'][] = array(
				'$item_id'       => (string) $sku,
				'$product_title' => $product->get_name(),
				'$price'         => self::get_transaction_micros( floatval( $product->get_price() ) ),
				'$quantity'      => intval( $item->get_quantity() ),
				'$currency_code' => $order->get_currency(),
				'$category'      => self::get_product_category( $product ) ? self::get_product_category( $product ) : 'Uncategorized',
			);
		}

		// Detect free orders.
		$is_free_order = self::is_free_order( $order );

		// Handle free orders by making required adjustments to properties.
		if ( $is_free_order ) {
			// Remove payment methods entirely for free orders.
			unset( $properties['$payment_methods'] );
		}

		// If session ID is empty, create one for this order.
		if ( empty( $properties['$session_id'] ) ) {
			$properties['$session_id'] = 'order_session_' . $order_id;
		}

		try {
			SiftEventsValidator::validate_create_or_update_order( $properties );
		} catch ( \Exception $e ) {
			// Log validation error in detail.
			Sift_For_WooCommerce::log(
				sprintf( 'Validation error for %s event: %s', $event, $e->getMessage() ),
				'error',
				array(
					'source'     => 'sift-order-events',
					'order_id'   => $order_id,
					'properties' => wp_json_encode( $properties ),
				)
			);
			return;
		}

		// Add event to queue.
		self::add( $event, $properties );
	}

	/**
	 * Determine if an order is a free order (zero total)
	 *
	 * @param \WC_Order $order The order to check.
	 *
	 * @return boolean True if the order is free.
	 */
	public static function is_free_order( \WC_Order $order ): bool {
		$total = $order->get_total();

		// If the total is 0 or 0.00, it's a free order.
		return 0.0 === $total;
	}

	/**
	 * Adds the event for transaction
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/transaction
	 *
	 * @param \WC_Order $order            Order.
	 * @param string    $status           Transaction status.
	 * @param string    $transaction_type Transaction type.
	 *
	 * @return void
	 */
	public static function transaction( \WC_Order $order, string $status, string $transaction_type ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$transaction ) ) {
			return;
		}

		$properties = array(
			'$user_id'            => self::format_user_id( $order->get_user_id() ),
			'$session_id'         => \WC()->session?->get_customer_unique_id() ?? '',
			'$amount'             => self::get_transaction_micros( floatval( $order->get_total() ) ), // Gotta multiply it up to give an integer.
			'$currency_code'      => $order->get_currency(),
			'$order_id'           => (string) $order->get_id(),
			'$transaction_type'   => $transaction_type,
			'$transaction_status' => $status,
			'$time'               => intval( 1000 * microtime( true ) ),
		);

		try {
			SiftEventsValidator::validate_transaction( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add( Sift_Event_Types::$transaction, $properties );
	}

	/**
	 * Adds the event for the order status update
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/order-status
	 *
	 * @param string    $order_id          Order ID.
	 * @param \WC_Order $order             The order object.
	 * @param array     $status_transition Status transition data.
	 *                                     type: array<string $from, string $to, string $note, boolean $manual>.
	 *
	 * @return void
	 */
	public static function change_order_status( string $order_id, \WC_Order $order, array $status_transition ): void {
		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$order_status ) ) {
			return;
		}

		// Check if this is a free order and if we should send a create_order event too.
		$is_free_order = self::is_free_order( $order );

		// For free orders that are just being created (moving to pending/processing), send the create_order event.
		if ( $is_free_order &&
			in_array( $status_transition['to'], array( 'pending', 'processing' ), true ) &&
			Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$create_order ) ) {

			// This will trigger the create_order event for this free order.
			self::create_order( $order_id, $order );
		}

		$properties = array(
			'$user_id'      => self::format_user_id( $order->get_user_id() ),
			'$session_id'   => \WC()->session?->get_customer_unique_id() ?? '',
			'$order_id'     => $order_id,
			'$source'       => $status_transition['manual'] ? '$manual_review' : '$automated',
			'$description'  => $status_transition['note'],
			'$browser'      => self::get_client_browser(),
			'$site_country' => wc_get_base_location()['country'],
			'$site_domain'  => wp_parse_url( site_url(), PHP_URL_HOST ),
			'$ip'           => self::get_client_ip(),
			'$time'         => intval( 1000 * microtime( true ) ),
		);

		// Add the $order_status property (based on the status transition).
		switch ( $status_transition['to'] ) {
			case 'pending':
			case 'processing':
			case 'on-hold':
				$properties['$order_status'] = '$held';
				self::transaction( $order, '$pending', '$sale' );
				break;
			case 'completed':
				$properties['$order_status'] = '$fulfilled';

				// When the status is completed, we also queue the $transaction event
				self::transaction( $order, '$success', '$sale' );
				break;
			case 'refunded':
				self::transaction( $order, '$failure', '$refund' );
				$properties['$order_status'] = '$canceled';
				break;
			case 'cancelled':
			case 'failed':
				self::transaction( $order, '$failure', '$sale' );
				$properties['$order_status'] = '$canceled';
				break;
		}

		// If the session ID is empty, create one from the order ID.
		// This ensures we always have a session ID even for admin-created orders.
		if ( empty( $properties['$session_id'] ) ) {
			$properties['$session_id'] = md5( 'order_session_' . $order_id );
		}

		// For manual reviews add the user as the `$analyst`.
		if ( $status_transition['manual'] ?? false ) {
			$properties['$analyst'] = wp_get_current_user()->user_login;
		}

		try {
			SiftEventsValidator::validate_order_status( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add( Sift_Event_Types::$order_status, $properties );
	}

	/**
	 * Adds and event for chargebacks
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/reserved-events/chargeback
	 *
	 * @param string    $order_id          Order ID.
	 * @param \WC_Order $order             The order object.
	 * @param string    $chargeback_reason Chargeback data.
	 *
	 * @return void
	 */
	public static function chargeback( string $order_id, \WC_Order $order, string $chargeback_reason ): void {

		if ( ! Sift_Event_Types::can_event_be_sent( Sift_Event_Types::$chargeback ) ) {
			return;
		}

		// Assemble the properties for the chargeback event.
		$properties = array(
			'$order_id'          => $order_id,
			'$user_id'           => self::format_user_id( $order->get_customer_id() ),
			'$chargeback_reason' => $chargeback_reason,
			'$ip'                => self::get_client_ip(),
		);

		try {
			SiftEventsValidator::validate_chargeback( $properties );
		} catch ( \Exception $e ) {
			Sift_For_WooCommerce::log( esc_html( $e->getMessage() ), 'error' );
			return;
		}

		self::add( Sift_Event_Types::$chargeback, $properties );
	}

	/**
	 * Logs when an unsupported order status change occurs.
	 * This is called for order status transitions not explicitly handled by our supported statuses.
	 *
	 * @param string $order_id   Order ID.
	 * @param string $old_status The old order status.
	 * @param string $new_status The new order status.
	 *
	 * @return void
	 */
	public static function maybe_log_change_order_status( string $order_id, string $old_status, string $new_status ): void {
		// Check if this is a supported status change that would be caught by our other hook.
		if ( in_array( $new_status, self::SUPPORTED_WOO_ORDER_STATUS_CHANGES, true ) ) {
			// This status change will be handled by the dedicated hook, so we can skip.
			return;
		}

		// Log the unsupported status change.
		Sift_For_WooCommerce::log(
			sprintf( 'Unsupported order status change for order %s from %s to %s', $order_id, $old_status, $new_status ),
			'info',
			array( 'source' => 'sift-order-events' )
		);
	}

	/**
	 * Get any new abuse decisions for a user, and apply it if needed.
	 *
	 * @param string $sift_user_id  The user ID sent to Sift, usually the WPCOM user ID.
	 * @param string $wccom_user_id The WooCommerce user ID.
	 *
	 * @return string|null
	 */
	public static function get_decision( string $sift_user_id, string $wccom_user_id ): ?string {
		$client = \Sift_For_WooCommerce\Sift_For_WooCommerce::get_api_client();
		if ( empty( $client ) ) {
			Sift_For_WooCommerce::log(
				'Failed to get the Sift API client.',
				'error',
				array(
					'source' => 'sift-events',
				)
			);
			return null;
		}

		// Get the abuse decision from Sift.
		$user_decisions_response = $client->getUserDecisions( $sift_user_id );

		$decision_id = null;

		// If $user_decisions_response->body['decisions'] is empty, log the info.
		if ( empty( $user_decisions_response->body['decisions'] ) ) {
			Sift_For_WooCommerce::log(
				'No decisions found for user',
				'info',
				array(
					'source'       => 'sift-events',
					'sift_user_id' => $sift_user_id,
				)
			);
			return null;
		}

		// Extract the decision ID for payment abuse if it exists.
		if ( isset( $user_decisions_response->body['decisions']['payment_abuse']['decision']['id'] ) ) {
			$decision_id = $user_decisions_response->body['decisions']['payment_abuse']['decision']['id'];
		}

		return self::apply_decision( $decision_id, $wccom_user_id );
	}

	/**
	 * Apply the decision to the filter.
	 * This is a helper function to apply the decision to the filter.
	 *
	 * @param string $decision_id The decision ID.
	 * @param string $user_id     The user ID.
	 *
	 * @return string|null The decision ID or null if no decision ID was provided.
	 */
	public static function apply_decision( string $decision_id, string $user_id ): ?string {
		\apply_filters( 'sift_decision_received', null, $decision_id, $user_id );
		return $decision_id;
	}

	/**
	 * Add a Sift Event to the queue.
	 *
	 * @param string $event      The event to enqueue.
	 * @param array  $properties The properties to send with the event.
	 *
	 * @return void
	 */
	public static function add( string $event, array $properties ): void {
		// Give a chance for the platform to modify the data (and add potentially new custom data)
		$properties = apply_filters( 'sift_for_woocommerce_pre_send_event_properties', $properties, $event );

		if ( empty( $properties ) ) {
			return;
		}

		self::$to_send[] = array(
			'event'      => $event,
			'properties' => array_filter(
				$properties,
				function ( $value ) {
					return null !== $value && '' !== $value;
				}
			),
		);
	}

	/**
	 * Return how many events have been registered thus far and are queued up to send.
	 *
	 * @return integer
	 */
	private static function count(): int {
		return count( self::$to_send );
	}

	/**
	 * Send off the events, if any.
	 *
	 * @return boolean
	 */
	public static function send(): bool {
		if ( self::count() > 0 ) {
			// Log all events that are about to be sent
			if ( function_exists( 'wc_get_logger' ) ) {
				$event_types = array_map(
					function ( $entry ) {
						return $entry['event'];
					},
					self::$to_send
				);
			}

			$client = Sift_For_WooCommerce::get_api_client();
			if ( empty( $client ) ) {
				Sift_For_WooCommerce::log(
					'Failed to send events to Sift',
					'error',
					array(
						'source' => 'sift-for-woocommerce',
						'reason' => 'Failed to get the Sift API client.',
						'events' => self::$to_send,
					)
				);
				return false;
			}

			foreach ( self::$to_send as $entry ) {
				// We need the original user ID to handle the decision locally after events are sent.
				$user_id = $entry['properties']['$user_id'] ?? null;

				$response = $client->track( $entry['event'], $entry['properties'] );

				if ( 200 !== $response->httpStatusCode ) {
					Sift_For_WooCommerce::log(
						sprintf( 'Sent `%s`, Error %d: %s', $entry['event'], $response->apiStatus, $response->apiErrorMessage ),
						'error',
						array(
							'source'     => 'sift-for-woocommerce',
							'properties' => $entry['properties'],
							'response'   => $response,
						)
					);
				}
			}

			// Now that it's sent, clear the $to_send static in case it was run manually.
			self::$to_send = array();

			// Get the user ID we sent to Sift from the properties.
			$sift_user_id = $entry['properties']['$user_id'] ?? null;

			// Get the current decision since events have been sent and could have changed the decision.
			// This is only done if the user ID is set.
			if ( $sift_user_id ) {
				// Get the decision for the user and apply if needed.
				self::get_decision( $sift_user_id, $sift_user_id );
			}

			return true;
		}
		return false;
	}

	/**
	 * Taken from core `get_unsafe_client_ip()` method.
	 *
	 * @return string The detected IP address of the user.
	 */
	private static function get_client_ip(): string {
		$client_ip = false;

		// In order of preference, with the best ones for this purpose first.
		$address_headers = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $address_headers as $header ) {
			if ( array_key_exists( $header, $_SERVER ) ) {
				/*
				 * HTTP_X_FORWARDED_FOR can contain a chain of comma-separated
				 * addresses. The first one is the original client. It can't be
				 * trusted for authenticity, but we don't need to for this purpose.
				 */
				$address_chain = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
				$client_ip     = trim( $address_chain[0] );

				break;
			}
		}

		return $client_ip;
	}

	/**
	 * Output the browser details as specified in Sift API docs.
	 *
	 * @return array The user agent, languages accepted, and current store language.
	 */
	private static function get_client_browser(): array {
		return array(
			'$user_agent'       => sanitize_title( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
			'$accept_language'  => sanitize_key( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-US' ) ), // default to en-US if not set (i.e., a server action)
			'$content_language' => get_locale(),
		);
	}

	/**
	 * Get the address details in the format that Sift expects.
	 *
	 * @param integer $user_id The User / Customer ID.
	 * @param string  $type    Either `billing` or `shipping`.
	 * @param string  $context Either `view` or `edit`.
	 *
	 * @return array|null
	 * @throws \Exception If a customer cannot be read/ found and $data is set.
	 */
	private static function get_customer_address( int $user_id, string $type = 'billing', string $context = 'view' ): ?array {
		$customer = new \WC_Customer( $user_id );

		switch ( strtolower( $type ) ) {
			case 'billing':
				$address = $customer->get_billing( $context );
				break;
			case 'shipping':
				$address = $customer->get_shipping( $context );
				break;
			default:
				return null;
		}

		return array(
			'$name'      => $address['first_name'] . ' ' . $address['last_name'],
			'$phone'     => $address['phone'],
			'$address_1' => $address['address_1'],
			'$address_2' => $address['address_2'],
			'$city'      => $address['city'],
			'$region'    => $address['state'],
			'$country'   => $address['country'],
			'$zipcode'   => $address['postcode'],
		);
	}

	/**
	 * Get the address details in the format that Sift expects.
	 *
	 * @param string $order_id The User / Customer ID.
	 * @param string $type     Either `billing` or `shipping`.
	 *
	 * @return array|null
	 */
	private static function get_order_address( string $order_id, string $type = 'billing' ): ?array {
		$order = wc_get_order( $order_id );

		if ( empty( $order ) ) {
			return null;
		}

		switch ( strtolower( $type ) ) {
			// WC_Order doesn't have the same `->get_billing` and `->get_shipping()` that the Customer object
			// has, so we call this way instead.  It also assumes `view`
			case 'billing':
				return array(
					'$name'      => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
					'$phone'     => $order->get_billing_phone(),
					'$address_1' => $order->get_billing_address_1(),
					'$address_2' => $order->get_billing_address_2(),
					'$city'      => $order->get_billing_city(),
					'$region'    => $order->get_billing_state(),
					'$country'   => $order->get_billing_country(),
					'$zipcode'   => $order->get_billing_postcode(),
				);
			case 'shipping':
				return array(
					'$name'      => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
					'$phone'     => $order->get_billing_phone(), // Orders don't have shipping phone, use billing phone
					'$address_1' => $order->get_shipping_address_1(),
					'$address_2' => $order->get_shipping_address_2(),
					'$city'      => $order->get_shipping_city(),
					'$region'    => $order->get_shipping_state(),
					'$country'   => $order->get_shipping_country(),
					'$zipcode'   => $order->get_shipping_postcode(),
				);
			default:
				return null;
		}
	}

	/**
	 * Format a user ID for sending to Sift.
	 *
	 * @param integer $user_id The user ID to format.
	 *
	 * @return string The formatted user ID.
	 */
	private static function format_user_id( int $user_id ): string {
		// If user ID is 0 (for guests or not set), return empty string.
		if ( empty( $user_id ) || 0 === $user_id ) {
			return '';
		}

		// Convert to string and return.
		return (string) $user_id;
	}

	/**
	 * Convert a currency amount to micros (1/1,000,000 of the base unit).
	 * This format is required by Sift's API for all currency values.
	 *
	 * @param float $amount The amount to convert to micros.
	 *
	 * @return integer The amount in micros (multiplied by 1,000,000).
	 */
	public static function get_transaction_micros( float $amount ): int {
		return (int) ( $amount * 1000000 );
	}

	/**
	 * Return the hierarchy of the product category
	 *
	 * @param WC_Product $product WooCommerce product.
	 *
	 * @link https://developers.sift.com/docs/curl/events-api/complex-field-types/item
	 *
	 * @return string The formatted product category, or Uncategorized if no category is found.
	 */
	private static function get_product_category( WC_Product $product ): string {

		$category_ids = wc_get_product_cat_ids( $product->get_id() );
		if ( empty( $category_ids ) ) {
			return 'Uncategorized';
		}

		$taxonomy  = 'product_cat'; // Taxonomy for product category
		$terms_ids = $product->get_category_ids();
		// Loop though terms ids (product categories)
		foreach ( $terms_ids as $term_id ) {
			$term_names = array(); // Initialising category array

			// Loop through product category ancestors
			foreach ( get_ancestors( $term_id, $taxonomy ) as $ancestor_id ) {
				// Add the ancestors term names to the category array
				$term_names[] = get_term( $ancestor_id, $taxonomy )->name;
			}
			// Add the product category term name to the category array
			$term_names[] = get_term( $term_id, $taxonomy )->name;

			// Add the formatted ancestors with the product category to main array
			$output[] = implode( ' > ', $term_names );
		}
		// Output the formatted product categories with their ancestors
		return implode( ', ', $output );
	}

	/**
	 * Get the customer payment methods in the format that Sift expects.
	 *
	 * @param integer $user_id The User ID.
	 *
	 * @return array An array of payment methods in Sift format.
	 */
	private static function get_customer_payment_methods( int $user_id ): array {
		// If WC Payment Methods is not available, return empty array
		if ( ! function_exists( 'wc_get_customer_saved_methods_list' ) ) {
			return array();
		}

		$payment_methods = array();
		$saved_methods   = wc_get_customer_saved_methods_list( $user_id );

		if ( ! empty( $saved_methods['payment'] ) ) {
			foreach ( $saved_methods['payment'] as $method ) {
				$payment_method = array(
					'$payment_type'    => '$credit_card',
					'$payment_gateway' => $method['method']['gateway'] ?? '',
					'$card_bin'        => substr( $method['method']['last4'] ?? '', 0, 6 ),
					'$card_last4'      => $method['method']['last4'] ?? '',
				);

				// Add expiration date if available
				if ( ! empty( $method['expires'] ) ) {
					$expiry_parts = explode( '/', $method['expires'] );
					if ( count( $expiry_parts ) === 2 ) {
						$payment_method['$card_expiry_month'] = $expiry_parts[0];
						$payment_method['$card_expiry_year']  = $expiry_parts[1];
					}
				}

				$payment_methods[] = $payment_method;
			}
		}

		return $payment_methods;
	}
}
