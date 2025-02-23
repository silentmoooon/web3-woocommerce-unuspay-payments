const useRef = window.React.useRef
const useState = window.React.useState
const useEffect = window.React.useEffect

export default function(props) {

  const { Button } = window.wp.components
  const [ settingsAreLoaded, setSettingsAreLoaded ] = useState(false)
  const [ isSaving, setIsSaving ] = useState()
  const [ isDisabled, setIsDisabled ] = useState()
  const [ checkoutTitle, setCheckoutTitle ] = useState('UnusPay')
  const [ paymentKey, setPaymentKey ] = useState()


  const setReceivingWalletAddress = (receiver, index, blockchain)=>{
    
    let newTokens = [...tokens]
    if(!receiver || receiver.length === 0) {
      newTokens[index].error = 'Please enter a receiver address!'
    } else {
      try {
        if(blockchain === 'solana') {
          receiver = new SolanaWeb3js.PublicKey(receiver).toString()
        } else {
          receiver = ethers.ethers.utils.getAddress(receiver)
        }
        newTokens[index].error = undefined
      } catch {
        newTokens[index].error = 'This address is invalid!'
      }
    }

    newTokens[index].receiver = receiver
    setTokens(newTokens)
  }

  const connectWallet = async(index, blockchain)=> {
    let { account, accounts, wallet }  = await window.DePayWidgets.Connect()
    setReceivingWalletAddress(account, index, blockchain)
  }

  const addToken = async ()=>{
    let token = await DePayWidgets.Select({ what: 'token' })
    if((tokens instanceof Array) && tokens.find((selectedToken)=>(selectedToken.blockchain == token.blockchain && selectedToken.address == token.address))) { return }
    token.error = 'Please enter a receiver address!'
    if(tokens instanceof Array) {
      setTokens(tokens.concat([token]))
    } else {
      setTokens([token])
    }
  }

  const removeToken = (index)=> {
    let newTokens = tokens.slice()
    newTokens.splice(index, 1)
    setTokens(newTokens)
  }

  const selectTokenForDenomination = async ()=>{
    let token = await DePayWidgets.Select({ what: 'token' })
    setTokenForDenomination(token)
  }

  const unsetTokenForDenomination = ()=> {
    setTokenForDenomination(undefined)
  }

  const saveSettings = () => {
      setIsSaving(true);
      const settings = new window.wp.api.models.Settings({

          depay_wc_checkout_title: checkoutTitle,
          unuspay_wc_payment_key: paymentKey,
      });

      settings.save().then((response) => {
          window.location.hash = "unuspay-settings-saved";
          window.location.reload(true);
      });
  };

  useEffect(() => {
      wp.api.loadPromise
          .then(() => {
              const settings = new wp.api.models.Settings();
              settings.fetch().then((response) => {

                  setSettingsAreLoaded(true);
                  setCheckoutTitle(
                      response.unuspay_wc_checkout_title || "UnusPay"
                  );

                  setPaymentKey(response.unuspay_wc_payment_key || "");
              });
          })
          .catch(() => {});
  }, []);

  useEffect(() => {
      if (tokens) {
          let count = {};
          tokens.forEach((token) => {
              if (count[token.blockchain] == undefined) {
                  count[token.blockchain] = 1;
              } else {
                  count[token.blockchain] += 1;
              }
          });
          setTooManyTokensPerChain(
              !!Object.values(count).find((value) => value > 2)
          );
      }
  }, [tokens]);

  useEffect(() => {
      setIsDisabled(
          !(
              tokens &&
              tokens.length &&
              tokens.every(
                  (token) =>
                      token.receiver &&
                      token.receiver.length > 0 &&
                      token.error === undefined
              )
          )
      );
  }, [tokens]);

  if (!settingsAreLoaded) {
      return null;
  }

  return (
      <div>
          <div className="woocommerce-section-header">
              <h2 className="woocommerce-section-header__title woocommerce-section-header__header-item">
                  Settings
              </h2>
              <hr role="presentation" />
          </div>

          {window.UNUSPAY_WC_SETUP.bcmath !== "1" && (
              <div className="woocommerce-settings__wrapper">
                  <div className="woocommerce-setting">
                      <div className="woocommerce-setting__label">
                          <label for="unuspay-woocommerce-payment-receiver-address">
                              Missing Requirements
                          </label>
                      </div>
                      <div className="woocommerce-setting__input">
                          <div className="notice inline notice-warning notice-alt">
                              <p>
                                  You need to install the "bcmath" php
                                  package!&nbsp;
                                  <a
                                      href="https://www.google.com/search?q=how+to+install+bcmath+php+wordpress"
                                      target="_blank"
                                  >
                                      Learn How
                                  </a>
                              </p>
                          </div>
                      </div>
                  </div>
              </div>
          )}

          {window.location.hash.match("unuspay-settings-saved") && (
              <div className="woocommerce-settings__wrapper">
                  <div className="woocommerce-setting">
                      <div className="woocommerce-setting__label">
                          <label for="unuspay-woocommerce-payment-receiver-address"></label>
                      </div>
                      <div className="woocommerce-setting__input">
                          <div className="notice inline notice-success notice-alt">
                              <p>Settings have been saved successfully.</p>
                          </div>
                      </div>
                  </div>
              </div>
          )}
 
         
         
          <div className="woocommerce-settings__wrapper">
              <div className="woocommerce-setting">
                  <div className="woocommerce-setting__label">
                      <label>Payment key</label>
                  </div>
                  <div className="woocommerce-setting__input">
                      <div className="woocommerce-setting__options-group">
                          <p className="description">
                              To increase your request limit towards UnusPay APIs,
                              please enter your Payment key here:
                          </p>
                          <div>
                              <label>
                                  <span className="woocommerce-settings-historical-data__progress-label">
                                  Payment key
                                  </span>
                                  <input
                                      type="text"
                                      value={paymentKey || ""}
                                      onChange={(e) => {
                                          setPaymentKey(e.target.value);
                                      }}
                                      style={{ width: "100%" }}
                                  />
                              </label>
                          </div>
                      </div>
                  </div>
              </div>
          </div>

          <div className="woocommerce-settings__wrapper">
              <div className="woocommerce-setting">
                  <div className="woocommerce-setting__label">
                      <label>Save</label>
                  </div>
                  <div className="woocommerce-setting__input">
                      <div className="woocommerce-setting__options-group">
                          <p className="description">
                              Make sure to save your settings:
                          </p>
                      </div>
                  </div>
              </div>
          </div>

          <div className="woocommerce-settings__wrapper">
              <div className="woocommerce-setting">
                  <div className="woocommerce-setting__label"></div>
                  <div className="woocommerce-setting__input">
                      <Button isPrimary isLarge onClick={() => saveSettings()}>
                          Save Settings
                      </Button>
                  </div>
              </div>
          </div>
      </div>
  );
}
