import AdminSettingsPage from './admin/AdminSettingsPage'
import AdminTransactionsPage from './admin/AdminTransactionsPage'
import SetupTask from './admin/SetupTask'

(function ( React, hooks ) {

  hooks.addFilter(
      "woocommerce_admin_onboarding_task_list",
      "unuspay-woocommerce-payments",
      (tasks) => {
          let completed = window.UNUSPAY_WC_SETUP.done == "1";
          const task = {
              key: "setup_unuspay_wc_payments",
              title: "Set up UnusPay",
              content:
                  "Simply connect your wallet and select the tokens you want to receive as payments.",
              container: <SetupTask />,
              completed,
              visible: !completed,
              additionalInfo:
                  "Simply connect your wallet and select the tokens you want to receive as payments.",
              time: "1 minute",
              isDismissable: false,
              type: "extension",
          };
          return [...tasks, task, { ...task, key: "payments", visible: false }];
      }
  );

  hooks.addFilter(
      "woocommerce_admin_pages_list",
      "unuspay-woocommerce-payments",
      (pages) => {
          pages.push({
              container: AdminSettingsPage,
              path: "/unuspay/settings",
              breadcrumbs: ["UnusPay", "Settings"],
              capability: "manage_woocommerce",
              wpOpenMenu: "toplevel_page_wc-admin-path--unuspay-settings",
              navArgs: {
                  id: "unuspay-woocommerce-payments-settings",
              },
          });
         /* pages.push({
              container: AdminTransactionsPage,
              path: "/unuspay/transactions",
              breadcrumbs: ["UnusPay", "Transactions"],
              capability: "manage_woocommerce",
              wpOpenMenu: "toplevel_page_wc-admin-path--unuspay-settings",
              navArgs: {
                  id: "unuspay-woocommerce-payments-transactions",
              },
          });*/
          return pages;
      }
  );

})(
  window.React,
  window.wp.hooks
)

