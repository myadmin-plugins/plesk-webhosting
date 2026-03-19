# MyAdmin Plesk Webhosting Plugin

[![Tests](https://github.com/detain/myadmin-plesk-webhosting/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-plesk-webhosting/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-plesk-webhosting/version)](https://packagist.org/packages/detain/myadmin-plesk-webhosting)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-plesk-webhosting/downloads)](https://packagist.org/packages/detain/myadmin-plesk-webhosting)
[![License](https://poser.pugx.org/detain/myadmin-plesk-webhosting/license)](https://packagist.org/packages/detain/myadmin-plesk-webhosting)

A MyAdmin plugin that provides Plesk control panel integration for automated webhosting provisioning and lifecycle management. It communicates with the Plesk XML API to handle account creation, suspension, reactivation, termination, and IP management operations.

## Features

- Automated webhosting account provisioning via the Plesk XML API
- Full service lifecycle management (activate, deactivate, reactivate, terminate)
- Client and subscription CRUD operations
- Site, database, email, and DNS management
- Service plan listing and assignment
- IP address management and migration
- Event-driven architecture using Symfony EventDispatcher

## Requirements

- PHP >= 8.2
- ext-soap
- ext-curl
- ext-xml
- ext-mbstring

## Installation

Install with Composer:

```sh
composer require detain/myadmin-plesk-webhosting
```

## Testing

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the LGPL-2.1-only license.
