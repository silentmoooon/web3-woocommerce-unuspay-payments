<?php
/**
 * Plugin Name: unuspay Payments for WooCommerce
 * Plugin URI: https://unuspay.com/plugins/woocommerce
 * Description: Web3 Payments directly into your own wallet. Accept thousands of different tokens with on-the-fly conversion on multiple blockchains.
 * Author: unuspay
 * Author URI: https://unuspay.com
 * Text Domain: unuspay-payments
 * Domain Path: /languages
 * WC requires at least: 6.2
 * WC tested up to: 8.7.0
 * Requires at least: 5.8
 * Requires PHP: 7.0
 * Version: 2.11.6
 *
 * @package woocommerce\Payments
 */

defined( 'ABSPATH' ) || exit;

define( 'UNUSPAY_WC_PLUGIN_FILE', __FILE__ );
define( 'UNUSPAY_WC_ABSPATH', __DIR__ . '/' );
define( 'UNUSPAY_MIN_WC_ADMIN_VERSION', '0.23.2' );
define( 'UNUSPAY_CURRENT_VERSION', '2.11.6' );

require_once UNUSPAY_WC_ABSPATH . '/vendor/autoload.php';

function unuspay_run_migration() {
	 global $wpdb;

	$latestDbVersion = 4;
	$currentDbVersion = get_option('unuspay_wc_db_version');

	if ( !empty($currentDbVersion) && $currentDbVersion >= $latestDbVersion ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta("

		CREATE TABLE {$wpdb->prefix}wc_unuspay_checkouts (
			id VARCHAR(36) NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			accept LONGTEXT NOT NULL,
			created_at datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
			PRIMARY KEY  (id)
		);

	");

/* 	$exists = $wpdb->get_col("SHOW COLUMNS FROM wp_wc_unuspay_transactions LIKE 'confirmations_required'");
	if (! empty( $exists ) ) {
		$wpdb->query( 'ALTER TABLE wp_wc_unuspay_transactions DROP COLUMN confirmations_required' );
	} */

	if ( 'wp_' != $wpdb->prefix ) {
		
		// Rename wp_wc_unuspay_logs to prefix_wc_unuspay_logs if it exists
		/* if ($wpdb->get_var( $wpdb->prepare('SHOW TABLES LIKE %s', 'wp_wc_unuspay_logs') ) == 'wp_wc_unuspay_logs') {
				$wpdb->query( $wpdb->prepare('RENAME TABLE %s TO %s', 'wp_wc_unuspay_logs', $wpdb->prefix . 'wc_unuspay_logs') );
		} */

		// Rename wp_wc_unuspay_checkouts to prefix_wc_unuspay_checkouts if it exists
		if ($wpdb->get_var( $wpdb->prepare('SHOW TABLES LIKE %s', 'wp_wc_unuspay_checkouts') ) == 'wp_wc_unuspay_checkouts') {
				$wpdb->query( $wpdb->prepare('RENAME TABLE %s TO %s', 'wp_wc_unuspay_checkouts', $wpdb->prefix . 'wc_unuspay_checkouts') );
		}

		// Rename wp_wc_unuspay_transactions to prefix_wc_unuspay_transactions if it exists
		/* if ($wpdb->get_var( $wpdb->prepare('SHOW TABLES LIKE %s', 'wp_wc_unuspay_transactions') ) == 'wp_wc_unuspay_transactions') {
				$wpdb->query( $wpdb->prepare('RENAME TABLE %s TO %s', 'wp_wc_unuspay_transactions', $wpdb->prefix . 'wc_unuspay_transactions') );
		} */
	}

	// Update latest DB version last
	update_option( 'unuspay_wc_db_version', $latestDbVersion );
}

add_action('admin_init', 'unuspay_run_migration');

function unuspay_activated() {

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) { 
		return;
	}

	unuspay_run_migration();
	
	try {
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
	}
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
