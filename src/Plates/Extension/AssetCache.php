<?php

/**
 * Plates Assets Cache Extension
 *
 * @copyright 2016 odan https://github.com/odan
 * @license http://opensource.org/licenses/MIT The MIT License (MIT)
 */

namespace Odan\Plates\Extension;

use Exception;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use MatthiasMullie\Minify;

/**
 * Extension that adds the ability to cache and minify assets.
 */
class AssetCache implements ExtensionInterface
{

    /**
     * Instance of the engine.
     * @var Engine
     */
    protected $engine;

    /**
     * Instance of the current template.
     * @var Template
     */
    public $template;

    /**
     * Cache key.
     *
     * @var string
     */
    protected $cacheKey = '';

    /**
     * Enables the filename method.
     * @var boolean
     */
    protected $cachePath;

    /**
     * Enables minify.
     *
     * @var boolean
     */
    protected $minify = false;

    /**
     * Create new instance.
     *
     * @param Engine $engine
     * @param array $options
     */
    public function __construct($options)
    {
        if (empty($options['cache_path'])) {
            throw new Exception('Cache path is not defined');
        }
        $this->cachePath = rtrim($options['cache_path'], '/');
        if (isset($options['minify'])) {
            $this->minify = $options['minify'];
        }
        $this->cacheKey = $this->minify;
    }

    /**
     * Register extension function.
     * @return null
     */
    public function register(Engine $engine)
    {
        $this->engine = $engine;
        $engine->registerFunction('js', array($this, 'js'));
        $engine->registerFunction('css', array($this, 'css'));
    }

    /**
     * Render and compress JavaScript assets
     *
     * @param array $assets
     * @param array $options
     * @return string content
     */
    public function js($assets, $options)
    {
        $options['extension'] = 'js';
        $options['glue'] = "\t";
        $options['render'] = array($this, 'renderSectionJs');
        return $this->cachedAssets($assets, $options);
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
        $options['extension'] = 'css';
        $options['glue'] = "\t";
        $options['render'] = array($this, 'renderSectionCss');
        return $this->cachedAssets($assets, $options);
    }

    /**
     * Render and compress assets
     *
     * @return string
     */
    protected function cachedAssets($assets, $options)
    {
        if (empty($assets)) {
            return '';
        }
        $sections = array();
        foreach ($assets as $fileName) {
            $extenstion = $this->fileExtension($fileName);
            if ($extenstion != $options['extension']) {
                continue;
            }
            $inline = isset($options['inline']) ? $options['inline'] : false;
            $sections[] = call_user_func_array($options['render'], array($fileName, $inline));
        }
        return implode($options['glue'], $sections);
    }

    /**
     * Returns the extension of the filename
     *
     * @param string $filename
     * @return string
     */
    protected function fileExtension($filename)
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * Get section code by file
     *
     * @param array $params args
     * @return string
     */
    protected function getSectionCodeByFile($params)
    {
        if ($this->isUrl($params['filename'])) {
            $code = $params['filename'];
            $code = $params['outline_open'] . $code . $params['outline_close'];
        } else {
            if ($params['inline']) {
                $code = $this->getFileContent($params['filename']);
                $code = $params['inline_open'] . $code . $params['inline_close'];
            } else {
                $code = $this->getFileUrl($params['filename']);
                $code = $params['outline_open'] . $code . $params['outline_close'];
            }
        }
        return $code;
    }

    /**
     * Check if url is valid
     *
     * @param string $url
     * @return bool
     */
    protected function isUrl($url)
    {
        return (!filter_var($url, FILTER_VALIDATE_URL) === false);
    }

    /**
     * Render section with JavaScript content
     *
     * @param string $fileName
     * @param bool $inline
     * @return string code
     */
    protected function renderSectionJs($fileName, $inline)
    {
        return $this->getSectionCodeByFile(array(
                    'filename' => $fileName,
                    'inline' => $inline,
                    'inline_open' => '<script type="text/javascript">',
                    'inline_close' => "</script>\n",
                    'outline_open' => '<script type="text/javascript" src="',
                    'outline_close' => '"></script>' . "\n"
        ));
    }

    /**
     * Render section with CSS content
     *
     * @param string $fileName
     * @param bool $inline
     * @return string code
     */
    protected function renderSectionCss($fileName, $inline)
    {
        $params = array();
        $params['outline_open'] = '<link rel="stylesheet" type="text/css" href="';
        $params['outline_close'] = '" media="all" />' . "\n";
        $params['inline_open'] = '<style>';
        $params['inline_close'] = '</style>' . "\n";
        $params['inline'] = $inline;
        $params['filename'] = $fileName;
        $result = $this->getSectionCodeByFile($params);
        return $result;
    }

    /**
     * Return cache file content
     *
     * @param string $filename
     * @return string
     * @throws Exception
     */
    protected function getFileContent($filename)
    {
        $cacheFile = $this->getCacheFile($filename);
        return file_get_contents($cacheFile);
    }

    /**
     * Returns cache filename
     *
     * @param string $fileName
     * @return string
     */
    protected function getCacheFile($fileName)
    {
        $cacheFile = $this->createCacheFile($fileName);
        $this->updateCacheFile($fileName, $cacheFile);
        $cacheFile = $this->getRealCacheFilename($cacheFile);
        return $cacheFile;
    }

    /**
     * Create cache file from fileName
     *
     * @param string $fileName
     * @return string
     */
    protected function createCacheFile($fileName)
    {
        $extension = $this->fileExtension($fileName);
        if (empty($extension)) {
            $extension = '.cache';
        }
        $checksum = sha1($fileName . $this->cacheKey);
        $checksumDir = $this->cachePath . '/' . substr($checksum, 0, 2);
        $cacheFile = $checksumDir . '/' . substr($checksum, 2) . '.' . $extension;

        // create cache dir
        if (!file_exists($checksumDir)) {
            $this->createDir($checksumDir);
        }

        if (!file_exists($cacheFile)) {
            $this->touchCacheFile($cacheFile);
        }
        return $cacheFile;
    }

    /**
     * Update cache file if required
     *
     * @param string $fileName
     * @param string $cacheFile
     * @return bool status true if file has changed
     */
    protected function updateCacheFile($fileName, $cacheFile)
    {
        $result = false;
        $localFileName = $this->getRealFilename($fileName);
        $fileTime = filemtime($localFileName);
        $cacheFileTime = filemtime($cacheFile);
        $cacheFileSize = filesize($cacheFile);

        // Compare modification time
        if (($fileTime != $cacheFileTime) || ($cacheFileSize == 0)) {
            $result = true;
            // File has changed, update cache file
            if ($this->minify) {
                $this->compressToFile($fileName, $cacheFile);
            } else {
                // Copy the file to the cache folder
                copy($localFileName, $cacheFile);
            }
            // Sync timestamp
            $this->touchCacheFile($cacheFile, $fileTime);
        }
        return $result;
    }

    /**
     * Returns realpath from filename
     *
     * @param string $filename
     * @return string
     */
    protected function getRealCacheFilename($filename)
    {
        $result = realpath($filename);
        $result = str_replace("\\", '/', $result);
        return $result;
    }

    /**
     * Returns url for filename
     *
     * @param string $filename
     * @return string
     */
    protected function getFileUrl($filename)
    {
        // For url we need to cache it
        $cacheFile = $this->getCacheFile($filename);
        $file = pathinfo($cacheFile, PATHINFO_BASENAME);
        $dir = pathinfo($cacheFile, PATHINFO_DIRNAME);
        $dirs = explode('/', $dir);
        // Folder: cache/ab
        $dirs = array_slice($dirs, count($dirs) - 2);
        // Folder: cache/ab/filename.ext
        $path = implode('/', $dirs) . '/' . $file;
        // Create url
        $cacheUrl = '/' . $path . '?' . urlencode(basename($filename));
        return $cacheUrl;
    }

    /**
     * Compress file to destination file
     *
     * @param string $filename
     * @param string $destFilename
     * @return boolean
     */
    protected function compressToFile($filename, $destFilename)
    {
        $result = true;
        $content = $this->compressFile($filename);
        file_put_contents($destFilename, $content);
        chmod($destFilename, 0775);
        return $result;
    }

    /**
     * Returns compressed file (js/css) content
     *
     * @param string $filename
     * @return string
     */
    protected function compressFile($filename)
    {
        $extension = $this->fileExtension($filename);
        $content = $this->compileFile($filename);

        if ($extension == 'js') {
            $minifier = new Minify\JS($content);
            $content = $minifier->minify();
        } elseif ($extension == 'css') {
            $minifier = new Minify\CSS($content);
            $content = $minifier->minify();
        }
        return $content;
    }

    /**
     * Returns full path and filename
     *
     * @param string $filename
     * @return string
     */
    protected function getRealFilename($filename)
    {
        $template = $this->engine->make($filename);
        $result = $template->path();
        return $result;
    }

    /**
     * Parse php files
     *
     * @param string $filename
     * @return string parsed content
     */
    protected function compileFile($filename)
    {
        $template = $this->engine->make($filename);
        $content = $template->render();
        return $content;
    }

    /**
     * Create directory
     *
     * @param string $dir
     */
    protected function createDir($dir)
    {
        mkdir($dir, 0775, true);
        $this->changeFileMode($dir);
    }

    /**
     * Create file
     *
     * @param string $filename
     * @param int $fileTime
     */
    protected function touchCacheFile($filename, $fileTime = null)
    {
        if ($fileTime === null) {
            touch($filename);
        } else {
            touch($filename, $fileTime);
        }
        $this->changeFileMode($filename);
    }

    /**
     * Change file mode
     *
     * @param string $filename
     */
    protected function changeFileMode($filename)
    {
        chmod($filename, 0775);
    }
}
