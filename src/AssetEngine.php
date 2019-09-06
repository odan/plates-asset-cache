<?php

namespace Odan\PlatesAsset;

use InvalidArgumentException;
use JSMin\JSMin;
use League\Plates\Engine;
use RuntimeException;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use tubalmartin\CssMin\Minifier as CssMinifier;

/**
 * Extension that adds the ability to cache and minify assets.
 */
final class AssetEngine
{
    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var string|null
     */
    private $fileExtension;

    /**
     * Cache.
     *
     * @var AbstractAdapter|ArrayAdapter
     */
    private $cache;

    /**
     * Cache.
     *
     * @var PlatesAssetsCache AssetCache
     */
    private $publicCache;

    /**
     * Default options.
     *
     * @var array
     */
    private $options = [
        'cache_adapter' => null,
        'cache_name' => 'assets-cache',
        'cache_lifetime' => 0,
        'cache_path' => null,
        'path' => null,
        'path_chmod' => 0750,
        'minify' => true,
        'inline' => false,
        'name' => 'file',
        'url_base_path' => null,
    ];

    /**
     * Create new instance.
     *
     * @param Engine $engine The template engine
     * @param array $options The options
     * - cache_adapter: The assets cache adapter. false or AbstractAdapter
     * - cache_name: Default is 'assets-cache'
     * - cache_lifetime: Default is 0
     * - cache_path: The temporary cache path
     * - path: The public assets cache directory (e.g. public/cache)
     * - url_base_path: The path of the minified css/js.
     * - minify: Enable JavaScript and CSS compression. The default value is true
     * - inline: Default is false
     * - name: The default asset name. The default value is 'file'
     */
    public function __construct(Engine $engine, array $options)
    {
        $this->engine = $engine;
        $this->fileExtension = $this->engine->getFileExtension();

        $options = array_replace_recursive($this->options, $options);

        if (empty($options['path'])) {
            throw new InvalidArgumentException('The option [path] is not defined');
        }

        $chmod = -1;
        if (isset($options['path_chmod']) && $options['path_chmod'] > -1) {
            $chmod = (int)$options['path_chmod'];
        }

        $this->publicCache = new PlatesAssetsCache($options['path'], $chmod);

        if (!empty($options['cache_path'])) {
            $this->cache = new FilesystemAdapter(
                $options['cache_name'],
                $options['cache_lifetime'],
                $options['cache_path']
            );
        } else {
            $this->cache = new ArrayAdapter();
        }

        unset($options['cache_adapter']);

        $this->options = $options;
    }

    /**
     * Render and compress JavaScript assets.
     *
     * @param array $assets The assets
     * @param array $options The options
     *
     * @return string The content
     */
    public function assets(array $assets, array $options = []): string
    {
        $assets = $this->prepareAssets($assets);
        $options = (array)array_replace_recursive($this->options, $options);

        $cacheKey = $this->getCacheKey($assets, $options);
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $jsFiles = [];
        $cssFiles = [];
        foreach ($assets as $file) {
            $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($fileType == 'js') {
                $jsFiles[] = $file;
            }
            if ($fileType == 'css') {
                $cssFiles[] = $file;
            }
        }
        $cssContent = $this->css($cssFiles, $options);
        $jsContent = $this->js($jsFiles, $options);
        $result = $cssContent . $jsContent;

        $cacheItem->set($result);
        $this->cache->save($cacheItem);

        return $result;
    }

    /**
     * Resolve real asset filenames.
     *
     * @param array $assets The assets
     *
     * @return array The real filenames
     */
    protected function prepareAssets(array $assets): array
    {
        $result = [];
        foreach ($assets as $name) {
            $result[] = $this->getRealFilename($name);
        }

        return $result;
    }

    /**
     * Render and compress CSS assets.
     *
     * @param array $assets Assets
     * @param array $options Options
     *
     * @return string content
     */
    public function js(array $assets, array $options): string
    {
        $contents = [];
        $public = '';

        foreach ($assets as $asset) {
            if ($this->isExternalUrl($asset)) {
                // External url
                $contents[] = sprintf('<script src="%s"></script>', $asset);
                continue;
            }
            $content = $this->getJsContent($asset, $options['minify']);

            if (!empty($options['inline'])) {
                $contents[] = sprintf('<script>%s</script>', $content);
            } else {
                $public .= sprintf("/* %s */\n", basename($asset)) . $content . "\n";
            }
        }
        if ($public !== '') {
            $name = $options['name'] ?? 'file.js';

            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.js';
            }

            $urlBasePath = $options['url_base_path'] ?? '';
            $url = $this->publicCache->createCacheBustedUrl($name, $public, $urlBasePath);

            $contents[] = sprintf('<script src="%s"></script>', $url);
        }

        return implode("\n", $contents);
    }

    /**
     * Minimise JS.
     *
     * @param string $file Name of default JS file
     * @param bool $minify Minify js if true
     *
     * @throws RuntimeException
     *
     * @return string JavaScript code
     */
    private function getJsContent(string $file, bool $minify): string
    {
        $content = file_get_contents($file);

        if ($content === false) {
            throw new RuntimeException(sprintf('File could could not be read %s', $file));
        }

        if ($minify) {
            $content = JSMin::minify($content);
        }

        return $content;
    }

    /**
     * Render and compress CSS assets.
     *
     * @param array $assets The assets
     * @param array $options The options
     *
     * @return string The content
     */
    public function css(array $assets, array $options): string
    {
        $contents = [];
        $public = '';

        foreach ($assets as $asset) {
            if ($this->isExternalUrl($asset)) {
                // External url
                $contents[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" media="all" />', $asset);
                continue;
            }
            $content = $this->getCssContent($asset, $options['minify']);

            if (!empty($options['inline'])) {
                $contents[] = sprintf('<style>%s</style>', $content);
            } else {
                $public .= $content . '';
            }
        }
        if ($public !== '') {
            $name = $options['name'] ?? 'file.css';

            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.css';
            }

            $urlBasePath = $options['url_base_path'] ?? '';
            $url = $this->publicCache->createCacheBustedUrl($name, $public, $urlBasePath);

            $contents[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" media="all" />', $url);
        }

        return implode("\n", $contents);
    }

    /**
     * Minimize CSS.
     *
     * @param string $fileName Name of default CSS file
     * @param bool $minify Minify css if true
     *
     * @throws RuntimeException
     *
     * @return string CSS code
     */
    public function getCssContent(string $fileName, bool $minify): string
    {
        $content = file_get_contents($fileName);

        if ($content === false) {
            throw new RuntimeException(sprintf('File could could not be read %s', $fileName));
        }

        if ($minify) {
            $compressor = new CssMinifier();
            $content = $compressor->run($content);
        }

        return $content;
    }

    /**
     * Get cache key.
     *
     * @param array $assets The assets
     * @param array $settings The settings
     *
     * @return string The cache key
     */
    protected function getCacheKey(array $assets, array $settings = null): string
    {
        $keys = [];
        foreach ($assets as $file) {
            $keys[] = sha1_file($file);
        }
        $keys[] = sha1(serialize($settings));

        return sha1(implode('', $keys));
    }

    /**
     * Check if url is valid.
     *
     * @param string $url The url
     *
     * @return bool The status
     */
    private function isExternalUrl($url): bool
    {
        return (!filter_var($url, FILTER_VALIDATE_URL) === false) && (strpos($url, 'vfs://') === false);
    }

    /**
     * Returns full path and filename.
     *
     * @param string $filename The filename
     *
     * @return string The real filename
     */
    protected function getRealFilename(string $filename): string
    {
        // Skip test stream but resolve Plates folders
        if (strpos($filename, 'vfs://') !== false ||
            strpos($filename, '::') === false) {
            return $filename;
        }

        // Resolve Plates folders alias
        $fullPath = $this->engine->path($filename);

        // Remove plates (php) file extension
        if (!empty($this->fileExtension)) {
            $fullPath = dirname($fullPath) . '/' . basename($fullPath, '.' . $this->fileExtension);
        }

        return $fullPath;
    }
}
