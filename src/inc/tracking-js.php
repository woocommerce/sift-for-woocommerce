<?php declare( strict_types=1 );

namespace Sift_For_WooCommerce\Tracking_Js;

/**
 * This function can run multiple times, but will only print once.
 * This will enable easy fallbacks if the correct hooks are not in place.
 *
 * @return  void|null
 */
function print_sift_tracking_js() {
	static $printed_already = false;
	if ( $printed_already ) {
		return null;
	}

	$beacon_key = get_option( 'wc_sift_for_woocommerce_sift_beacon_key' );
	if ( ! $beacon_key ) {
		return null;
	}

	$sift_user_id = null;
	if ( is_user_logged_in() ) {
		$wccom_user_id = get_current_user_id();
		// Translate WC user ID to Sift user ID (wpcom or prefixed wccom ID)
		$sift_user_id = apply_filters( 'sift_for_woocommerce_translate_user_id_for_decision', $wccom_user_id );
	}

	$session_id = WC()->session->get_customer_unique_id();
	?>
<script type="text/javascript">
	var _sift = window._sift = window._sift || [];
	_sift.push([ '_setAccount', '<?php echo esc_js( $beacon_key ); ?>' ]);
	_sift.push([ '_setUserId',  '<?php echo esc_js( $sift_user_id ); ?>' ]);
	_sift.push([ '_setSessionId', '<?php echo esc_js( $session_id ); ?>' ]);
	_sift.push([ '_trackPageview' ]);

	(function() {
		function ls() {
			var e = document.createElement('script');
			e.src = 'https://cdn.sift.com/s.js';
			document.body.appendChild( e );
		}
		if ( window.attachEvent ) {
			window.attachEvent( 'onload', ls );
		} else {
			window.addEventListener( 'load', ls, false );
		}
	})();
</script>
	<?php

	// Now we set the static to true to avoid any duplicates.
	$printed_already = true;
}
