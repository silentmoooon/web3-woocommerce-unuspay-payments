<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UnusPay_WC_Payments_Gateway extends WC_Payment_Gateway {

	const GATEWAY_ID = 'unuspay_wc_payments';

	public $blockchain;

	public function __construct() {
		$this->id									= static::GATEWAY_ID;
		$this->method_title				= 'UnusPay';
		$this->method_description = 'Web3 Payments directly into your wallet. Accept any token with on-the-fly conversion.';
		$this->supports    				= [ 'products' ];
		$this->init_form_fields();
		$this->init_settings();
		$title 										= get_option( 'unuspay_wc_checkout_title' );
		$this->title  						= empty($title) ? 'UnusPay' : $title;
		$description 							= get_option( 'unuspay_wc_checkout_description' );
		$this->description  			= empty($description) ? null : $description;
		$this->blockchain				  = null;
	}

	public function get_title() {
		return $this->title;
	}

	public function get_icon() {
		$icon = '';
		if ( empty( get_option( 'unuspay_wc_blockchains' ) ) ) {
			$icon = '';
		} else if ( null != $this->blockchain ) {
			$url = esc_url( plugin_dir_url( __FILE__ ) . 'images/blockchains/' . $this->blockchain . '.svg' );
			$icon = $icon . "<img title='Payments on " . ucfirst($this->blockchain) . "' class='wc-unuspay-blockchain-icon' src='" . $url . "'/>";
		} else {
			$blockchains = json_decode( get_option( 'unuspay_wc_blockchains' ) );
			$index = 0;
			foreach ( $blockchains as $blockchain ) {
				$url = esc_url( plugin_dir_url( __FILE__ ) . 'images/blockchains/' . $blockchain . '.svg' );
				$icon = $icon . "<img title='Payments on " . ucfirst($blockchain) . "' class='wc-unuspay-blockchain-icon' src='" . $url . "'/>";
			}
		}
		return $icon;
	}

	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable gateway',
				'default' => 'yes'
			),
			'title' => array(
				'title'       => 'Title',
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.' ),
				'default'     => 'UnusPay',
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'Payment method description that the customer will see on your checkout.',
				'default'     => '',
				'desc_tip'    => true,
			)
		);
	}

	public function process_payment( $order_id ) {
		global $wpdb;
		$order = wc_get_order( $order_id );

		if ( $order->get_total() > 0 ) {
             
            $checkout_id = wp_generate_uuid4();
			$accept = $this->getUnusPayOrder( $order ,$checkout_id);

			$result = $wpdb->insert( "{$wpdb->prefix}wc_unuspay_checkouts", array(
				'id' => $checkout_id,
				'order_id' => $order_id,
				'accept' => json_encode( $accept ),
				'created_at' => current_time( 'mysql' )
			));
			if ( false === $result ) {
				$error_message = $wpdb->last_error;
				UnusPay_WC_Payments::log( 'Storing checkout failed: ' . $error_message );
				throw new Exception( 'Storing checkout failed: ' . $error_message );
			}
			return( [
            				'result'         => 'success',
            				'redirect'       => '#wc-unuspay-checkout-' . $checkout_id . '@' . time()
            				// 'redirect'       => get_option('woocommerce_enable_signup_and_login_from_checkout') === 'yes' ? $order->get_checkout_payment_url() . '#wc-depay-checkout-' . $checkout_id . '@' . time() : '#wc-depay-checkout-' . $checkout_id . '@' . time()
            			] );
		} else {
			$order->payment_complete();
		}
	}

	public function round_token_amount( $amount ) {
		$amount = strval( $amount );
		preg_match( '/\d+\.0*(\d{4})/' , $amount, $digits_after_decimal );
		if ( !empty( $digits_after_decimal ) ) {
			$digits_after_decimal = $digits_after_decimal[0];
			preg_match( '/\d{4}$/', $digits_after_decimal, $focus );
			$focus = $focus[0];
			if ( preg_match( '/^0/', $focus ) ) {
				$float = floatval( "$focus[1].$focus[2]$focus[3]" );
				$fixed = '0' . number_format( round( $float, 2 ) , 2, '', '' );
			} else {
				$float = floatval( "$focus[0].$focus[1]$focus[2]9" );
				$fixed = number_format( round( $float, 2 ), 2, '', '' );
			}
			if ( '0999' == $fixed && round( $amount, 0 ) == 0 ) {
				return preg_replace( '/\d{4}$/', '1000', $digits_after_decimal );
			} elseif ( '1000' == $fixed && round( $amount, 0 ) == 0 ) {
				return preg_replace( '/\d{5}$/', '1000', $digits_after_decimal );
			} elseif ( '0' != $fixed[0] && strlen( $fixed ) > 3 ) {
				return round( $amount, 0 );
			} else {
				return preg_replace( '/\d{4}$/', $fixed, $digits_after_decimal );
			}
		} else {
			return $amount;
		}
	}

	public function getUnusPayOrder( $order ,$checkout_id ) {
	$lang=$_SERVER['HTTP_ACCEPT_LANGUAGE'];
	$headers = array(
    				'accept-language' => $lang,
    				'Content-Type' => 'application/json; charset=utf-8',
    			);
	    $website=get_option("siteurl");

		$total = $order->get_total();
		$currency = $order->get_currency();

		$payment_key = get_option( 'unuspay_wc_payment_key' );
		if ( empty( $payment_key ) ) {
			throw new Exception( 'No payment key found!' );
		}

        $post_response = wp_remote_post( "http://110.41.71.103:9080/payment/ecommerce/order",
        			array(
        				'headers' => $headers,
        				'body' => json_encode([
        				    'checkout_id' => $checkout_id,
        					'website' => $website,
        					'lang' => $lang,
        					'orderNo' => $order->get_id(),
                            'email' => $order->get_billing_email(),
        					'payLinkId' => $payment_key,
        					'currency' => $currency,
        					'amount' => $order->get_total()
        				]),
        				'method' => 'POST',
        				'data_format' => 'body'
        			)
        		);
        $post_response_code = $post_response['response']['code'];
        		$post_response_successful = ! is_wp_error( $post_response_code ) && $post_response_code >= 200 && $post_response_code < 300;
        		if(!$post_response_successful){
        		    UnusPay_WC_Payments::log( 'ecommerce order failed!' . $post_response->get_error_message() );
                	throw new Exception( 'request failed!' );
        		}
        		$post_response_json = json_decode( $post_response['body']);
        		if($post_response_json->code!=200){
        		    UnusPay_WC_Payments::log( 'ecommerce order failed!' . $post_response_json->message() );
                    throw new Exception( 'request failed!' );
        		}

		        return $post_response_json;
	}

	public function admin_options() {
		wp_redirect( '/wp-admin/admin.php?page=wc-admin&path=%2Funuspay%2Fsettings' );
	}
}
