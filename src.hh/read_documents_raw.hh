<?hh

function read_documents_raw(string $filename) : Vector<Vector<string>>
{
    $words = Vector {};

    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < count($lines); ++$i) {
        $words[] = explode(' ', $lines[$i]);
    }

    return $words;
}
