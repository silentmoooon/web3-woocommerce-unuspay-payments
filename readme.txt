=== UnusPay Crypto Payments ===
Contributors: UnusPay
Tags: web3, payments, woocommerce, depay, cryptocurrency
Requires at least: 6.0
Tested up to: 10.0
Requires PHP: 7.2
Stable tag: 1.0.1
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept Web3 payments, supporting various cryptocurrency tokens, blockchains and wallets, with the UnusPay Payments extension for WooCommerce.

== Public REST API Endpoints ==

This plugin registers several public REST API endpoints under the `/wp-json/unuspay/wc/` namespace. These endpoints are intentionally exposed without authentication (`__return_true`) for integration with the UnusPay payment platform. They are safe for public use and designed for communication between the WooCommerce store and UnusPay.

- `/wp-json/unuspay/wc/checkouts/{id}`  
  Used by the user to create a UnusPay order and initiate the payment process.

- `/wp-json/unuspay/wc/track`  
  Used by the user to submit payment results to UnusPay for tracking the payment status.

- `/wp-json/unuspay/wc/validate`  
  Used by the user to query the payment status from UnusPay to check whether the transaction has been verified.

- `/wp-json/unuspay/wc/callback`  
  Called by UnusPay to send a notification after the transaction has been successfully verified.

These endpoints are required for the payment workflow and are meant to be accessible from external clients and the UnusPay server. If needed, additional security mechanisms such as token validation can be implemented on the server side.


== Simple Web3 Cryptocurrency Payments with UnusPay ==

[youtube https://www.youtube.com/watch?v=o3ANPF-eXZ0]


== Supported Blockchains ==

* Ethereum
* BNB Smart Chain
* Polygon
* Solana
* Fantom
* Gnosis
* Avalanche
* Arbitrum
* Optimism
* Base

== Supported Tokens ==

All* standard tokens.

* if the token standard is strictly adhered to and the token is convertible on a supported decentralized exchange. Check UnusPay’s documentation for further details about [what tokens are supported](https://unuspay.com/docs/payments/supported/tokens/).

