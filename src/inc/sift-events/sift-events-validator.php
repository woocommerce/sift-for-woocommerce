<?php declare( strict_types = 1 );

namespace Sift_For_WooCommerce\Sift;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sift object validator.
 */
class SiftEventsValidator {

	const PAYMENT_TYPES = array(
		'$cash',
		'$check',
		'$credit_card',
		'$crypto_currency',
		'$debit_card',
		'$digital_wallet',
		'$electronic_fund_transfer',
		'$financing',
		'$gift_card',
		'$invoice',
		'$in_app_purchase',
		'$money_order',
		'$points',
		'$prepaid_card',
		'$store_credit',
		'$third_party_processor',
		'$voucher',
		'$sepa_credit',
		'$sepa_instant_credit',
		'$sepa_direct_debit',
		'$ach_credit',
		'$ach_debit',
		'$wire_credit',
		'$wire_debit',
	);

	const PAYMENT_GATEWAY = array(
		'$abra',
		'$acapture',
		'$accpet_blue',
		'$adyen',
		'$aeropay',
		'$afex',
		'$affinipay',
		'$affipay',
		'$affirm',
		'$afrivoucher',
		'$afterpay',
		'$airpay',
		'$airwallex',
		'$alipay',
		'$alipay_hk',
		'$allpago',
		'$altapay',
		'$amazon_payments',
		'$ambank_fpx',
		'$amex_checkout',
		'$android_iap',
		'$android_pay',
		'$apg',
		'$aplazo',
		'$apple_iap',
		'$apple_pay',
		'$argus',
		'$asiabill',
		'$astropay',
		'$atome',
		'$atrium',
		'$au_kantan',
		'$authorizenet',
		'$avangate',
		'$balanced',
		'$bancodobrasil',
		'$bancontact',
		'$bancoplural',
		'$banorte',
		'$banrisul',
		'$banwire',
		'$barclays',
		'$bayanpay',
		'$bbcn',
		'$bcb',
		'$beanstream',
		'$belfius',
		'$best_inc',
		'$billdesk',
		'$billpocket',
		'$bitcash',
		'$bitgo',
		'$bitpay',
		'$bizum',
		'$blackhawk',
		'$blik',
		'$blinc',
		'$blockchain',
		'$bluepay',
		'$bluesnap',
		'$bnpparibas',
		'$boacompra',
		'$bob',
		'$boku',
		'$bold',
		'$boletobancario',
		'$boltpay',
		'$bpay',
		'$bradesco',
		'$braintree',
		'$bread',
		'$bridgepay',
		'$brite',
		'$buckaroo',
		'$buckzy',
		'$cadc',
		'$cardconnect',
		'$cardknox',
		'$cashapp',
		'$cashfree',
		'$cashlesso',
		'$cashlib',
		'$catchball',
		'$ccbill',
		'$ccavenue',
		'$ceevo',
		'$cellulant',
		'$cepbank',
		'$chain_commerce',
		'$chase_paymentech',
		'$checkalt',
		'$checkoutcom',
		'$cielo',
		'$circle',
		'$citi',
		'$citizen',
		'$citrus_pay',
		'$clear_junction',
		'$clearbridge',
		'$clearsettle',
		'$clearcommerce',
		'$cleverbridge',
		'$close_brothers',
		'$cloudpayments',
		'$codi',
		'$cofinoga',
		'$coinbase',
		'$coindirect',
		'$coinpayments',
		'$collector',
		'$community_bank_transfer',
		'$commweb',
		'$compropago',
		'$concardis',
		'$conekta',
		'$copo',
		'$credit_union_atlantic',
		'$credorax',
		'$credsystem',
		'$cross_river',
		'$cuentadigital',
		'$culqi',
		'$cybersource',
		'$cryptocapital',
		'$cryptopay',
		'$currencycloud',
		'$customers_bank',
		'$d_barai',
		'$dana',
		'$daopay',
		'$datacash',
		'$dbs_paylah',
		'$dcbank',
		'$decta',
		'$debitway',
		'$deltec',
		'$democracy_engine',
		'$deutsche_bank',
		'$dibs',
		'$digital_river',
		'$digitalpay',
		'$dinero_services',
		'$directa24',
		'$dlocal',
		'$docomo',
		'$doku',
		'$dospara',
		'$dotpay',
		'$dragonpay',
		'$dreftorpay',
		'$dwarkesh',
		'$dwolla',
		'$ebanx',
		'$ecommpay',
		'$ecopayz',
		'$edenred',
		'$edgil_payway',
		'$efecty',
		'$eft',
		'$elavon',
		'$elipa',
		'$emerchantpay',
		'$empcorp',
		'$enets',
		'$epay',
		'$epayeu',
		'$epoch',
		'$epospay',
		'$eprocessing_network',
		'$eps',
		'$esitef',
		'$etana',
		'$euteller',
		'$everypay',
		'$eway',
		'$e_xact',
		'$fastnetwork',
		'$fat_zebra',
		'$fidor',
		'$finix',
		'$finmo',
		'$fintola',
		'$fiserv',
		'$first_atlantic_commerce',
		'$first_data',
		'$flexepin',
		'$flexiti',
		'$fluidpay',
		'$flutterwave',
		'$fpx',
		'$frick',
		'$fxpaygate',
		'$g2apay',
		'$galileo',
		'$gcash',
		'$geoswift',
		'$getnet',
		'$gigadat',
		'$giropay',
		'$globalcollect',
		'$global_payments',
		'$global_payways',
		'$gmo',
		'$gmopg',
		'$gocardless',
		'$gocoin',
		'$google_pay',
		'$google_wallet',
		'$grabpay',
		'$hanmi',
		'$happy_money',
		'$hayhay',
		'$hdfc_fssnet',
		'$heidelpay',
		'$hipay',
		'$humm',
		'$hyperpay',
		'$i2c',
		'$ibok',
		'$ideal',
		'$ifthenpay',
		'$ikajo',
		'$incomm',
		'$incore',
		'$ingenico',
		'$inghomepay',
		'$inovapay',
		'$inovio',
		'$instamojo',
		'$interac',
		'$internetsecure',
		'$interswitch',
		'$intuit_quickbooks_payments',
		'$ipay',
		'$ipay88',
		'$isignthis',
		'$itau',
		'$itelebill',
		'$iugu',
		'$ixopay',
		'$iyzico',
		'$izettle',
		'$jabong',
		'$januar',
		'$jatis',
		'$jeton',
		'$jnfx',
		'$juspay',
		'$kakaopay',
		'$kash',
		'$kbc',
		'$kddi',
		'$kevin',
		'$khipu',
		'$klarna',
		'$knet',
		'$komoju',
		'$konbini',
		'$kopay',
		'$korapay',
		'$kushki',
		'$latamgateway',
		'$latampass',
		'$laybuy',
		'$lean',
		'$lemonway',
		'$letzpay',
		'$lifemiles',
		'$limelight',
		'$linepay',
		'$link4pay',
		'$logon',
		'$mada',
		'$mangopay',
		'$mastercard_payment_gateway',
		'$masterpass',
		'$matera',
		'$maxipago',
		'$maxpay',
		'$maybank',
		'$mcb',
		'$meikopay',
		'$mercadopago',
		'$merchant_esolutions',
		'$merpay',
		'$mfs',
		'$midtrans',
		'$minerva',
		'$mirjeh',
		'$mobile_money',
		'$mockpay',
		'$modo',
		'$moip',
		'$mollie',
		'$momopay',
		'$moneris_solutions',
		'$moneygram',
		'$monoova',
		'$moyasar',
		'$mpesa',
		'$muchbetter',
		'$multibanco',
		'$multicaja',
		'$multiplus',
		'$mvb',
		'$mybank',
		'$myfatoorah',
		'$nanaco',
		'$nanoplazo',
		'$naranja',
		'$naverpay',
		'$neosurf',
		'$net_cash',
		'$netbilling',
		'$netregistry',
		'$neteller',
		'$network_for_good',
		'$nhn_kcp',
		'$nicepay',
		'$ngenius',
		'$nmcryptgate',
		'$nmi',
		'$noble',
		'$noon_payments',
		'$nupay',
		'$ocean',
		'$ogone',
		'$okpay',
		'$omcp',
		'$omise',
		'$onebip',
		'$oneio',
		'$opay',
		'$openpay',
		'$openpaymx',
		'$optile',
		'$optimal_payments',
		'$ovo',
		'$oxxo',
		'$pacypay',
		'$paddle',
		'$pagar_me',
		'$pago_efectivo',
		'$pagoefectivo',
		'$pagofacil',
		'$pagseguro',
		'$paidy',
		'$papara',
		'$paxum',
		'$pay_garden',
		'$pay_zone',
		'$pay4fun',
		'$paybright',
		'$paycase',
		'$paycash',
		'$payco',
		'$paycell',
		'$paydo',
		'$paydoo',
		'$payease',
		'$payeasy',
		'$payeer',
		'$payeezy',
		'$payfast',
		'$payfix',
		'$payflow',
		'$payfort',
		'$paygarden',
		'$paygate',
		'$paygent',
		'$pago24',
		'$pagsmile',
		'$pay2',
		'$payaid',
		'$payfun',
		'$payix',
		'$payjp',
		'$payjunction',
		'$paykun',
		'$paykwik',
		'$paylike',
		'$paymaya',
		'$paymee',
		'$paymentez',
		'$paymentos',
		'$paymentwall',
		'$payment_express',
		'$paymill',
		'$paynl',
		'$payone',
		'$payoneer',
		'$payop',
		'$paypal',
		'$paypal_express',
		'$paypay',
		'$payper',
		'$paypost',
		'$paysafe',
		'$paysafecard',
		'$paysera',
		'$paysimple',
		'$payssion',
		'$paystack',
		'$paystation',
		'$paystrax',
		'$paytabs',
		'$paytm',
		'$paytrace',
		'$paytrail',
		'$paystrust',
		'$paytrust',
		'$payture',
		'$payway',
		'$payu',
		'$payulatam',
		'$payvalida',
		'$payvector',
		'$payza',
		'$payzen',
		'$peach_payments',
		'pep',
		'$perfect_money',
		'$perla_terminals',
		'$picpay',
		'$pinpayments',
		'$pivotal_payments',
		'$pix',
		'$plaid',
		'$planet_payment',
		'$plugandplay',
		'$poli',
		'$posconnect',
		'$ppro',
		'$primetrust',
		'$princeton_payment_solutions',
		'$prisma',
		'$prismpay',
		'$processing',
		'$przelewy24',
		'$psigate',
		'$pubali_bank',
		'$pulse',
		'$pwmb',
		'$qiwi',
		'$qr_code_bt',
		'$quadpay',
		'$quaife',
		'$quickpay',
		'$quickstream',
		'$quikipay',
		'$raberil',
		'$radial',
		'$railsbank',
		'$rakbank',
		'$rakuten_checkout',
		'$rapid_payments',
		'$rapipago',
		'$rappipay',
		'$rapyd',
		'$ratepay',
		'$ravepay',
		'$razorpay',
		'$rbkmoney',
		'$reach',
		'$recurly',
		'$red_dot_payment',
		'$rede',
		'$redpagos',
		'$redsys',
		'$revolut',
		'$rewardspay',
		'$rietumu',
		'$ripple',
		'$rocketgate',
		'$safecharge',
		'$safetypay',
		'$safexpay',
		'$sagepay',
		'$saltedge',
		'$samsung_pay',
		'$santander',
		'$sbi',
		'$sbpayments',
		'$secure_trading',
		'$securepay',
		'$securionpay',
		'$sentbe',
		'$sepa',
		'$sermepa',
		'$servipag',
		'$sezzle',
		'$shopify_payments',
		'$sightline',
		'$signature',
		'$signet',
		'$silvergate',
		'$simpaisa',
		'$simplify_commerce',
		'$skrill',
		'$smart2pay',
		'$smartcoin',
		'$smartpayments',
		'$smbc',
		'$snapscan',
		'$sofort',
		'$softbank_matomete',
		'$solanapay',
		'$splash_payments',
		'$splitit',
		'$spotii',
		'$sps_decidir',
		'$square',
		'$starkbank',
		'$starpayment',
		'$stcpay',
		'$sticpay',
		'$stitch',
		'$stone',
		'$stp',
		'$stripe',
		'$surepay',
		'$swedbank',
		'$synapsepay',
		'$tabapay',
		'$tabby',
		'$tamara',
		'$tapcompany',
		'$tdcanada',
		'$telerecargas',
		'$tfm',
		'$tink',
		'$tipalti',
		'$tnspay',
		'$todopago',
		'$toss',
		'$touchngo',
		'$towah',
		'$tpaga',
		'$transact_pro',
		'$transactive',
		'$transactworld',
		'$transfirst',
		'$transpay',
		'$truelayer',
		'$truemoney',
		'$trust',
		'$trustcommerce',
		'$trustly',
		'$trustpay',
		'$tsys_sierra',
		'$tsys_transit',
		'$tu_compra',
		'$twoc2p',
		'$twocheckout',
		'$undostres',
		'$unlimint',
		'$unionpay',
		'$upay',
		'$usa_epay',
		'$usafill',
		'$utrust',
		'$vantiv',
		'$vapulus',
		'$venmo',
		'$veritrans',
		'$versapay',
		'$verve',
		'$vesta',
		'$viabaloto',
		'$vindicia',
		'$vip_preferred',
		'$virtual_card_services',
		'$virtualpay',
		'$visa',
		'$vme',
		'$vogogo',
		'$volt',
		'$vpos',
		'$watchman',
		'$web_money',
		'$webbilling',
		'$webmoney',
		'$webpay',
		'$webpay_oneclick',
		'$wechat',
		'$wepay',
		'$western_union',
		'$wirecard',
		'$worldpay',
		'$worldspan',
		'$wompi',
		'$wp_cnpapi',
		'$wyre',
		'$xendit',
		'$xfers',
		'$xipay',
		'$yandex_money',
		'$yapily',
		'$yapstone',
		'$zapper',
		'$zenrise',
		'$zer0pay',
		'$zeus',
		'$zgold',
		'$zimpler',
		'$zip',
		'$zipmoney',
		'$zoop',
		'$zotapay',
		'$zooz_paymentsos',
		'$zuora',
		'$2c2p',
	);

	const VERIFICATION_STATUSES = array(
		'$success',
		'$failure',
		'$pending',
	);

	const WALLET_TYPES = array(
		'$crypto',
		'$digital',
		'$fiat',
	);

	const FAILURE_REASONS = array(
		'$already_used',
		'$invalid_code',
		'$not_applicable',
		'$expired',
	);

	const SOCIAL_SIGN_ON_TYPES = array(
		'$facebook',
		'$google',
		'$linkedin',
		'$twitter',
		'$yahoo',
		'$microsoft',
		'$amazon',
		'$apple',
		'$wechat',
		'$github',
		'$other',
	);


	const SUPPORTED_TRANSACTION_STATUS = array(
		'$success',
		'$pending',
		'$failure',
	);

	// See https://developers.sift.com/docs/curl/events-api/reserved-events/transaction
	const SUPPORTED_TRANSACTION_TYPE = array(
		'$sale',
		'$authorize',
		'$capture',
		'$void',
		'$refund',
		'$deposit',
		'$withdrawal',
		'$transfer',
		'$buy',
		'$sell',
		'$send',
		'$receive',
	);

	const ORDER_STATUSES = array(
		'$approved',
		'$canceled',
		'$held',
		'$fulfilled',
		'$returned',
	);

	const CANCELLATION_REASONS = array(
		'$payment_risk',
		'$abuse',
		'$policy',
		'$other',
	);

	/**
	 * This is the main validation function.
	 *
	 * It takes an array of data and a map of validators. (The map is an array of key => callable pairs.)
	 *
	 * @param array $data          The data to validate.
	 * @param array $validator_map The map of validators (key => callable pairs).
	 *
	 * @return true
	 * @throws \Exception If the data is invalid.
	 */
	protected static function validate( $data, array $validator_map ) {
		$default_validators = array(
			'$ip'   => array( __CLASS__, 'validate_ip' ),
			'$time' => 'is_int',
		);
		$validator_map      = array_merge( $default_validators, $validator_map );
		foreach ( $data as $key => $value ) {
			if ( ! isset( $validator_map[ $key ] ) && '$' === $key[0] ) {
				throw new \Exception( esc_html( "Unknown key: $key" ) );
			}
			$validator = $validator_map[ $key ] ?? null;
			if ( is_callable( $validator ) ) {
				try {
					if ( true !== $validator( $value ) ) {
						throw new \Exception( esc_html( 'validator returned non-true value' ) );
					}
				} catch ( \Exception $e ) {
					throw new \Exception( esc_html( "$key: " . $e->getMessage() ) );
				}
			} elseif ( is_array( $validator ) ) {
				if ( ! in_array( $value, $validator, true ) ) {
					throw new \Exception( esc_html( "invalid value for $key" ) );
				}
			}
		}

		if ( ! empty( $data['$app'] ) && ! empty( $data['$browser'] ) ) {
			throw new \Exception( 'Cannot have both $app and $browser' );
		}

		return true;
	}

	/**
	 * Validate an IP address.
	 *
	 * @param string $value The IP address to validate.
	 *
	 * @return true
	 * @throws \Exception If the IP address is invalid.
	 */
	public static function validate_ip( $value ) {
		if ( ! empty( $value ) && ! is_string( $value ) ) {
			throw new \Exception( 'must be a string' );
		}
		if ( ! empty( $value ) && ! filter_var( $value, FILTER_VALIDATE_IP ) ) {
			throw new \Exception( 'must be a valid IPv4 or IPv6 address' );
		}
		return true;
	}

	/**
	 * Validate an ID.
	 *
	 * @param string $value The ID to validate.
	 *
	 * @return true
	 * @throws \Exception If the ID is invalid.
	 */
	public static function validate_id( $value ) {
		if ( ! empty( $value ) && ! is_string( $value ) ) {
			throw new \Exception( 'must be a string' );
		}
		// The id's are limited to a-z,A-Z,0-9,=, ., -, _, +, @, :, &, ^, %, !, $
		if ( ! empty( $value ) && ! preg_match( '/^[a-zA-Z0-9=.\-_+@:&^%!$]+$/', $value ) ) {
			throw new \Exception( 'must be limited to a-z,A-Z,0-9,=, ., -, _, +, @, :, &, ^, %, !, $' );
		}
		return true;
	}

	/**
	 * Validate a currency code.
	 *
	 * @param string $value The currency code to validate.
	 *
	 * @return true
	 * @throws \InvalidArgumentException If the currency code is invalid.
	 */
	public static function validate_currency_code( $value ) {
		if ( ! empty( $value ) && ! is_string( $value ) ) {
			throw new \Exception( 'must be a string' );
		}
		// ISO-4217 currency code.
		if ( ! empty( $value ) && ! preg_match( '/^[A-Z]{3}$/', $value ) ) {
			throw new \InvalidArgumentException( 'invalid ISO-4217 currency code' );
		}
		return true;
	}

	/**
	 * Validate an array with a function.
	 *
	 * @param callable $callable_function The function to validate the array items.
	 *
	 * @return callable The usable validation function.
	 */
	public static function validate_array_fn( $callable_function ) {
		return function ( $value ) use ( $callable_function ) {
			if ( ! is_array( $value ) ) {
				throw new \Exception( 'invalid array' );
			}
			foreach ( $value as $item ) {
				if ( true !== $callable_function( $item ) ) {
					throw new \Exception( 'invalid array item' );
				}
			}
			return true;
		};
	}

	/**
	 * Validate an item.
	 *
	 * @param array $value The item to validate.
	 *
	 * @return true
	 * @throws \Exception If the item is invalid.
	 */
	public static function validate_item( $value ) {
		$validator_map = array(
			'$item_id'       => array( __CLASS__, 'validate_id' ),
			'$product_title' => 'is_string',
			'$price'         => 'is_int',
			'$currency_code' => array( __CLASS__, 'validate_currency_code' ),
			'$quantity'      => 'is_int',
			'$upc'           => 'is_string',
			'$sku'           => 'is_string',
			'$isbn'          => 'is_string',
			'$brand'         => 'is_string',
			'$manufacturer'  => 'is_string',
			'$category'      => 'is_string',
			'$tags'          => static::validate_array_fn( 'is_string' ),
			'$color'         => 'is_string',
			'$size'          => 'is_string',
		);
		try {
			// check required fields: $item_id, $product_title, $price.
			if (
				empty( $value['$item_id'] )
				|| empty( $value['$product_title'] )
				|| ! isset( $value['$price'] )
				|| ! is_numeric( $value['$price'] ) // Sometimes $price is 0 due to it being a free download.
			) {
				throw new \Exception( 'missing required fields' );
			}

			static::validate( $value, $validator_map );
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid $item: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate a browser.
	 *
	 * @param array $value The browser to validate.
	 *
	 * @return true
	 * @throws \Exception If the browser is invalid.
	 */
	public static function validate_browser( $value ) {

		if ( empty( $value ) ) {
			return true;
		}

		$validator_map = array(
			'$user_agent'       => 'is_string',
			'$accept_language'  => array( __CLASS__, 'is_string_or_null' ),
			'$content_language' => array( __CLASS__, 'is_string_or_null' ),
		);
		try {
			static::validate( $value, $validator_map );
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid browser: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Returns if the value is a string or null
	 *
	 * @param mixed $value The value.
	 *
	 * @return boolean
	 */
	public static function is_string_or_null( mixed $value ): bool {
		return is_string( $value ) || is_null( $value );
	}

	/**
	 * Validate an ISO 3166 language code.
	 *
	 * @param string $value The ISO 3166 language code to validate.
	 *
	 * @return boolean
	 * @throws \InvalidArgumentException If the ISO 3166 language code is invalid.
	 */
	public static function validate_ISO3166_language( string $value ): bool { //phpcs:ignore
		// ISO 3166 language code.
		if ( ! empty( $value ) && ! preg_match( '/^[a-z]{2}-[A-Z]{2}$/', $value ) ) {
			throw new \InvalidArgumentException( 'must be valid ISO-3166 format' );
		}
		return true;
	}

	/**
	 * Validate an app.
	 *
	 * @param array $value The app to validate.
	 *
	 * @return true
	 * @throws \Exception If the app is invalid.
	 */
	public static function validate_app( $value ) {
		$validator_map = array(
			'$os'                  => 'is_string',
			'$os_version'          => 'is_string',
			'$device_manufacturer' => 'is_string',
			'$device_model'        => 'is_string',
			'$device_unique_id'    => 'is_string',
			'$app_name'            => 'is_string',
			'$app_version'         => 'is_string',
			'$client_language'     => array( __CLASS__, 'validate_ISO3166_language' ),
		);
		try {
			static::validate( $value, $validator_map );
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid app: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate an email address.
	 *
	 * @param string $value The email address to validate.
	 *
	 * @return true
	 * @throws \InvalidArgumentException If the email address is invalid.
	 */
	public static function validate_email( $value ) {
		if ( ! empty( $value ) && ! is_string( $value ) ) {
			throw new \Exception( 'must be a string' );
		}
		if ( ! empty( $value ) && ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
			throw new \Exception( 'invalid email address' );
		}
		return true;
	}

	/**
	 * Validate a phone number.
	 *
	 * @param string $value The phone number to validate.
	 *
	 * @return true
	 * @throws \InvalidArgumentException If the phone number is invalid.
	 */
	public static function validate_phone_number( $value ) {
		// This one is tricky so we'll just check if it's a string and doesn't contain any letters.
		if ( ! empty( $value ) && ( ! is_string( $value ) || preg_match( '/[a-zA-Z]/', $value ) ) ) {
			throw new \Exception( 'invalid phone number' );
		}
		return true;
	}

	/**
	 * Validate a country code.
	 *
	 * @param string $value The country code to validate.
	 *
	 * @return true
	 * @throws \InvalidArgumentException If the country code is invalid.
	 */
	public static function validate_country_code( $value ) {
		if ( ! empty( $value ) && ! is_string( $value ) ) {
			throw new \Exception( 'must be a string' );
		}
		// ISO 3166 country code.
		if ( ! empty( $value ) && ! preg_match( '/^[A-Z]{2}$/', $value ) ) {
			throw new \Exception( 'must be an ISO 3166 country code' );
		}
		return true;
	}

	/**
	 * Validate an add item to cart event.
	 *
	 * @param array $data The data to validate.
	 *
	 * @return true
	 * @throws \Exception If the data is invalid.
	 */
	public static function validate_add_item_to_cart( $data ) {
		$validator_map = array(
			'$session_id'                => array( __CLASS__, 'validate_id' ),
			'$user_id'                   => array( __CLASS__, 'validate_id' ),
			'$item'                      => array( __CLASS__, 'validate_item' ),
			'$browser'                   => array( __CLASS__, 'validate_browser' ),
			'$app'                       => array( __CLASS__, 'validate_app' ),
			'$brand_name'                => 'is_string',
			'$site_country'              => array( __CLASS__, 'validate_country_code' ),
			'$site_domain'               => 'is_string',
			'$user_email'                => array( __CLASS__, 'validate_email' ),
			'$verification_phone_number' => array( __CLASS__, 'validate_phone_number' ),
		);
		try {
			static::validate( $data, $validator_map );
			// Required fields: $session_id (if $user_id is not present)
			if ( ! isset( $data['$user_id'] ) && empty( $data['$session_id'] ) ) {
				throw new \Exception( 'missing $session_id' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Failed to validate $add_item_to_cart event: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate the "card_bin" field.
	 *
	 * The card bin is the first 6 or 8 digits of the card number.
	 *
	 * @param string $value The card bin to validate.
	 *
	 * @return true
	 * @throws \Exception If the card bin is invalid.
	 */
	public static function validate_card_bin( $value ) {
		// The card bin is the first 6 or 8 digits of the card number.
		if ( ! empty( $value ) ) {
			if ( ! is_string( $value ) ) {
				throw new \Exception( 'must be a string' );
			}
			if ( ! preg_match( '/^[0-9]{6,8}$/', $value ) ) {
				throw new \Exception( 'invalid card bin' );
			}
			if ( 6 !== strlen( $value ) && 8 !== strlen( $value ) ) {
				throw new \Exception( 'invalid card bin length' );
			}
		}
		return true;
	}

	/**
	 * Validate the "card_last4" field.
	 *
	 * @param string $value The card last4 to validate.
	 *
	 * @return true
	 * @throws \Exception If the card last4 is invalid.
	 */
	public static function validate_card_last4( $value ) {
		if (
			! empty( $value )
			&& ! is_string( $value )
			&& ! preg_match( '/^[0-9]{4}$/', $value )
		) {
			throw new \Exception( 'not 4 numbers' );
		}
		return true;
	}

	/**
	 * Validate a payment method.
	 *
	 * @param array $data The payment method to validate.
	 *
	 * @return true
	 * @throws \Exception If the payment method is invalid.
	 */
	public static function validate_payment_method( $data ) {
		$validator_map = array(
			'$payment_type'               => self::PAYMENT_TYPES,
			'$payment_gateway'            => self::PAYMENT_GATEWAY,
			'$card_bin'                   => array( __CLASS__, 'validate_card_bin' ),
			'$card_last4'                 => array( __CLASS__, 'validate_card_last4' ),
			'$avs_result_code'            => 'is_string',
			'$cvv_result_code'            => 'is_string',
			'$verification_status'        => self::VERIFICATION_STATUSES,
			'$routing_number'             => 'is_string',
			'$shortened_iban_first6'      => array( __CLASS__, 'validate_iban' ),
			'$shortened_iban_last4'       => array( __CLASS__, 'validate_iban' ),
			'$sepa_direct_debit_mandate'  => array( true, false ),
			'$decline_reason_code'        => 'is_string',
			'$wallet_address'             => 'is_string',
			'$wallet_type'                => self::WALLET_TYPES,
			'$paypal_payer_id'            => 'is_string',
			'$paypal_payer_email'         => array( __CLASS__, 'validate_email' ),
			'$paypal_payer_status'        => 'is_string',
			'$stripe_cvc_check'           => 'is_string',
			'$stripe_address_line1_check' => 'is_string',
			'$stripe_address_line2_check' => 'is_string',
			'$stripe_address_zip_check'   => 'is_string',
			'$stripe_funding'             => 'is_string',
			'$stripe_brand'               => 'is_string',
			'$account_holder_name'        => array( __CLASS__, 'validate_name' ),
			'$account_number_last5'       => array( __CLASS__, 'validate_card_last5' ),
			'$bank_name'                  => 'is_string',
			'$bank_country'               => array( __CLASS__, 'validate_country_code' ),
		);
		try {
			static::validate( $data, $validator_map );
			// Account holder name, account number last 5, bank name and bank country are required for
			// certain payment types...
			$additional_info_payment_types = array(
				'$electronic_fund_transfer',
				'$sepa_credit',
				'$sepa_instant_credit',
				'$sepa_debit',
				'$ach_credit',
				'$ach_debit',
				'$wire_credit',
				'$wire_debit',
			);
			if ( ! empty( $data['$payment_type'] ) && in_array( $data['$payment_type'], $additional_info_payment_types, true ) ) {
				if (
					empty( $data['$account_holder_name'] )
					|| empty( $data['$account_number_last5'] )
					|| empty( $data['$bank_name'] )
					|| empty( $data['$bank_country'] )
				) {
					throw new \Exception( 'missing required fields' );
				}
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid payment method: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate a "credit_point" field.
	 *
	 * @param array $data The credit point to validate.
	 *
	 * @return true
	 * @throws \Exception If the credit point is invalid.
	 */
	public static function validate_credit_point( $data ) {
		$validator_map = array(
			'$amount'            => 'is_int',
			'$credit_point_type' => 'is_string',
		);
		try {
			static::validate( $data, $validator_map );
			// both fields are required
			if ( empty( $data['$amount'] ) || empty( $data['$credit_point_type'] ) ) {
				throw new \Exception( 'missing required fields' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid credit point: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate a "discount" field.
	 *
	 * @param array $data The discount to validate.
	 *
	 * @return true
	 * @throws \Exception If the discount is invalid.
	 */
	public static function validate_discount( $data ) {
		$validator_map = array(
			'$percentage_off'          => 'is_float',
			'$amount'                  => 'is_int',
			'$currency_code'           => array( __CLASS__, 'validate_currency_code' ),
			'$minimum_purchase_amount' => 'is_int',
		);
		try {
			static::validate( $data, $validator_map );
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid discount: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}


	/**
	 * Validate AddPromotion.
	 *
	 * @param array $data The add_promotion to validate.
	 *
	 * @return true
	 * @throws \Exception If the add_promotion is invalid.
	 */
	public static function validate_add_promotion( $data ) {
		$validator_map = array(
			'$user_id'    => array( __CLASS__, 'validate_id' ),
			'$session_id' => array( __CLASS__, 'validate_id' ),
			'$promotions' => static::validate_array_fn( array( __CLASS__, 'validate_promotion' ) ),
			'$browser'    => array( __CLASS__, 'validate_browser' ),
		);
		try {
			static::validate( $data, $validator_map );
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid promotion: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate Promotion.
	 *
	 * @param array $data The promotion to validate.
	 *
	 * @return true
	 * @throws \Exception If the promotion is invalid.
	 */
	public static function validate_promotion( $data ) {
		$validator_map = array(
			'$promotion_id'     => array( __CLASS__, 'validate_id' ),
			'$status'           => array( '$success', '$failure' ),
			'$failure_reason'   => self::FAILURE_REASONS,
			'$description'      => 'is_string',
			'$referrer_user_id' => array( __CLASS__, 'validate_id' ),
			'$discount'         => array( __CLASS__, 'validate_discount' ),
			'$credit_point'     => array( __CLASS__, 'validate_credit_point' ),
		);
		try {
			static::validate( $data, $validator_map );
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid promotion: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate an address.
	 *
	 * @param array $value The address to validate.
	 *
	 * @return true
	 * @throws \Exception If the address is invalid.
	 */
	public static function validate_address( $value ) {
		$validator_map = array(
			'$name'      => 'is_string',
			'$address_1' => 'is_string',
			'$address_2' => 'is_string',
			'$city'      => 'is_string',
			'$company'   => 'is_string',
			'$region'    => 'is_string',
			'$country'   => array( __CLASS__, 'validate_country_code' ),
			'$zipcode'   => 'is_string',
			'$phone'     => array( __CLASS__, 'validate_phone_number' ),
		);
		try {
			static::validate( $value, $validator_map );
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid address: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate the create account event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return true
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_create_account( $data ) {
		$validator_map = array(
			'$user_id'                   => array( __CLASS__, 'validate_id' ),
			'$session_id'                => array( __CLASS__, 'validate_id' ),
			'$user_email'                => array( __CLASS__, 'validate_email' ),
			'$verification_phone_number' => array( __CLASS__, 'validate_phone_number' ),
			'$name'                      => 'is_string',
			'$phone'                     => array( __CLASS__, 'validate_phone_number' ),
			'$referrer_user_id'          => array( __CLASS__, 'validate_id' ),
			'$payment_methods'           => static::validate_array_fn( array( __CLASS__, 'validate_payment_method' ) ),
			'$billing_address'           => array( __CLASS__, 'validate_address' ),
			'$shipping_address'          => array( __CLASS__, 'validate_address' ),
			'$promotions'                => static::validate_array_fn( array( __CLASS__, 'validate_promotion' ) ),
			'$social_sign_on_type'       => self::SOCIAL_SIGN_ON_TYPES,
			'$browser'                   => array( __CLASS__, 'validate_browser' ),
			'$app'                       => array( __CLASS__, 'validate_app' ),
			'$account_types'             => static::validate_array_fn( 'is_string' ),
			'$brand_name'                => 'is_string',
			'$site_country'              => array( __CLASS__, 'validate_country_code' ),
			'$site_domain'               => 'is_string',
			'$merchant_profile'          => array( __CLASS__, 'validate_merchant_profile' ),
		);
		try {
			static::validate( $data, $validator_map );
			// required field: $user_id
			if ( ! isset( $data['$user_id'] ) ) {
				throw new \Exception( 'missing $user_id' );
			}
			if ( ! isset( $data['$session_id'] ) ) {
				throw new \Exception( 'missing $session_id' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Failed to validate $create_account event: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate a "Ordered From" field.
	 *
	 * @param array $data The "Ordered From" data to validate.
	 *
	 * @return true
	 * @throws \Exception If the data is invalid.
	 */
	public static function validate_ordered_from( $data ) {
		$validator_map = array(
			'$store_id'      => 'is_string',
			'$store_address' => array( __CLASS__, 'validate_address' ),
		);

		try {
			static::validate( $data, $validator_map );
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid ordered_from: ' . esc_html( $e->getMessage() ) );
		}

		return true;
	}

	/**
	 * Validate a Merchant Profile field.
	 *
	 * @param array $data The Merchant Profile data to validate.
	 *
	 * @return true
	 * @throws \Exception If the data is invalid.
	 */
	public static function validate_merchant_profile( $data ) {
		$validator_map = array(
			'$merchant_id'            => 'is_string',
			'$merchant_category_code' => 'is_string',
			'$merchant_name'          => 'is_string',
			'$merchant_address'       => array( __CLASS__, 'validate_address' ),
		);

		try {
			static::validate( $data, $validator_map );
			// required: merchant_id, merchant_name
			if ( empty( $data['$merchant_id'] ) || empty( $data['$merchant_name'] ) ) {
				throw new \Exception( 'missing a required field (id, name)' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'invalid merchant_profile: ' . esc_html( $e->getMessage() ) );
		}

		return true;
	}

	/**
	 * Validate a Booking field.
	 *
	 * @param array $data The Booking data to validate.
	 *
	 * @return mixed
	 * @throws \Exception If the data is invalid.
	 */
	public static function validate_booking( $data ) { //phpcs:ignore
		throw new \Exception( 'Not implemented' );
	}

	/**
	 * Validate a $create_order or $update_order event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return mixed
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_create_or_update_order( $data ) {
		$validator_map = array(
			'$user_id'                   => array( __CLASS__, 'validate_id' ),
			'$session_id'                => 'is_string',
			'$order_id'                  => 'is_string',
			'$user_email'                => array( __CLASS__, 'validate_email' ),
			'$verification_phone_number' => array( __CLASS__, 'validate_phone_number' ),
			'$amount'                    => 'is_int',
			'$currency_code'             => array( __CLASS__, 'validate_currency_code' ),
			'$billing_address'           => array( __CLASS__, 'validate_address' ),
			'$payment_methods'           => static::validate_array_fn( array( __CLASS__, 'validate_payment_method' ) ),
			'$shipping_address'          => array( __CLASS__, 'validate_address' ),
			'$expedited_shipping'        => array( true, false ),
			'$items'                     => static::validate_array_fn( array( __CLASS__, 'validate_item' ) ),
			'$bookings'                  => static::validate_array_fn( array( __CLASS__, 'validate_booking' ) ),
			'$seller_user_id'            => array( __CLASS__, 'validate_id' ),
			'$promotions'                => static::validate_array_fn( array( __CLASS__, 'validate_promotion' ) ),
			'$shipping_method'           => array( '$electronic', '$physical' ),
			'$shipping_carrier'          => 'is_string',
			'$shipping_tracking_numbers' => static::validate_array_fn( 'is_string' ),
			'$ordered_from'              => array( __CLASS__, 'validate_ordered_from' ),
			'$browser'                   => array( __CLASS__, 'validate_browser' ),
			'$app'                       => array( __CLASS__, 'validate_app' ),
			'$brand_name'                => 'is_string',
			'$site_country'              => array( __CLASS__, 'validate_country_code' ),
			'$site_domain'               => 'is_string',
			'$merchant_profile'          => array( __CLASS__, 'validate_merchant_profile' ),
			'$digital_orders'            => static::validate_array_fn( array( __CLASS__, 'validate_digital_order' ) ),
		);
		try {
			static::validate( $data, $validator_map );
			// required field: $session_id if no $user_id provided.
			if ( ! isset( $data['$user_id'] ) && empty( $data['$session_id'] ) ) {
				throw new \Exception( 'missing $session_id' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Failed to validate order event: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate a $link_session_to_user event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return mixed
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_link_session_to_user( $data ) {
		$validator_map = array(
			'$user_id'    => array( __CLASS__, 'validate_id' ),
			'$session_id' => 'is_string',
		);
		try {
			static::validate( $data, $validator_map );
			// required field: $user_id, $session_id
			if ( empty( $data['$user_id'] ) || empty( $data['$session_id'] ) ) {
				throw new \Exception( 'missing $user_id or $session_id' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Invalid $link_session_to_user event: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate a $chargeback event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return mixed
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_chargeback( $data ) {
		$validator_map = array(
			'$order_id'          => array( __CLASS__, 'validate_id' ),
			'$user_id'           => array( __CLASS__, 'validate_id' ),
			'$chargeback_reason' => 'is_string',
		);

		try {
			static::validate( $data, $validator_map );
			// Required field: $user_id, $order_id
			if ( empty( $data['$user_id'] ) || empty( $data['$order_id'] ) ) {
				wc_get_logger()->error(
					'Invalid $chargeback event',
					array(
						'source' => 'sift-for-woocommerce',
						'data'   => $data,
					)
				);

				throw new \Exception( 'missing $user_id or $session_id' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Invalid $chargeback event: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate a $login event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return mixed
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_login( $data ) {
		$validator_map = array(
			'$user_id'                   => array( __CLASS__, 'validate_id' ),
			'$session_id'                => 'is_string',
			'$login_status'              => array( '$success', '$failure' ),
			'$user_email'                => array( __CLASS__, 'validate_email' ),
			'$verification_phone_number' => array( __CLASS__, 'validate_phone_number' ),
			'$browser'                   => array( __CLASS__, 'validate_browser' ),
			'$app'                       => array( __CLASS__, 'validate_app' ),
			'$failure_reason'            => array( '$account_unknown', '$account_suspended', '$account_disabled', '$wrong_password' ),
			'$username'                  => 'is_string',
			'$social_sign_on_type'       => self::SOCIAL_SIGN_ON_TYPES,
			'$account_types'             => static::validate_array_fn( 'is_string' ),
			'$brand_name'                => 'is_string',
			'$site_country'              => array( __CLASS__, 'validate_country_code' ),
			'$site_domain'               => 'is_string',
		);
		try {
			static::validate( $data, $validator_map );
			// required field: $user_id
			if ( ! isset( $data['$user_id'] ) ) {
				throw new \Exception( 'missing $user_id' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Invalid $login event: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate a $remove_item_from_cart event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return mixed
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_remove_item_from_cart( $data ) {
		//  functionally identical
		return static::validate_add_item_to_cart( $data );
	}


	/**
	 * Validate the update account event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return true
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_update_account( $data ) {
		$validator_map = array(
			'$user_id'                   => array( __CLASS__, 'validate_id' ),
			'$session_id'                => array( __CLASS__, 'validate_id' ),
			'$changed_password'          => array( true, false ),
			'$user_email'                => array( __CLASS__, 'validate_email' ),
			'$verification_phone_number' => array( __CLASS__, 'validate_phone_number' ),
			'$name'                      => 'is_string',
			'$phone'                     => array( __CLASS__, 'validate_phone_number' ),
			'$referrer_user_id'          => array( __CLASS__, 'validate_id' ),
			'$payment_methods'           => static::validate_array_fn( array( __CLASS__, 'validate_payment_method' ) ),
			'$billing_address'           => array( __CLASS__, 'validate_address' ),
			'$shipping_address'          => array( __CLASS__, 'validate_address' ),
			'$promotions'                => static::validate_array_fn( array( __CLASS__, 'validate_promotion' ) ),
			'$social_sign_on_type'       => self::SOCIAL_SIGN_ON_TYPES,
			'$browser'                   => array( __CLASS__, 'validate_browser' ),
			'$app'                       => array( __CLASS__, 'validate_app' ),
			'$account_types'             => static::validate_array_fn( 'is_string' ),
			'$brand_name'                => 'is_string',
			'$site_country'              => array( __CLASS__, 'validate_country_code' ),
			'$site_domain'               => 'is_string',
			'$merchant_profile'          => array( __CLASS__, 'validate_merchant_profile' ),
		);
		try {
			static::validate( $data, $validator_map );
			// required field: $user_id
			if ( ! isset( $data['$user_id'] ) ) {
				throw new \Exception( 'missing $user_id' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Invalid $update_account event: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}


	/**
	 * Validate the update password event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return true
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_update_password( $data ) {
		$validator_map = array(
			'$user_id'                   => array( __CLASS__, 'validate_id' ),
			'$reason'                    => array( '$user_update', '$forgot_password', '$forced_reset' ),
			'$status'                    => array( '$pending', '$success', '$failure' ),
			'$browser'                   => array( __CLASS__, 'validate_browser' ),
			'$app'                       => array( __CLASS__, 'validate_app' ),
			'$account_types'             => static::validate_array_fn( 'is_string' ),
			'$brand_name'                => 'is_string',
			'$site_country'              => array( __CLASS__, 'validate_country_code' ),
			'$site_domain'               => 'is_string',
			'$user_email'                => array( __CLASS__, 'validate_email' ),
			'$verification_phone_number' => array( __CLASS__, 'validate_phone_number' ),
		);
		try {
			static::validate( $data, $validator_map );
			// Required fields for update password: $user_id, $reason, $status
			if ( ! isset( $data['$user_id'] ) ) {
				throw new \Exception( 'missing $user_id' );
			}
			if ( empty( $data['$reason'] ) ) {
				throw new \Exception( 'missing $reason' );
			}
			if ( empty( $data['$status'] ) ) {
				throw new \Exception( 'missing $status' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Invalid $update_password event: ' . esc_html( $e->getMessage() ) );
		}
		return true;
	}

	/**
	 * Validate the order status event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return true
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_order_status( array $data ) {
		$validator_map = array(
			'$user_id'      => array( __CLASS__, 'validate_id' ),
			'$session_id'   => array( __CLASS__, 'validate_id' ),
			'$order_id'     => 'is_string',
			'$order_status' => self::ORDER_STATUSES,
			'$reason'       => self::CANCELLATION_REASONS,
			'$source'       => array( '$automated', '$manual_review' ),
			'$analyst'      => 'is_string',
			'$webhook_id'   => 'is_string',
			'$description'  => 'is_string',
			'$browser'      => array( __CLASS__, 'validate_browser' ),
			'$app'          => array( __CLASS__, 'validate_app' ),
			'$brand_name'   => 'is_string',
			'$site_country' => array( __CLASS__, 'validate_country_code' ),
			'$site_domain'  => 'is_string',
		);

		try {
			static::validate( $data, $validator_map );
			// Required fields for order status: $user_id, $order_id, $order_status
			if ( ! isset( $data['$user_id'] ) ) {
				throw new \Exception( 'missing $user_id' );
			}
			if ( ! isset( $data['$session_id'] ) ) {
				throw new \Exception( 'missing $session_id' );
			}
			if ( empty( $data['$order_id'] ) ) {
				throw new \Exception( 'missing $order_id' );
			}
			if ( empty( $data['$order_status'] ) ) {
				throw new \Exception( 'missing $order_status' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Invalid $order_status event: ' . esc_html( $e->getMessage() ) );
		}

		return true;
	}

	/**
	 * Validate the order status event.
	 *
	 * @param array $data The event to validate.
	 *
	 * @return true
	 * @throws \Exception If the event is invalid.
	 */
	public static function validate_transaction( array $data ) {
		$validator_map = array(
			'$user_id'            => array( __CLASS__, 'validate_id' ),
			'$session_id'         => array( __CLASS__, 'validate_id' ),
			'$amount'             => 'is_int',
			'$currency_code'      => array( __CLASS__, 'validate_currency_code' ),
			'$order_id'           => 'is_string',
			'$transaction_type'   => self::SUPPORTED_TRANSACTION_TYPE,
			'$transaction_status' => self::SUPPORTED_TRANSACTION_STATUS,
		);

		try {
			static::validate( $data, $validator_map );
			// Required fields for order status: $user_id, $order_id, $order_status
			if ( ! array_key_exists( '$user_id', $data ) ) { // Might be an empty string if no user_id is present yet.
				throw new \Exception( 'missing $user_id' );
			}
			if ( ! isset( $data['$session_id'] ) ) {
				throw new \Exception( 'missing $session_id' );
			}
			if ( empty( $data['$amount'] ) ) {
				throw new \Exception( 'missing $amount' );
			}
			if ( empty( $data['$currency_code'] ) ) {
				throw new \Exception( 'missing $currency_code' );
			}
		} catch ( \Exception $e ) {
			throw new \Exception( 'Invalid $order_status event: ' . esc_html( $e->getMessage() ) );
		}

		return true;
	}
}
