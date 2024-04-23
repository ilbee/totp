# ilbee/totp
![GitHub last commit](https://img.shields.io/github/last-commit/ilbee/totp)
![GitHub Sponsors](https://img.shields.io/github/sponsors/ilbee)
![GitHub Issues or Pull Requests](https://img.shields.io/github/issues/ilbee/totp)
![Packagist License](https://img.shields.io/packagist/l/ilbee/totp)
![Packagist Version](https://img.shields.io/packagist/v/ilbee/totp)
![Packagist Downloads](https://img.shields.io/packagist/dt/ilbee/totp)
![Packagist Stars](https://img.shields.io/packagist/stars/ilbee/totp)

This project is a PHP implementation of the TOTP (Time-Based One-Time Password) algorithm.
This library is designed to be used with the Symfony framework.

## Installation
You can install this library via Composer:
```bash
composer require ilbee/totp
```

## Usage
* [Prerequisites](doc/USAGE.md#prerequisites)
* [Generate a secret key](doc/USAGE.md#generate_a_secret_key)
* [Validate a TOTP](doc/USAGE.md#validate_a_totp)
* [Generate a QR code](doc/USAGE.md#generate_a_qr_code)

## Configuration
You can configure TOTP by passing additional options when creating the instance:

```php
<?php
// ./src/Controller/UserController.php

use Ilbee\Totp\Totp;

$totp = new Totp([
    'digits' => 6, // Number of digits for the one-time password
    'period' => 30, // Time period for which a password is valid (in seconds)
    'algorithm' => 'sha1', // TOTP Hash algorithm
]);
```

## Contributions
Contributions are welcome.
Please open an issue or submit a pull request for any contributions.

## Sponsoring
[![BuyMeACoffee](https://img.shields.io/badge/Buy%20Me%20a%20Coffee-ffdd00?style=for-the-badge&logo=buy-me-a-coffee&logoColor=black)](https://buymeacoffee.com/julienprigent)

## License
This project is licensed under the MIT License.
See the LICENSE file for more details.