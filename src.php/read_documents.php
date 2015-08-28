<?php

function read_documents($filename)
{
    $words = array();

    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    for ($i = 1; $i < count($lines); ++$i) {
        list ($doc, $word, $count) = explode(',', $lines[$i]);
        --$doc;
        if (! array_key_exists($doc, $words)) {
            $words[$doc] = array();
        }
        for ($j = 0; $j < $count; ++$j) {
            $words[$doc][] = ":$word";
        }
    }

    return $words;
}
