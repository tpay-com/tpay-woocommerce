### Tpay.com Payment Gateway for WooCommerce

Contributors: tpay.com \
Tags: tpay, tpay.com, payment, payment gateway, woocommerce \
Tested up to: 6.2.2 \
Stable tag: 1.4 \
Requires PHP: 7.1 or higher \
License: GPLv3 \
Tpay is payment gateway for WooCommerce.

### Description

**Tpay is payment gateway for WooCommerce.
Accept online payments in a safe and fast way.**

### Requirements:

* WordPress with a minimum PHP 7.1 version
* Installed WooCommerce plugin version above 6.x.x
* Shop with the Polish Zloty (PLN) currency set
* Active account at [Tpay](https://tpay.com)
* Open API account access enabled

### The main advantages of the Tpay plugin:

* Clear presentation of payment methods (on-site mode, BLIK LVL 0).
* Various payment methods (Cards payment, electronic wallets, installments, etc.)
* Management of returns from the WooCommerce panel.
* Payment method limitation for delivery.
* The ability to customize the order of bank logos.
* Subscription card payments (additional extension required, e.g. WooCommerce Subscriptions).

### The plugin offers the following payment methods:

* Tpay banks list – payment shows a list of banks, depending on the selected type, the payer will be redirected to the
  bank.
* Tpay BLIK – payer will be redirected to eBlik page or be able to type BLIK's code in the cart.
* Tpay Credit Card Standard - a secure form for card data on Tpay panel.
* Tpay Credit Card SF - a secure form for card data in the cart.
* Tpay Google Pay - payer will be redirected to Google Pay payment.
* Tpay Twisto – payer will be redirected to Twisto payment form.
* Tpay Installments – payer will be redirected to installment payment form.
  Detailed information how to configure plugin you
  will [find here](https://support.tpay.com/pl/developer/addons/woocommerce/woocommerce-wdrozenie-wtyczki-tpay-do-woocommerce-wersja-open-api?_gl=1*qce368*_gcl_aw*R0NMLjE2OTAyODc2OTEuQ2owS0NRanc1ZjJsQmhDa0FSSXNBSGVUdmxnS0paekpQcWswQlVBelhISWdRaTN5R2p0dlBXT1ZNOThUTDBVNFpUZE1XbGp6N28xRDZfZ2FBcU9PRUFMd193Y0I.*_gcl_au*NDAzNTk5MTk2LjE2ODMwMDk4MTc.).

### Installation

1. Log in to your WordPress admin panel.
2. Go to WooCommerce and then click the Tpay settings tab. (using global settings means you don't have to re-enter the
   same configuration details for each payment method)
3. Select the environment you want to use.
4. Fill in the fields with data from the Merchant Account *.
5. Go to WooCommerce, select Settings, then click on the Payments tab.
6. Enable the payments you want to be visible on your website.

* Production environment use data from the [Merchant Panel](https://panel.tpay.com/?lang=1). Use
  your [Sandbox account](https://panel.sandbox.tpay.com/integration/payment-links-form?lang=1) details for Sandbox
  integration.
  If you have any questions, please contact our [technical support](https://tpay.com/en#contact).

### Frequently Asked Questions

#### What do I need to use the plugin:

1. You need to have WooCommerce installed and activated on your WordPress site.
2. You need to open a Merchant account on [Tpay.com](https:/tpay.com)

#### How can I accept subscription payments

1. You need to have WooCommerce installed and activated on your WordPress site.
2. You need a plugin which extends WooCommerce with this functionality. (Tested on the WooCommerce Subscriptions).
3. You need to open a Merchant account on [Tpay.com](https:/tpay.com)

#### Where can I find configuration data?

Go to [Merchant Panel](https://panel.tpay.com/) ->  Integration -> API -> Open API section.

#### How to enable BLIK in LVL 0 mode?

Go to the Tpay BLIK payment settings and select the "Enable Blik 0" option.

#### How to enable Card in on-site mode?

Go to the Tpay Credit Card SF payment settings and enter the RSA key in the "RSA Key" field.

#### Where can I find the RSA key?

Go to [Merchant Panel](https://panel.tpay.com/) ->  Credit Card payment -> API.
If you do not have such tab in your Merchant Panel, please [contact us](https://tpay.com/en#contact).

### Screenshots

1. List of banks
2. Blik LVL 0
3. Card payment on-site
4. Global configuration
5. Payments configuration

### [Changelog](./CHANGELOG.md)
