<?php
function MyHeader($state)
{
    return <<<HTML
  <style>
      h1 {
        color: red;
      }
    </style>
    <h1><slot></slot></h1>
HTML;
}
