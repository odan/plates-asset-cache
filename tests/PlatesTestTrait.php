<?php

namespace Odan\PlatesAsset\Test;

use League\Plates\Engine;
use League\Plates\Template\Template;
use Odan\PlatesAsset\AssetEngine;
use Odan\PlatesAsset\PlatesAssetExtension;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

/**
 * BaseTest.
 */
trait PlatesTestTrait
{
    /** @var Engine */
    protected $engine;

    /** @var Template */
    protected $template;

    /** @var PlatesAssetExtension */
    protected $extension;

    /** @var vfsStreamDirectory */
    protected $root;

    /**
     * <script src="/cache/file.96ce14164e1f92eb0ec93044a005be906f56d4.js"></script>.
     *
     * @var string
     */
    protected $scriptInlineFileRegex = '/^\<script src=\"file\.[a-zA-Z0-9]{36}/';

    /**
     * <link rel="stylesheet" type="text/css" href="file.c736045df3ebc9fc934d653ecb8738d0955d15.css" media="all" />.
     *
     * @var string
     */
    protected $styleInlineFileRegex = '/^\<link rel=\"stylesheet\" type=\"text\/css\" href=\"file\.[a-zA-Z0-9]{36}/';

    /**
     * @var array<mixed>
     */
    protected $options = [];

    /**
     * Setup.
     *
     * @return void
     */
    public function setUp()
    {
        vfsStream::setup('templates');
        $this->engine = new Engine(vfsStream::url('templates'));
        $this->template = new Template($this->engine, 'template');

        $this->options = [
            // Public assets cache directory
            'path' => vfsStream::url('root/public/cache'),
            // The public url base path
            'url_base_path' => '',
            // Cache settings
            'cache_enabled' => true,
            'cache_path' => vfsStream::url('root/tmp'),
            'cache_name' => 'assets-cache',
            'cache_lifetime' => 0,
            'minify' => true,
        ];

        $this->root = vfsStream::setup('root');
        vfsStream::newDirectory('tmp/assets-cache')->at($this->root);
        vfsStream::newDirectory('public')->at($this->root);
        vfsStream::newDirectory('public/cache')->at($this->root);
        vfsStream::newDirectory('templates')->at($this->root);

        // Add alias path: public:: -> root/public
        $this->engine->addFolder('public', vfsStream::url('root/public'));

        $this->extension = $this->newExtensionInstance();
        $this->engine->loadExtension($this->extension);
    }

    /**
     * Create instance.
     *
     * @return PlatesAssetExtension extension
     */
    protected function newExtensionInstance(): PlatesAssetExtension
    {
        $assetEngine = new AssetEngine($this->engine, $this->options);

        return new PlatesAssetExtension($assetEngine);
    }
}
