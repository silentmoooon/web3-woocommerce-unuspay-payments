(function ( ) {

jQuery(function ($) {
    $(document).ajaxError(function () {
        if (typeof window._depayUnmountLoading == "function") {
            window._depayUnmountLoading();
        }
    });

    $(document).ajaxComplete(function () {
        if (typeof window._depayUnmountLoading == "function") {
            window._depayUnmountLoading();
        }
    });

    $("form.woocommerce-checkout").on("submit", async () => {
        var values = $("form.woocommerce-checkout").serialize();
        if (values.match("payment_method=unuspay_wc_payments")) {
            let { unmount } = await DePayWidgets.Loading({
                text: "Loading payment data...",
            });
            setTimeout(unmount, 10000);
        }
    });
});

const feeReceivers = {
    ethereum: "0x9Db58B260EfAa2d6a94bEb7E219d073dF51cc7Bb",
    bsc: "0x9Db58B260EfAa2d6a94bEb7E219d073dF51cc7Bb",
    polygon: "0x9Db58B260EfAa2d6a94bEb7E219d073dF51cc7Bb",
    solana: "5hqJfrh7SrokFqj16anNqACyUv1PCg7oEqi7oUya1kMQ",
    fantom: "0x9Db58B260EfAa2d6a94bEb7E219d073dF51cc7Bb",
    gnosis: "0x9Db58B260EfAa2d6a94bEb7E219d073dF51cc7Bb",
    avalanche: "0x9Db58B260EfAa2d6a94bEb7E219d073dF51cc7Bb",
    arbitrum: "0x9Db58B260EfAa2d6a94bEb7E219d073dF51cc7Bb",
    optimism: "0x9Db58B260EfAa2d6a94bEb7E219d073dF51cc7Bb",
    base: "0x9Db58B260EfAa2d6a94bEb7E219d073dF51cc7Bb",
};

const displayCheckout = async () => {
    if (window.location.hash.startsWith("#wc-unuspay-checkout-")) {
        const checkoutId = window.location.hash.match(
            /wc-unuspay-checkout-(.*?)@/
        )[1];
        const response = JSON.parse(
            await wp.apiRequest({
                path: `/unuspay/wc/checkouts/${checkoutId}`,
                method: "POST",
            })
        );
        if (response.redirect) {
            window.location = response.redirect;
            return;
        }
        const paymentInfo = [];
        response.tokens.forEach((token) => {
            paymentInfo.push({
                blockchain: token.blockchain,
                amount: token.amount,
                token: token.tokenAddress,
                receiver: token.receiveAddress,
                fee: {
                    amount: token.feeRate + "%",
                    receiver: token.feeAddress,
                },
            });
        });
        let configuration = {
            accept: paymentInfo,
            closed: () => {
                window.location.hash = "";
                window.location.reload(true);
            },
            track: {
                method: (payment) => {
                    return new Promise((resolve, reject) => {
                        try {
                            wp.apiRequest({
                                path: `/unuspay/wc/checkouts/${checkoutId}/track`,
                                method: "POST",
                                data: payment,
                            })
                                .done(() => resolve({ status: 200 }))
                                .fail((request, status) => reject(status));
                        } catch {
                            reject();
                        }
                    });
                },
                poll: {
                    method: () => {
                        return new Promise((resolve, reject) => {
                            wp.apiRequest({
                                path: "/unuspay/wc/release",
                                method: "POST",
                                data: { checkout_id: checkoutId },
                            })
                                .done((responseData) => {
                                    resolve(responseData);
                                })
                                .fail(resolve);
                        });
                    },
                },
            },
        };
        if (
            window.UNUSPAY_WC_CURRENCY &&
            window.UNUSPAY_WC_CURRENCY.displayCurrency == "store" &&
            window.UNUSPAY_WC_CURRENCY.storeCurrency?.length
        ) {
            configuration.currency = window.UNUSPAY_WC_CURRENCY.storeCurrency;
        }
        DePayWidgets.Payment(configuration);
    }
};

document.addEventListener('DOMContentLoaded', displayCheckout);
window.addEventListener('hashchange', displayCheckout);

})()
