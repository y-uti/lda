<?hh

function write_doc_topic_freq(Vector<Vector<float>> $matrix, string $delimiter = ' ') : void
{
    foreach ($matrix as $vector) {
        echo implode($delimiter, $vector) . "\n";
    }
}

function write_topic_word_freq(Vector<Map<string, float>> $matrix, string $delimiter = ' ') : void
{
    $words = array_keys($matrix[0]);
    foreach ($words as $w) {
        echo $w;
        $sep = "\t";
        foreach ($matrix as $vector) {
            echo $sep . $vector[$w];
            $sep = $delimiter;
        }
        echo "\n";
    }
}
