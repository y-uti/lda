<?php
namespace YUti\Lda;

class CorpusReader
{
    public function read($filename)
    {
        $corpus = array();

        $lines = file($filename, FILE_IGNORE_NEW_LINES);
        for ($i = 0; $i < count($lines); ++$i) {
            $corpus[] = array_map(function ($word) {
                return ":$word";
            }, explode(' ', $lines[$i]));
        }

        return $corpus;
    }
}
