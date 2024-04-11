<?php
function MyList($state)
{
    $items = $state["store"]["items"] ?? [];
    $listItems = "";
    if (isset($items) && is_array($items)) {
        $listItems = implode(
            "",
            array_map(function ($item) {
                $title = $item["title"] ?? "";
                return "<li>{$title}</li>";
            }, $items)
        );
    }

    return <<<HTMLDOC
<slot name="title">
  <h4>My list</h4>
</slot>
<ul>
  {$listItems}
</ul>
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
HTMLDOC;
}
