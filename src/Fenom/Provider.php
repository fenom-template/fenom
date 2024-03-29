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

/**
 * Base template provider
 * @author Ivan Shalganov
 */
class Provider implements ProviderInterface
{
    private string $_path;

    protected bool $_clear_cache = false;

    /**
     * Clean directory from files
     *
     * @param string $path
     */
    public static function clean(string $path)
    {
        if (is_file($path)) {
            unlink($path);
        } elseif (is_dir($path)) {
            $iterator = iterator_to_array(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path,
                        \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                )
            );
            foreach ($iterator as $file) {
                /* @var \splFileInfo $file */
                if ($file->isFile()) {
                    if (!str_starts_with($file->getBasename(), ".")) {
                        unlink($file->getRealPath());
                    }
                } elseif ($file->isDir()) {
                    rmdir($file->getRealPath());
                }
            }
        }
    }

    /**
     * Recursive remove directory
     *
     * @param string $path
     */
    public static function rm(string $path)
    {
        self::clean($path);
        if (is_dir($path)) {
            rmdir($path);
        }
    }

    /**
     * @param string $template_dir directory of templates
     * @throws \LogicException if directory doesn't exist
     */
    public function __construct(string $template_dir)
    {
        if ($_dir = realpath($template_dir)) {
            $this->_path = $_dir;
        } else {
            throw new \LogicException("Template directory {$template_dir} doesn't exists");
        }
    }

    /**
     * Disable PHP cache for files. PHP cache some operations with files then script works.
     * @see http://php.net/manual/en/function.clearstatcache.php
     * @param bool $status
     */
    public function setClearCachedStats(bool $status = true) {
        $this->_clear_cache = $status;
    }

    /**
     * Get source and mtime of template by name
     * @param string $tpl
     * @param float|null $time load last modified time
     * @return string
     */
    public function getSource(string $tpl, ?float &$time): string
    {
        $tpl = $this->_getTemplatePath($tpl);
        if($this->_clear_cache) {
            clearstatcache(true, $tpl);
        }
        $time = filemtime($tpl);
        return file_get_contents($tpl);
    }

    /**
     * Get last modified of template by name
     * @param string $tpl
     * @return float
     */
    public function getLastModified(string $tpl): float
    {
        $tpl = $this->_getTemplatePath($tpl);
        if($this->_clear_cache) {
            clearstatcache(true, $tpl);
        }
        return (float)filemtime($tpl);
    }

    /**
     * Get all names of templates from provider.
     *
     * @param string $extension all templates must have this extension, default .tpl
     * @return iterable
     */
    public function getList(string $extension = "tpl"): iterable
    {
        $list     = array();
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->_path,
                \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        $path_len = strlen($this->_path);
        foreach ($iterator as $file) {
            /* @var \SplFileInfo $file */
            if ($file->isFile() && $file->getExtension() == $extension) {
                $list[] = substr($file->getPathname(), $path_len + 1);
            }
        }
        return $list;
    }

    /**
     * Get template path
     * @param string $tpl
     * @return string
     * @throws \RuntimeException
     */
    protected function _getTemplatePath(string $tpl): string
    {
        $path = realpath($this->_path . "/" . $tpl);
        if ($path && str_starts_with($path, $this->_path)) {
            return $path;
        } else {
            throw new \RuntimeException("Template $tpl not found");
        }
    }

    /**
     * @param string $tpl
     * @return bool
     */
    public function templateExists(string $tpl): bool
    {
        return ($path = realpath($this->_path . "/" . $tpl)) && str_starts_with($path, $this->_path);
    }

    /**
     * Verify templates (check change time)
     *
     * @param array $templates [template_name => modified, ...] By conversation, you may trust the template's name
     * @return bool
     */
    public function verify(array $templates): bool
    {
        foreach ($templates as $template => $mtime) {
            $template = $this->_path . '/' . $template;
            if($this->_clear_cache) {
                clearstatcache(true, $template);
            }
            if (@filemtime($template) != $mtime) {
                return false;
            }

        }
        return true;
    }
}