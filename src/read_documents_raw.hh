<?hh

function read_documents_raw(string $filename) : Vector<Vector<string>>
{
    $w = Vector {};

    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    for ($i = 0; $i < count($lines); ++$i) {
        $w[] = explode(' ', $lines[$i]);
    }

    return $w;
}
