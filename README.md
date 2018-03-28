Fenom - Template Engine for PHP
===============================

**Fenóm**  - lightweight and fast template engine for PHP.

* **Subject:** Template engine
* **Syntax:** Smarty-like
* **Documentation:** **[English](./docs/en/readme.md)**, **[Russian](./docs/ru/readme.md)**
* **PHP version:** 5.4+
* **State:** [![Build Status](https://travis-ci.org/fenom-template/fenom.svg?branch=master)](https://travis-ci.org/fenom-template/fenom) [![Coverage Status](https://coveralls.io/repos/fenom-template/fenom/badge.svg?branch=master)](https://coveralls.io/r/fenom-template/fenom?branch=master)
* **Version:** [![Latest Stable Version](https://poser.pugx.org/fenom/fenom/v/stable.png)](https://packagist.org/packages/fenom/fenom)
* **Packagist:** [fenom/fenom](https://packagist.org/packages/fenom/fenom) [![Total Downloads](https://poser.pugx.org/fenom/fenom/downloads.png)](https://packagist.org/packages/fenom/fenom)
* **Composer:** `composer require fenom/fenom`
* **Discussion:** [Fenom Forum](https://groups.google.com/forum/#!forum/php-ion)
* **Versioning:** [semver2](http://semver.org/)
* **Performance:** see [benchmark](./docs/en/benchmark.md)

***

## Quick Start

### Install

If you use composer in your project then you can to install Fenom as package.
However, if you are not using composer you have to configure _autoloader_ to work with Fenom.
Fenom implements the `PSR-0` PHP standard to load classes which are located in the `src/` directory.
Templater already has own autoload-function, to register call method `Fenom::registerAutoload`:
```php
Fenom::registerAutoload();
```

### Setup

There is two way to create Fenom instance:

* Long way: use operator `new`
* Shot way: use static factory-method

**Long way.** Create you own template provider or default provider `Fenom\Provider` (that is provider read [there](./)).
Using provider instance create Fenom instance:

```php
$fenom = new Fenom(new Fenom\Provider($template_dir));
```

After that, set compile directory:

```php
$fenom->setCompileDir($template_cache_dir);
```

This directory will be used for storing compiled templates, therefore it should be writable for Fenom.
Now Fenom is ready to work and now you can to configure it:

```php
$fenom->setOptions($options);
```

**Short way.** Creating an object via factory method with arguments from long way.

```php
$fenom = Fenom::factory($template_dir, $template_cache_dir, $options);
```

Now Fenom is ready to work.

### Usage

### Example