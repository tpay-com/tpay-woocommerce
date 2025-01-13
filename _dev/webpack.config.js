const path = require("path");
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');

const wcDepMap = {
    '@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
    '@woocommerce/settings': ['wc', 'wcSettings']
};

const wcHandleMap = {
    '@woocommerce/blocks-registry': 'wc-blocks-registry',
    '@woocommerce/settings': 'wc-settings'
};

const requestToExternal = (request) => {
    if (wcDepMap[request]) {
        return wcDepMap[request];
    }
};

const requestToHandle = (request) => {
    if (wcHandleMap[request]) {
        return wcHandleMap[request];
    }
};

module.exports = {
    ...defaultConfig,
    entry: {
        main: ['./js/main.ts', './scss/main.scss'],
        admin: ['./js/admin.ts', './scss/admin.scss'],
        'checkout-blocks': ['./js/blocks/checkout.tsx', './js/blocks/checkout-blik.tsx', './js/blocks/checkout-cc.tsx', './js/blocks/checkout-pekao.tsx', './js/blocks/checkout-sf.tsx', './js/blocks/checkout-generic.tsx'],
        'thank-you': ['./js/thank-you.js', './scss/thank-you.scss'],
        cart: ['./js/installments/cart.ts', './scss/installments.scss'],
        checkout: ['./js/installments/checkout.ts', './scss/installments.scss'],
        product: ['./js/installments/product.ts', './scss/installments.scss'],
        'installments-blocks': ['./js/installments/block.tsx', './scss/installments.scss']
    },
    output: {
        path: path.resolve(__dirname, '../views/assets'), filename: '[name].min.js',
    },
    plugins: [...defaultConfig.plugins.filter((plugin) => plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'), new WooCommerceDependencyExtractionWebpackPlugin({
        requestToExternal, requestToHandle
    })]
}
