# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [1.7.15]

### Changed

- Adding a division between physical and digital products.

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
