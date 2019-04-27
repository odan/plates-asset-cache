<?php

namespace Odan\Asset;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use League\Plates\Template\Name;

/**
 * Extension that adds the ability to cache and minify assets.
 */
final class PlatesAssetExtension implements ExtensionInterface
{
    /**
     * Instance of the engine.
     *
     * @var Engine
     */
    private $engine;

    /**
     * @var AssetEngine
     */
    private $assetEngine;

    /**
     * Constructor.
     *
     * @param AssetEngine $assetEngine
     */
    public function __construct(AssetEngine $assetEngine)
    {
        $this->assetEngine = $assetEngine;
    }

    /**
     * Register extension function.
     *
     * @param Engine $engine Engine instance
     *
     * @return null
     */
    public function register(Engine $engine)
    {
        $this->engine = $engine;
        $engine->registerFunction('assets', [$this, 'assets']);
    }

    /**
     * Render and compress assets content.
     *
     * @param string|array $assets assets
     * @param array $options options
     *
     * @return string minified content
     */
    public function assets($assets, array $options = []): string
    {
        if (is_string($assets)) {
            return $this->assetEngine->assetFile($assets, $options);
        } else {
            return $this->assetEngine->assetFiles($assets, $options);
        }
    }
}
