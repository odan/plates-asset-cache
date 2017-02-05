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

// Asset extention options
$options = array(
	// View base path
	'cachepath' => '/path/to/public/cache',
	// Enable JavaScript and CSS compression
	'minify' => true
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
        <?= $this->css(['default.css', 'print.css'], ['inline' => false]); ?>
    </head>
...
```

Output cached and minified JavaScript content:

```php
<!-- JavaScript -->
<?= $this->js(['mylib.js', 'page.js'], ['inline' => false]); ?>
```
