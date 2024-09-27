<?php declare( strict_types=1 );

class Payment_Method {

	public static function get_payment_method_details_from_order( string $gateway_id, \WC_Order $order ) {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_payment_method_details_from_order', $gateway_id ), $order );
	}

	public static function get_charge_details_from_order( string $gateway_id, \WC_Order $order ) {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_charge_details_from_order', $gateway_id ), $order );
	}

	public static function get_payment_gateway_string( string $gateway_id ): string {
		$payment_gateway = apply_filters( sprintf( 'wc_sift_decisions_%s_payment_gateway_string', $gateway_id ), '' );
		if ( self::payment_gateway_is_valid( $payment_gateway ) ) {
			return $payment_gateway;
		}
	}

	public static function get_payment_type_string( string $gateway_id, ?string $gateway_payment_type = null ): string {
		$payment_type = apply_filters( sprintf( 'wc_sift_decisions_%s_payment_type_string', $gateway_id ), $gateway_payment_type );
		if ( self::payment_type_is_valid( $payment_type ) ) {
			return $payment_type;
		}
	}

	public static function get_card_last4( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_card_last4', $gateway_id ), $data );
	}

	public static function get_card_bin( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_card_bin', $gateway_id ), $data );
	}

	public static function get_avs_result_code( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_avs_result_code', $gateway_id ), $data );
	}

	public static function get_cvv_result_code( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_cvv_result_code', $gateway_id ), $data );
	}

	public static function get_verification_status( string $gateway_id, $data ): string {
		$verification_status = apply_filters( sprintf( 'wc_sift_decisions_%s_verification_status', $gateway_id ), $data );
		if ( self::verification_status_is_valid( $verification_status ) ) {
			return $verification_status;
		}
	}

	public static function get_routing_number( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_routing_number', $gateway_id ), $data );
	}

	public static function get_shortened_iban_first6( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_shortened_iban_first6', $gateway_id ), $data );
	}

	public static function get_shortened_iban_last4( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_shortened_iban_last4', $gateway_id ), $data );
	}

	public static function get_sepa_direct_debit_mandate( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_sepa_direct_debit_mandate', $gateway_id ), $data );
	}

	public static function get_decline_reason_code( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_decline_reason_code', $gateway_id ), $data );
	}

	public static function get_wallet_address( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_wallet_address', $gateway_id ), $data );
	}

	public static function get_wallet_type( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_wallet_type', $gateway_id ), $data );
	}

	public static function get_paypal_payer_id( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_paypal_payer_id', $gateway_id ), $data );
	}

	public static function get_paypal_payer_email( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_paypal_payer_email', $gateway_id ), $data );
	}

	public static function get_paypal_payer_status( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_paypal_payer_status', $gateway_id ), $data );
	}

	public static function get_paypal_address_status( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_paypal_address_status', $gateway_id ), $data );
	}

	public static function get_paypal_protection_eligibility( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_paypal_protection_eligibility', $gateway_id ), $data );
	}

	public static function get_paypal_payment_status( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_paypal_payment_status', $gateway_id ), $data );
	}

	public static function get_account_holder_name( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_account_holder_name', $gateway_id ), $data );
	}

	public static function get_account_number_last5( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_account_number_last5', $gateway_id ), $data );
	}

	public static function get_bank_name( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_bank_name', $gateway_id ), $data );
	}

	public static function get_bank_country( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_bank_country', $gateway_id ), $data );
	}

	public static function get_stripe_cvc_check( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_stripe_cvc_check', $gateway_id ), $data );
	}

	public static function get_stripe_address_line1_check( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_stripe_address_line1_check', $gateway_id ), $data );
	}

	public static function get_stripe_address_line2_check( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_stripe_address_line2_check', $gateway_id ), $data );
	}

	public static function get_stripe_address_zip_check( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_stripe_address_zip_check', $gateway_id ), $data );
	}

	public static function get_stripe_funding( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_stripe_funding', $gateway_id ), $data );
	}

	public static function get_stripe_brand( string $gateway_id, $data ): string {
		return apply_filters( sprintf( 'wc_sift_decisions_%s_stripe_brand', $gateway_id ), $data );
	}

	public static function payment_gateway_is_valid( string $payment_gateway ): bool {
		return in_array(
			$payment_gateway,
			array(
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
				'$pep',
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
			)
		);
	}

	public static function payment_type_is_valid( string $payment_type ): bool {
		return in_array(
			$payment_type, 
			array(
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
			)
		);
	}

	public static function verification_status_is_valid( string $verification_status ): bool {
		return in_array(
			$verification_status,
			array(
				'$success',
				'$failure',
				'$pending',
			)
			);
	}
}