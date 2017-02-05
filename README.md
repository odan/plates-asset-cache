# Plates Assets Cache Extension

Caching and compression for Plates template assets (JavaScript and CSS).

# Installation

```
composer install odan/plates-asset-cache
```

# Configuration

```php
$view = new \League\Plates\Engine('/path/with/html/templates', null);

// Optional: Add folder shortcut (assets::file.js)
$view->addFolder('assets', '/public/assets');

// Optional: Set base url
$view->addData(['baseurl' => 'http://localhost']);

// Asset extention options
$options = array(
	// View base path
	'cachepath' => '/path/to/public/cache',
	// Create different hash for each language
	'cachekey' => 'en_US',
	// Base Url for public cache directory
	'baseurl' => 'http://localhost',
	// Enable JavaScript and CSS compression
	'minify' => 1
);

// Register Asset extension
$view->loadExtension(new \Odan\Plates\Extension\AssetCache($options));
```
# Usage

## Template

Output cached and minified CSS content:

```php
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <base href="<?= $baseurl; ?>" />
        <title>Demo</title>
        <?= $this->assetCss(['default.css', 'print.css'], ['inline' => false]); ?>
    </head>
...
```

Output cached and minified JavaScript content:

```php
<!-- JavaScript -->
<?= $this->assetJs(['mylib.js', 'page.js'], ['inline' => false]); ?>
```
