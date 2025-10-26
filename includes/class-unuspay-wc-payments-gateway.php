<?php
if (!defined('ABSPATH')) {
    exit;
}

class UnusPay_WC_Payments_Gateway extends WC_Payment_Gateway
{

    const GATEWAY_ID = 'unuspay_wc_payments';

    public $blockchain;

    public function __construct()
    {
        $this->id = static::GATEWAY_ID;
        $this->method_title = 'UnusPay';
        $this->method_description = 'Web3 Cryptocurrency Payments with UnusPay';
        $this->supports = ['products'];
        $this->init_form_fields();
        $this->init_settings();
        $title = $this->get_option('unuspay_wc_checkout_title');
        $this->title = empty($title) ? 'UnusPay' : $title;
        $description = $this->get_option('unuspay_wc_checkout_description');
        $this->description = empty($description) ? null : $description;
        $this->unuspay_wc_payment_key = $this->get_option('unuspay_wc_payment_key');
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
    }
 
    public function get_title()
    {
        return $this->title;
    }

    public function get_icon()
    {
        $icon = '';
        if (empty($this->get_option('unuspay_wc_payment_key'))) {
            $icon = '';
        }  else {
            $post_response = wp_remote_get("https://dapp.unuspay.com/api/payment/link/blockchains?linkId=" . $this->get_option('unuspay_wc_payment_key'),
            array(
                 
                'method' => 'GET'
                
            )
        );
        $post_response_code = $post_response['response']['code'];
        $post_response_successful = !is_wp_error($post_response_code) && $post_response_code == 200 ;
        if (!$post_response_successful) {
            $icon = '';
        }
        $post_response_json = json_decode($post_response['body']);
        if ($post_response_json->code != 200) {
            $icon = '';
        }
        $url = esc_url(plugins_url('assets/images/logo.jpg', dirname(__FILE__)));
        $icon = $icon . "<img title='Unuspay' class='wc-unuspay-blockchain-logo' src='" . $url . "'/>";
        foreach ($post_response_json->data as $blockchain) {
            $url = esc_url(plugins_url('assets/images/blockchains/' . $blockchain . '.svg', dirname(__FILE__)));
            $icon = $icon . "<img title='Payments on " . ucfirst($blockchain) . "' class='wc-unuspay-blockchain-icon' src='" . $url . "'/>";
        }
        }
        return $icon;
    }

    public function init_form_fields()
    {

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable gateway',
                'default' => 'yes'
            ),
            'unuspay_wc_checkout_title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'UnusPay',
                'desc_tip' => true,
            ),
            'unuspay_wc_payment_key' => array(
                'title' => 'Payment Key',
                'type' => 'text',
                'description' => 'To increase your request limit towards UnusPay APIs,
                              please enter your Payment key.',
                'default' => '',
                'desc_tip' => true,
            ),
			'unuspay_wc_checkout_description' => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'Payment method description that the customer will see on your checkout.',
				'default'     => '',
				'desc_tip'    => true,
			)
        );
    }
public function process_admin_options() {

    
        if ( get_transient( 'unuspay_gateway_notified' ) ) {
            return;
        }
        set_transient( 'unuspay_gateway_notified', true, 3 );

		parent::process_admin_options();

		$api_key = isset( $_POST['woocommerce_unuspay_wc_payments_unuspay_wc_payment_key'] ) ? sanitize_text_field( $_POST['woocommerce_unuspay_wc_payments_unuspay_wc_payment_key'] ) : '';

 
    // 如果为空，直接阻止保存
    if (empty($api_key)) {
        WC_Admin_Settings::add_error('[Unuspay] Payment Key cannot be empty.');
        return false; // 返回旧值，不保存新值
    }
    $headers = array(
            'Content-Type' => 'application/json; charset=utf-8'
        );
     $website = get_option("siteurl");
    $endpoint = 'https://dapp.unuspay.com/api/plugin/collect';
    $response = wp_remote_post( $endpoint,
                array(
                    'headers' => $headers,
                    'body' => json_encode([
                        'website' => $website,
                        'paymentKey' => $api_key,
                        'platform' => 'woo'
                    ]),
                    'method' => 'POST',
                    'data_format' => 'body'
                )
            );
                

    if (is_wp_error($response)) {
        WC_Admin_Settings::add_error('[Unuspay] Failed to connect to the verification server.');
        return false;
    }

 
    $rspBody = json_decode(wp_remote_retrieve_body($response));
    if ($rspBody->code == 404) {

        WC_Admin_Settings::add_error('[Unuspay]  Invalid Payment Key. Please check and try again.');
        return false;
    }
    if ($rspBody->code != 200) {

        WC_Admin_Settings::add_error('[Unuspay]  Failed to connect to the verification server.');
        return false;
    }

    // 校验通过，允许保存
    return true;
}
    public function process_payment($order_id)
    {
        global $wpdb;
        $order = wc_get_order($order_id);

        if ($order->get_total() > 0) {

            $accept = $this->getUnusPayOrder($order);

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $result = $wpdb->insert(
                $wpdb->prefix . 'wc_unuspay_checkouts',
                array(
                    'id'        => sanitize_text_field( $accept->id ),
                    'order_id'  => absint( $order_id ),
                    'accept'    => wp_json_encode( $accept ),
                    'created_at'=> current_time( 'mysql' )
                ),
                array(
                    '%s', // id
                    '%d', // order_id
                    '%s', // accept
                    '%s'  // created_at
                )
            );
            if (false === $result) {
                $error_message = $wpdb->last_error;
                UnusPay_WC_Payments::log('Storing checkout failed: ' . $error_message);
                throw new Exception('Storing checkout failed: ');
            }

            return ([
                'result' => 'success',
                'redirect' => '#wc-unuspay-checkout-' . $accept->id . '@' . time()
            ]);
        } else {
            $order->payment_complete();
        }
    }
 
    public function getUnusPayOrder($order)
    {
        $lang = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_ACCEPT_LANGUAGE'])) : '';
        $headers = array(
            'accept-language' => $lang,
            'Content-Type' => 'application/json; charset=utf-8',
        );
        $website = get_option("siteurl");

        $total = $order->get_total();
        $currency = $order->get_currency();

        $payment_key = $this->get_option('unuspay_wc_payment_key');
        if (empty($payment_key)) {
            throw new Exception('No payment key found!');
        }

        $post_response = wp_remote_post("https://dapp.unuspay.com/api/payment/ecommerce/order",
            array(
                'headers' => $headers,
                'body' => json_encode([
                    'website' => $website,
                    'lang' => $lang,
                    'orderNo' => $order->get_id(),
                    'email' => $order->get_billing_email(),
                    'payLinkId' => $payment_key,
                    'currency' => $currency,
                    'amount' => $order->get_total(),
                    'commerceType'=>1
                ]),
                'method' => 'POST',
                'data_format' => 'body'
            )
        );
        $post_response_code = $post_response['response']['code'];
        $post_response_successful = !is_wp_error($post_response_code) && $post_response_code == 200 ;
        if (!$post_response_successful) {
            UnusPay_WC_Payments::log('ecommerce order failed!' . $post_response->get_error_message());
            throw new Exception('request failed!');
        }
        $post_response_json = json_decode($post_response['body']);
        if ($post_response_json->code != 200) {
            UnusPay_WC_Payments::log('ecommerce order failed!' . $post_response_json->message());
            throw new Exception('request failed!');
        }

        return $post_response_json->data;
    }

/*     public function admin_options()
    {
        wp_redirect('/wp-admin/admin.php?page=wc-admin&path=%2Funuspay%2Fsettings');
    }
 */
    
}
