# Laravel BadgeFactor2

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]
[![Github Actions][ico-github-actions]][link-github-actions]

**Laravel BadgeFactor2** is a Laravel package which allows to use the Badgr Server project in Laravel.
## Installation

## Usage

## Migration from WordPress

To migrate users from WordPress, you need to make a few modifications to a few classes in you app :

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

### App\Models\User

```php
protected $fillable = [
    ...
    'created_at',
    'wp_id',
    'wp_password',
];

protected $hidden = [
    ...
    'wp_password',
];
```

### Migration

```php
$table->unsignedBigInteger('wp_id')->nullable();
$table->string('wp_password', 60)->nullable();
```            

## Overview

## Tools

## Credits


[ico-version]: https://img.shields.io/packagist/v/ctrlwebinc/badgefactor2-laravel.svg
[ico-downloads]: https://img.shields.io/packagist/dt/ctrlwebinc/badgefactor2-laravel.svg
[ico-styleci]: https://styleci.io/repos/438762514/shield?style=flat
[ico-github-actions]: https://github.com/ctrlwebinc/badgefactor2-laravel/actions/workflows/laravel.yml/badge.svg

[link-packagist]: https://packagist.org/packages/ctrlwebinc/badgefactor2-laravel
[link-downloads]: https://packagist.org/packages/ctrlwebinc/badgefactor2-laravel
[link-styleci]: https://styleci.io/repos/438762514
[link-github-actions]: https://github.com/ctrlwebinc/badgefactor2-laravel/actions
