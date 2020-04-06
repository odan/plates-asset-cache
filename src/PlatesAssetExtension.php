<?php

namespace Odan\PlatesAsset;

use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

/**
 * Extension that adds the ability to cache and minify assets.
 */
final class PlatesAssetExtension implements ExtensionInterface
{
    /** @var AssetEngine */
    private $assetEngine;

    /**
     * Constructor.
     *
     * @param AssetEngine $assetEngine The instance of the asset engine
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
     * @return void
     */
    public function register(Engine $engine)
    {
        $engine->registerFunction('assets', [$this, 'assets']);
    }

    /**
     * Render and compress assets content.
     *
     * @param string|array<string> $assets The assets
     * @param array<mixed> $options The options
     *
     * @return string The minified content
     */
    public function assets($assets, array $options = []): string
    {
        if (is_string($assets)) {
            $assets = (array)$assets;
        }

        return $this->assetEngine->assets($assets, $options);
    }
}
