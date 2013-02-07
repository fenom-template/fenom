<?php
namespace Aspect;
/**
 * Templates provider
 * @author Ivan Shalganov
 */
class Provider implements ProviderInterface {
    private $_tpl_path = array();

    public function setTemplateDirs($dirs) {
        foreach((array)$dirs as $dir) {
            $this->addTemplateDir($dir);
        }
        return $this;
    }

    public function addTemplateDir($dir) {
        if($_dir = realpath($dir)) {
            $this->_tpl_path[] = $_dir;
        } else {
            throw new \LogicException("Template directory {$dir} doesn't exists");
        }
    }

    /**
     * @param string $tpl
     * @return string
     */
    public function loadCode($tpl) {
        return file_get_contents($tpl = $this->_getTemplatePath($tpl));
    }

    public function getLastModified($tpl) {
        clearstatcache(null, $tpl = $this->_getTemplatePath($tpl));
        return filemtime($tpl);
    }

    public function getAll() {

    }

    /**
     * Get template path
     * @param $tpl
     * @return string
     * @throws \RuntimeException
     */
    private function _getTemplatePath($tpl) {
        foreach($this->_tpl_path as $tpl_path) {
            if(($path = realpath($tpl_path."/".$tpl)) && strpos($path, $tpl_path) === 0) {
                return $path;
            }
        }
        throw new \RuntimeException("Template $tpl not found");
    }

	/**
	 * @param string $tpl
	 * @return bool
	 */
	public function isTemplateExists($tpl) {
		foreach($this->_tpl_path as $tpl_path) {
			if(($path = realpath($tpl_path."/".$tpl)) && strpos($path, $tpl_path) === 0) {
				return true;
			}
		}

		return false;
	}

    public function getLastModifiedBatch($tpls) {
        $tpls = array_flip($tpls);
        foreach($tpls as $tpl => &$time) {
            $time = $this->getLastModified($tpl);
        }
        return $tpls;
    }
}
