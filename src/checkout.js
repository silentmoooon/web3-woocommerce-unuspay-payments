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
            let { unmount } = await UnusPayWidgets.Loading({
                text: "Loading payment data...",
            });
            setTimeout(unmount, 10000);
        }
    });
});


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
                id: checkoutId,
                endpoint: "/wp-json/unuspay/wc/track",
                poll: {
                    endpoint: "/wp-json/unuspay/wc/release"
                }

            },
        };
        // if (
        //     window.UNUSPAY_WC_CURRENCY &&
        //     window.UNUSPAY_WC_CURRENCY.displayCurrency == "store" &&
        //     window.UNUSPAY_WC_CURRENCY.storeCurrency?.length
        // ) {
        //     configuration.currency = window.UNUSPAY_WC_CURRENCY.storeCurrency;
        // }
        UnusPayWidgets.Payment(configuration);
    }
};

document.addEventListener('DOMContentLoaded', displayCheckout);
window.addEventListener('hashchange', displayCheckout);

})()
