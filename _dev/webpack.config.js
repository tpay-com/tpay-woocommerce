const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require("terser-webpack-plugin");

let config = {
    target: ["web", "es5"],
    entry: {
        main: ['./js/main.ts', './scss/main.scss'],
        admin: ['./js/admin.ts', './scss/admin.scss'],
        checkout: ['./js/blocks/checkout.ts', './js/blocks/checkout-blik.ts', './js/blocks/checkout-sf.ts', './js/blocks/checkout-cc.ts', './js/blocks/checkout-gpay.ts', './js/blocks/checkout-installments.ts', './js/blocks/checkout-twisto.ts', './js/blocks/checkout-pekao.ts'],
        'thank-you': ['./js/thank-you.js', './scss/thank-you.scss']
    },
    output: {
        path: path.resolve(__dirname, '../views/js'),
        filename: '[name].min.js'
    },
    module: {
        rules: [
            {
                test: /\.ts?$/,
                loader: 'esbuild-loader',
                options: {loader: 'ts', target: 'es2015'}
            },
            {
                test: /\.js$/,
                loader: 'esbuild-loader',
                options: {loader: 'js', target: 'es2015'}
            },
            {
                test: /\.js/,
                loader: 'esbuild-loader'
            },
            {
                test: /\.scss$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader'],
            },
            {
                test: /.(png|woff(2)?|eot|otf|ttf|svg|gif)(\?[a-z0-9=\.]+)?$/,
                use: [{loader: 'file-loader', options: {name: '../css/[hash].[ext]'}}]
            },
            {
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, 'style-loader', 'css-loader'],
            }
        ]
    },
    resolve: {extensions: ['.ts', '.tsx', '.js']},
    plugins: [new MiniCssExtractPlugin({filename: path.join('..', 'css', '[name].css')})]
};

if (process.env.NODE_ENV === 'production') {
    config.optimization = {
        minimizer: [new TerserPlugin({
            minify: (file, sourceMap) => {
                // https://github.com/mishoo/UglifyJS2#minify-options
                const uglifyJsOptions = {};

                if (sourceMap) {
                    uglifyJsOptions.sourceMap = {content: sourceMap};
                }

                return require("uglify-js").minify(file, uglifyJsOptions);
            },
            terserOptions: {format: {comments: false}},
            extractComments: "all",
        })]
    }
} else {
    config.optimization = {
        minimizer: [new TerserPlugin({
            terserOptions: {format: {comments: false}},
            extractComments: "all",
        })]
    }
}

config.mode = process.env.NODE_ENV ?? 'development';

module.exports = config;
