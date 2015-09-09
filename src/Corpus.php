<?php
namespace YUti\Lda;

class Corpus
{
    private static $wordHeader = ':';

    private $documents;
    private $wordTypes;
    private $wordIdMap;

    public function __construct()
    {
        $this->documents = array();
        $this->wordTypes = array();
        $this->wordIdMap = array();
    }

    public function getDocuments()
    {
        return $this->documents;
    }

    public function getWordTypes()
    {
        return $this->wordTypes;
    }

    public function addDocument($document)
    {
        $this->documents[] = array_map(
            function ($word) {
                return $this->getWordId($word);
            },
            $document
        );
    }

    private function getWordId($word)
    {
        if (!in_array($word, $this->wordTypes, true)) {
            return $this->registerNewWord($word);
        }

        return $this->wordIdMap[self::addHeader($word)];
    }

    private function registerNewWord($word)
    {
        $wordId = count($this->wordTypes);
        $this->wordTypes[] = $word;
        $this->wordIdMap[self::addHeader($word)] = $wordId;

        return $wordId;
    }

    private static function addHeader($word)
    {
        return self::$wordHeader . $word;
    }
}
