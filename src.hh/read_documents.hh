<?hh

function read_documents(string $filename) : Vector<Vector<string>>
{
    $words = Vector {};

    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    for ($i = 1; $i < count($lines); ++$i) {
        list ($doc, $word, $count) = explode(',', $lines[$i]);
        --$doc;
        if (! array_key_exists($doc, $words)) {
            $words[] = Vector {};
        }
        for ($j = 0; $j < $count; ++$j) {
            $words[$doc][] = $word;
        }
    }

    return $words;
}
