=== UnusPay Crypto Payments ===
Contributors: UnusPay
Tags: web3, payments, woocommerce, cryptocurrency
Requires at least: 6.0
Tested up to: 10.0
Requires PHP: 7.2
Stable tag: 1.0.1
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept Web3 payments, supporting various cryptocurrency tokens, blockchains and wallets, with the UnusPay Payments extension for WooCommerce.

== Source Code ==

The plugin includes minified assets in the /dist folder.  
Source files can be found in the /src directory or at:  
https://github.com/unuspay/widgets

== Public REST API Endpoints ==

This plugin registers several public REST API endpoints under the `/wp-json/unuspay/wc/` namespace. These endpoints are intentionally exposed without authentication (`__return_true`) for integration with the UnusPay payment platform. They are safe for public use and designed for communication between the WooCommerce store and UnusPay.

- `/wp-json/unuspay/wc/checkouts/{id}`  
  Used by the user to create a UnusPay order and initiate the payment process.

- `/wp-json/unuspay/wc/track`  
  Used by the user to submit payment results to UnusPay for tracking the payment status.

- `/wp-json/unuspay/wc/release`  
  Used by the user to query the payment status from UnusPay to check whether the transaction has been verified.

- `/wp-json/unuspay/wc/validate`  
  Called by UnusPay to send a notification after the transaction has been successfully verified.

These endpoints are required for the payment workflow and are meant to be accessible from external clients and the UnusPay server. If needed, additional security mechanisms such as token validation can be implemented on the server side.

== External Services ==

This plugin relies on external services provided by UnusPay to enable cryptocurrency payments.  
Below are the external endpoints used, along with explanations of what data is sent, when, and why:

1. **UnusPay Blockchain Info API**  
   - **Endpoint**: https://dapp.unuspay.com/api/payment/link/blockchains  
   - **Purpose**: Retrieves a list of supported blockchains for a given payment.  
   - **Data Sent**: payment key
   - **When**: Called when initiating a new crypto payment to show available blockchain options.  
   - **Terms of Service**: https://unuspay.com/terms-of-use/  
   - **Privacy Policy**: https://unuspay.com/privacy-policy/

2. **UnusPay Order Creation API**  
   - **Endpoint**: https://dapp.unuspay.com/api/payment/ecommerce/order  
   - **Purpose**: Creates a crypto payment order on the UnusPay system.  
   - **Data Sent**: website, lang, orderId,email, payment key, currency, amount, commerceType
   - **When**: Called when a user confirms checkout with cryptocurrency as the selected payment method.  
   - **Terms of Service**: https://unuspay.com/terms-of-use/  
   - **Privacy Policy**: https://unuspay.com/privacy-policy/

3. **UnusPay Payment API**  
   - **Endpoint**: https://dapp.unuspay.com/api/payment/pay  
   - **Purpose**: Initiates the payment process for the order.  
   - **Data Sent**: payment informations , etc.Order ID, supported blockchains and tokens, amount  etc. 
   - **When**: Called after order creation when payment is being initiated.  
   - **Terms of Service**: https://unuspay.com/terms-of-use/  
   - **Privacy Policy**: https://unuspay.com/privacy-policy/

4. **UnusPay Payment Status API**  
   - **Endpoint**: https://dapp.unuspay.com/api/payment/release  
   - **Purpose**: Checks the status of a payment to confirm whether it was completed.  
   - **Data Sent**: Order ID.  
   - **When**: Called periodically after payment initiation to track payment confirmation.  
   - **Terms of Service**: https://unuspay.com/terms-of-use/  
   - **Privacy Policy**: https://unuspay.com/privacy-policy/

5. **Web3 Wallet Interaction (e.g., MetaMask, WalletConnect, etc.)**  
    - **Endpoint**: https://verify.walletconnect.com, https://api.mainnet-beta.solana.com, https://usernames.worldcoin.org/api/v1/query etc.
   - **Purpose**: Allows users to authorize and sign blockchain transactions using their own Web3 wallet.  
   - **Data Sent**: Public wallet address, transaction payload (amount, destination address, gas fees), and digital signature — initiated and approved by the user within their wallet app.  
   - **When**: Triggered when a user clicks “Pay with Web3 Wallet” and confirms the transaction using their browser extension or mobile wallet.  
   - **Note**: These actions are handled entirely by the user's wallet and blockchain network. This plugin does not collect or transmit private keys.  
   - **Privacy Policies of common providers**:  
     - MetaMask: https://consensys.net/privacy-policy/  
     - WalletConnect: https://walletconnect.com/privacy-policy/  
     - Ethereum: https://ethereum.org/en/privacy-policy/

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

