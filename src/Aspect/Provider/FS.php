<?php
namespace Aspect\Provider;

use Aspect\ProviderInterface;
/**
 * Templates provider
 * @author Ivan Shalganov
 */
class FS implements ProviderInterface {
    private $_path;

    public function __construct($template_dir) {
        if($_dir = realpath($template_dir)) {
            $this->_path = $_dir;
        } else {
            throw new \LogicException("Template directory {$template_dir} doesn't exists");
        }
    }

    /**
     *
     * @param string $tpl
     * @param int $time
     * @return string
     */
    public function getSource($tpl, &$time) {
        $tpl = $this->_getTemplatePath($tpl);
        clearstatcache(null, $tpl);
        $time = filemtime($tpl);
        return file_get_contents($tpl);
    }

    public function getLastModified($tpl) {
        clearstatcache(null, $tpl = $this->_getTemplatePath($tpl));
        return filemtime($tpl);
    }

    public function getList() {

    }

    /**
     * Get template path
     * @param $tpl
     * @return string
     * @throws \RuntimeException
     */
    protected function _getTemplatePath($tpl) {
        if(($path = realpath($this->_path."/".$tpl)) && strpos($path, $this->_path) === 0) {
            return $path;
        } else {
            throw new \RuntimeException("Template $tpl not found");
        }
    }

	/**
	 * @param string $tpl
	 * @return bool
	 */
	public function isTemplateExists($tpl) {
        return file_exists($this->_path."/".$tpl);
	}

    public function getLastModifiedBatch($tpls) {
        $tpls = array_flip($tpls);
        foreach($tpls as $tpl => &$time) {
            $time = $this->getLastModified($tpl);
        }
        return $tpls;
    }
}