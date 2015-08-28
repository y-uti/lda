<?php

function read_documents_raw($filename)
{
    $words = array();

    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < count($lines); ++$i) {
        $words[] = array_map(function ($word) {
            return ":$word";
        }, explode(' ', $lines[$i]));
    }

    return $words;
}
