<?php

namespace Odan\Asset;

use Exception;
use RuntimeException;

/**
 * Asset Cache for public JS ans CSS files.
 */
final class AssetCache
{
    /**
     * Cache.
     *
     * @var string Path
     */
    private $publicDir;

    /**
     * Create new instance.
     *
     * @param string $publicDir
     *
     * @throws Exception
     */
    public function __construct(string $publicDir = null)
    {
        if (isset($publicDir)) {
            $this->publicDir = $publicDir;
        }
        if (!file_exists($this->publicDir)) {
            throw new RuntimeException("Path {$this->publicDir} not found");
        }
    }

    /**
     * Returns url for filename.
     *
     * @param string $fileName
     * @param string $content
     *
     * @return string
     */
    public function createCacheBustedUrl(string $fileName, string $content): string
    {
        // For url we need to cache it
        $cacheFile = $this->createPublicCacheFile($fileName, $content);
        $name = pathinfo($cacheFile, PATHINFO_BASENAME);
        $dir = pathinfo($cacheFile, PATHINFO_DIRNAME);
        $dirs = explode('/', $dir);

        // Folder: cache/ab
        $cacheDirs = array_slice($dirs, count($dirs) - 2);

        // Folder: cache/ab/filename.ext
        $path = implode('/', $cacheDirs) . '/' . $name;

        // Create url
        $cacheUrl = $path;

        return $cacheUrl;
    }

    /**
     * Create cache file from fileName.
     *
     * @param string $fileName
     * @param string $content
     *
     * @return string cacheFile
     */
    protected function createPublicCacheFile(string $fileName, string $content): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (empty($extension)) {
            $extension = 'cache';
        }

        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $checksum = sha1($fileName . $content);
        $checksumDir = $this->publicDir . '/' . substr($checksum, 0, 2);
        $cacheFile = $checksumDir . '/' . $name . '.' . substr($checksum, 2) . '.' . $extension;

        // create cache dir
        if (!file_exists($checksumDir)) {
            mkdir($checksumDir, 0775, true);
        }

        file_put_contents($cacheFile, $content);
        chmod($cacheFile, 0775);

        return $cacheFile;
    }
}