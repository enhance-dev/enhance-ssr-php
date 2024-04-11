<?php
function MyStoreData($state)
{
    $appIndex = $state["attrs"]["app-index"];
    $userIndex = $state["attrs"]["user-index"];
    $id = $state["store"]["apps"][$appIndex]["users"][$userIndex]["id"] ?? [];
    $name =
        $state["store"]["apps"][$appIndex]["users"][$userIndex]["name"] ?? [];
    return <<<HTML
<div>
  <h1>{$name}</h1>
  <h1>{$id}</h1>
</div>
HTML;
}
