# reCAPTCHA PHP client library

[![Build Status](https://travis-ci.org/google/recaptcha.svg)](https://travis-ci.org/google/recaptcha)
[![Latest Stable Version](https://poser.pugx.org/google/recaptcha/v/stable.svg)](https://packagist.org/packages/google/recaptcha)
[![Total Downloads](https://poser.pugx.org/google/recaptcha/downloads.svg)](https://packagist.org/packages/google/recaptcha)

* Project page: http://www.google.com/recaptcha/
* Repository: https://github.com/google/recaptcha
* Version: 1.1.1
* License: BSD, see [LICENSE](LICENSE)

## Description

reCAPTCHA is a free CAPTCHA service that protect websites from spam and abuse.
This is Google authored code that provides plugins for third-party integration
with reCAPTCHA.

## Installation

### Composer (Recommended)

[Composer](https://getcomposer.org/) is a widely used dependency manager for PHP
packages. This reCAPTCHA client is available on Packagist as
[`google/recaptcha`](https://packagist.org/packages/google/recaptcha) and can be
installed either by running the `composer require` command or adding the library
to your `composer.json`. To enable Composer for you project, refer to the
project's [Getting Started](https://getcomposer.org/doc/00-intro.md)
documentation.

To add this dependency using the command, run the following from within your
project directory:
```
composer require google/recaptcha "~1.1"
```

Alternatively, add the dependency directly to your `composer.json` file:
```json
"require": {
    "google/recaptcha": "~1.1"
}
```

### Direct download (no Composer)

If you wish to install the library manually (i.e. without Composer), then you
can use the links on the main project page to either clone the repo or download
the [ZIP file](https://github.com/google/recaptcha/archive/master.zip). For
convenience, an autoloader script is provided in `src/autoload.php` which you
can require into your script instead of Composer's `vendor/autoload.php`. For
example:

```php
require('/path/to/recaptcha/src/autoload.php');
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
```

The classes in the project are structured according to the
[PSR-4](http://www.php-fig.org/psr/psr-4/) standard, so you may of course also
use your own autoloader or require the needed files directly in your code.

### Development install

If you would like to contribute to this project or run the unit tests on within
your own environment you will need to install the development dependencies, in
this case that means [PHPUnit](https://phpunit.de/). If you clone the repo and
run `composer install` from within the repo, this will also grab PHPUnit and all
its dependencies for you. If you only need the autoloader installed, then you
can always specify to Composer not to run in development mode, e.g. `composer
install --no-dev`.

*Note:* These dependencies are only required for development, there's no
requirement for them to be included in your production code.

## Usage

First, register keys for your site at https://www.google.com/recaptcha/admin

When your app receives a form submission containing the `g-recaptcha-response`
field, you can verify it using:
```php
<?php
$recaptcha = new \ReCaptcha\ReCaptcha($secret);
$resp = $recaptcha->verify($gRecaptchaResponse, $remoteIp);
if ($resp->isSuccess()) {
    // verified!
} else {
    $errors = $resp->getErrorCodes();
}
```

You can see an end-to-end working example in
[examples/example-captcha.php](examples/example-captcha.php)

## Upgrading

### From 1.0.0

The previous version of this client is still available on the `1.0.0` tag [in
this repo](https://github.com/google/recaptcha/tree/1.0.0) but it is purely for
reference and will not receive any updates.

The major changes in 1.1.0 are:
* installation now via Composer;
* class loading also via Composer;
* classes now namespaced;
* old method call was `$rc->verifyResponse($remoteIp, $response)`, new call is
  `$rc->verify($response, $remoteIp)`

## Contributing

We accept contributions via GitHub Pull Requests, but all contributors need to
be covered by the standard Google Contributor License Agreement. You can find
instructions for this in [CONTRIBUTING](CONTRIBUTING.md)
