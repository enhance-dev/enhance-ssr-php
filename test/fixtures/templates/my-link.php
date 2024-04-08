<?php
function MyLink($state)
{
    $href = $state["attrs"]["href"] ?? "";
    $text = $state["attrs"]["text"] ?? "";
    return <<<HTML
<a href="{$href}">{$text}</a>
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
HTML;
}
