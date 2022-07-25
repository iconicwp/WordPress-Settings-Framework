# WooCommerce Sniffs

Collection of PHP_CodeSniffer sniffs for WooCommerce.

## Installation

```php
composer require woocommerce/woocommerce-sniffs
```

## Usage

### Command line

```bash
./vendor/bin/phpcs --standard=WooCommerce-Core <file>
```

### Config file

PHPCS config file:

```xml
<?xml version="1.0"?>
<ruleset name="WooCommerce Coding Standards">
	<description>My projects ruleset.</description>
	
	<!-- Configs -->
	<config name="minimum_supported_wp_version" value="4.7" />
	<config name="testVersion" value="7.2-" />

	<!-- Rules -->
	<rule ref="WooCommerce-Core" />

	<rule ref="WordPress.WP.I18n">
		<properties>
			<property name="text_domain" type="array" value="new-text-domain" />
		</properties>
	</rule>

	<rule ref="PHPCompatibility">
		<exclude-pattern>tests/</exclude-pattern>
	</rule>
</ruleset>
```


## Changelog

[See changelog for details](https://github.com/woocommerce/woocommerce-sniffs/blob/master/CHANGELOG.md)
