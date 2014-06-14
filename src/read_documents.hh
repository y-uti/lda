<?hh

function read_documents(string $filename) : Vector<Vector<string>>
{
    $w = Vector {};

    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    for ($i = 1; $i < count($lines); ++$i) {
        list ($doc, $word, $count) = explode(',', $lines[$i]);
        --$doc;
        if (! array_key_exists($doc, $w)) {
            $w[] = Vector {};
        }
        for ($j = 0; $j < $count; ++$j) {
            $w[$doc][] = $word;
        }
    }

    return $w;
}
