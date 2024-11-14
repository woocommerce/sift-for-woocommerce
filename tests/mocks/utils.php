<?php declare(strict_types=1);

namespace Sift_For_WooCommerce\Sift_For_WooCommerce\Tests\Mocks\Utils;

/**
 * Build an object (or array) which represents the payment method returned by the Stripe API.
 *
 * @param array   $config   An array of overrides to use custom values if desired.
 * @param boolean $as_array If true, the return will be an array instead of an object.
 *
 * @return object|array An array or object depending on the `$as_array` argument.
 */
function build_mock_stripe_payment_method_object( array $config, bool $as_array = false ): object|array {
	if ( $as_array ) {
		return array(
			'card'       => array(
				'last4'   => $config['last4'] ?? '0000',
				'iin'     => $config['iin'] ?? '000000',
				'checks'  => $config['checks'] ?? array(
					'cvc_check'                 => 'OK',
					'address_line1_check'       => 'OK',
					'address_postal_code_check' => 'OK',
				),
				'wallet'  => array(
					'type' => 'crypto',
				),
				'funding' => 'card funding',
				'brand'   => 'FakeCard',
			),
			'sepa_debit' => array(
				'mandate' => 'sepa direct debit mandate code',
			),
		);
	}
	return (object) array(
		'card'       => (object) array(
			'last4'   => $config['last4'] ?? '0000',
			'iin'     => $config['iin'] ?? '000000',
			'checks'  => $config['checks'] ?? (object) array(
				'cvc_check'                 => 'OK',
				'address_line1_check'       => 'OK',
				'address_postal_code_check' => 'OK',
			),
			'wallet'  => (object) array(
				'type' => 'crypto',
			),
			'funding' => 'card funding',
			'brand'   => 'FakeCard',
		),
		'sepa_debit' => (object) array(
			'mandate' => 'sepa direct debit mandate code',
		),
	);
}