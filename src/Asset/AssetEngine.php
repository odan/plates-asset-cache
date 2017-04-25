<?php

/**
 * Asset Engine.
 *
 * @copyright 2017 odan https://github.com/odan
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Odan\Asset;

use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Odan\CssMin\CssMinify;
use Odan\JsMin\JsMinify;

/**
 * Extension that adds the ability to cache and minify assets.
 */
class AssetEngine
{
    /**
     * Cache
     *
     * @var AbstractAdapter
     */
    protected $cache;

    /**
     * Cache
     *
     * @var string Path
     */
    protected $publicCaache;

    /**
     * Enables minify.
     *
     * @var bool
     */
    protected $options = array(
        'minify' => true,
        'inline' => true,
        'public_dir' => null,
        'name' => 'file'
    );

    /**
     * Create new instance.
     *
     * @param Engine $engine
     * @param array $options
     */
    public function __construct($options)
    {
        if (!empty($options['cache']) && $options['cache'] instanceof AbstractAdapter) {
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
     * Render and compress JavaScript assets
     *
     * @param array $assets
     * @param array $options
     * @return string content
     */
    public function assets($assets, $options = array())
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
        foreach ((array) $assets as $file) {
            $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($fileType == "js") {
                $jsFiles[] = $file;
            }
            if ($fileType == "css") {
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
     * @param mixed $assets
     * @return array
     */
    protected function prepareAssets($assets)
    {
        $result = array();
        foreach ((array) $assets as $name) {
            $result[] = $this->getRealFilename($name);
        }
        return $result;
    }

    /**
     * Render and compress CSS assets
     *
     * @param array $assets
     * @param array $options
     * @return string content
     */
    public function js($assets, $options)
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
                $contents[] = sprintf("<script>%s</script>", $content);
            } else {
                $public .= $content . "";
            }
        }
        if (strlen($public) > 0) {
            $name = isset($options['name']) ? $options['name'] : 'file.js';
            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.js';
            }
            $url = $this->publicCache->createCacheBustedUrl($name, $public);
            $contents[] = sprintf('<script src="%s"></script>', $url);
        }
        $result = implode("\n", $contents);
        return $result;
    }

    /**
     * Minimise JS.
     *
     * @param string $file Name of default JS file
     * @param bool $minify  Minify js if true
     *
     * @return string JavaScript code
     */
    protected function getJsContent($file, $minify)
    {
        $content = file_get_contents($file);
        if ($minify) {
            $content = JsMinify::minify($content);
        }
        return $content;
    }

    /**
     * Render and compress CSS assets
     *
     * @param array $assets
     * @param array $options
     * @return string content
     */
    public function css($assets, $options)
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
                $contents[] = sprintf("<style>%s</style>", $content);
            } else {
                $public .= $content . "";
            }
        }
        if (strlen($public) > 0) {
            $name = isset($options['name']) ? $options['name'] : 'file.css';
            if (empty(pathinfo($name, PATHINFO_EXTENSION))) {
                $name .= '.css';
            }
            $url = $this->publicCache->createCacheBustedUrl($name, $public);
            $contents[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" media="all" />', $url);
        }
        $result = implode("\n", $contents);
        return $result;
    }

    /**
     * Minimize CSS.
     *
     * @param string $fileName Name of default CSS file
     * @param bool   $minify   Minify css if true

     * @return string CSS code
     */
    public function getCssContent($fileName, $minify)
    {
        $content = file_get_contents($fileName);
        if ($minify) {
            $compressor = new CssMinify();
            $content = $compressor->run($content);
        }
        return $content;
    }

    /**
     * Get cache key.
     *
     * @param mixed $assets
     * @param mixed $settings
     * @return string
     */
    protected function getCacheKey($assets, $settings = null)
    {
        $keys = [];
        foreach ((array) $assets as $file) {
            $keys[] = sha1_file($file);
        }
        $keys[] = sha1(serialize($settings));
        return sha1(implode('', $keys));
    }

    /**
     * Check if url is valid
     *
     * @param string $url
     * @return bool
     */
    protected function isExternalUrl($url)
    {
        return (!filter_var($url, FILTER_VALIDATE_URL) === false) && (strpos($url, 'vfs://') === false);
    }

    /**
     * Returns full path and filename
     *
     * @param string $filename
     * @return string
     */
    protected function getRealFilename($filename)
    {
        return $filename;
    }
}
