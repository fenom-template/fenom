<?php
/*
 * This file is part of Fenom.
 *
 * (c) 2013 Ivan Shalganov
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */
namespace Fenom;

use InvalidArgumentException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Base template provider.
 * 
 * @author Ivan Shalganov
 */
class Provider implements ProviderInterface
{
    /**
     * @var string template directory
     */
    protected $path;
    
    /**
     * @var string template extension
     */
    protected $extension = 'tpl';

    /**
     * @var bool
     */
    protected $clear_cache = false;

    /**
     * Cleans directory from files and sub directories.
     *
     * @param string $path
     * @return void
     */
    public static function clean($path)
    {
        if (is_file($path)) {
            unlink($path);
        } else {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path,
                    RecursiveDirectoryIterator::CURRENT_AS_FILEINFO | RecursiveDirectoryIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            /* @var \SplFileInfo $info */
            foreach ($iterator as $info) {
                if ($info->isFile()) {
                    unlink($info->getRealPath());
                } else {
                    rmdir($info->getRealPath());
                }
            }
        }
    }

    /**
     * Removes directory or file.
     *
     * @param string $path template directory
     * @return void
     */
    public static function rm($path)
    {
        static::clean($path);
        if (is_dir($path)) {
            rmdir($path);
        }
    }

    /**
     * @param string $template_dir template directory
     * @param string|null $extension template extension
     * @throws InvalidArgumentException if directory doesn't exists
     */
    public function __construct($template_dir, $extension = null)
    {
        $path = realpath($template_dir)
        if (! $path) {
            throw new InvalidArgumentException("Template directory {$template_dir} doesn't exists");
        }
        $this->path = $path . DIRECTORY_SEPARATOR;
        if ($extension !== null) {
            $this->extension = strtolower($extension);
        }
        
    }

    /**
     * Get template path.
     * @param string $tpl
     * @param bool $throw
     * @return string|null
     * @throws InvalidArgumentException template not found
     */
    protected function getTemplatePath($tpl, $throw = true)
    {
        $path = realpath($this->path . $tpl);
        if ($throw && ! $path) {
            throw new InvalidArgumentException("Template $tpl not found");
        }
        return $path;
    }
    
    /**
     * Disable PHP cache for files. PHP cache some operations with files then script works.
     * @link https://www.php.net/manual/en/function.clearstatcache.php
     * @param bool $status
     * @return $this
     */
    public function setClearCachedStats($status = true)
    {
        $this->clear_cache = (bool) $status;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSource($tpl, &$time)
    {
        $path = $this->getTemplatePath($tpl);
        if ($this->clear_cache) {
            clearstatcache(true, $path);
        }
        $time = filemtime($path);
        return file_get_contents($path);
    }

    /**
     * @inheritDoc
     */
    public function getLastModified($tpl)
    {
        $path = $this->getTemplatePath($tpl);
        if ($this->clear_cache) {
            clearstatcache(true, $path);
        }
        return filemtime($path);
    }

    /**
     * {@inheritDoc}
     * @param string $extension deprecated, set extension in constructor
     */
    public function getList($extension = null)
    {
        $list = array();
        $path_len = strlen($this->path) + 1;
        $extension = $extension ? strtolower($extension) : $this->extension;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->path,
                RecursiveDirectoryIterator::CURRENT_AS_FILEINFO | RecursiveDirectoryIterator::SKIP_DOTS
            )
        );
        /* @var \SplFileInfo $info */
        foreach ($iterator as $info) {
            if ($info->isFile() && strtolower($info->getExtension()) == $extension) {
                $list[] = substr($info->getRealPath(), $path_len);
            }
        }
        return $list;
    }

    /**
     * @inheritDoc
     */
    public function templateExists($tpl)
    {
        return (bool) $this->getTemplatePath($tpl, false);
    }

    /**
     * @inheritDoc
     */
    public function verify(array $templates)
    {
        foreach ($templates as $template => $mtime) {
            $path = $this->getTemplatePath($template, false);
            if (! $path) {
                return false;
            }
            if ($this->clear_cache) {
                clearstatcache(true, $path);
            }
            if (filemtime($path) != $mtime) {
                return false;
            }

        }
        return true;
    }
}
