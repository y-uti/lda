<?php
namespace YUti\Lda;

class CorpusReader
{
    public function read($filename)
    {
        $corpus = new Corpus();

        $documents = file($filename, FILE_IGNORE_NEW_LINES);
        foreach ($documents as $document) {
            $corpus->addDocument(explode(' ', $document));
        }

        return $corpus;
    }
}
