(function ( React ) {

  const settings = window.wc.wcSettings.getPaymentMethodData(
      "unuspay_wc_payments"
  );

  if (settings) {
      const DePayPaymentBlockLabel = ({}) => {
          return (
              <span>
                  {settings.title}
                  <span className="wc-unuspay-icons-container">
                      {settings.blockchains.map((blockchain, index) => {
                          return (
                              <img
                                  className="wc-unuspay-blockchain-icon"
                                  key={index}
                                  title={`Payments on #{blockchain}`}
                                  src={`${settings.pluginUrl}images/blockchains/${blockchain}.svg`}
                              />
                          );
                      })}
                  </span>
              </span>
          );
      };

      const DePayPaymentBlockContent = ({}) => {
          return (
              <>
                  {settings.description && settings.description.length > 0 && (
                      <div>{settings.description}</div>
                  )}
              </>
          );
      };

      window.wc.wcBlocksRegistry.registerPaymentMethod({
          name: "unuspay_wc_payments",
          label: Object(window.wp.element.createElement)(
              DePayPaymentBlockLabel,
              null
          ),
          ariaLabel: settings.title,
          content: Object(window.wp.element.createElement)(
              DePayPaymentBlockContent,
              null
          ),
          edit: Object(window.wp.element.createElement)(
              DePayPaymentBlockContent,
              null
          ),
          canMakePayment: () => {
              return true;
          },
          paymentMethodId: "unuspay_wc_payments",
          supports: {
              features: ["products"],
          },
      });
  }

  [
      "arbitrum",
      "avalanche",
      "base",
      "bsc",
      "ethereum",
      "fantom",
      "gnosis",
      "optimism",
      "polygon",
      "solana",
  ].forEach((blockchain) => {
      const settings = window.wc.wcSettings.getPaymentMethodData(
          `unuspay_wc_payments_${blockchain}`
      );

      if (settings) {
          const DePayPaymentPerBlockchainBlockLabel = ({}) => {
              return (
                  <span>
                      {settings.title}
                      <span className="wc-unuspay-icons-container">
                          <img
                              className="wc-unuspay-blockchain-icon"
                              title={`Payments on #{blockchain}`}
                              src={`${settings.pluginUrl}images/blockchains/${blockchain}.svg`}
                          />
                      </span>
                  </span>
              );
          };

          const DePayPaymentPerBlockchainBlockContent = ({}) => {
              return (
                  <>
                      {settings.description && (
                          <div>{settings.description}</div>
                      )}
                  </>
              );
          };

          window.wc.wcBlocksRegistry.registerPaymentMethod({
              name: `unuspay_wc_payments_${blockchain}`,
              label: Object(window.wp.element.createElement)(
                  DePayPaymentPerBlockchainBlockLabel,
                  null
              ),
              ariaLabel: settings.title,
              content: Object(window.wp.element.createElement)(
                  DePayPaymentPerBlockchainBlockContent,
                  null
              ),
              edit: Object(window.wp.element.createElement)(
                  DePayPaymentPerBlockchainBlockContent,
                  null
              ),
              canMakePayment: () => {
                  return true;
              },
              paymentMethodId: `unuspay_wc_payments_${blockchain}`,
              supports: {
                  features: ["products"],
              },
          });
      }
  });

})( window.React )
