<?php
/**
 * Prepares the store of a WordPress Playground preview of a pull request build.
 *
 * The Blueprint in .github/workflows/pr-build-live-branch.yml runs this file once, after
 * WooCommerce and the Sift for WooCommerce build are active. The plugin does not load it.
 *
 * It saves no Sift keys, and none must ever be added here: see README.md in this directory.
 */

declare( strict_types=1 );

// A Blueprint step runs as a bare PHP script, so WordPress has to be loaded first.
require_once '/wordpress/wp-load.php';

// WooCommerce would otherwise replace the Blueprint's landing page with its setup wizard.
delete_transient( '_wc_activation_redirect' );
update_option( 'woocommerce_onboarding_profile', array( 'skipped' => true ) );

update_option( 'woocommerce_coming_soon', 'no' );
update_option( 'woocommerce_allow_tracking', 'no' );

// A WooCommerce installed by a Blueprint starts on legacy order storage, which is not what new stores use.
update_option( 'woocommerce_custom_orders_table_enabled', 'yes' );

// Cash on delivery lets an order be placed without a payment account.
update_option( 'woocommerce_cod_settings', array( 'enabled' => 'yes' ) );

WC_Install::create_pages();

$sift_for_woocommerce_product = new WC_Product_Simple();
$sift_for_woocommerce_product->set_name( 'Playground test product' );
$sift_for_woocommerce_product->set_slug( 'playground-test-product' );
$sift_for_woocommerce_product->set_regular_price( '10' );
// Virtual, because the preview store has no shipping zones for checkout to offer.
$sift_for_woocommerce_product->set_virtual( true );
$sift_for_woocommerce_product->set_status( 'publish' );
$sift_for_woocommerce_product->save();

// The Checkout page WooCommerce creates uses the Checkout block, so the shortcode checkout gets its own page.
wp_insert_post(
	array(
		'post_title'   => 'Shortcode Checkout',
		'post_name'    => 'shortcode-checkout',
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_author'  => 1,
		'post_content' => '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
	)
);

// Every event type is off on a new install, and a store that queues no events shows nothing of the
// plugin. This switches on the ones the settings screen of this build lets a merchant switch on.
foreach ( \Sift_For_WooCommerce\WC_Settings_Tab\get_sift_for_woocommerce_events_settings() as $sift_for_woocommerce_setting ) {
	if ( 'checkbox' === ( $sift_for_woocommerce_setting['type'] ?? '' ) && empty( $sift_for_woocommerce_setting['disabled'] ) ) {
		update_option( $sift_for_woocommerce_setting['id'], 'yes' );
	}
}
