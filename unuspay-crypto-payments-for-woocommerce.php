<?php
/**
 * Plugin Name: unuspay crypto payments
 * Plugin URI: https://unuspay.com/e-commerce
 * Description: unuspay Payments directly into your own wallet.
 * Author: unuspay
 * Author URI: https://unuspay.com
 * Text Domain: unuspay-crypto-payments-for-woocommerce
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * WC requires at least: 6.2
 * WC tested up to: 9.8.5
 * Requires at least: 6.0
 * Requires PHP: 7.2
 * Version: 1.0.0
 *
 * @package UnusPay\Payments
 */

defined( 'ABSPATH' ) || exit;

define( 'UNUSPAY_WC_PLUGIN_FILE', __FILE__ );
define( 'UNUSPAY_WC_ABSPATH', __DIR__ . '/' );
define( 'UNUSPAY_MIN_WC_ADMIN_VERSION', '0.23.2' );
define( 'UNUSPAY_CURRENT_VERSION', '0.0.2' );

require_once UNUSPAY_WC_ABSPATH . '/vendor/autoload.php';

function unuspay_run_migration() {
	 global $wpdb;

	$latestDbVersion = 5;
	$currentDbVersion = get_option('unuspay_wc_db_version');

	if ( !empty($currentDbVersion) && $currentDbVersion >= $latestDbVersion ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta("

		CREATE TABLE {$wpdb->prefix}wc_unuspay_checkouts (
			id VARCHAR(64) NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			accept LONGTEXT NOT NULL,
			created_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
			PRIMARY KEY  (id)
		);
        CREATE TABLE {$wpdb->prefix}wc_unuspay_transactions (
        			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        			checkout_id VARCHAR(64) NOT NULL,
        			tracking_uuid TINYTEXT NOT NULL,
        			blockchain TINYTEXT NOT NULL,
        			transaction_id TINYTEXT NOT NULL,
        			sender_id TINYTEXT NOT NULL,
        			receiver_id TINYTEXT NOT NULL,
        			token_id TINYTEXT NOT NULL,
        			amount TINYTEXT NOT NULL,
        			status TINYTEXT NOT NULL,
        			failed_reason TINYTEXT NOT NULL,
        			confirmed_by TINYTEXT NOT NULL,
        			confirmed_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
        			created_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
        			PRIMARY KEY  (id),
        			KEY tracking_uuid_index (tracking_uuid(191))
        		);
	");



	// Update latest DB version last
	update_option( 'unuspay_wc_db_version', $latestDbVersion );
}

add_action('admin_init', 'unuspay_run_migration');

function unuspay_activated() {

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { 
		return;
	}

	unuspay_run_migration();
	
	/*try {
		wp_remote_post( 'https://unuspay.com/installs',
			array(
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body' => json_encode( [
					'type' => 'woocommerce',
					'host' => get_option( 'siteurl' ),
				] ),
				'method' => 'POST',
				'data_format' => 'body'
			)
		);
	} catch (Exception $e) {
		error_log('Reporting install failed');
	}*/
}
register_activation_hook( __FILE__, 'unuspay_activated' );

function unuspay_deactivated() {

}
register_deactivation_hook( __FILE__, 'unuspay_deactivated' );

function unuspay_init() {

	require_once UNUSPAY_WC_ABSPATH . '/includes/class-unuspay-wc-payments.php';
	UnusPay_WC_Payments::init();
}
add_action( 'plugins_loaded', 'unuspay_init', 11 );

function unuspay_tasks_init() {
	
}
add_action( 'plugins_loaded', 'unuspay_tasks_init' );

// HPOS compatible
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
});

function unuspay_blocks_support() {
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				
				require_once UNUSPAY_WC_ABSPATH . 'includes/class-unuspay-wc-payments-block.php';
                					$payment_method_registry->register( new UnusPay_WC_Payments_Block() );
			}
		);
	}
}
add_action( 'woocommerce_blocks_loaded', 'unuspay_blocks_support' );
