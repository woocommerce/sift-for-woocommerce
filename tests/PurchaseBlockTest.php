<?php
/**
 * Class Sift_For_WooCommerce_Purchase_Block_Test
 *
 * @package Sift_For_WooCommerce
 */
declare( strict_types=1 );

use Sift_For_WooCommerce\Sift_For_WooCommerce;

/**
 * Tests for Sift_For_WooCommerce::has_new_purchase_block().
 */
class Sift_For_WooCommerce_Purchase_Block_Test extends WP_UnitTestCase {

	/**
	 * Clean up filters after each test to prevent leaks.
	 *
	 * @return void
	 */
	public function tear_down() {
		remove_all_filters( 'sift_for_woocommerce_has_new_purchase_block' );
		parent::tear_down();
	}

	/**
	 * Test a user is not blocked when nothing hooks the filter.
	 *
	 * @return void
	 */
	public function test_user_is_not_blocked_by_default() {
		$this->assertFalse( Sift_For_WooCommerce::has_new_purchase_block( 5 ) );
	}

	/**
	 * Test the block status comes from the filter.
	 *
	 * @return void
	 */
	public function test_block_status_comes_from_the_filter() {
		add_filter(
			'sift_for_woocommerce_has_new_purchase_block',
			function ( $has_block, $user_id ) {
				$this->assertFalse( $has_block );
				return 5 === $user_id;
			},
			10,
			2
		);

		$this->assertTrue( Sift_For_WooCommerce::has_new_purchase_block( 5 ) );
		$this->assertFalse( Sift_For_WooCommerce::has_new_purchase_block( 6 ) );
	}
}
