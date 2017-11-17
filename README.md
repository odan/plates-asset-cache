# Plates Assets Cache Extension

Caching and compression for Plates template assets (JavaScript and CSS).

[![Latest Version](https://img.shields.io/github/release/odan/plates-asset-cache.svg)](https://github.com/loadsys/odan/plates-asset-cache/releases)
[![Build Status](https://travis-ci.org/odan/plates-asset-cache.svg?branch=master)](https://travis-ci.org/odan/plates-asset-cache)
[![Crutinizer](https://img.shields.io/scrutinizer/g/odan/plates-asset-cache.svg)](https://scrutinizer-ci.com/g/odan/plates-asset-cache)
[![Code Coverage](https://scrutinizer-ci.com/g/odan/plates-asset-cache/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/odan/plates-asset-cache/code-structure)
[![Total Downloads](https://img.shields.io/packagist/dt/odan/plates-asset-cache.svg)](https://packagist.org/packages/odan/plates-asset-cache)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)


## Installation

```
composer require odan/plates-asset-cache
```

## Configuration

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$view = new \League\Plates\Engine('/path/with/html/templates', null);

$options = array(
    // Enable JavaScript and CSS compression
    'minify' => true,
    // Public assets cache directory
    'public_dir' => 'public/cache',
    // Internal cache adapter
    'cache' => new FilesystemAdapter('assets-cache', 0, 'tmp/cache')
);

// Register asset extension
$view->loadExtension(new \Odan\Asset\PlatesAssetExtension($options));
```
## Usage

### Template

Output cached and minified CSS content:

```php
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <base href="<?= $baseurl; ?>" />
        <title>Demo</title>
        // The root path is the path defined at the instantiation (/path/with/html/templates)
        // The inline value defines, if the file should be returned as inline CSS or 
        // as <link> tag to the cache file
        <?= $this->assets(['default.css', 'print.css'], ['inline' => true]); ?>
    </head>
...
```

Output cached and minified JavaScript content:

```php
<!-- JavaScript -->
// The root path is the path defined at the instantiation (/path/with/html/templates)
// The inline value defines, if the file should be returned as inline JS or as 
// <script> tag to the cache file
<?= $this->assets(['mylib.js', 'page.js'], ['inline' => true]); ?>
```
