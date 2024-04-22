<?php

require "vendor/autoload.php";
use PHPUnit\Framework\TestCase;
use Enhance\Elements;

global $allElements;
$allElements = new Elements(__DIR__ . "/../fixtures/templates");

class ElementsTest extends TestCase
{
    public function testExecuteHtml()
    {
        global $allElements;
        $this->assertSame(
            strip($allElements->execute("my-paragraph")),
            strip('<p><slot name="my-text">My default text</slot></p>'),
            "Loaded HTML from Elements"
        );
    }
    public function testExecuteFunction()
    {
        global $allElements;
        $this->assertSame(
            strip($allElements->execute("my-pre")),
            strip("<pre></pre>"),
            "Loaded Function form from Elements"
        );
    }
}

