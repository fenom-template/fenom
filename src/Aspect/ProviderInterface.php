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
     * @return string
     */
    public function loadCode($tpl);

    /**
     * @param string $tpl
     * @return int
     */
    public function getLastModified($tpl);

    public function getLastModifiedBatch($tpls);

    /**
     * @return array
     */
    public function getAll();
}
