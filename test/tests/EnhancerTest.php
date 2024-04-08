<?php

require "vendor/autoload.php";
use PHPUnit\Framework\TestCase;
use Enhance\Enhancer;
use Enhance\Elements;

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

    public function testShouldNotRenderDefaultContentInUnnamedSlots()
    {
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => true,
            "enhancedAttr" => false,
        ]);

        $htmlString = '<my-unnamed id="0"></my-unnamed>';
        $expectedString = '<my-unnamed id="0"></my-unnamed>';

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
          <img src="/" />
          <input />
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
        global $allElements;
        $enhancer = new Enhancer([
            "elements" => $allElements,
            "bodyContent" => false,
            "enhancedAttr" => false,
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
             super()
           }
         }
         customElements.define('my-transform-script', MyTransformScript)
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
    // test('should run style transforms', t => {
    //   const html = enhance({
    //     elements: {
    //       'my-transform-style': MyTransformStyle
    //     },
    //     styleTransforms: [
    //       function({ attrs, raw, tagName, context }) {
    //         if (attrs.find(i => i.name === "scope")?.value === "global" && context === "template") return ''
    //         return `
    //         ${raw}
    //         /*
    //         ${tagName} styles
    //         context: ${context}
    //         */
    //         `

    //       }
    //     ],
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    //   ${Head()}
    //   <my-transform-style></my-transform-style>
    //   `
    //   const expected = `
    // <!DOCTYPE html>
    // <html>
    // <head>
    // <style>

    //   :host {
    //     display: block;
    //   }
    //   /*
    //   my-transform-style styles
    //   context: markup
    //   */

    //   :slot {
    //     display: inline-block;
    //   }
    //   /*
    //   my-transform-style styles
    //   context: markup
    //   */

    // </style>
    // </head>
    // <body>
    // <my-transform-style>
    //   <h1>My Transform Style</h1>
    // </my-transform-style>
    // <script type="module">
    //   class MyTransformStyle extends HTMLElement {
    //     constructor() {
    //       super()
    //     }
    //   }
    //   customElements.define('my-transform-style', MyTransformStyle)
    // </script>
    // </body>
    // </html>
    //   `

    //   t.equal(strip(actual), strip(expected), 'ran style transform style')
    //   t.end()
    // })

    // test('should not add duplicated style tags to head', t => {
    //   const html = enhance({
    //     elements: {
    //       'my-transform-style': MyTransformStyle,
    //     },
    //     styleTransforms: [
    //       function({ attrs, raw, tagName, context }) {
    //         // if tagged as global only add to the head
    //         if (attrs.find(i => i.name === "scope")?.value === "global" && context === "template") return ''

    //         return `
    //         ${raw}
    //         /*
    //         ${tagName} styles
    //         context: ${context}
    //         */
    //         `

    //       }
    //     ],
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    //   ${Head()}
    //   <my-transform-style></my-transform-style>
    //   <my-transform-style></my-transform-style>
    //   `
    //   const expected = `
    // <!DOCTYPE html>
    // <html>
    // <head>
    // <style>
    // :host {
    //     display: block;
    //   }
    //   /*
    //   my-transform-style styles
    //   context: markup
    //   */
    //   :slot {
    //     display: inline-block;
    //   }
    //   /*
    //   my-transform-style styles
    //   context: markup
    //   */

    // </style>
    // </head>
    // <body>
    // <my-transform-style>
    //   <h1>My Transform Style</h1>
    // </my-transform-style>
    // <my-transform-style>
    //   <h1>My Transform Style</h1>
    // </my-transform-style>
    // <script type="module">
    //   class MyTransformStyle extends HTMLElement {
    //     constructor() {
    //       super()
    //     }
    //   }
    //   customElements.define('my-transform-style', MyTransformStyle)
    // </script>
    // </body>
    // </html>
    //   `

    //   t.equal(strip(actual), strip(expected), 'removed duplicate style sheet')
    //   t.end()
    // })

    // test('should respect as attribute', t => {
    //   const html = enhance({
    //     bodyContent: true,
    //     elements: {
    //       'my-slot-as': MySlotAs
    //     },
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    //   <my-slot-as></my-slot-as>
    //   `
    //   const expected = `
    //   <my-slot-as>
    //     <div slot="stuff">
    //       stuff
    //     </div>
    //   </my-slot-as>
    //   `
    //   t.equal(strip(actual), strip(expected), 'respects as attribute')
    //   t.end()
    // })

    // test('should add multiple external scripts', t => {
    //   const html = enhance({
    //     elements: {
    //       'my-external-script': MyExternalScript
    //     },
    //     scriptTransforms: [
    //       function({ attrs, raw, tagName }) {
    //         return `${raw}\n${tagName}`
    //       }
    //     ],
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    //   ${Head()}
    //   <my-external-script></my-external-script>
    //   <my-external-script></my-external-script>
    //   `
    //   const expected = `
    // <!DOCTYPE html>
    // <html>
    // <head>
    // </head>
    // <body>
    //   <my-external-script>
    //     <input type="range">
    //   </my-external-script>
    //   <my-external-script>
    //     <input type="range">
    //   </my-external-script>
    //   <script type="module" src="_static/range.mjs"></script>
    //   <script src="_static/another.mjs"></script>
    // </body>
    // </html>
    //   `
    //   t.equal(strip(actual), strip(expected), 'Adds multiple external scripts')
    //   t.end()
    // })

    // test('should support unnamed slot without whitespace', t => {
    //   const html = enhance({
    //     bodyContent: true,
    //     elements: {
    //       'my-unnamed': MyUnnamed
    //     },
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    //   <my-unnamed>My Text</my-unnamed>
    //   `
    //   const expected = `
    //   <my-unnamed>My Text</my-unnamed>
    // `

    //   t.equal(
    //     strip(actual),
    //     strip(expected),
    //     'Renders content without whitepace into unnamed slot'
    //   )
    //   t.end()
    // })

    // test('should support nested custom elements with nested slots', t => {
    //   const html = enhance({
    //     bodyContent: true,
    //     elements: {
    //       'my-heading': MyHeading,
    //       'my-super-heading': MySuperHeading
    //     },
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    //   <my-super-heading>
    //     <span slot="emoji">
    //       ✨
    //     </span>
    //     My Heading
    //   </my-super-heading>
    //   `
    //   const expected = `
    //   <my-super-heading>
    //     <span slot="emoji">
    //       ✨
    //     </span>
    //     <my-heading>
    //       <h1>
    //         My Heading
    //       </h1>
    //     </my-heading>
    //   </my-super-heading>
    // `

    //   t.equal(
    //     strip(actual),
    //     strip(expected),
    //     'Renders nested slots in nested custom elements'
    //   )
    //   t.end()
    // })

    // test('should not fail when passed a custom element without a template function', t => {
    //   const html = enhance()
    //   const out = html`<noop-noop></noop-noop>`
    //   t.ok(out, 'Does not fail when passed a custom element that has no template function')
    //   t.end()
    // })

    // test('should supply instance ID', t => {
    //   const html = enhance({
    //     bodyContent: true,
    //     uuidFunction: function() { return 'abcd1234' },
    //     elements: {
    //       'my-instance-id': MyInstanceID
    //     },
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    //   <my-instance-id></my-instance-id>
    //   `
    //   const expected = `
    // <my-instance-id>
    //   <p>abcd1234</p>
    // </my-instance-id>
    //   `
    //   t.equal(
    //     strip(actual),
    //     strip(expected),
    //     'Has access to instance ID'
    //   )
    //   t.end()
    // })

    // test('should supply context', t => {
    //   const html = enhance({
    //     bodyContent: true,
    //     elements: {
    //       'my-context-parent': MyContextParent,
    //       'my-context-child': MyContextChild
    //     },
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    //   <my-context-parent message="hmmm">
    //     <div>
    //       <span>
    //         <my-context-child></my-context-child>
    //       </span>
    //     </div>
    //     <my-context-parent message="sure">
    //       <my-context-child></my-context-child>
    //     </my-context-parent>
    //   </my-context-parent>
    //   `
    //   const expected = `
    //   <my-context-parent message="hmmm">
    //     <div>
    //       <span>
    //         <my-context-child>
    //           <span>hmmm</span>
    //         </my-context-child>
    //       </span>
    //     </div>
    //     <my-context-parent message="sure">
    //       <my-context-child>
    //         <span>sure</span>
    //       </my-context-child>
    //     </my-context-parent>
    //   </my-context-parent>
    //   `
    //   t.equal(
    //     strip(actual),
    //     strip(expected),
    //     'Passes context data to child elements'
    //   )
    //   t.end()

    // })

    // test('move link elements to head', t => {
    //   const html = enhance({
    //     elements: {
    //       'my-link-node-first': MyLinkNodeFirst,
    //       'my-link-node-second': MyLinkNodeSecond
    //     },
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    // ${Head()}
    // <my-link-node-first>first</my-link-node-first>
    // <my-link-node-second>second</my-link-node-second>
    // <my-link-node-first>first again</my-link-node-first>
    //   `
    //   const expected = `
    // <!DOCTYPE html>
    // <html>
    // <head>
    // <link rel="stylesheet" href="my-link-node-first.css">
    // <link rel="stylesheet" href="my-link-node-second.css">
    // </head>
    // <body>
    // <my-link-node-first>first</my-link-node-first>
    // <my-link-node-second>second</my-link-node-second>
    // <my-link-node-first>first again</my-link-node-first>
    // </body>
    // </html>
    // `
    //   t.equal(
    //     strip(actual),
    //     strip(expected),
    //     'moves deduplicated link elements to the head'
    //   )
    //   t.end()
    // })

    // test('should hoist css imports', t => {
    //   const html = enhance({
    //     elements: {
    //       'my-style-import-first': MyStyleImportFirst,
    //       'my-style-import-second': MyStyleImportSecond
    //     },
    //     enhancedAttr: false
    //   })
    //   const actual = html`
    //   ${Head()}
    //   <my-style-import-first></my-style-import-first>
    //   <my-style-import-second></my-style-import-second>
    //   `

    //   const expected = `
    //   <!DOCTYPE html>
    //   <html>
    //   <head>
    //   <style>
    //   @import 'my-style-import-first.css';
    //   @import 'my-style-import-second.css';
    //   my-style-import-first { display: block }
    //   my-style-import-second { display: block }
    //   </style>
    //   </head>
    //   <body>
    //   <my-style-import-first></my-style-import-first>
    //   <my-style-import-second></my-style-import-second>
    //   </body>
    //   </html>
    //   `
    //   t.equal(strip(actual), strip(expected), 'Properly hoists CSS imports')
    //   t.end()
    // })

    // test('Should render nested named slot inside unnamed slot', t => {

    //   const html = enhance({
    //     bodyContent: true,
    //     elements: {
    //       'my-custom-heading': MyCustomHeading,
    //       'my-custom-heading-with-named-slot': MyCustomHeadingWithNamedSlot
    //     },
    //     enhancedAttr: false
    //   })

    //   const actual = html`
    //     <my-custom-heading-with-named-slot>
    //       <span slot="heading-text">Here's my text</span>
    //     </my-custom-heading-with-named-slot>
    //   `
    //   const expected = `
    //     <my-custom-heading-with-named-slot>
    //       <my-custom-heading>
    //         <h1>
    //           <span slot="heading-text">Here's my text</span>
    //         </h1>
    //       </my-custom-heading>
    //     </my-custom-heading-with-named-slot>
    //   `

    //   t.equal(
    //     strip(actual),
    //     strip(expected),
    //     'Renders nested named slot inside unnamed slot'
    //   )
    //   t.end()
    // })

    // test('multiple slots with unnamed slot first', t => {
    //   const html = enhance({
    //     bodyContent: true,
    //     elements: {
    //       'multiple-slots': MultipleSlots,
    //     }
    //   })
    //   const actual = html`
    //   <multiple-slots>unnamed slot<div slot="slot1">slot One</div></multiple-slots>
    //   `
    //   const expected = `
    // <multiple-slots enhanced="✨">
    //   unnamed slot<div slot="slot1">slot One</div>
    // </multiple-slots>
    // `
    //   t.equal(
    //     strip(actual),
    //     strip(expected),
    //     'Unnamed and named slots work together'
    //   )
    //   t.end()
    // })
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
