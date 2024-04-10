<?php
function MyContextChild($state)
{
    print_r("MyContextChild-----------------\n");
    print_r($state);
    $message = $state["context"]["message"] ?? "default message";
    return "<span>{$message}</span>";
}
