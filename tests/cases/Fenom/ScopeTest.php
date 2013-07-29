<?php
namespace Fenom;

class ScopeTest extends TestCase
{
    public function openTag($tokenizer, $scope)
    {
        $this->assertInstanceOf('Fenom\Tokenizer', $tokenizer);
        $this->assertInstanceOf('Fenom\Scope', $scope);
        $scope["value"] = true;
        return "open-tag";
    }

    public function closeTag($tokenizer, $scope)
    {
        $this->assertInstanceOf('Fenom\Tokenizer', $tokenizer);
        $this->assertInstanceOf('Fenom\Scope', $scope);
        $this->assertTrue($scope["value"]);
        return "close-tag";
    }

    public function testBlock()
    {
        /*$scope = new Scope($this->fenom, new Template($this->fenom), 1, array(
            "open" => array($this, "openTag"),
            "close" => array($this, "closeTag")
        ), 0);
        $tokenizer = new Tokenizer("1+1");
        $this->assertSame("open-tag /*#{$scope->id}#* /", $scope->open($tokenizer));
        $this->assertSame("close-tag", $scope->close($tokenizer));

        $content = " some ?> content\n\nwith /*#9999999#* / many\n\tlines";
        $scope->tpl->_body = "start <?php ".$scope->open($tokenizer)." ?>".$content;
        $this->assertSame($content, $scope->getContent());*/
    }
}
