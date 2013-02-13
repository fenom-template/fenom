<?php
namespace Aspect\Provider;
/**
 * Templates provider
 * @author Ivan Shalganov
 */
class Provider extends FS {
    private $_tpl_path = array();

    public function __construct($template_dir) {
        $this->addTemplateDirs($template_dir);
    }

    public function addTemplateDirs($dirs) {
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
     * Get template path
     * @param $tpl
     * @return string
     * @throws \RuntimeException
     */
    protected function _getTemplatePath($tpl) {
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
			if(file_exists($tpl_path."/".$tpl)) {
				return true;
			}
		}

		return false;
	}
}