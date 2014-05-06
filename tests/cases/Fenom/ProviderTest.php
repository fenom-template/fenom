<?php
namespace Fenom;

use Fenom;
use Fenom\TestCase;

class ProviderTest extends TestCase
{
    /**
     * @var Provider
     */
    public $provider;

    public function setUp()
    {
        parent::setUp();
        $this->tpl("template1.tpl", 'Template 1 {$a}');
        $this->tpl("template2.tpl", 'Template 2 {$a}');
        $this->tpl("sub/template3.tpl", 'Template 3 {$a}');
        $this->provider = new Provider(FENOM_RESOURCES . '/template');
        clearstatcache();
    }

    public function testIsTemplateExists()
    {
        clearstatcache();
        $this->assertTrue($this->provider->templateExists("template1.tpl"));
        $this->assertFalse($this->provider->templateExists("unexists.tpl"));
    }

    public function testGetSource()
    {
        clearstatcache();
        $src = $this->provider->getSource("template1.tpl", $time);
        clearstatcache();
        $this->assertEquals(file_get_contents(FENOM_RESOURCES . '/template/template1.tpl'), $src);
        $this->assertEquals(filemtime(FENOM_RESOURCES . '/template/template1.tpl'), $time);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetSourceInvalid()
    {
        $this->provider->getSource("unexists.tpl", $time);
    }

    public function testGetLastModified()
    {
        $time = $this->provider->getLastModified("template1.tpl");
        clearstatcache();
        $this->assertEquals(filemtime(FENOM_RESOURCES . '/template/template1.tpl'), $time);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetLastModifiedInvalid()
    {
        $this->provider->getLastModified("unexists.tpl");
    }

    public function testVerify()
    {
        $templates = array(
            "template1.tpl" => filemtime(FENOM_RESOURCES . '/template/template1.tpl'),
            "template2.tpl" => filemtime(FENOM_RESOURCES . '/template/template2.tpl')
        );
        clearstatcache();
        $this->assertTrue($this->provider->verify($templates));
        clearstatcache();
        $templates = array(
            "template2.tpl" => filemtime(FENOM_RESOURCES . '/template/template2.tpl'),
            "template1.tpl" => filemtime(FENOM_RESOURCES . '/template/template1.tpl')
        );
        clearstatcache();
        $this->assertTrue($this->provider->verify($templates));
    }

    public function testVerifyInvalid()
    {
        $templates = array(
            "template1.tpl" => filemtime(FENOM_RESOURCES . '/template/template1.tpl'),
            "template2.tpl" => filemtime(FENOM_RESOURCES . '/template/template2.tpl') + 1
        );
        clearstatcache();
        $this->assertFalse($this->provider->verify($templates));
        clearstatcache();
        $templates = array(
            "template1.tpl" => filemtime(FENOM_RESOURCES . '/template/template1.tpl'),
            "unexists.tpl"  => 1234567890
        );
        $this->assertFalse($this->provider->verify($templates));
    }

    public function testGetAll()
    {
        $list = $this->provider->getList();
        sort($list);
        $this->assertSame(
            array(
                "sub/template3.tpl",
                "template1.tpl",
                "template2.tpl"
            ),
            $list
        );
    }

    public function testRm()
    {
        $this->assertTrue(is_dir(FENOM_RESOURCES . '/template/sub'));
        Provider::rm(FENOM_RESOURCES . '/template/sub');
        $this->assertFalse(is_dir(FENOM_RESOURCES . '/template/sub'));
        $this->assertTrue(is_file(FENOM_RESOURCES . '/template/template1.tpl'));
        Provider::rm(FENOM_RESOURCES . '/template/template1.tpl');
        $this->assertFalse(is_file(FENOM_RESOURCES . '/template/template1.tpl'));
        $this->assertTrue(is_file(FENOM_RESOURCES . '/template/template2.tpl'));
        Provider::clean(FENOM_RESOURCES . '/template/');
        $this->assertFalse(is_file(FENOM_RESOURCES . '/template/template2.tpl'));
    }
}

