<?php

namespace Odan\PlatesAsset\Test;

use Odan\PlatesAsset\PlatesAssetsCache;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Test.
 *
 * @coversDefaultClass \Odan\PlatesAsset\PlatesAssetsCache
 */
class PlatesAssetsCacheTest extends TestCase
{
    use PlatesTestTrait;

    /**
     * @var string
     */
    protected $cacheBustedRegex = '/^cache\/cache\.[a-zA-Z0-9]{36}/';

    /**
     * @return PlatesAssetsCache
     */
    private function newInstance(): PlatesAssetsCache
    {
        return new PlatesAssetsCache(vfsStream::url('root/public/cache'), 0750);
    }

    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstanceError(): void
    {
        $this->expectException(RuntimeException::class);
        $cache = new PlatesAssetsCache(vfsStream::url('root/nada'));
        $this->assertInstanceOf(PlatesAssetsCache::class, $cache);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCacheBustedUrl(): void
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache'), 'content', 'cache/');
        $this->assertMatchesRegularExpression($this->cacheBustedRegex, $actual);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCacheBustedNormalUrl(): void
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache/aa/file.js'), 'content', 'cache/');
        $this->assertSame('cache/file.5654d9a3d587a044a6d9d9ba34003c65bd036d97.js', $actual);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCreateCacheBustedAdBlockedUrl(): void
    {
        $cache = $this->newInstance();
        $actual = $cache->createCacheBustedUrl(vfsStream::url('root/public/cache/ad/file.js'), 'content', 'cache/');
        $this->assertSame('cache/file.52f659a1fc90ca55c1d3f1ab8d2c4c2d573b676f.js', $actual);
    }
}
