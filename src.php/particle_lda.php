<?php

// require_once 'read_documents.php';
require_once 'read_documents_raw.php';
require_once 'write_results.php';

class LDA
{
    private $k;
    private $alpha;
    private $beta;

    private $ntd;
    private $nwt;
    private $nt;

    private $logWeight;

    public function __construct($k, $alpha, $beta)
    {
        $this->k = $k;
        $this->alpha = $alpha;
        $this->beta = $beta;

        $this->nwt = [];
        $this->nt = array_fill(0, $this->k, 0);
        $this->logWeight = 0;
    }

    public function getLogWeight()
    {
        return $this->logWeight;
    }

    public function getDocumentTopicCounts()
    {
        return $this->ntd;
    }

    public function getWordTopicCounts()
    {
        return $this->nwt;
    }

    public function prepareForNewDocument()
    {
        $this->ntd = array_fill(0, $this->k, 0);
    }

    public function processNextWord($word)
    {
        if (!array_key_exists($word, $this->nwt)) {
            $this->nwt[$word] = array_fill(0, $this->k, 0);
        }

        $topic = $this->sample($word);

        ++$this->ntd[$topic];
        ++$this->nwt[$word][$topic];
        ++$this->nt[$topic];
    }

    public function clone()
    {
        $newParticle = new LDA($this->k, $this->alpha, $this->beta);
        $newParticle->ntd = $this->ntd;
        $newParticle->nwt = $this->nwt;
        $newParticle->nt = $this->nt;

        return $newParticle;
    }

    private function sample($word)
    {
        $total = 0;
        $nWordTypes = count($this->nwt);
        $cfreq = array();

        $td_denom = array_sum($this->ntd) + $this->k * $this->alpha;
        for ($t = 0; $t < $this->k; ++$t) {
            $td_numer = $this->ntd[$t] + $this->alpha;
            $wt_numer = $this->nwt[$word][$t] + $this->beta;
            $wt_denom = $this->nt[$t] + $nWordTypes * $this->beta;
            $freq = $td_numer * $wt_numer / ($td_denom * $wt_denom);
            $total += $freq;
            $cfreq[] = $total;
        }

        $this->logWeight += log($total);

        $r = mt_rand() / mt_getrandmax() * $total;
        for ($t = 0; $t < $this->k; ++$t) {
            if ($r < $cfreq[$t]) {
                return $t;
            }
        }
        return $this->k - 1;
    }
}

function main($argc, $argv)
{
    if ($argc < 2) {
        echo "Usage: $argv[0] filename k S R [ess-threshold [alpha beta]]\n";
        return;
    }

    $k = intval($argv[2]);
    $s = intval($argv[3]);
    $r = intval($argv[4]);
    $threshold = $argc > 5 ? doubleval($argv[5]) : $s * 0.2;
    $alpha = $argc > 6 ? doubleval($argv[6]) : 0.1;
    $beta = $argc > 6 ? doubleval($argv[7]) : 0.01;

    $particles = createParticles($s, $k, $alpha, $beta);

    $documents = read_documents_raw($argv[1]);
    $nDocuments = count($documents);

    for ($i = 0; $i < $nDocuments; ++$i) {
        // echo "Process documents ($i / $nDocuments)\n";
        $document = $documents[$i];
        prepareForNewDocument($particles);
        foreach ($document as $word) {
            processNextWord($particles, $word);
            $ess = calculateEss($particles);
            if ($ess < $threshold) {
                $particles = resample($particles);
            }
        }
        $topics = getDocTopicFreq($particles, $k, $s);
        echo implode(',', $topics) . "\n";
    }
}

function createParticles($nParticles, $nTopics, $alpha, $beta)
{
    $particles = [];
    for ($i = 0; $i < $nParticles; ++$i) {
        $particles[$i] = new LDA($nTopics, $alpha, $beta);
    }

    return $particles;
}

function prepareForNewDocument($particles)
{
    foreach ($particles as $particle) {
        $particle->prepareForNewDocument();
    }
}

function processNextWord($particles, $word)
{
    foreach ($particles as $particle) {
        $particle->processNextWord($word);
    }
}

function calculateEss($particles)
{
    $weights = getWeights($particles);

    $totalWeight = array_sum($weights);

    $normWeights = array_map(
        function ($weight) use ($totalWeight) {
            return $weight / $totalWeight;
        },
        $weights
    );

    $sqNormWeights = array_map(
        function ($weight) {
            return pow($weight, 2);
        },
        $normWeights
    );

    $ess = 1 / array_sum($sqNormWeights);

    return $ess;
}

function resample($particles)
{
    $weights = getWeights($particles);

    $totalWeight = 0;
    $cumulativeWeights = [];
    foreach ($weights as $weight) {
        $totalWeight += $weight;
        $cumulativeWeights[] = $totalWeight;
    }

    $newParticles = [];
    for ($si = 0; $si < count($particles); ++$si) {
        $r = mt_rand() / mt_getrandmax() * $totalWeight;
        $r = $r < $totalWeight ? $r : 0;
        for ($i = 0; $i < count($cumulativeWeights); ++$i) {
            if ($r < $cumulativeWeights[$i]) {
                $newParticles[] = $particles[$i]->clone();
                break;
            }
        }
    }

    return $newParticles;
}

function getWeights($particles)
{
    $logWeights = array_map(
        function ($particle) {
            return $particle->getLogWeight();
        },
        $particles
    );

    $maxLogWeight = max($logWeights);

    $weights = array_map(
        function ($logWeight) use ($maxLogWeight) {
            return exp($logWeight - $maxLogWeight);
        },
        $logWeights
    );

    return $weights;
}

function getDocTopicFreq($particles, $k, $s)
{
    $result = array_fill(0, $k, 0);
    foreach ($particles as $particle) {
        $counts = $particle->getDocumentTopicCounts();
        for ($i = 0; $i < count($counts); ++$i) {
            $result[$i] += $counts[$i];
        }
    }

    $result = array_map(
        function ($c) use ($s) {
            return $c / $s;
        },
        $result
    );

    return $result;
}

main($argc, $argv);
