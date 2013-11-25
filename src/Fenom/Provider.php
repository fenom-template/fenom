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

use Fenom\ProviderInterface;

/**
 * Base template provider
 * @author Ivan Shalganov
 */
class Provider implements ProviderInterface
{
    /**
     * Templates source path
     * @var string
     */
    private $_path;
    /**
     * Fenom engine object
     * @var \Fenom $_fenom
     */
    private $_fenom;
    /**
     * Templates compile_dir
     * @var string compile directory
     */
    protected $_compile_dir = "/tmp";

    /**
     * @param string $template_dir directory of templates
     * @param string $compile_dir directory of compiled templates
     * @throws \LogicException if directory doesn't exists
     */
    public function __construct($template_dir, $compile_dir = null)
    {
        if ($_dir = realpath($template_dir)) {
            $this->_path = $_dir;
        } else {
            throw new \LogicException("Template directory {$template_dir} doesn't exists");
        }
        if(!is_null($compile_dir))
        {
            $this->setCompileDir($compile_dir);
        }
    }

    /**
     * @param \Fenom $fenom
     */
    public function setFenom(\Fenom $fenom)
    {
        $this->_fenom = $fenom;
    }

    /**
     * Get source and mtime of template by name
     * @param string $tpl
     * @param int $time load last modified time
     * @return string
     */
    public function getSource($tpl, &$time)
    {
        $tpl = $this->_getTemplatePath($tpl);
        clearstatcache(null, $tpl);
        $time = filemtime($tpl);
        return file_get_contents($tpl);
    }

    /**
     * Get last modified of template by name
     * @param string $tpl
     * @return int
     */
    public function getLastModified($tpl)
    {
        clearstatcache(null, $tpl = $this->_getTemplatePath($tpl));
        return filemtime($tpl);
    }

    /**
     * Get all names of templates from provider.
     *
     * @param string $extension all templates must have this extension, default .tpl
     * @return array|\Iterator
     */
    public function getList($extension = "tpl")
    {
        $list = array();
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
     * @param $tpl
     * @return string
     * @throws \RuntimeException
     */
    protected function _getTemplatePath($tpl)
    {
        if (($path = realpath($this->_path . "/" . $tpl)) && strpos($path, $this->_path) === 0) {
            return $path;
        } else {
            throw new \RuntimeException("Template $tpl not found");
        }
    }

    /**
     * @param string $tpl
     * @return bool
     */
    public function templateExists($tpl)
    {
        return file_exists($this->_path . "/" . $tpl);
    }

    /**
     * Verify templates (check change time)
     *
     * @param array $templates [template_name => modified, ...] By conversation, you may trust the template's name
     * @return bool
     */
    public function verify(array $templates)
    {
        foreach ($templates as $template => $mtime) {
            clearstatcache(null, $template = $this->_path . '/' . $template);
            if (@filemtime($template) !== $mtime) {
                return false;
            }

        }
        return true;
    }

    /**
     * Set compile directory
     *
     * @param string $dir directory to store compiled templates in
     * @throws \LogicException
     * @return \Fenom
     */
    public function setCompileDir($dir)
    {
        if (!is_writable($dir)) {
            throw new \LogicException("Cache directory $dir is not writable");
        }
        $this->_compile_dir = $dir;
        return $this;
    }

    /**
     * Generate unique name of compiled template
     *
     * @param string $tpl
     * @param int $options
     * @return string
     */
    private function _getCacheName($tpl, $options)
    {
        $hash = $tpl . ":" . $options;
        return sprintf("%s.%x.%x.php", str_replace(":", "_", basename($tpl)), crc32($hash), strlen($hash));
    }

    /**
     * Compile and save template
     *
     * @param string $tpl
     * @param bool $store store template on disk
     * @param int $options
     * @throws \RuntimeException
     * @return \Fenom\Template
     */
    public function compile($tpl, $store = true, $options = 0)
    {
        $options = $this->_fenom->getOptions() | $options;
        $template = $this->_fenom->getRawTemplate()->load($tpl);
        if ($store) {
            $cache = $this->_getCacheName($tpl, $options);
            $tpl_tmp = tempnam($this->_compile_dir, $cache);
            $tpl_fp = fopen($tpl_tmp, "w");
            if (!$tpl_fp) {
                throw new \RuntimeException("Can't to open temporary file $tpl_tmp. Directory " . $this->_compile_dir . " is writable?");
            }
            fwrite($tpl_fp, $template->getTemplateCode());
            fclose($tpl_fp);
            $file_name = $this->_compile_dir . "/" . $cache;
            if (!rename($tpl_tmp, $file_name)) {
                throw new \RuntimeException("Can't to move $tpl_tmp to $file_name");
            }
        }
        return $template;
    }

    /**
     * Load template from cache or create cache if it doesn't exists.
     *
     * @param string $tpl
     * @param int $opts
     * @return \Fenom\Render
     */
    public function load($tpl, $opts)
    {
        $file_name = $this->_getCacheName($tpl, $opts);
        if (is_file($this->_compile_dir . "/" . $file_name)) {
            $fenom = $this->_fenom; // used in template
            $_tpl = include($this->_compile_dir . "/" . $file_name);
            /* @var \Fenom\Render $_tpl */
            if (!($this->_fenom->getOptions() & \Fenom::AUTO_RELOAD) || ($this->_fenom->getOptions() & \Fenom::AUTO_RELOAD) && $_tpl->isValid()) {
                return $_tpl;
            }
        }
        return $this->compile($tpl, true, $opts);
    }

    /**
     * Clear compiles cache
     */
    public function clearCompiles()
    {
        if (is_file($this->_compile_dir)) {
            unlink($this->_compile_dir);
        } elseif (is_dir($this->_compile_dir)) {
            $iterator = iterator_to_array(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($this->_compile_dir,
                        \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                )
            );
            foreach ($iterator as $file) {
                /* @var \splFileInfo $file */
                if ($file->isFile()) {
                    if (strpos($file->getBasename(), ".") !== 0) {
                        unlink($file->getRealPath());
                    }
                } elseif ($file->isDir()) {
                    rmdir($file->getRealPath());
                }
            }
        }

    }
}