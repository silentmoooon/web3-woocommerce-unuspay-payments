<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UnusPay_WC_Payments_Settings {

	public function __construct() {

		add_action( 'rest_api_init', [ $this, 'register_settings' ] );
	}

	public static function register_settings() {

		register_setting(
			'unuspay_wc',
			'unuspay_wc_payment_pey',
			[
				'type' => 'string',
				'show_in_rest' => true,
				'default' => null
			]
		);


	}
}
