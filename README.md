# Laravel BadgeFactor2

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]
[![Codecov][ico-codecov]][link-codecov]
[![Github Actions][ico-github-actions]][link-github-actions]

**Laravel BadgeFactor2** is a Laravel package which allows to use the Badgr Server project in Laravel.
## Installation

You install the package via composer:
```bash
composer require ctrlwebinc/laravel-badgefactor2
php artisan vendor:publish --tag="bf2-config"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

Add the following provider in your `config\app.php` file:

```php
        /*
         * Package Service Providers...
         */
        Spatie\Permission\PermissionServiceProvider::class,
        ...
```

## Usage

## Migration from WordPress

To migrate users from WordPress, you need to make a few modifications to your app :

### App\Providers\EventServiceProvider

```php
...
use Illuminate\Auth\Events\Attempting;
use Ctrlweb\BadgeFactor2\Listeners\WordPressPasswordUpdate;

    protected $listen = [
        Attempting::class => [
            WordPressPasswordUpdate::class,
        ],
        ...
    ];
```

## Overview

## Tools

## Credits


[ico-version]: https://img.shields.io/packagist/v/ctrlwebinc/laravel-badgefactor2.svg
[ico-downloads]: https://img.shields.io/packagist/dt/ctrlwebinc/laravel-badgefactor2.svg
[ico-styleci]: https://styleci.io/repos/438762514/shield?style=flat
[ico-codecov]: https://img.shields.io/codecov/c/github/ctrlwebinc/laravel-badgefactor2
[ico-github-actions]: https://github.com/ctrlwebinc/laravel-badgefactor2/actions/workflows/laravel.yml/badge.svg

[link-packagist]: https://packagist.org/packages/ctrlwebinc/laravel-badgefactor2
[link-downloads]: https://packagist.org/packages/ctrlwebinc/laravel-badgefactor2
[link-styleci]: https://styleci.io/repos/438762514
[link-codecov]: https://app.codecov.io/gh/ctrlwebinc/laravel-badgefactor2
[link-github-actions]: https://github.com/ctrlwebinc/laravel-badgefactor2/actions
