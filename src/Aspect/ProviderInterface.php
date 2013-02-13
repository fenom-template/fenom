<?php
namespace Aspect;

interface ProviderInterface {
    /**
     * @param string $tpl
     * @return bool
     */
    public function isTemplateExists($tpl);
    /**
     * @param string $tpl
     * @param int $time
     * @return string
     */
    public function getSource($tpl, &$time);

    /**
     * @param string $tpl
     * @return int
     */
    public function getLastModified($tpl);

    public function getLastModifiedBatch($tpls);

    /**
     * @return array
     */
    public function getList();
}
