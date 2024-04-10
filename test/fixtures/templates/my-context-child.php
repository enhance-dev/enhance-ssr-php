<?php
function MyContextChild($state)
{
    $message = $state["context"]["message"] ?? "default message";
    return "<span>{$message}</span>";
}
