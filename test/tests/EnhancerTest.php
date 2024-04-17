<?php

require "vendor/autoload.php";
use PHPUnit\Framework\TestCase;
use Enhance\Enhancer;
use Enhance\Elements;
use Enhance\ShadyStyles;

global $allElements;
$allElements = new Elements(__DIR__ . "/../fixtures/templates");

class EnhancerTest extends TestCase
{
    public function testEnhance()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "initialState" => ["message" => "Hello, World!"],
            "enhancedAttr" => false,
        ]);

        $htmlString =
            "<html><head><title>Test</title></head><body>Content</body></html>";
        $expectedString =
            "<html><head><title>Test</title></head><body>Content</body></html>";

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "The html doc matches."
        );

        $htmlString = "Fragment content";
        $expectedString = "<html><body><p>Fragment content</p></body></html>";

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "html, and body are added."
        );

        $htmlString =
            "<div><div><my-heading></my-heading></div></div><my-heading></my-heading>";
        $expectedString =
            "<html><body><div><div><my-heading><h1></h1></my-heading></div></div><my-heading><h1></h1></my-heading></body></html>";

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Custom Element Expansion."
        );
    }
    public function testEmptySlot()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = "<my-paragraph></my-paragraph>";
        $expectedString =
            "<my-paragraph><p><span slot=\"my-text\">My default text</span></p></my-paragraph>";

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "by gum, i do believe that it does expand that template with slotted default content"
        );
    }
    public function testTemplateExpansion()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString =
            "<my-paragraph><span slot=\"my-text\">I'm in a slot</span></my-paragraph>";
        $expectedString =
            "<my-paragraph><p><span slot=\"my-text\">I'm in a slot</span></p></my-paragraph>";

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "slotted content is added to the template"
        );
    }
    public function testAddEnhancedAttr()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => true,
        ]);

        $htmlString =
            "<my-paragraph><span slot=\"my-text\">I'm in a slot</span></my-paragraph>";
        $expectedString =
            "<my-paragraph enhanced=\"✨\"><p><span slot=\"my-text\">I'm in a slot</span></p></my-paragraph>";

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Enhanced attribute is added to the template"
        );
    }
    public function testPassStateThroughLevels()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "initialState" => ["items" => ["test"]],
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = "<my-pre-page items=\"\"></my-pre-page>";
        $expectedString = <<<HTMLCONTENT
            <my-pre-page items="">
                <my-pre items="">
                  <pre>test</pre>
                </my-pre>
              </my-pre-page>
HTMLCONTENT;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Enhanced attribute is added to the template"
        );
    }

    public function testShouldRenderAsDivWithSlotName()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = "<my-multiples>></my-multiples>";
        $expectedString = <<<HTMLCONTENT
              <my-multiples>
                <div slot="my-content">
                  My default text

                  <h3>
                    A smaller heading
                  </h3>


                  Random text

                  <code> a code block</code>
                </div>
              </my-multiples>
HTMLCONTENT;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "It renders slot as div tag with slot name added"
        );
    }

    public function testShouldNotDuplicateSlottedContent()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = <<<HTML
        <my-outline>
          <div slot="toc" class="toc">things</div>
        </my-outline>
HTML;
        $expectedString = <<<HTMLCONTENT
        <my-outline>
          <div slot="toc" class="toc">things</div>
        </my-outline>
HTMLCONTENT;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "It does not duplicate slotted content"
        );
    }

    public function testFillNamedSlots()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);
        $htmlString = <<<HTML
        <my-paragraph id="0">
          <span slot="my-text">Slotted</span>
        </my-paragraph>
HTML;
        $expectedString = <<<HTMLCONTENT
        <my-paragraph id="0">
          <p><span slot="my-text">Slotted</span></p>
        </my-paragraph>
HTMLCONTENT;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "It fills named slots"
        );
    }

    public function testShouldRenderDefaultContentInUnnamedSlots()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = '<my-unnamed id="0"></my-unnamed>';
        $expectedString = '<my-unnamed id="0">Default Content</my-unnamed>';

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "It fills named slots"
        );
    }
    public function testShouldNotRenderDefaultContentInUnnamedSlotsWithWhiteSpace()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = '<my-unnamed id="0">  </my-unnamed>';
        $expectedString = '<my-unnamed id="0">  </my-unnamed>';

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "It fills named slots"
        );
    }

    public function testAddAuthoredChildrenToUnnamedSlot()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = <<<HTML
        <my-content id="0">
          <h4 slot=title>Custom title</h4>
        </my-content>
HTML;

        $expectedString = <<<HTML
      <my-content id="0">
        <h2>My Content</h2>
        <h4 slot="title">Custom title</h4>
      </my-content>
HTML;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "It adds authored children to unnamed slot"
        );
    }

    public function testPassAttributesAsState()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        {$head}
        <my-link href='/yolo' text='sketchy'></my-link>
HTML;

        $expectedString = <<<HTML
        <!DOCTYPE html>
        <html>
        <head></head>
        <body>
        <my-link href="/yolo" text="sketchy">
          <a href="/yolo">sketchy</a>
        </my-link>
        <script type="module">
          class MyLink extends HTMLElement {
            constructor() {
              super()
            }
            connectedCallback() {
              console.log('My Link')
            }
          }
        </script>
        </body>
        </html>
HTML;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "passes attributes as a state object when executing template functions"
        );
    }

    public function testBadXML()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = <<<HTML
        <my-bad-xml></my-bad-xml>
HTML;

        $expectedString = <<<HTMLDOC
        <my-bad-xml>
          <h4 slot="title">My list</h4>
          <img src="/">
          <input>
        </my-bad-xml>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Poorly formed html that does not meet xml standards"
        );
    }
    public function testPassArrayValuesDoesnt()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "initialState" => [
                "items" => [
                    ["title" => "one"],
                    ["title" => "two"],
                    ["title" => "three"],
                ],
            ],
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        {$head}
        <my-list items="" ></my-list>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head></head>
        <body>
        <my-list items="">
          <h4 slot="title">My list</h4>
          <ul>
            <li>one</li>
            <li>two</li>
            <li>three</li>
          </ul>
        </my-list>
        <script type="module">
          class MyList extends HTMLElement {
            constructor() {
              super()
            }
            connectedCallback() {
              console.log('My List')
            }
          }
        </script>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Expands list items from state"
        );
    }

    public function testDeeplyNestedSlots()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = <<<HTML
        <my-content>
          <my-content id="0">
            <h3 slot="title">Second</h3>
            <my-content id="1">
              <h3 slot="title">Third</h3>
            </my-content>
          </my-content>
        </my-content>
HTML;

        $expectedString = <<<HTMLDOC
        <my-content>
          <h2>My Content</h2>
          <h3 slot="title">
            Title
          </h3>
          <my-content id="0">
            <h2>My Content</h2>
            <h3 slot="title">Second</h3>
            <my-content id="1">
              <h2>My Content</h2>
              <h3 slot="title">Third</h3>
            </my-content>
          </my-content>
        </my-content>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Fills deeply nested slots"
        );
    }

    public function testFillNestedRenderedSlots()
    {
        //TODO: This tests is modified from the JS version to use the Store. We need to reconcile the two tests
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "initialState" => [
                "items" => [
                    ["title" => "one"],
                    ["title" => "two"],
                    ["title" => "three"],
                ],
            ],
        ]);
        $head = HeadTag();

        $htmlString = <<<HTML
        {$head}
      <my-list-container items="">
        <span slot=title>YOLO</span>
      </my-list-container>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head></head>
        <body>
        <my-list-container items="">
          <h2>My List Container</h2>
          <span slot="title">
            YOLO
          </span>
          <my-list items="">
            <h4 slot="title">Content List</h4>
            <ul>
              <li>one</li>
              <li>two</li>
              <li>three</li>
            </ul>
          </my-list>
        </my-list-container>
        <script type="module">
          class MyListContainer extends HTMLElement {
            constructor() {
              super()
            }

            connectedCallback() {
              console.log('My List Container')
            }
          }
        </script>
        <script type="module">
          class MyList extends HTMLElement {
            constructor() {
              super()
            }

            connectedCallback() {
              console.log('My List')
            }
          }
        </script>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Wow it renders nested custom elements by passing that handy render function when executing template functions"
        );
    }

    public function testAllowCustomHeadTag()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
        ]);

        $htmlString = <<<HTML
        <!DOCTYPE html>
        <head>
          <meta charset="utf-8">
          <title>Yolo!</title>
          <link rel="stylesheet" href="/style.css">
        </head>
        <my-counter count="3"></my-counter>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>Yolo!</title>
          <link rel="stylesheet" href="/style.css">
        </head>
        <body>
        <my-counter count="3"><h3>Count: 3</h3></my-counter>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "It allows custom head tag"
        );
    }

    public function testShouldPassStoreToTemplate()
    {
        // test('should pass store to template', t => {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "initialState" => [
                "apps" => [
                    [
                        "id" => 1,
                        "name" => "one",
                        "users" => [
                            [
                                "id" => 1,
                                "name" => "jim",
                            ],
                            [
                                "id" => 2,
                                "name" => "kim",
                            ],
                            [
                                "id" => 3,
                                "name" => "phillip",
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        {$head}
        <my-store-data app-index="0" user-index="1"></my-store-data>
HTML;

        $expectedString = <<<HTMLDOC
<!DOCTYPE html>
<html>
<head></head>
<body>
<my-store-data app-index="0" user-index="1">
  <div>
    <h1>kim</h1>
    <h1>2</h1>
  </div>
</my-store-data>
</body>
</html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Store data is passed to template"
        );
    }
    public function testRunScriptTransform()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "scriptTransforms" => [
                function ($params) {
                    $raw = $params["raw"];
                    $tagName = $params["tagName"];
                    return "{$raw}\n{$tagName}";
                },
            ],
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        {$head}
    <my-transform-script></my-transform-script>
    <my-transform-script></my-transform-script>
HTML;

        $expectedString = <<<HTMLDOC
       <!DOCTYPE html>
       <html>
       <head></head>
       <body>
       <my-transform-script>
         <h1>My Transform Script</h1>
       </my-transform-script>
       <my-transform-script>
         <h1>My Transform Script</h1>
       </my-transform-script>
       <script type="module">
         class MyTransformScript extends HTMLElement {
           constructor() {
             super();
           }
         }
         customElements.define("my-transform-script", MyTransformScript);
         my-transform-script
       </script>
       </body>
       </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Script Transform is run"
        );
    }

    public function testShouldNotAddDuplicateStyleTags()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "styleTransforms" => [
                function ($params) {
                    $attrs = $params["attrs"];
                    $raw = $params["raw"];
                    $tagName = $params["tagName"];
                    $context = $params["context"];
                    $globalScope =
                        isset($attrs["scope"]) && $attrs["scope"] === "global";
                    if ($globalScope && $context === "template") {
                        return "";
                    }
                    // Otherwise, return the raw content and additional comment
                    return <<<CSS
                    $raw
                    /*
                    $tagName styles
                    context: $context
                    */
CSS;
                },
            ],
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        {$head}
      <my-transform-style></my-transform-style>
      <my-transform-style></my-transform-style>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head>
        <style>
        :host {
            display: block;
          }
          /*
          my-transform-style styles
          context: markup
          */
          :slot {
            display: inline-block;
          }
          /*
          my-transform-style styles
          context: markup
          */

        </style>
        </head>
        <body>
        <my-transform-style>
          <h1>My Transform Style</h1>
        </my-transform-style>
        <my-transform-style>
          <h1>My Transform Style</h1>
        </my-transform-style>
        <script type="module">
          class MyTransformStyle extends HTMLElement {
            constructor() {
              super();
            }
          }
          customElements.define("my-transform-style", MyTransformStyle);
        </script>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Removed Duplicate Style Tags"
        );
    }

    public function testShouldRespectAsAttribute()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = <<<HTML
          <my-slot-as></my-slot-as>
HTML;

        $expectedString = <<<HTMLDOC
        <my-slot-as>
          <div slot="stuff">
            stuff
          </div>
        </my-slot-as>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Respects as attribute"
        );
    }

    public function testShouldAddMultipleExternalScritps()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "scriptTransforms" => [
                function ($params) {
                    return "{$params["raw"]}\n{$params["tagName"]}";
                },
            ],
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        $head
        <my-external-script></my-external-script>
        <my-external-script></my-external-script>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head>
        </head>
        <body>
          <my-external-script>
            <input type="range">
          </my-external-script>
          <my-external-script>
            <input type="range">
          </my-external-script>
          <script type="module" src="_static/range.mjs"></script>
          <script src="_static/another.mjs"></script>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Adds multiple external scripts"
        );
    }

    public function testShouldSupportUnnamedSlotWithoutWhitespace()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        <my-unnamed>My Text</my-unnamed>
HTML;

        $expectedString = <<<HTMLDOC
        <my-unnamed>My Text</my-unnamed>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Unnamed slot without whitespace"
        );
    }

    public function testShouldSupportNestedCustomElementWithNestedSlot()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        <!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>
        <my-super-heading>
          <span slot="emoji">
            ✨
          </span>
          My Heading
        </my-super-heading>
        </body></html>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>
        <my-super-heading>
          <span slot="emoji">
            ✨
          </span>
          <my-heading>
            <h1>
              My Heading
            </h1>
          </my-heading>
        </my-super-heading>
        </body></html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Renders nested slots in nested custom elements"
        );
    }

    public function testShouldNotFailWhenPassedCustomElementWithoutTemplate()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        <noop-noop></noop-noop>
HTML;

        $expectedString = <<<HTMLDOC
        <noop-noop></noop-noop>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Does not fail when passed custom element without template"
        );
    }

    public function testShouldSupplyInstanceID()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "uuidFunction" => function () {
                return "abcd1234";
            },
            "enhancedAttr" => false,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        <my-instance-id></my-instance-id>
HTML;

        $expectedString = <<<HTMLDOC
        <my-instance-id>
        <p>abcd1234</p>
        </my-instance-id>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Has access to instance ID"
        );
    }

    public function testShouldSupplyContext()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        <my-context-parent message="hmmm">
          <div>
            <span>
              <my-context-child></my-context-child>
            </span>
          </div>
          <my-context-parent message="sure">
            <my-context-child></my-context-child>
          </my-context-parent>
        </my-context-parent>
HTML;

        $expectedString = <<<HTMLDOC
        <my-context-parent message="hmmm">
          <div>
            <span>
              <my-context-child>
                <span>hmmm</span>
              </my-context-child>
            </span>
          </div>
          <my-context-parent message="sure">
            <my-context-child>
              <span>sure</span>
            </my-context-child>
          </my-context-parent>
        </my-context-parent>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Passes context data to child elements"
        );
    }

    public function testShouldMoveLinkElementsToTheHead()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        $head
        <my-link-node-first>first</my-link-node-first>
        <my-link-node-second>second</my-link-node-second>
        <my-link-node-first>first again</my-link-node-first>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head>
        <link rel="stylesheet" href="my-link-node-first.css">
        <link href="my-link-node-second.css" rel="stylesheet">
        </head>
        <body>
        <my-link-node-first>first</my-link-node-first>
        <my-link-node-second>second</my-link-node-second>
        <my-link-node-first>first again</my-link-node-first>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Moves deduplicated link elements to the head"
        );
    }

    public function testShouldHoistCssImports()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        $head
        <my-style-import-first></my-style-import-first>
        <my-style-import-second></my-style-import-second>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head>
        <style>
        @import "my-style-import-first.css";
        @import "my-style-import-second.css";
        my-style-import-first {display: block;}
        my-style-import-second {display: block;}
        </style>
        </head>
        <body>
        <my-style-import-first></my-style-import-first>
        <my-style-import-second></my-style-import-second>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "CSS imports are hoisted"
        );
    }

    public function testShouldRenderNestedSlotsInsideUnnamedSlot()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        <my-custom-heading-with-named-slot>
          <span slot="heading-text">Here's my text</span>
        </my-custom-heading-with-named-slot>
HTML;

        $expectedString = <<<HTMLDOC
        <my-custom-heading-with-named-slot>
          <my-custom-heading>
            <h1>
              <span slot="heading-text">Here's my text</span>
            </h1>
          </my-custom-heading>
        </my-custom-heading-with-named-slot>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Renders nested named slot inside unnamed slot"
        );
    }
    public function testMultipleSlotsWithUnnamedSlotFirst()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => true,
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        <multiple-slots>unnamed slot<div slot="slot1">slot One</div></multiple-slots>
HTML;

        $expectedString = <<<HTMLDOC
        <multiple-slots enhanced="✨">
          unnamed slot<div slot="slot1">slot One</div>
        </multiple-slots>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Unnamed and named slots work together"
        );
    }

    public function testStyleTransform()
    {
        global $allElements;
        $scopeMyStyle = new ShadyStyles();
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "styleTransforms" => [
                function ($params) use ($scopeMyStyle) {
                    return $scopeMyStyle->styleTransform($params);
                },
            ],
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        $head
        <my-style-transform><p class="yup">Hello</p></my-style-transform>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                my-style-transform {
                display: block;
                }
                my-style-transform .yup {
                color: red;
                }
            </style>
        </head>
        <body>
            <my-style-transform><p class="yup">Hello</p></my-style-transform>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Style transform worked"
        );
    }

    public function testMyHeaderStyle()
    {
        global $allElements;
        $scopeMyStyle = new ShadyStyles();
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "styleTransforms" => [
                function ($params) use ($scopeMyStyle) {
                    return $scopeMyStyle->styleTransform($params);
                },
            ],
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        $head
        <my-header>Hello World</my-header>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                my-header h1 {
                color: red;
                }
            </style>
        </head>
        <body>
            <my-header><h1>Hello World</h1></my-header>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "My Header style worked"
        );
    }
    public function testEntityEncodedCaracters()
    {
        global $allElements;
        $scopeMyStyle = new ShadyStyles();
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "styleTransforms" => [
                function ($params) use ($scopeMyStyle) {
                    return $scopeMyStyle->styleTransform($params);
                },
            ],
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        $head
        <my-header>&amp;&times;</my-header>
HTML;

        $expectedString = <<<HTMLDOC
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                my-header h1 {
                color: red;
                }
            </style>
        </head>
        <body>
            <my-header><h1>&amp;&times;</h1></my-header>
        </body>
        </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "My Header style worked"
        );
    }

    public function testCssNestingGlobalStyles()
    {
        global $allElements;
        $scopeMyStyle = new ShadyStyles();
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
            "styleTransforms" => [
                function ($params) use ($scopeMyStyle) {
                    return $scopeMyStyle->styleTransform($params);
                },
            ],
        ]);

        $head = HeadTag();

        $htmlString = <<<HTML
        $head
        <e-tag>Test</e-tag>
HTML;

        $expectedString = <<<HTMLDOC
    <!DOCTYPE html>
    <html>
      <head>
        <style>
          e-tag {
            display: inline-flex;
    
            & button[type="remove"] {
              padding-left: var(--e-space-xs);
            }
          }
        </style>
      </head>
      <body><e-tag>Test</e-tag></body>
    </html>
HTMLDOC;

        $this->assertSame(
            strip($expectedString),
            strip($enhancer->ssr($htmlString)),
            "Global Styles with nesting worked"
        );
    }
}

function HeadTag()
{
    return <<<HTML
<!DOCTYPE html>
<head></head>
HTML;
}

function loadFixtureHTML($name)
{
    return file_get_contents(__DIR__ . "/fixtures/templates/$name");
}
function strip($str)
{
    return preg_replace('/\r?\n|\r|\s\s+/u', "", $str);
}
?>
