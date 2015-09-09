<?php
namespace YUti\Lda;

class ModelWriter
{
    public function writeDocTopicFreq($matrix, $delimiter = ' ')
    {
        foreach ($matrix as $vector) {
            echo implode($delimiter, $vector) . "\n";
        }
    }

    public function writeTopicWordFreq($corpus, $matrix, $delimiter = ' ')
    {
        $wordTypes = $corpus->getWordTypes();
        for ($i = 0; $i < count($wordTypes); ++$i) {
            echo $wordTypes[$i] . "\t" .
            implode(
                $delimiter,
                array_map(
                    function ($vector) use ($i) {
                        return $vector[$i];
                    },
                    $matrix
                )
            ) . "\n";
        }
    }
}
