<?php

namespace Odan\PlatesAsset\Test;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * AssetCacheTest.
 */
class AssetCacheTest extends TestCase
{
    use PlatesTestTrait;

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

        // Get from cache
        $actual2 = $this->extension->assets($filename, ['inline' => true]);
        $this->assertSame('<script>alert(1);</script>', $actual2);

        $file->setContent('alert(2);');
        $actual3 = $this->extension->assets($filename, ['inline' => true]);
        $this->assertSame('<script>alert(2);</script>', $actual3);

        // Array of files
        $file = vfsStream::newFile('test.js')->at($this->root)->setContent('alert(1);');
        $filename = $file->url();
        $actual = $this->extension->assets([$filename], ['inline' => true]);
        $this->assertSame('<script>alert(1);</script>', $actual);

        // get from cache
        $actual2 = $this->extension->assets([$filename], ['inline' => true]);
        $this->assertSame('<script>alert(1);</script>', $actual2);

        $file->setContent('alert(2);');
        $actual3 = $this->extension->assets([$filename], ['inline' => true]);
        $this->assertSame('<script>alert(2);</script>', $actual3);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testJsDefault()
    {
        $file = vfsStream::newFile('test.js')->at($this->root)->setContent('alert(2);');
        $filename = $file->url();
        $actual = $this->extension->assets([$filename], ['inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual);

        // get from cache
        $actual2 = $this->extension->assets([$filename], ['inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual2);

        // update js file, cache must be rebuild
        file_put_contents($filename, 'alert(4);');
        $actual3 = $this->extension->assets([$filename], ['inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual3);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testJsPublic()
    {
        // <script src="/cache/ab/file.96ce14164e1f92eb0ec93044a005be906f56d4.js"></script>

        $file = vfsStream::newFile('public.js')->at($this->root)->setContent('alert(3);');
        $filename = $file->url();
        $actual = $this->extension->assets($filename, ['inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual);

        // Get from cache
        $actual2 = $this->extension->assets($filename, ['inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual2);

        // Update js file, cache must be rebuild
        file_put_contents($filename, 'alert(4);');
        $actual3 = $this->extension->assets($filename, ['inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual3);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testJsPublicWithAlias()
    {
        // Array of files and folder alias
        $file = vfsStream::newFile('public/test.js')->at($this->root)->setContent('alert(3);');
        $realFileUrl = $file->url();
        $filename = 'public::test.js';

        // Generate
        $actual = $this->extension->assets([$filename], ['inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual);

        // Get from cache
        $actual2 = $this->extension->assets([$filename], ['inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual2);

        // Update js file, cache must be rebuild
        file_put_contents($realFileUrl, 'alert(4);');
        $actual3 = $this->extension->assets([$filename], ['inline' => false]);
        $this->assertRegExp($this->scriptInlineFileRegex, $actual3);
        $this->assertNotSame($actual2, $actual3);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCssDefault()
    {
        $content = 'body {
            /* background-color: #F4F4F4; */
            background-color: #f9fafa;
            /* background-color: #f8f8f8; */
            /* 60px to make the container go all the way to the bottom of the topbar */
            padding-top: 60px;
        }';

        $file = vfsStream::newFile('test.css')->at($this->root)->setContent($content);
        $filename = $file->url();
        $actual = $this->extension->assets([$filename], ['inline' => false]);
        $this->assertRegExp($this->styleInlineFileRegex, $actual);

        // get from cache
        $actual2 = $this->extension->assets([$filename], ['inline' => false]);
        $this->assertRegExp($this->styleInlineFileRegex, $actual2);

        // update js file, cache must be rebuild
        file_put_contents($filename, 'alert(4);');
        $actual3 = $this->extension->assets([$filename], ['inline' => false]);
        $this->assertRegExp($this->styleInlineFileRegex, $actual3);
    }
}
