<?php
function MyContextParent($state)
{
    $state["context"]["message"] = $state["attrs"]["message"];
    return "<slot></slot>";
}
