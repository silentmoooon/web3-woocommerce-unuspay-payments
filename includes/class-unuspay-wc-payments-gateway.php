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
        $title = get_option('unuspay_wc_checkout_title');
        $this->title = empty($title) ? 'UnusPay' : $title;
        $description = get_option('unuspay_wc_checkout_description');
        $this->description = empty($description) ? null : $description;
        $this->blockchain = null;
    }

    public function get_title()
    {
        return $this->title;
    }

    public function get_icon()
    {
        $icon = '';
        if (empty(get_option('unuspay_wc_payment_key'))) {
            $icon = '';
        }  else {
            $post_response = wp_remote_get("https://dapp.unuspay.com/api/payment/link/blockchains?linkId=" . get_option('unuspay_wc_payment_key'),
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
        $url = esc_url(plugin_dir_url(__FILE__) . 'images/logo.jpg');
        $icon = $icon . "<img title='Unuspay' class='wc-unuspay-blockchain-logo' src='" . $url . "'/>";
            foreach ($post_response_json->data as $blockchain) {
                $url = esc_url(plugin_dir_url(__FILE__) . 'images/blockchains/' . $blockchain . '.svg');
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
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'UnusPay',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'Payment method description that the customer will see on your checkout.',
                'default' => '',
                'desc_tip' => true,
            )
        );
    }

    public function process_payment($order_id)
    {
        global $wpdb;
        $order = wc_get_order($order_id);

        if ($order->get_total() > 0) {

            $accept = $this->getUnusPayOrder($order);

            $result = $wpdb->insert("{$wpdb->prefix}wc_unuspay_checkouts", array(
                'id' => $accept->id,
                'order_id' => $order_id,
                'accept' => json_encode($accept),
                'created_at' => current_time('mysql')
            ));
            if (false === $result) {
                $error_message = $wpdb->last_error;
                UnusPay_WC_Payments::log('Storing checkout failed: ' . $error_message);
                throw new Exception('Storing checkout failed: ');
            }
          /*   $redirect_url = "Location: " . '#wc-unuspay-checkout-' . $accept->id . '@' . time();
            header($redirect_url);
            die();
            return rest_ensure_response('{}'); */
            return ([
                'result' => 'success',
                'redirect' => '#wc-unuspay-checkout-' . $accept->id . '@' . time()
                // 'redirect'       => get_option('woocommerce_enable_signup_and_login_from_checkout') === 'yes' ? $order->get_checkout_payment_url() . '#wc-depay-checkout-' . $checkout_id . '@' . time() : '#wc-depay-checkout-' . $checkout_id . '@' . time()
            ]);
        } else {
            $order->payment_complete();
        }
    }
 
    public function getUnusPayOrder($order)
    {
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $headers = array(
            'accept-language' => $lang,
            'Content-Type' => 'application/json; charset=utf-8',
        );
        $website = get_option("siteurl");

        $total = $order->get_total();
        $currency = $order->get_currency();

        $payment_key = get_option('unuspay_wc_payment_key');
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

    public function admin_options()
    {
        wp_redirect('/wp-admin/admin.php?page=wc-admin&path=%2Funuspay%2Fsettings');
    }

    
}
