<?php

namespace Odan\Test;

use League\Plates\Engine;
use League\Plates\Template\Template;
use Odan\Asset\AssetEngine;
use Odan\Asset\PlatesAssetExtension;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

/**
 * AssetCacheTest.
 */
class AssetCacheTest extends TestCase
{
    /**
     * @var Engine
     */
    protected $engine;

    /**
     * @var Template
     */
    protected $template;

    /**
     * @var PlatesAssetExtension
     */
    protected $extension;

    /**
     * @var vfsStreamDirectory
     */
    protected $root;

    public function setUp()
    {
        vfsStream::setup('templates');
        $this->engine = new Engine(vfsStream::url('templates'));
        $this->extension = $this->newExtensionInstance();
        $this->extension->register($this->engine);
        $this->template = new Template($this->engine, 'template');
    }

    public function newExtensionInstance()
    {
        $this->root = vfsStream::setup('root');
        vfsStream::newDirectory('tmp/asset-cache')->at($this->root);
        vfsStream::newDirectory('public/cache')->at($this->root);

        $options = [
            'cache' => new FilesystemAdapter(sha1(__DIR__), 0, vfsStream::url('root/tmp/asset-cache')),
            'public_dir' => vfsStream::url('root/public/cache'),
            'minify' => true,
        ];

        $engine = new AssetEngine($options);
        $extension = new PlatesAssetExtension($engine);

        return $extension;
    }

    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance()
    {
        $extension = $this->newExtensionInstance();
        $this->assertInstanceOf(PlatesAssetExtension::class, $extension);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testJsInline()
    {
        $file = vfsStream::newFile('test.js')->at($this->root)->setContent('alert(1);');
        $filename = $file->url();
        $actual = $this->extension->assets($filename, ['inline' => true]);
        $this->assertSame('<script>alert(1);</script>', $actual);

        // get from cache
        $actual2 = $this->extension->assets($filename, ['inline' => true]);
        $this->assertSame('<script>alert(1);</script>', $actual2);

        $file->setContent('alert(2);');
        $actual3 = $this->extension->assets($filename, ['inline' => true]);
        $this->assertSame('<script>alert(2);</script>', $actual3);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testJsPublic()
    {
        // <script src="/cache/ab/file.96ce14164e1f92eb0ec93044a005be906f56d4.js"></script>
        $regex = '/^\<script src=\"cache\/[a-zA-Z0-9]{2,2}\/file\.[a-zA-Z0-9]{36}/';

        $file = vfsStream::newFile('public.js')->at($this->root)->setContent('alert(3);');
        $filename = $file->url();
        $actual = $this->extension->assets($filename, ['inline' => false]);
        $this->assertRegExp($regex, $actual);

        // get from cache
        $actual2 = $this->extension->assets($filename, ['inline' => false]);
        $this->assertRegExp($regex, $actual2);

        // update js file, cache must be rebuild
        file_put_contents($filename, 'alert(4);');
        $actual3 = $this->extension->assets($filename, ['inline' => false]);
        $this->assertRegExp($regex, $actual3);
    }
}
