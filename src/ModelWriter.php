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

    public function writeTopicWordFreq($matrix, $delimiter = ' ')
    {
        $words = array_keys($matrix[0]);
        foreach ($words as $w) {
            echo substr($w, 1);
            $sep = "\t";
            foreach ($matrix as $vector) {
                echo $sep . $vector[$w];
                $sep = $delimiter;
            }
            echo "\n";
        }
    }
}
