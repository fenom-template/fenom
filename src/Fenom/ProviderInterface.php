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
 * Interface of templates provider
 * @package Fenom
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
interface ProviderInterface
{
    /**
     * @param string $tpl
     * @return bool
     */
    public function templateExists($tpl);

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

    /**
     * Verify templates (check mtime)
     *
     * @param array $templates [template_name => modified, ...] By conversation, you may trust the template's name
     * @return bool if true - all templates are valid else some templates are invalid
     */
    public function verify(array $templates);

    /**
     * Get all names of template from provider
     * @return array|\Iterator
     */
    public function getList();

    /**
     * Clear provider compiles
     *
     * @return mixed
     */
    public function clearCompiles();

    /**
     * Compile and save template
     *
     * @param string $tpl
     * @param bool $store store template on disk
     * @param int $options
     * @throws \RuntimeException
     * @return \Fenom\Template
     */
    public function compile($tpl, $store = true, $options = 0);

    /**
     * Load template from cache or create cache if it doesn't exists.
     *
     * @param string $tpl
     * @param int $opts
     * @return \Fenom\Render
     */
    public function load($tpl, $opts);

    /**
     * Set Fenom engine
     *
     * @param \Fenom $fenom
     * @return mixed
     */
    public function setFenom(\Fenom $fenom);
}
