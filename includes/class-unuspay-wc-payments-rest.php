<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\PublicKeyLoader;

class UnusPay_WC_Payments_Rest {

	private static $key = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAtqsu0wy94cpz90W4pGsJ\nSf0bfvmsq3su+R1J4AoAYz0XoAu2MXJZM8vrQvG3op7OgB3zze8pj4joaoPU2piT\ndH7kcF4Mde6QG4qKEL3VE+J8CL3qK2dUY0Umu20x/O9O792tlv8+Q/qAVv8yPfdM\nn5Je9Wc7VI5XeIBKP2AzsCkrXuzQlR48Ac5LpViNSSLu0mz5NTBoHkW2sz1sNWc6\nUpYISJkiKTvYc8Bo4p5xD6+ZmlL4hj1Ad/+26SjYcisX2Ut4QD7YKRBP2SbItVkI\nqp9mp6c6MCKNmEUkosxAr0KVfOcrk6/fcc4tI8g+KYZ32G11Ri8Xo4fgHH06DLYP\n3QIDAQAB\n-----END PUBLIC KEY-----\n";

	public function register_routes() {

		register_rest_route(
			'unuspay/wc',
			'/checkouts/(?P<id>[\w-]+)', 
			[
				'methods' => 'POST',
				'callback' => [ $this, 'get_checkout_accept' ],
				'permission_callback' => '__return_true'
			]
		);
		register_rest_route(
			'unuspay/wc',
			'/track',
			[
				'methods' => 'POST',
				'callback' => [ $this, 'track_payment' ],
				'permission_callback' => '__return_true'
			]
		);
		register_rest_route(
			'unuspay/wc', 
			'/validate',
			[
				'methods' => 'POST',
				'callback' => [ $this, 'validate_payment' ],
				'permission_callback' => '__return_true'
			]
		);
		register_rest_route(
			'unuspay/wc',
			'/release',
			[
				'methods' => 'POST',
				'callback' => [ $this, 'check_release' ],
				'permission_callback' => '__return_true'
			]
		);
        /*
		 register_rest_route(
			'unuspay/wc',
			'/transactions',
			[
				'methods' => 'GET',
				'callback' => [ $this, 'fetch_transactions' ],
				'permission_callback' => array( $this, 'must_be_wc_admin' ) 
			]
		);
        
		register_rest_route(
			'unuspay/wc',
			'/transaction',
			[
				'methods' => 'DELETE',
				'callback' => [ $this, 'delete_transaction' ],
				'permission_callback' => array( $this, 'must_be_wc_admin' ) 
			]
		); */
		/* register_rest_route(
			'unuspay/wc',
			'/confirm',
			[
				'methods' => 'POST',
				'callback' => [ $this, 'confirm_payment' ],
				'permission_callback' => array( $this, 'must_be_wc_admin' )
			]
		); */
		/* register_rest_route(
			'unuspay/wc',
			'/debug', 
			[
				'methods' => 'GET',
				'callback' => [ $this, 'debug' ],
				'permission_callback' => array( $this, 'must_be_signed_by_remote' )
			]
		); */
	}

	public function get_checkout_accept( $request ) {

		global $wpdb;
		$id = $request->get_param( 'id' );
		$accept = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT accept FROM {$wpdb->prefix}wc_unuspay_checkouts WHERE id = %s LIMIT 1",
				$id
			)
		);
		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_id FROM {$wpdb->prefix}wc_unuspay_checkouts WHERE id = %s LIMIT 1",
				$id
			)
		);
		$checkout_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}wc_unuspay_checkouts WHERE id = %s LIMIT 1",
				$id
			)
		);
		$order = wc_get_order( $order_id );

		if ( $order->has_status('completed') || $order->has_status('processing') ) {
			$response = rest_ensure_response( 
				json_encode( [
					'redirect' => $order->get_checkout_order_received_url()
				] )
			);
		} else {
			$response = rest_ensure_response( $accept );
		}

	/* 	$response->header( 'X-Checkout', json_encode( [ 
			'request_id' => $id,
			'checkout_id' => $checkout_id,
			'order_id' => $order_id,
			'total' => $order->get_total(),
			'currency' => $order->get_currency()
		] ) ); */
		return $response;
	}

	public function track_payment( $request ) {

		global $wpdb;
        $body = $request->get_body();
        $jsonBody = json_decode($body,true);
		$id = $jsonBody["orderId"];
		$accept = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT accept FROM {$wpdb->prefix}wc_unuspay_checkouts WHERE id = %s LIMIT 1",
				$id
			)
		);
		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_id FROM {$wpdb->prefix}wc_unuspay_checkouts WHERE id = %s LIMIT 1",
				$id
			)
		);
		$order = wc_get_order( $order_id );

		$tracking_uuid;

		$total = $order->get_total();

		$transaction_id = $jsonBody["transaction"];

		if ( empty($transaction_id) ) { // PAYMENT TRACE

			if ( $order->has_status('completed') || $order->has_status('processing') ) {
				UnusPay_WC_Payments::log( 'Order has been completed already!' );
				throw new Exception( 'Order has been completed already!' );
			}

			
		} else { // PAYMENT TRACKING
            $tracking_uuid = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT tracking_uuid FROM {$wpdb->prefix}wc_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
                    $id
                )
            );
            if (empty($tracking_uuid)) {
                $tracking_uuid = wp_generate_uuid4();
			    $result = $wpdb->insert("{$wpdb->prefix}wc_unuspay_transactions", array(
                    'order_id' => $order_id,
                    'checkout_id' => $id,
                    'tracking_uuid' => $tracking_uuid,
                    'blockchain' => $jsonBody["blockchain"],
                    'transaction_id' => $transaction_id,
                    'sender_id' => $jsonBody["sender"],
                    'receiver_id' => '',
                    'token_id' => '',
                    'amount' => 0.00,
                    'status' => 'VALIDATING',

                    'created_at' => current_time( 'mysql' )
			    ) );
                if ( false === $result ) {
                    UnusPay_WC_Payments::log( 'Storing tracking failed!' );
                    throw new Exception( 'Storing tracking failed!!' );
                }
            }
		}

		$endpoint = 'https://dapp.unuspay.com/api/payment/pay';

        $jsonBody["callback"] = get_site_url(null, 'wp-json/unuspay/wc/validate');
        $jsonBody["trackingId"] = $tracking_uuid;
        $jsonBody["orderId"] = $id;

        $headers = array(
            'Content-Type' => 'application/json; charset=utf-8',
            'csrf_token' => $id
        );
		$post = wp_remote_post( $endpoint,
			array(
                'headers' => $headers,
				'body' => json_encode($jsonBody),
				'method' => 'POST',
				'data_format' => 'body'
			)
		);

		$response = rest_ensure_response(json_decode(wp_remote_retrieve_body($post),true));
        $response->set_status(200);
        return $response;
	}

	public function check_release( $request ) {

		global $wpdb;
        $body = $request->get_body();
        $jsonBody = json_decode($body,true);

        $checkout_id =  $jsonBody["orderId"];
		$existing_transaction_status = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT status FROM {$wpdb->prefix}wc_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
				$checkout_id
			)
		);

		if ( 'VALIDATING' === $existing_transaction_status ) {
			$tracking_uuid = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT tracking_uuid FROM {$wpdb->prefix}wc_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
					$checkout_id
				)
			);
			
			$endpoint = 'https://dapp.unuspay.com/api/payment/release';
            $headers = array(
                'Content-Type' => 'application/json; charset=utf-8',
            );
            $response = wp_remote_post( $endpoint,
                array(
                    'headers' => $headers,
                    'body' => json_encode($jsonBody),
                    'method' => 'POST',
                    'data_format' => 'body'
                )
            );
                
            $rspBody = json_decode(wp_remote_retrieve_body($response));
            if (!is_wp_error($response) && (wp_remote_retrieve_response_code($response) == 200) && $rspBody->code == 200) {

                $order_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT order_id FROM {$wpdb->prefix}wc_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
                        $tracking_uuid
                    )
                );

                
                $expected_transaction = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT transaction_id FROM {$wpdb->prefix}wc_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
                        $tracking_uuid
                    )
                );
                $order = wc_get_order( $order_id );
                //$responseBody = json_decode( $response['body'] );
                $status = $rspBody->data->status;
                $transaction = $rspBody->data->transaction;

                if ( $expected_transaction != $transaction ) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->prefix}wc_unuspay_transactions SET transaction_id = %s WHERE tracking_uuid = %s",
                            $transaction,
                            $tracking_uuid
                        )
                    );
                }

                if (
                    'success' === $status
                ) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->prefix}wc_unuspay_transactions SET status = %s, confirmed_at = %s, confirmed_by = %s, failed_reason = NULL WHERE tracking_uuid = %s",
                            'SUCCESS',
                            current_time( 'mysql' ),
                            'API',
                            $tracking_uuid
                        )
                    );
                    $order->payment_complete();
                } else if ( 'failed' === $status ) {
                    $failed_reason = 'fail';
                    if ( empty( $failed_reason ) ) {
                        $failed_reason = 'MISMATCH';
                    }
                    UnusPay_WC_Payments::log( 'Validation failed: ' . $failed_reason );
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE {$wpdb->prefix}wc_unuspay_transactions SET failed_reason = %s, status = %s, confirmed_by = %s WHERE tracking_uuid = %s",
                            $failed_reason,
                            'FAILED',
                            'API',
                            $tracking_uuid
                        )
                    );
                    $order->update_status('failed', '');
                }
			}
		}

		$existing_transaction_status = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT status FROM {$wpdb->prefix}wc_unuspay_transactions WHERE checkout_id = %s ORDER BY created_at DESC LIMIT 1",
				$checkout_id
			)
		);

		if ( empty( $existing_transaction_status ) || 'VALIDATING' === $existing_transaction_status ) {
			$response = new WP_REST_Response("{}");
			$response->set_status( 200 );
			return $response;
		}

		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_id FROM {$wpdb->prefix}wc_unuspay_transactions WHERE checkout_id = %s ORDER BY id DESC LIMIT 1",
				$checkout_id
			)
		);
		$order = wc_get_order( $order_id );

		if ( 'SUCCESS' === $existing_transaction_status ) {
			$response = rest_ensure_response( [
			    'code' => 200,
			    'data' =>[
                    'status'=>'success',
                    'forward_to' => $order->get_checkout_order_received_url()
				]
			] );
			$response->set_status( 200 );
			return $response;
		} else {
			$failed_reason = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT failed_reason FROM {$wpdb->prefix}wc_unuspay_transactions WHERE checkout_id = %s ORDER BY id DESC LIMIT 1",
					$checkout_id
				)
			);
			$response = rest_ensure_response( [
            			    'code' => 500,
            			    'data' =>[
            			        'status'=>'failed'
            				]
            			] );

			$response->set_status( 200 );
			return $response;
		}
	}

	public function validate_payment( $request ) {
		global $wpdb;
		$response = new WP_REST_Response();
        $body = $request->get_body();
        $jsonBody = json_decode($body,true);

        $tracking_uuid = $jsonBody['trackingId'];
		$existing_transaction_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}wc_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
				$tracking_uuid
			)
		);

		if ( empty( $existing_transaction_id ) ) {
			UnusPay_WC_Payments::log( 'Transaction not found for tracking_uuid' );
			$response->set_status( 404 );
			return $response;
		}

		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_id FROM {$wpdb->prefix}wc_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
				$tracking_uuid
			)
		);
		 
		$expected_transaction = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT transaction_id FROM {$wpdb->prefix}wc_unuspay_transactions WHERE tracking_uuid = %s ORDER BY id DESC LIMIT 1",
				$tracking_uuid
			)
		);
		$order = wc_get_order( $order_id );
		$status = $jsonBody['status'];
        $transaction = $jsonBody['transaction'];

		if ( $expected_transaction != $transaction ) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}wc_unuspay_transactions SET transaction_id = %s WHERE tracking_uuid = %s",
					$transaction,
					$tracking_uuid
				)
			);
		}

		if (
			'success' === $status 
		) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}wc_unuspay_transactions SET status = %s, confirmed_at = %s, confirmed_by = %s, failed_reason = NULL WHERE tracking_uuid = %s",
					'SUCCESS',
					current_time( 'mysql' ),
					'API',
					$tracking_uuid
				)
			);
			$order->payment_complete();
		} else {
			$failed_reason = $request->get_param( 'failed_reason' );
			if ( empty( $failed_reason ) ) {
				$failed_reason = 'MISMATCH';
			}
            $order->update_status('failed', '');
			UnusPay_WC_Payments::log( 'Validation failed: ' . $failed_reason );
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}wc_unuspay_transactions SET failed_reason = %s, status = %s, confirmed_by = %s WHERE tracking_uuid = %s",
					$failed_reason,
					'FAILED',
					'API',
					$tracking_uuid
				)
			);
		}

		$response->set_status( 200 );
		return $response;
	}
 

}
