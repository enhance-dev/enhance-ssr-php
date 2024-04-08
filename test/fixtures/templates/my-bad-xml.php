<?php
function MyBadXml($state)
{
    return <<<HTMLDOC
<slot name=title>
  <h4>My list</h4>
</slot>
<img src=/ >
<input>
HTMLDOC;
}
