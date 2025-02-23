const replace = require('replace-in-file')
const prompt = require('prompt')
const package = require('./package.json')
const openExplorer = require('open-file-explorer')

console.log(`Enter new version (current version ${package.version})`)
prompt.start()
prompt.get(['version'], async (err, result)=> {
  if(!result.version) { return }
  if(!result.version.match(/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/)){
    console.log('WRONG VERSION FROMAT (see: https://semver.org)')
    return
  }
  
  const date = new Date()
  var month = date.getUTCMonth() + 1
  var day = date.getUTCDate()
  var year = date.getUTCFullYear()

  await replace({ files: './package.json', from: `"version": "${package.version}",`, to: `"version": "${result.version}",`})
  await replace({ files: './readme.txt', from: `Stable tag: ${package.version}`, to: `Stable tag: ${result.version}`})
  await replace({
      files: "./changelog.txt",
      from: `*** UnusPay Web3 Payments for WooCommerce Changelog ***`,
      to: `*** UnusPay Web3 Payments for WooCommerce Changelog ***\n\n${year}-${month}-${day} - version ${result.version}\n*`,
  });
  await replace({
      files: "./unuspay-woocommerce-payments.php",
      from: `* Version: ${package.version}`,
      to: `* Version: ${result.version}`,
  });
  await replace({
      files: "./unuspay-woocommerce-payments.php",
      from: `define( 'UNUSPAY_CURRENT_VERSION', '${package.version}' );`,
      to: `define( 'UNUSPAY_CURRENT_VERSION', '${result.version}' );`,
  });
  await replace({
      files: "./languages/unuspay-woocommerce-payments.pot",
      from: `Project-Id-Version: UnusPay WooCommerce Payments ${package.version}`,
      to: `Project-Id-Version: UnusPay WooCommerce Payments ${result.version}`,
  });

  console.log('Dont forget to add a changelog entry!')
  openExplorer('./changelog.txt')
})

