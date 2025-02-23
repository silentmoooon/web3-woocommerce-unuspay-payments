<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodTypeInterface;

final class UnusPay_WC_Payments_Block_Ethereum extends AbstractPaymentMethodType {

	private $gateway;

	public $name = UnusPay_WC_Payments_Ethereum_Gateway::GATEWAY_ID;

	public function initialize() {
		$this->gateway = new UnusPay_WC_Payments_Ethereum_Gateway();
		$this->settings = array(
			'blockchains' => '["ethereum"]'
		);
	}

	public function is_active() {
		return $this->gateway->is_available();
	}

	public function get_payment_method_script_handles() {

		wp_register_script(
			'UNUSPAY_WC_BLOCKS_INTEGRATION',
			plugins_url( 'dist/block.js', UNUSPAY_WC_PLUGIN_FILE ),
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element' ),
			UNUSPAY_CURRENT_VERSION,
			true
		);

		return [ 'UNUSPAY_WC_BLOCKS_INTEGRATION' ];
	}

	public function get_payment_method_data() {
		return array(
			'id'          => $this->gateway->id,
			'title'       => $this->gateway->title,
			'description' => $this->gateway->description,
			'enabled'     => $this->gateway->is_available(),
			'blockchains' => ['ethereum'],
			'pluginUrl'   => plugin_dir_url( __FILE__ ),
		);
	}
}
