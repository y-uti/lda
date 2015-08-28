<?php

function write_doc_topic_freq($matrix, $delimiter = ' ')
{
    foreach ($matrix as $vector) {
        echo implode($delimiter, $vector) . "\n";
    }
}

function write_topic_word_freq($matrix, $delimiter = ' ')
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
