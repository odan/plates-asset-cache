# Plates Assets Cache Extension

Caching and compression for Plates template assets (JavaScript and CSS).

# Installation

```
composer install odan/plates-asset-cache
```

# Configuration

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$view = new \League\Plates\Engine('/path/with/html/templates', null);

$options = array(
    // Enable JavaScript and CSS compression
    'minify' => true,
    // public assets cache directory
    'public_dir' => 'public/cache',
    // internal cache adapter
    'cache' => new FilesystemAdapter('assets-cache', 0, 'tmp/cache')),
);

// Register asset extension
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
        <?= $this->assets(['default.css', 'print.css'], ['inline' => true]); ?>
    </head>
...
```

Output cached and minified JavaScript content:

```php
<!-- JavaScript -->
<?= $this->assets(['mylib.js', 'page.js'], ['inline' => true]); ?>
```
