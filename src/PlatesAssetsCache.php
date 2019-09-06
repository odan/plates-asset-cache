<?php

namespace Odan\PlatesAsset;

use DirectoryIterator;
use FilesystemIterator;
use RuntimeException;
use SplFileInfo;

/**
 * Asset Cache for the internal JS ans CSS files.
 */
class PlatesAssetsCache
{
    /**
     * Cache.
     *
     * @var string Path
     */
    protected $directory;

    /**
     * @var int File mode
     */
    protected $chmod = -1;

    /**
     * Create new instance.
     *
     * @param string $publicDir Public directory
     * @param int $chmod Changes file mode (optional)
     */
    public function __construct(string $publicDir, int $chmod = -1)
    {
        $this->directory = $publicDir;
        $this->chmod = $chmod;

        if (!file_exists($this->directory)) {
            throw new RuntimeException("Path {$this->directory} not found");
        }
    }

    /**
     * Clear the existing cache.
     *
     * @return bool Success
     */
    public function clearCache(): bool
    {
        return $this->removeDirectory($this->directory);
    }

    /**
     * Remove directory recursively.
     * This function is compatible with vfsStream.
     *
     * @param string $path Path
     *
     * @return bool true on success or false on failure
     */
    private function removeDirectory(string $path): bool
    {
        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }
            $dirName = $fileInfo->getPathname();
            $this->removeDirectory($dirName);
        }

        $files = new FilesystemIterator($path);

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $fileName = $file->getPathname();
            unlink($fileName);
        }

        return rmdir($path);
    }

    /**
     * Returns url for filename.
     *
     * @param string $fileName The filename
     * @param string $content The content
     * @param string $urlBasePath The url base path
     *
     * @return string The url
     */
    public function createCacheBustedUrl(string $fileName, string $content, string $urlBasePath): string
    {
        $cacheFile = $this->createPublicCacheFile($fileName, $content);

        return $urlBasePath . pathinfo($cacheFile, PATHINFO_BASENAME);
    }

    /**
     * Create cache file from fileName.
     *
     * @param string $fileName The filename
     * @param string $content The content
     *
     * @return string The cache filename
     */
    private function createPublicCacheFile(string $fileName, string $content): string
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (empty($extension)) {
            $extension = 'cache';
        }

        $name = pathinfo($fileName, PATHINFO_FILENAME);
        $checksum = sha1($fileName . $content);
        $cacheFile = $this->directory . '/' . $name . '.' . $checksum . '.' . $extension;

        file_put_contents($cacheFile, $content);

        if ($this->chmod > -1) {
            chmod($cacheFile, $this->chmod);
        }

        return $cacheFile;
    }
}
