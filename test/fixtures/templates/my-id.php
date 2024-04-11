<?php
function MyId($state)
{
    $id = $state["attrs"]["id"];
    return "<span id=\"{$id}\"></span>";
}
