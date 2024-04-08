<?php
function MyPage($state)
{
    $items = $state["attrs"]["items"] ?? [];
    return <<<HTML
<h1>My Page</h1>
<my-content items={$items}>
  <h3 slot=title>YOLO</h3>
</my-content>
HTML;
}
