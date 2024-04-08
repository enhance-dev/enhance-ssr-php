<?php
function MyCounter($state)
{
    $count = $state["attrs"]["count"] ?? 0;
    return "<h3>Count: {$count}</h3>";
}
