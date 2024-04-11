<?php
function MyPre($state)
{
    $item0 = $state["store"]["items"][0] ?? "";
    return "<pre>{$item0}</pre>";
}
