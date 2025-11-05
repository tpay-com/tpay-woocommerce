# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.12.5]

### Fixed

- Validation for IPv6

## [1.12.4]

### Changed

- Enforced length limit (trimming) on UserAgent field sent to payment gateway

## [1.12.3]

### Fixed

- Typo in url to Merchant Panel

## [1.12.2]

### Fixed

- Additional attempt to stip non digits from BLIK code before submitting to payment gateway

## [1.12.1]

### Changed

- Improved interactions with BLIK code inputs (paste on mobile, length enforcement)

## [1.12.0]

### Added

- Blik Pay Later support and separate config section


## [1.11.0]

### Changed
- Update payer data
- Increased callback urls length

## [1.10.1]

### Changed

- Static icons to dynamic

## [1.10.0]

### Added

- support for filters: `tpay_transport_before_transaction`, `tpay_transport_before_pay`

## [1.9.0]

### Added

- Option to automatically cancel payment pending orders after selected number of days

### Changed

- Uses Tpay OpenApi SDK 2.0 

### Fixed

- Resolves issue with translations loading to early (produced warning to logs)

### Removed

- Dependency on additional constants added to `wp-config.php` file added during plugin initialization

## [1.8.5]

### Fixed

- Fixed issue where payment constraints where not applied to generic payments methods in classic checkout

## [1.8.4]

### Fixed

- Fixed Issue with BLIK lvl0 payments

## [1.8.3]

### Fixed

- Proper thousands and decimals separator parsing when calculating installments availability

## [1.8.2]

### Changed

- Improved BLIK lvl0 visuals in checkout
- Improved retry counter logic for BLIK lvl0

### Added

- Trimming whitespaces from API configuration fields

## [1.8.1]

### Fixed

- Fixed thank you page status when payment gateway is not Tpay.

### Added
- Hard notification if you're trying to install raw package from github (not a release);

## [1.8.0]

### Added

- Added custom paid statuses.

### Changed

- Cache mechanism swapped to transient.
- BLIK Level 0 UI/UX improvements with custom widget on "thank you page"

### Fixed 
- Notification handling with warnings when key is not set
- Getting bank groups fixed when API call fails
- IPN update fixed for generic payment methods

## [1.7.15]

### Changed

- Added a division between physical and digital products.

## [1.7.14]

### Fixed

- Credit card encryption function for non "woo-blocks" checkout

## [1.7.13]

### Fixed

- Woocommerce subscriptions double charges fix

## [1.7.12]

### Fixed

- Fix public card details being sent when RSA key not filled

## [1.7.11]

### Fixed

- Order retrieval fallback
- Use absolute path to load autoloader

## [1.7.10]

### Fixed

- Logger implementation
- Notification class
- Plugin activation checks

## [1.7.9]

### Changed

- Prefix vendor to avoid conflicts

## [1.7.8]

### Fixed

- Performance fixes for Tpay API Auth calls

## [1.7.7]

### Fixed

- Performance fixes for Tpay API calls - fixed abusive usage of Auth Endpoint


## [1.7.6]

### Fixed

- Performance fixes

## [1.7.5]

### Fixed

- Assets file path correction

## [1.7.4]

### Fixed

- Read more button for payment method
- Excessive records in wp_options table

### Added

- API lang parameter handling

## [1.7.3]

### Fixed

- Improve merchant ID logic for installments simulator

## [1.7.2]

### Added

- Added generic payment
- Added installments simulator

## [1.7.1]

### Fixed

- Subscriptions support for Woocommerce Blocks

## [1.7.0]

### Fixed

- Blik0 mechanism correction
- Metadata set method update
- Calling Order ID directly

## [1.6.4]

### Added

- Added tooltip to tpay global setting

### Fixed

- Fixed package version info

## [1.6.3]

### Fixed

- Fix banks custom order function
- Fix virtual product payment method handling with customized shipping

## [1.6.2]

### Fixed

- Changing order search for notifications

## [1.6.1]

### Added

- Handling taxId field in API calls

## [1.6.0]

### Added

- Github Actions new release workflow with checks

## [1.5.0]

### Added

- WooCommerce Blocks support

## [1.4.5]

### Fixed

- Fix options getting at plugin init

## [1.4.4]

### Fixed

- Support for wc_get_orders metadata query

## [1.4.3]

### Removed

- Unnecessary scrollToTop when changing payment gateway

## [1.4.2]

### Changed

- Updated version and changelog

## [1.4.1]

### Fixed

- Fix initiating class from classmap

## [1.4]

### Added

- compatibility with HPOS (High-Performance Order Storage)

### Fixed

- Blik LVL 0 validation
- Fields in the order form
- Code refactoring

### Changed

- Cache mechanism

## [1.3]

### Added

- Payment method Pekao Raty
- Support for payment channels
- Redirection to the Tpay panel
- Status "Completed"
- Changelog to plugin
- Blik LVL 0 and list of payments validation

### Fixed

- Security code special character decode
- Information about the regulations

### Removed

- Status "Waiting for payment"
- Option "Disable inactive payments"

## [1.2]

- Initial release
