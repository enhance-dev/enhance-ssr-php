<?php

require "vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use Enhance\Enhancer;

class IsCustomElementTests extends TestCase
{
    public function testIsCustomElement()
    {
        $this->assertFalse(
            Enhancer::isCustomElement("Tag-Name"),
            "catches uppercase"
        );
        $this->assertFalse(
            Enhancer::isCustomElement("-tag-Name"),
            "catches starting dash"
        );
        $this->assertFalse(
            Enhancer::isCustomElement("1tag-name"),
            "catches digit"
        );
        $this->assertFalse(
            Enhancer::isCustomElement("1tag-name"),
            "catches digit"
        );
        $this->assertFalse(
            Enhancer::isCustomElement("font-face"),
            "catches reserved word"
        );
        $this->assertTrue(
            Enhancer::isCustomElement("tag-name"),
            "valid custom element"
        );
        $this->assertTrue(
            Enhancer::isCustomElement("tag-😊"),
            "unicode character"
        );
    }
}
?>
