<?php

function read_documents_raw($filename)
{
    $w = array();

    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < count($lines); ++$i) {
        $w[] = explode(' ', $lines[$i]);
    }

    return $w;
}
