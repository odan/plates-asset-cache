<?php

namespace Odan\Asset;

use JSMin\JSMin;
use RuntimeException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use tubalmartin\CssMin\Minifier as CssMinifier;

/**
 * Extension that adds the ability to cache and minify assets.
 */
final class AssetEngine
{
    /** @var AdapterInterface Cache */
    private $cache;

    /** @var AssetCache */
    private $publicCache;

    /**
     * Enables minify.
     *
     * @var array
     */
    private $options = [
        'minify' => true,
        'inline' => true,
        'public_dir' => null,
        'name' => 'file',
    ];

    /**
     * Create new instance.
     *
     * @param array $options options
     */
    public function __construct(array $options)
    {
        if (!empty($options['cache']) && $options['cache'] instanceof AdapterInterface) {
            $this->cache = $options['cache'];
        } else {
            $this->cache = new ArrayAdapter();
        }
        $this->publicCache = new AssetCache($options['public_dir']);

        unset($options['public_cache']);
        unset($options['cache']);

        $this->options = array_replace_recursive($this->options, $options);
    }

    /**
     * Render and compress JavaScript assets.
     *
     * @param array $options
     *
     * @return string content
     */
    public function assetFile(string $asset, array $options = []): string
    {
        return $this->assetFiles((array)$asset, $options);
    }

    /**
     * Render and compress JavaScript assets.
     *
     * @param array $assets
     * @param array $options
     *
     * @return string content
     */
    public function assetFiles(array $assets, array $options = []): string
    {
        $assets = $this->prepareAssets($assets);
        $options = array_replace_recursive($this->options, $options);

        $cacheKey = $this->getCacheKey($assets, $options);
        $cacheItem = $this->cache->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $jsFiles = [];
        $cssFiles = [];
        foreach ($assets as $file) {
            $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($fileType === 'js') {
                $jsFiles[] = $file;
            }
            if ($fileType === 'css') {
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
     * @param array $assets
     *
     * @return array
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
     * @param array $assets
     * @param array $options
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
                $public .= $content . '';
            }
        }
        if (strlen($public) > 0) {
            $name = $options['name'] ?? 'file.js';
            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.js';
            }
            $url = $this->publicCache->createCacheBustedUrl($name, $public);
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
    protected function getJsContent(string $file, bool $minify): string
    {
        $content = file_get_contents($file);

        if ($content === false) {
            throw new RuntimeException(sprintf('File could be read: %s', $file));
        }

        if ($minify) {
            $content = JSMin::minify($content);
        }

        return $content;
    }

    /**
     * Render and compress CSS assets.
     *
     * @param array $assets
     * @param array $options
     *
     * @return string content
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

        if (strlen($public) > 0) {
            $name = $options['name'] ?? 'file.css';
            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.css';
            }
            $url = $this->publicCache->createCacheBustedUrl($name, $public);
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
            throw new RuntimeException(sprintf('File could be read: %s', $fileName));
        }

        if ($minify === true) {
            $compressor = new CssMinifier();
            $content = $compressor->run($content);
        }

        return $content;
    }

    /**
     * Get cache key.
     *
     * @param array $assets
     * @param array $settings
     *
     * @return string
     */
    protected function getCacheKey(array $assets, ?array $settings = null): string
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
     * @return bool
     */
    protected function isExternalUrl(string $url): bool
    {
        return (!filter_var($url, FILTER_VALIDATE_URL) === false) && (strpos($url, 'vfs://') === false);
    }

    /**
     * Returns full path and filename.
     *
     * @return string
     */
    protected function getRealFilename(string $filename): string
    {
        return $filename;
    }
}
