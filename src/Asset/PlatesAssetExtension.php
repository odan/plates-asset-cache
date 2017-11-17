<?php

/**
 * Plates Asset Extension
 *
 * @copyright 2017 odan https://github.com/odan
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Odan\Asset;

use League\Plates\Engine;
use League\Plates\Template\Name;
use League\Plates\Extension\ExtensionInterface;
use Odan\Asset\AssetEngine;

/**
 * Extension that adds the ability to cache and minify assets.
 */
class PlatesAssetExtension extends AssetEngine implements ExtensionInterface
{
    /**
     * Instance of the engine.
     *
     * @var Engine
     */
    protected $engine;

    protected $nameEngine;

    /**
     * Register extension function.
     *
     * @param Engine $engine Engine instance
     * @return null
     */
    public function register(Engine $engine)
    {
        $this->engine = $engine;
        $engine->registerFunction('assets', array($this, 'assets'));
    }

    /**
     * Returns full path and filename
     *
     * @param string $file
     * @return string
     */
    protected function getRealFilename($file)
    {
        if (strpos($file, 'vfs://') !== false) {
            return $file;
        }
        if ($file[0] === '/' || $file[1] === ':') {
            // absolute path
            return $file;
        }
        if (!$this->nameEngine) {
            $this->nameEngine = clone $this->engine;
            $this->nameEngine->setFileExtension(null);
        }
        $name = new Name($this->nameEngine, $file);
        $result = $name->getPath();
        return $result;
    }
}
